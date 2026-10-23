<?php

namespace App\Services;

use App\Models\EntregaDispositivo;
use App\Models\EquipoCelular;
use App\Models\EquipoComputo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Genera reportes en Excel (.xlsx) de los registros de inventario: una sola
 * sección (lo que se ve filtrado en cada lista) o todas juntas en un libro con
 * una hoja por sección.
 *
 * Los reportes NUNCA incluyen contraseñas, PIN, claves de Windows/Office/
 * BitLocker ni la CURP: son datos para consulta, no para respaldar accesos.
 */
class ReporteInventario
{
    public const COMPUTO = 'computo';

    public const CELULAR = 'celular';

    public const DISPOSITIVOS = 'dispositivos';

    public const SECCIONES = [
        self::COMPUTO => 'Equipos de Cómputo',
        self::CELULAR => 'Equipos Móviles',
        self::DISPOSITIVOS => 'Dispositivos Externos',
    ];

    private const ORIGENES = [
        'publico' => 'Formulario público',
        'masivo' => 'Carga masiva',
        'manual' => 'Administración',
    ];

    private const MOVIMIENTOS = [
        'alta' => 'Alta',
        'baja' => 'Baja',
        'cambio_equipo' => 'Cambio de equipo',
        'reasignacion' => 'Reasignación',
        'mantenimiento' => 'Mantenimiento',
        'prestamo_temporal' => 'Préstamo temporal',
        'entrega' => 'Entrega',
        'devolucion' => 'Devolución',
    ];

    private const TIPOS_EQUIPO = [
        'laptop' => 'Laptop',
        'desktop' => 'Desktop / PC',
        'all_in_one' => 'All in One',
        'workstation' => 'Workstation',
        'mini_pc' => 'Mini PC',
        'celular' => 'Celular',
        'tablet' => 'Tablet',
        'ipad' => 'iPad',
        'otro' => 'Otro',
    ];

    /**
     * @return class-string<Model>
     */
    public static function modelo(string $seccion): string
    {
        return match ($seccion) {
            self::COMPUTO => EquipoComputo::class,
            self::CELULAR => EquipoCelular::class,
            self::DISPOSITIVOS => EntregaDispositivo::class,
        };
    }

    /**
     * Registros de una sección con los filtros del reporte combinado.
     *
     * @param  array{estado?: ?string, desde?: ?string, hasta?: ?string, sucursal_id?: mixed, razon_social_id?: mixed}  $filtros
     */
    public function consulta(string $seccion, array $filtros = []): Builder
    {
        $query = self::modelo($seccion)::query()->orderBy('id');

        // "Activos / Bajas" solo existe en cómputo y móviles; dispositivos usa entrega/devolución.
        if ($seccion !== self::DISPOSITIVOS) {
            match ($filtros['estado'] ?? 'todos') {
                'activos' => $query->where('tipo_movimiento', '!=', 'baja'),
                'baja' => $query->where('tipo_movimiento', 'baja'),
                default => null,
            };
        }

        $query
            ->when(filled($filtros['desde'] ?? null), fn ($q) => $q->whereDate('fecha_entrega', '>=', $filtros['desde']))
            ->when(filled($filtros['hasta'] ?? null), fn ($q) => $q->whereDate('fecha_entrega', '<=', $filtros['hasta']))
            ->when(filled($filtros['sucursal_id'] ?? null), fn ($q) => $q->where('sucursal_id', $filtros['sucursal_id']))
            ->when(filled($filtros['razon_social_id'] ?? null), fn ($q) => $q->where('razon_social_id', $filtros['razon_social_id']));

        return $query;
    }

    /**
     * Libro con una hoja por sección (y una hoja "Resumen" al inicio).
     *
     * @param  array<int, string>  $secciones
     * @param  array<string, mixed>  $filtros
     */
    public function descargarCombinado(array $secciones, array $filtros, string $generadoPor): StreamedResponse
    {
        $datos = [];
        foreach ($secciones as $seccion) {
            $datos[$seccion] = $this->consulta($seccion, $filtros)->get();
        }

        return $this->respuesta('reporte-inventario', function (Writer $writer) use ($datos, $filtros, $generadoPor) {
            $writer->getCurrentSheet()->setName('Resumen');
            $this->escribirResumen($writer, $datos, $filtros, $generadoPor);

            foreach ($datos as $seccion => $registros) {
                $writer->addNewSheetAndMakeItCurrent()->setName(self::SECCIONES[$seccion]);
                $this->escribirHoja($writer, $seccion, $registros);
            }
        });
    }

    /**
     * Una sola sección, tal como la muestra la lista (con sus filtros y búsqueda).
     */
    public function descargarSeccion(string $seccion, Builder|Collection $registros): StreamedResponse
    {
        if ($registros instanceof Builder) {
            $registros = $registros->get();
        }

        return $this->respuesta('reporte-' . Str::slug(self::SECCIONES[$seccion]), function (Writer $writer) use ($seccion, $registros) {
            $writer->getCurrentSheet()->setName(self::SECCIONES[$seccion]);
            $this->escribirHoja($writer, $seccion, $registros);
        });
    }

    private function respuesta(string $base, \Closure $escribir): StreamedResponse
    {
        $nombre = $base . '-' . now()->format('Y-m-d_Hi') . '.xlsx';

        return response()->streamDownload(function () use ($escribir) {
            $opciones = new Options();
            $opciones->setColumnWidthForRange(22, 1, 40);

            $writer = new Writer($opciones);
            $writer->openToFile('php://output');
            $escribir($writer);
            $writer->close();
        }, $nombre, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  Collection<int, Model>  $registros
     */
    private function escribirHoja(Writer $writer, string $seccion, Collection $registros): void
    {
        $columnas = $this->columnas($seccion);
        $negrita = (new Style())->setFontBold();

        $writer->addRow(Row::fromValues(array_keys($columnas), $negrita));

        // En dispositivos la marca es texto libre, no una relación.
        $registros->loadMissing($seccion === self::DISPOSITIVOS ? ['razonSocial', 'sucursal', 'area'] : ['razonSocial', 'sucursal', 'area', 'marca']);

        foreach ($registros as $registro) {
            $writer->addRow(Row::fromValues(array_map(fn (\Closure $valor) => $valor($registro), array_values($columnas))));
        }
    }

    /**
     * @param  array<string, Collection<int, Model>>  $datos
     * @param  array<string, mixed>  $filtros
     */
    private function escribirResumen(Writer $writer, array $datos, array $filtros, string $generadoPor): void
    {
        $negrita = (new Style())->setFontBold();

        $writer->addRow(Row::fromValues(['Reporte de inventario — Grupo Hunan'], $negrita));
        $writer->addRow(Row::fromValues(['Generado', now()->format('d/m/Y H:i')]));
        $writer->addRow(Row::fromValues(['Generado por', $generadoPor]));
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValues(['Filtros aplicados'], $negrita));
        $writer->addRow(Row::fromValues(['Estado (cómputo y móviles)', ['todos' => 'Todos', 'activos' => 'Solo activos', 'baja' => 'Solo bajas'][$filtros['estado'] ?? 'todos'] ?? 'Todos']));
        $writer->addRow(Row::fromValues(['Fecha de entrega desde', $this->fecha($filtros['desde'] ?? null) ?: '—']));
        $writer->addRow(Row::fromValues(['Fecha de entrega hasta', $this->fecha($filtros['hasta'] ?? null) ?: '—']));
        $writer->addRow(Row::fromValues(['Sucursal', filled($filtros['sucursal_id'] ?? null) ? (\App\Models\Sucursal::find($filtros['sucursal_id'])?->nombre ?? '—') : 'Todas']));
        $writer->addRow(Row::fromValues(['Razón social', filled($filtros['razon_social_id'] ?? null) ? (\App\Models\RazonSocial::find($filtros['razon_social_id'])?->nombre ?? '—') : 'Todas']));
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValues(['Sección', 'Registros'], $negrita));
        foreach ($datos as $seccion => $registros) {
            $writer->addRow(Row::fromValues([self::SECCIONES[$seccion], $registros->count()]));
        }
    }

    /**
     * Encabezado => cómo obtener el valor de cada registro.
     *
     * @return array<string, \Closure(Model): mixed>
     */
    private function columnas(string $seccion): array
    {
        $comunes = [
            'ID' => fn ($r) => $r->id,
            'Movimiento' => fn ($r) => self::MOVIMIENTOS[$r->tipo_movimiento] ?? $r->tipo_movimiento,
            'Fecha de entrega' => fn ($r) => $this->fecha($r->fecha_entrega),
        ];

        $ubicacion = [
            'Razón social' => fn ($r) => $r->razonSocial?->nombre,
            'Nombre del usuario' => fn ($r) => $r->nombre_usuario,
            'Sucursal' => fn ($r) => $r->sucursal?->nombre,
            'Área' => fn ($r) => $r->area?->nombre,
        ];

        return match ($seccion) {
            self::COMPUTO => $comunes + [
                'Fecha de baja' => fn ($r) => $this->fecha($r->fecha_baja),
            ] + $ubicacion + [
                'Correo' => fn ($r) => $r->correo_electronico,
                'Extensión' => fn ($r) => $r->ext,
                'Tipo de equipo' => fn ($r) => self::TIPOS_EQUIPO[$r->tipo_equipo] ?? $r->tipo_equipo,
                'Marca' => fn ($r) => $r->marca?->nombre,
                'Modelo' => fn ($r) => $r->modelo,
                'No. de serie' => fn ($r) => $r->numero_serie,
                'Procesador' => fn ($r) => $r->procesador,
                'RAM' => fn ($r) => $r->ram,
                'Almacenamiento' => fn ($r) => $r->almacenamiento,
                'Sistema operativo' => fn ($r) => ['windows' => 'Windows', 'apple' => 'Apple (macOS)'][$r->sistema_operativo] ?? $r->sistema_operativo,
                'Versión de Windows' => fn ($r) => $r->windows_version,
                'Versión de Office' => fn ($r) => $r->office_version,
                'Correo de Office' => fn ($r) => $r->correo_office,
                'Antivirus' => fn ($r) => $r->antivirus_nombre,
                'Antivirus vigente hasta' => fn ($r) => $this->fecha($r->antivirus_vigencia),
                'Usuario del equipo' => fn ($r) => $r->usuario_equipo,
                'Correo de Outlook' => fn ($r) => $r->outlook_correo,
                'Origen del registro' => fn ($r) => self::ORIGENES[$r->origen_registro] ?? 'Administración',
                'Revisión' => fn ($r) => $r->estado_revision === 'pendiente' ? 'Pendiente' : 'Completo',
                'Responsiva de entrega' => fn ($r) => filled($r->responsiva_pdf) ? 'Sí' : 'No',
                'Responsiva de baja' => fn ($r) => filled($r->responsiva_baja_pdf) ? 'Sí' : 'No',
                'Observaciones' => fn ($r) => $r->observaciones,
                'Registrado el' => fn ($r) => $this->fecha($r->created_at, true),
            ],
            self::CELULAR => $comunes + [
                'Fecha de baja' => fn ($r) => $this->fecha($r->fecha_baja),
            ] + $ubicacion + [
                'Extensión' => fn ($r) => $r->ext,
                'Tipo de equipo' => fn ($r) => self::TIPOS_EQUIPO[$r->tipo_equipo] ?? $r->tipo_equipo,
                'Marca' => fn ($r) => $r->marca?->nombre,
                'Modelo' => fn ($r) => $r->modelo,
                'Número telefónico' => fn ($r) => $r->numero_telefonico,
                'IMEI' => fn ($r) => $r->imei,
                'ICCID' => fn ($r) => $r->iccid,
                'Responsiva de entrega' => fn ($r) => filled($r->responsiva_pdf) ? 'Sí' : 'No',
                'Responsiva de baja' => fn ($r) => filled($r->responsiva_baja_pdf) ? 'Sí' : 'No',
                'Origen del registro' => fn ($r) => self::ORIGENES[$r->origen_registro] ?? 'Administración',
                'Revisión' => fn ($r) => $r->estado_revision === 'pendiente' ? 'Pendiente' : 'Completo',
                'Observaciones' => fn ($r) => $r->observaciones,
                'Registrado el' => fn ($r) => $this->fecha($r->created_at, true),
            ],
            self::DISPOSITIVOS => $comunes + $ubicacion + [
                'Correo' => fn ($r) => $r->correo_electronico,
                'Tipo de dispositivo' => fn ($r) => $r->tipo_dispositivo,
                'Descripción' => fn ($r) => $r->descripcion,
                'Marca' => fn ($r) => $r->marca,
                'Modelo' => fn ($r) => $r->modelo,
                'No. de serie' => fn ($r) => $r->numero_serie,
                'Observaciones' => fn ($r) => $r->observaciones,
                'Registrado el' => fn ($r) => $this->fecha($r->created_at, true),
            ],
        };
    }

    private function fecha(mixed $valor, bool $conHora = false): ?string
    {
        if (blank($valor)) {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($valor)->format($conHora ? 'd/m/Y H:i' : 'd/m/Y');
    }
}

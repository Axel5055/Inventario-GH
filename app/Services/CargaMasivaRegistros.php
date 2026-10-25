<?php

namespace App\Services;

use App\Filament\Resources\EquipoCelulars\Schemas\EquipoCelularForm;
use App\Filament\Resources\EquipoComputos\Schemas\EquipoComputoForm;
use App\Models\EntregaDispositivo;
use App\Models\EquipoCelular;
use App\Models\EquipoComputo;
use App\Models\Marca;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Carga masiva de equipos de cómputo, móviles o dispositivos externos a partir de responsivas (PDF,
 * escaneo o foto) leídas con IA. Todo se crea como "pendiente" de revisión
 * (igual que el formulario público): lo que la IA no logra leer queda vacío y
 * se anota en Observaciones para que el personal lo complete y valide.
 *
 * Nunca modifica registros existentes: si el equipo ya está activo (mismo
 * número de serie, teléfono o IMEI, o mismo nombre y modelo), el nuevo se crea
 * igual pero como BAJA y pendiente, con la advertencia en Observaciones, para
 * que el personal decida cuál debe quedar vigente.
 */
class CargaMasivaRegistros
{
    public function __construct(
        private readonly ExtractorResponsivaPdf $extractor,
        private readonly ResponsivaAFormulario $mapeador,
        private readonly ExcelRegistros $excel,
    ) {}

    /**
     * Un archivo = una responsiva. Si es PDF se adjunta al registro creado.
     *
     * @return array<int, array{archivo: string, estado: string, nombre: ?string, mensaje: string}>
     */
    public function procesarArchivo(string $tipo, string $rutaRelativa, string $nombreOriginal): array
    {
        $disco = Storage::disk('local');

        try {
            $crudo = $this->conReintento(fn () => $this->extractor->extraer($disco->path($rutaRelativa), $tipo));

            $esPdf = $disco->mimeType($rutaRelativa) === 'application/pdf';

            return [$this->crear($tipo, $crudo, $nombreOriginal, $esPdf ? $rutaRelativa : null)];
        } catch (Throwable $e) {
            report($e);

            return [$this->fila($nombreOriginal, 'error', null, $e->getMessage())];
        } finally {
            // Si el PDF se adjuntó ya fue movido; en cualquier otro caso es temporal.
            $disco->delete($rutaRelativa);
        }
    }

    /**
     * Un PDF con varias responsivas: solo se crean los registros (sin adjuntos).
     *
     * @return array<int, array{archivo: string, estado: string, nombre: ?string, mensaje: string}>
     */
    public function procesarPdfUnico(string $tipo, string $rutaRelativa, string $nombreOriginal): array
    {
        $disco = Storage::disk('local');

        try {
            $lista = $this->conReintento(fn () => $this->extractor->extraerVarios($disco->path($rutaRelativa), $tipo));

            if ($lista === []) {
                return [$this->fila($nombreOriginal, 'error', null, 'No se detectó ninguna responsiva en el PDF.')];
            }

            $filas = [];
            foreach ($lista as $i => $crudo) {
                $filas[] = $this->crear($tipo, $crudo, $nombreOriginal . ' · responsiva ' . ($i + 1), null);
            }

            return $filas;
        } catch (Throwable $e) {
            report($e);

            return [$this->fila($nombreOriginal, 'error', null, $e->getMessage())];
        } finally {
            $disco->delete($rutaRelativa);
        }
    }

    /**
     * Plantilla de Excel (o CSV): una fila por equipo, sin IA ni adjuntos.
     *
     * @return array<int, array{archivo: string, estado: string, nombre: ?string, mensaje: string}>
     */
    public function procesarExcel(string $tipo, string $rutaRelativa, string $nombreOriginal): array
    {
        $disco = Storage::disk('local');

        try {
            $filas = $this->excel->leer($disco->path($rutaRelativa), pathinfo($nombreOriginal, PATHINFO_EXTENSION), $tipo);

            if ($filas === []) {
                return [$this->fila($nombreOriginal, 'error', null, 'No se encontraron filas con datos debajo de los encabezados.')];
            }

            $resultado = [];
            foreach ($filas as $fila) {
                $origen = $nombreOriginal . ' · fila ' . $fila['fila'];

                try {
                    $resultado[] = $this->crear($tipo, $fila['datos'], $origen, null);
                } catch (Throwable $e) {
                    report($e);
                    $resultado[] = $this->fila($origen, 'error', $fila['datos']['nombre_completo'] ?? $fila['datos']['nombre_colaborador'] ?? null, $e->getMessage());
                }
            }

            return $resultado;
        } catch (Throwable $e) {
            report($e);

            return [$this->fila($nombreOriginal, 'error', null, $e->getMessage())];
        } finally {
            $disco->delete($rutaRelativa);
        }
    }

    /**
     * @param  array<string, mixed>  $crudo  lo que devolvió la IA
     * @param  string|null  $adjunto  ruta (disco local) del PDF a adjuntar como responsiva
     * @return array{archivo: string, estado: string, nombre: ?string, mensaje: string}
     */
    private function crear(string $tipo, array $crudo, string $origen, ?string $adjunto): array
    {
        $celular = $tipo === ExtractorResponsivaPdf::TIPO_CELULAR;
        $dispositivos = $tipo === ExtractorResponsivaPdf::TIPO_DISPOSITIVOS;
        ['datos' => $d, 'avisos' => $avisos] = $this->mapeador->mapear($crudo, $tipo);

        $identificadores = match (true) {
            $celular => ['nombre_usuario', 'imei', 'modelo', 'numero_telefonico'],
            $dispositivos => ['nombre_usuario', 'descripcion', 'modelo', 'numero_serie', 'correo_electronico'],
            default => ['nombre_usuario', 'numero_serie', 'modelo', 'correo_electronico'],
        };

        if (collect($identificadores)->every(fn ($campo) => blank($d[$campo] ?? null))) {
            return $this->fila($origen, 'omitido', null, 'No se detectó información de una responsiva.');
        }

        $d['tipo_movimiento'] ??= $dispositivos ? 'entrega' : 'alta';

        // Si ya hay un registro activo que coincide, este se crea igual pero como BAJA y
        // pendiente de revisar: no se toca el registro existente ni se pierde el documento.
        // En Dispositivos Externos no se revisan repetidos: una persona puede recibir varios iguales.
        $duplicado = ($dispositivos || $d['tipo_movimiento'] === 'baja') ? null : $this->buscarDuplicado($celular, $d);

        if ($duplicado) {
            $d['tipo_movimiento'] = 'baja';
        }

        $esBaja = $d['tipo_movimiento'] === 'baja';

        // Campos obligatorios en BD que no se pudieron leer: se completan con un valor
        // provisional y se avisa en Observaciones.
        $faltantes = [];
        $revisar = function (string $campo, string $etiqueta) use (&$d, &$faltantes) {
            if (blank($d[$campo] ?? null)) {
                $faltantes[] = $etiqueta;
            }
        };

        $obligatorios = $dispositivos
            ? ['sucursal_id' => 'Sucursal', 'area_id' => 'Área', 'correo_electronico' => 'Correo', 'tipo_dispositivo' => 'Tipo de dispositivo', 'descripcion' => 'Descripción']
            : ['sucursal_id' => 'Sucursal', 'area_id' => 'Área', 'marca_id' => 'Marca', 'modelo' => 'Modelo'] + ($celular
                ? ['imei' => 'IMEI', 'numero_telefonico' => 'Número telefónico', 'tipo_equipo' => 'Tipo de equipo']
                : ['numero_serie' => 'Número de serie', 'correo_electronico' => 'Correo', 'tipo_equipo' => 'Tipo de equipo']);

        foreach ($obligatorios as $campo => $etiqueta) {
            $revisar($campo, $etiqueta);
        }

        $d['nombre_usuario'] = $d['nombre_usuario'] ?? Str::limit('Sin nombre (' . $origen . ')', 250, '');
        if (blank($crudo['nombre_completo'] ?? $crudo['nombre_colaborador'] ?? null)) {
            $faltantes[] = 'Nombre';
        }

        if ($dispositivos) {
            $d['fecha_entrega'] ??= now()->format('Y-m-d');
            $d['tipo_dispositivo'] ??= 'Otro';
            $d['descripcion'] ??= '(sin descripción)';
            $d['correo_electronico'] ??= '';
        } else {
            $d['fecha_entrega'] ??= now()->format('Y-m-d H:i:s');
            $d['tipo_equipo'] ??= $celular ? 'celular' : 'laptop';
            $d['modelo'] ??= '';

            if (! $celular) {
                $d['correo_electronico'] ??= '';
                $d['numero_serie'] ??= '';
                $this->resolverMarcaComputo($d, $crudo['marca'] ?? null);
            }
        }

        if ($esBaja) {
            $d['fecha_baja'] = $duplicado ? now()->format('Y-m-d H:i:s') : $d['fecha_entrega'];
        }

        $notas = array_filter([
            $d['observaciones'] ?? null,
            '⚠ Carga masiva (' . $origen . '): pendiente de revisar'
                . ($faltantes ? ' — no se detectó: ' . implode(', ', array_unique($faltantes)) : '') . '.',
            $duplicado ? "⚠ Posible duplicado: ya existe un registro activo ({$duplicado}). Se creó como BAJA; revisa cuál debe quedar vigente." : null,
            $avisos ? 'Avisos: ' . implode(' ', $avisos) : null,
        ]);
        $d['observaciones'] = implode("\n", $notas);

        $d['origen_registro'] = 'masivo';
        $d['estado_revision'] = 'pendiente';

        $modelo = match (true) {
            $celular => EquipoCelular::class,
            $dispositivos => EntregaDispositivo::class,
            default => EquipoComputo::class,
        };

        if ($adjunto) {
            $d['responsiva_pdf'] = $this->adjuntar($adjunto, $d['nombre_usuario'], $tipo);
        }

        $registro = $modelo::create($d);

        $detalle = $faltantes ? ' Falta: ' . implode(', ', array_unique($faltantes)) . '.' : '';

        if ($duplicado) {
            return $this->fila($origen, 'duplicado', $registro->nombre_usuario, "Creado como BAJA y pendiente de revisar: ya existe un registro activo ({$duplicado}).{$detalle}");
        }

        return $this->fila($origen, 'creado', $registro->nombre_usuario, 'Creado como pendiente.' . $detalle);
    }

    /**
     * Describe el registro ACTIVO que coincide con el que se va a crear, o null.
     * Coincide si comparte número de serie (cómputo), teléfono o IMEI (móviles),
     * o —cuando no hay un identificador que lo distinga— el mismo nombre y modelo.
     *
     * @param  array<string, mixed>  $d
     */
    private function buscarDuplicado(bool $celular, array $d): ?string
    {
        if ($celular) {
            $coincidencia = EquipoCelularForm::buscarConflictos($d, null)
                ->map(fn ($c) => $c['registro']->nombre_usuario . ' — ' . implode(' y ', $c['motivos']))
                ->first();
        } else {
            $porSerie = EquipoComputoForm::buscarConflictoNumeroSerie($d['numero_serie'] ?? null, null);
            $coincidencia = $porSerie ? $porSerie->nombre_usuario . ' — mismo número de serie (' . $porSerie->numero_serie . ')' : null;
        }

        if ($coincidencia || blank($d['nombre_usuario'] ?? null) || blank($d['modelo'] ?? null)) {
            return $coincidencia;
        }

        $modelo = $celular ? EquipoCelular::class : EquipoComputo::class;

        $igual = $modelo::query()
            ->where('tipo_movimiento', '!=', 'baja')
            ->where('nombre_usuario', $d['nombre_usuario'])
            ->where('modelo', $d['modelo'])
            ->first();

        return $igual ? $igual->nombre_usuario . ' — mismo nombre y modelo (' . $igual->modelo . ')' : null;
    }

    /**
     * Igual que el formulario público: una marca que no está en el catálogo cae en
     * «Otra» y se conserva el texto detectado.
     *
     * @param  array<string, mixed>  $d
     */
    private function resolverMarcaComputo(array &$d, ?string $marcaDetectada): void
    {
        if (blank($marcaDetectada)) {
            return;
        }

        $d['marca_detectada'] = Str::limit(trim($marcaDetectada), 250, '');

        if (blank($d['marca_id'] ?? null)) {
            $d['marca_id'] = Marca::firstOrCreate(['nombre' => 'Otra'], ['categoria' => 'ambas', 'activo' => true])->id;
        }
    }

    /**
     * Mueve el PDF subido a la carpeta de responsivas con el nombre de la persona.
     */
    private function adjuntar(string $rutaTemporal, string $nombre, string $tipo): string
    {
        $carpeta = match ($tipo) {
            ExtractorResponsivaPdf::TIPO_CELULAR => 'responsivas/celulares/',
            ExtractorResponsivaPdf::TIPO_DISPOSITIVOS => 'responsivas/dispositivos/',
            default => 'responsivas/computo/',
        };

        $destino = $carpeta . Str::slug($nombre ?: 'equipo') . '-' . Str::random(6) . '.pdf';

        Storage::disk('local')->move($rutaTemporal, $destino);

        return $destino;
    }

    /**
     * El nivel gratuito de la IA limita las solicitudes por minuto: ante un 429 se
     * espera y se reintenta una vez antes de marcar el archivo con error.
     */
    private function conReintento(\Closure $accion): mixed
    {
        try {
            return $accion();
        } catch (RuntimeException $e) {
            if (! str_contains($e->getMessage(), 'límite gratuito')) {
                throw $e;
            }

            sleep(20);

            return $accion();
        }
    }

    /**
     * @return array{archivo: string, estado: string, nombre: ?string, mensaje: string}
     */
    private function fila(string $archivo, string $estado, ?string $nombre, string $mensaje): array
    {
        return compact('archivo', 'estado', 'nombre', 'mensaje');
    }
}

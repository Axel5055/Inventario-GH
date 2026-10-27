<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Marca;
use App\Models\RazonSocial;
use App\Models\Sucursal;
use App\Models\TipoDispositivo;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Options as WriterOptions;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Plantilla de Excel para la carga masiva y lectura de la plantilla llena.
 *
 * Cada fila se convierte en el mismo formato que devuelve la IA al leer una
 * responsiva, de modo que se reutiliza todo el flujo de la carga masiva
 * (empate con catálogos, registros pendientes, duplicados, observaciones).
 */
class ExcelRegistros
{
    public const MAX_FILAS = 500;

    private const TIPOS_EQUIPO = [
        ExtractorResponsivaPdf::TIPO_COMPUTO => [
            'laptop' => 'laptop', 'desktop' => 'desktop', 'desktop pc' => 'desktop', 'pc' => 'desktop', 'escritorio' => 'desktop',
            'all in one' => 'all_in_one', 'workstation' => 'workstation', 'mini pc' => 'mini_pc',
        ],
        ExtractorResponsivaPdf::TIPO_CELULAR => [
            'celular' => 'celular', 'telefono' => 'celular', 'tablet' => 'tablet', 'ipad' => 'ipad', 'otro' => 'otro',
        ],
    ];

    private const MOVIMIENTOS_DISPOSITIVOS = ['entrega' => 'entrega', 'devolucion' => 'devolucion'];

    private const MOVIMIENTOS = [
        'alta' => 'alta', 'baja' => 'baja', 'cambio de equipo' => 'cambio_equipo', 'cambio' => 'cambio_equipo',
        'reasignacion' => 'reasignacion', 'mantenimiento' => 'mantenimiento', 'prestamo temporal' => 'prestamo_temporal',
    ];

    /**
     * Columnas de la plantilla: etiqueta => [clave, obligatoria, ayuda, aliases].
     * La clave es la misma que usa la lectura con IA.
     *
     * @return array<string, array{clave: string, obligatoria: bool, ayuda: string, aliases: array<int, string>}>
     */
    public function columnas(string $tipo): array
    {
        $comunes = fn (string $nombreClave, string $areaClave, string $fechaClave) => [
            'Nombre completo' => ['clave' => $nombreClave, 'obligatoria' => true, 'ayuda' => 'Nombre de la persona a quien se entrega el equipo.', 'aliases' => ['nombre', 'usuario', 'nombre del usuario', 'colaborador']],
            'Área' => ['clave' => $areaClave, 'obligatoria' => false, 'ayuda' => 'Escríbela como está en el catálogo (ver hojas de abajo).', 'aliases' => ['area', 'departamento']],
            'Razón social' => ['clave' => 'razon_social', 'obligatoria' => false, 'ayuda' => 'Escríbela como está en el catálogo (hoja Catálogos).', 'aliases' => ['razon', 'empresa']],
            'Sucursal' => ['clave' => 'sucursal', 'obligatoria' => false, 'ayuda' => 'Escríbela como está en el catálogo.', 'aliases' => ['unidad']],
            'Tipo de movimiento' => ['clave' => 'tipo_movimiento', 'obligatoria' => false, 'ayuda' => 'Alta, Baja, Cambio de equipo, Reasignación, Mantenimiento o Préstamo temporal. Vacío = Alta.', 'aliases' => ['movimiento', 'status']],
            'Fecha de entrega' => ['clave' => $fechaClave, 'obligatoria' => false, 'ayuda' => 'Fecha (ej. 25/10/2026). Vacío = hoy.', 'aliases' => ['fecha', 'fecha de movimiento']],
        ];

        if ($tipo === ExtractorResponsivaPdf::TIPO_DISPOSITIVOS) {
            $columnas = $comunes('nombre_completo', 'area', 'fecha');
            $columnas['Tipo de movimiento']['ayuda'] = 'Entrega o Devolución. Vacío = Entrega.';

            return $columnas + [
                'Correo' => ['clave' => 'correo', 'obligatoria' => false, 'ayuda' => 'Correo de la persona.', 'aliases' => ['correo electronico', 'email', 'e-mail']],
                'Tipo de dispositivo' => ['clave' => 'tipo_dispositivo', 'obligatoria' => false, 'ayuda' => 'Como está en el catálogo (hoja Catálogos). Si no existe se usa «Otro».', 'aliases' => ['tipo']],
                'Descripción' => ['clave' => 'descripcion', 'obligatoria' => false, 'ayuda' => 'Ej. Disco duro externo 1 TB.', 'aliases' => ['descripcion del dispositivo', 'dispositivo', 'detalle']],
                'Marca' => ['clave' => 'marca', 'obligatoria' => false, 'ayuda' => 'Como está en el catálogo de marcas.', 'aliases' => []],
                'Modelo' => ['clave' => 'modelo', 'obligatoria' => false, 'ayuda' => '', 'aliases' => []],
                'Cantidad' => ['clave' => 'cantidad', 'obligatoria' => false, 'ayuda' => 'Número entero, mínimo 1. Vacío = 1.', 'aliases' => ['cant', 'piezas', 'unidades']],
                'Número de serie' => ['clave' => 'numero_serie', 'obligatoria' => false, 'ayuda' => 'Opcional. En dispositivos no se revisan repetidos.', 'aliases' => ['serie', 'no de serie', 'sn']],
                'Observaciones' => ['clave' => 'observaciones', 'obligatoria' => false, 'ayuda' => '', 'aliases' => ['notas', 'comentarios']],
            ];
        }

        if ($tipo === ExtractorResponsivaPdf::TIPO_CELULAR) {
            return $comunes('nombre_colaborador', 'puesto_area', 'fecha') + [
                'Tipo de equipo' => ['clave' => 'tipo_equipo', 'obligatoria' => false, 'ayuda' => 'Celular, Tablet, iPad u Otro.', 'aliases' => ['tipo']],
                'Marca' => ['clave' => 'marca', 'obligatoria' => false, 'ayuda' => 'Como está en el catálogo de marcas.', 'aliases' => []],
                'Modelo' => ['clave' => 'modelo', 'obligatoria' => false, 'ayuda' => '', 'aliases' => []],
                'Número telefónico' => ['clave' => 'telefono', 'obligatoria' => false, 'ayuda' => 'Con lada, ej. 5512345678. Se usa para detectar equipos duplicados.', 'aliases' => ['telefono', 'numero', 'celular numero']],
                'IMEI' => ['clave' => 'imei', 'obligatoria' => false, 'ayuda' => '15 dígitos.', 'aliases' => []],
                'ICCID' => ['clave' => 'iccid', 'obligatoria' => false, 'ayuda' => 'Número de la SIM.', 'aliases' => ['sim']],
                'CURP' => ['clave' => 'curp', 'obligatoria' => false, 'ayuda' => '', 'aliases' => []],
                'Observaciones' => ['clave' => 'observaciones', 'obligatoria' => false, 'ayuda' => '', 'aliases' => ['notas', 'comentarios']],
            ];
        }

        return $comunes('nombre_completo', 'area', 'fecha_movimiento') + [
            'Correo' => ['clave' => 'correo_corporativo', 'obligatoria' => false, 'ayuda' => 'Correo corporativo (@grupohunan.com).', 'aliases' => ['correo electronico', 'email', 'e-mail']],
            'Extensión' => ['clave' => 'extension', 'obligatoria' => false, 'ayuda' => 'Solo números.', 'aliases' => ['ext']],
            'Tipo de equipo' => ['clave' => 'tipo_equipo', 'obligatoria' => false, 'ayuda' => 'Laptop, Desktop / PC, All in One, Workstation o Mini PC.', 'aliases' => ['tipo']],
            'Marca' => ['clave' => 'marca', 'obligatoria' => false, 'ayuda' => 'Como está en el catálogo de marcas.', 'aliases' => []],
            'Modelo' => ['clave' => 'modelo', 'obligatoria' => false, 'ayuda' => '', 'aliases' => []],
            'Número de serie' => ['clave' => 'numero_serie', 'obligatoria' => false, 'ayuda' => 'Se usa para detectar equipos duplicados.', 'aliases' => ['serie', 'no de serie', 'sn']],
            'Procesador' => ['clave' => 'procesador', 'obligatoria' => false, 'ayuda' => '', 'aliases' => ['cpu']],
            'RAM' => ['clave' => 'ram', 'obligatoria' => false, 'ayuda' => 'Ej. 16 GB.', 'aliases' => ['memoria']],
            'Almacenamiento' => ['clave' => 'almacenamiento', 'obligatoria' => false, 'ayuda' => 'Ej. 512 GB.', 'aliases' => ['disco', 'disco duro']],
            'Sistema operativo' => ['clave' => 'sistema_operativo', 'obligatoria' => false, 'ayuda' => 'Windows 10 Home, Windows 10 Pro, Windows 11 Home, Windows 11 Pro, Windows 11 Enterprise o Apple.', 'aliases' => ['so', 'windows']],
            'Observaciones' => ['clave' => 'observaciones', 'obligatoria' => false, 'ayuda' => '', 'aliases' => ['notas', 'comentarios']],
        ];
    }

    /**
     * Plantilla vacía (encabezados) más una hoja de instrucciones y catálogos.
     */
    public function plantilla(string $tipo): StreamedResponse
    {
        $celular = $tipo === ExtractorResponsivaPdf::TIPO_CELULAR;
        $dispositivos = $tipo === ExtractorResponsivaPdf::TIPO_DISPOSITIVOS;
        $columnas = $this->columnas($tipo);
        $negrita = (new Style())->setFontBold();

        return response()->streamDownload(function () use ($columnas, $celular, $dispositivos, $tipo, $negrita) {
            $opciones = new WriterOptions();
            $opciones->setColumnWidthForRange(24, 1, 20);

            $writer = new Writer($opciones);
            $writer->openToFile('php://output');

            $writer->getCurrentSheet()->setName('Datos');
            $writer->addRow(Row::fromValues(array_keys($columnas), $negrita));

            $writer->addNewSheetAndMakeItCurrent()->setName('Instrucciones');
            $writer->addRow(Row::fromValues(['Cómo llenar la hoja «Datos»'], $negrita));
            $writer->addRow(Row::fromValues(['Una fila por equipo. No cambies los encabezados. Lo que dejes vacío se completa después en la revisión; solo «Nombre completo» es necesario.']));
            $writer->addRow(Row::fromValues([$dispositivos
                ? 'Los registros se crean como «pendientes» para que los revises y valides. En Dispositivos Externos no se revisan repetidos: puedes registrar varios dispositivos para la misma persona.'
                : 'Los registros se crean como «pendientes» para que los revises y valides. Si el equipo ya está activo en el sistema (mismo ' . ($celular ? 'teléfono o IMEI' : 'número de serie') . ', o mismo nombre y modelo), esa fila se crea igual pero como BAJA y pendiente, con la advertencia en Observaciones.']));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(['Columna', 'Obligatoria', 'Qué poner'], $negrita));
            foreach ($columnas as $etiqueta => $def) {
                $writer->addRow(Row::fromValues([$etiqueta, $def['obligatoria'] ? 'Sí' : 'No', $def['ayuda']]));
            }

            $writer->addNewSheetAndMakeItCurrent()->setName('Catálogos');
            $listas = [
                'Razones sociales' => RazonSocial::where('activo', true)->orderBy('nombre')->pluck('nombre')->all(),
                'Sucursales' => Sucursal::where('activo', true)->orderBy('nombre')->pluck('nombre')->all(),
                'Áreas' => Area::where('activo', true)->orderBy('nombre')->pluck('nombre')->all(),
                'Marcas' => ($dispositivos ? Marca::activas() : ($celular ? Marca::activas()->deCelular() : Marca::activas()->deComputo()))->orderBy('nombre')->pluck('nombre')->all(),
            ];

            if ($dispositivos) {
                $listas['Tipos de dispositivo'] = TipoDispositivo::where('activo', true)->orderBy('nombre')->pluck('nombre')->all();
            }
            $writer->addRow(Row::fromValues(array_keys($listas), $negrita));
            for ($i = 0, $max = max(array_map('count', $listas)); $i < $max; $i++) {
                $writer->addRow(Row::fromValues(array_map(fn ($lista) => $lista[$i] ?? '', array_values($listas))));
            }

            $writer->close();
        }, 'plantilla-carga-masiva-' . ($dispositivos ? 'dispositivos' : ($celular ? 'moviles' : 'computo')) . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Filas del Excel/CSV con el formato de la lectura con IA.
     *
     * @return array<int, array{fila: int, datos: array<string, mixed>}>
     */
    public function leer(string $rutaAbsoluta, string $extension, string $tipo): array
    {
        $extension = Str::lower($extension);

        if ($extension === 'csv') {
            $opciones = new CsvOptions();
            $opciones->FIELD_DELIMITER = $this->delimitador($rutaAbsoluta);
            $lector = new CsvReader($opciones);
        } elseif ($extension === 'xlsx') {
            $lector = new XlsxReader();
        } else {
            throw new RuntimeException('Formato no compatible: usa un archivo .xlsx (Excel) o .csv. Si tu archivo es .xls, guárdalo como .xlsx.');
        }

        $lector->open($rutaAbsoluta);

        $encabezados = null;
        $filas = [];
        $mapa = $this->mapaEncabezados($tipo);

        try {
            foreach ($lector->getSheetIterator() as $hoja) {
                foreach ($hoja->getRowIterator() as $i => $fila) {
                    $celdas = array_map(fn ($c) => $this->texto($c), $fila->toArray());

                    if ($encabezados === null) {
                        if (collect($celdas)->every(fn ($c) => $c === null)) {
                            continue;
                        }

                        $encabezados = array_map(fn ($c) => $mapa[$this->normalizar((string) $c)] ?? null, $celdas);

                        if (! in_array($this->columnas($tipo)['Nombre completo']['clave'], $encabezados, true)) {
                            throw new RuntimeException('No se encontró la columna «Nombre completo» en la primera fila. Usa la plantilla descargada desde esta página.');
                        }

                        continue;
                    }

                    if (collect($celdas)->every(fn ($c) => $c === null)) {
                        continue;
                    }

                    $datos = [];
                    foreach ($encabezados as $columna => $clave) {
                        if ($clave !== null && ($celdas[$columna] ?? null) !== null) {
                            $datos[$clave] = $celdas[$columna];
                        }
                    }

                    $filas[] = ['fila' => $i, 'datos' => $this->normalizarValores($datos, $tipo)];

                    if (count($filas) > self::MAX_FILAS) {
                        throw new RuntimeException('El archivo tiene más de ' . self::MAX_FILAS . ' filas. Divídelo en varios archivos.');
                    }
                }

                break; // solo se lee la primera hoja (en la plantilla es «Datos»)
            }
        } finally {
            $lector->close();
        }

        if ($encabezados === null) {
            throw new RuntimeException('El archivo está vacío.');
        }

        return $filas;
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function normalizarValores(array $datos, string $tipo): array
    {
        if (isset($datos['tipo_equipo'])) {
            $datos['tipo_equipo'] = self::TIPOS_EQUIPO[$tipo][$this->normalizar($datos['tipo_equipo'])] ?? null;
        }

        if (isset($datos['tipo_movimiento'])) {
            $mapa = $tipo === ExtractorResponsivaPdf::TIPO_DISPOSITIVOS ? self::MOVIMIENTOS_DISPOSITIVOS : self::MOVIMIENTOS;
            $datos['tipo_movimiento'] = $mapa[$this->normalizar($datos['tipo_movimiento'])] ?? null;
        }

        foreach (['fecha', 'fecha_movimiento'] as $clave) {
            if (isset($datos[$clave])) {
                $datos[$clave] = $this->fechaIso($datos[$clave]);
            }
        }

        return $datos;
    }

    /**
     * Fechas de Excel: número de serie (días desde 1899-12-30), dd/mm/aaaa o ISO.
     * Lo que no se reconoce se descarta (se usa la fecha de hoy).
     */
    private function fechaIso(string $valor): ?string
    {
        $valor = trim($valor);

        if (preg_match('/^\d{5}$/', $valor)) {
            return Carbon::create(1899, 12, 30)->addDays((int) $valor)->format('Y-m-d');
        }

        if (preg_match('#^(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{4})$#', $valor, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}/', $valor) ? substr($valor, 0, 10) : null;
    }

    /**
     * @return array<string, string> encabezado normalizado => clave
     */
    private function mapaEncabezados(string $tipo): array
    {
        $mapa = [];

        foreach ($this->columnas($tipo) as $etiqueta => $def) {
            foreach ([$etiqueta, ...$def['aliases']] as $nombre) {
                $mapa[$this->normalizar($nombre)] = $def['clave'];
            }
        }

        return $mapa;
    }

    private function normalizar(string $valor): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii($valor))));
    }

    /**
     * Valor de celda como texto: sin notación científica en números largos (IMEI,
     * teléfonos), fechas en ISO y vacíos como null.
     */
    private function texto(mixed $valor): ?string
    {
        if ($valor instanceof DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        if (is_float($valor) || is_int($valor)) {
            return is_float($valor) && floor($valor) !== $valor ? rtrim(rtrim(number_format($valor, 6, '.', ''), '0'), '.') : number_format($valor, 0, '', '');
        }

        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }

    private function delimitador(string $ruta): string
    {
        $primera = (string) strtok((string) file_get_contents($ruta, false, null, 0, 4096), "\n");

        return substr_count($primera, ';') > substr_count($primera, ',') ? ';' : ',';
    }
}

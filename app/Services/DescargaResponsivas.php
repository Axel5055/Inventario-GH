<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Arma un ZIP con las responsivas (de entrega y de baja) de un conjunto de
 * registros, una carpeta por sección y con el nombre de la persona en cada
 * archivo. Incluye un RESUMEN.txt con lo que se incluyó y lo que no tiene archivo.
 */
class DescargaResponsivas
{
    private const CARPETAS = [
        ReporteInventario::COMPUTO => 'Equipos de Computo',
        ReporteInventario::CELULAR => 'Equipos Moviles',
        ReporteInventario::DISPOSITIVOS => 'Dispositivos Externos',
    ];

    /**
     * @param  array<string, iterable<int, Model>>  $porSeccion  sección => registros
     *
     * @throws RuntimeException cuando no hay nada que descargar o el servidor no puede crear ZIP
     */
    public function zip(array $porSeccion): BinaryFileResponse
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('El servidor no tiene habilitada la extensión ZIP de PHP. Pídele a tu proveedor de hosting que la active.');
        }

        @set_time_limit(300);

        $disco = Storage::disk('local');
        $ruta = tempnam(sys_get_temp_dir(), 'resp');

        $zip = new ZipArchive();

        if ($zip->open($ruta, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo ZIP en el servidor.');
        }

        $incluidas = 0;
        $resumen = [];
        $sinResponsiva = [];

        foreach ($porSeccion as $seccion => $registros) {
            $carpeta = self::CARPETAS[$seccion];
            $total = 0;
            $conArchivo = 0;

            foreach ($registros as $registro) {
                $total++;
                $tiene = false;

                foreach (['responsiva_pdf' => '', 'responsiva_baja_pdf' => ' - baja'] as $campo => $sufijo) {
                    $archivo = $registro->getAttribute($campo);

                    if (blank($archivo)) {
                        continue;
                    }

                    if (! $disco->exists($archivo)) {
                        $sinResponsiva[] = "{$carpeta}: {$registro->nombre_usuario} (ID {$registro->getKey()}) — el archivo de {$campo} no se encontró en el servidor";

                        continue;
                    }

                    $extension = pathinfo($archivo, PATHINFO_EXTENSION) ?: 'pdf';
                    $nombre = Str::limit(trim(preg_replace('/[\/\\\\:*?"<>|]+/', '', Str::ascii((string) $registro->nombre_usuario))), 60, '')
                        . " - ID {$registro->getKey()}{$sufijo}.{$extension}";

                    $zip->addFile($disco->path($archivo), "{$carpeta}/{$nombre}");
                    $incluidas++;
                    $tiene = true;

                    if ($campo === 'responsiva_pdf') {
                        $conArchivo++;
                    }
                }

                if (blank($registro->getAttribute('responsiva_pdf')) && ! $tiene) {
                    $sinResponsiva[] = "{$carpeta}: {$registro->nombre_usuario} (ID {$registro->getKey()}) — sin responsiva";
                }
            }

            $resumen[] = "{$carpeta}: {$total} registro(s), {$conArchivo} con responsiva de entrega";
        }

        if ($incluidas === 0) {
            $zip->close();
            @unlink($ruta);

            throw new RuntimeException('Ninguno de los registros tiene una responsiva cargada.');
        }

        $zip->addFromString('RESUMEN.txt', implode("\r\n", [
            'Responsivas descargadas el ' . now()->format('d/m/Y H:i'),
            'Archivos incluidos: ' . $incluidas,
            '',
            ...$resumen,
            ...($sinResponsiva ? ['', 'Registros sin archivo:', ...$sinResponsiva] : []),
        ]) . "\r\n");

        $zip->close();

        return response()->download($ruta, 'responsivas-' . now()->format('Y-m-d_Hi') . '.zip')->deleteFileAfterSend(true);
    }

    /**
     * @param  Collection<int, Model>  $registros
     */
    public function zipSeccion(string $seccion, iterable $registros): BinaryFileResponse
    {
        return $this->zip([$seccion => $registros]);
    }
}

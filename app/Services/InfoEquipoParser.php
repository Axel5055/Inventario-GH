<?php

namespace App\Services;

/**
 * Interpreta el .txt generado por InfoEquipo.exe. Las etiquetas han
 * cambiado de versión a versión (p.ej. "Memoria RAM:" → "Memoria RAM
 * Total:", o el formato de la sección de discos), así que los patrones
 * se escriben de forma flexible para tolerar esas variaciones.
 */
class InfoEquipoParser
{
    /**
     * @return array{
     *     windows_version: ?string,
     *     marca_detectada: ?string,
     *     modelo: ?string,
     *     numero_serie: ?string,
     *     procesador: ?string,
     *     ram: ?string,
     *     almacenamiento: ?string,
     *     usuario_equipo: ?string,
     *     windows_key: ?string,
     *     office_clave: ?string,
     * }
     */
    public function parse(string $contenido): array
    {
        $contenido = str_replace("\r\n", "\n", $contenido);

        return [
            'windows_version' => $this->extraerCampo($contenido, 'Sist\. Operativo', fn (string $v) => trim(str_ireplace('Microsoft ', '', $v))),
            'marca_detectada' => $this->extraerCampo($contenido, 'Marca'),
            'modelo' => $this->extraerCampo($contenido, 'Modelo'),
            'numero_serie' => $this->extraerCampo($contenido, 'Numero de Serie'),
            'procesador' => $this->extraerCampo($contenido, 'Procesador'),
            'ram' => $this->extraerRam($contenido),
            'almacenamiento' => $this->extraerAlmacenamiento($contenido),
            'usuario_equipo' => $this->extraerCampo($contenido, 'Usuario'),
            'windows_key' => $this->extraerClave($contenido, 'Windows Key \(OEM\)'),
            'office_clave' => $this->extraerClave($contenido, 'Office Key \(ult\. 5 car\.\)'),
        ];
    }

    private function extraerCampo(string $contenido, string $etiqueta, ?\Closure $transformar = null): ?string
    {
        if (! preg_match('/^' . $etiqueta . ':\s*(.+)$/mi', $contenido, $m)) {
            return null;
        }

        $valor = trim($m[1]);

        if ($valor === '') {
            return null;
        }

        return $transformar ? $transformar($valor) : $valor;
    }

    /**
     * Claves de licencia: si el programa no logró detectarlas, escribe algo
     * como "No detectado (sin Office con clave local instalado)" — eso se
     * trata igual que si no existiera la etiqueta.
     */
    private function extraerClave(string $contenido, string $etiquetaRegex): ?string
    {
        if (! preg_match('/' . $etiquetaRegex . ':\s*(.+)$/mi', $contenido, $m)) {
            return null;
        }

        $valor = trim($m[1]);

        if ($valor === '' || stripos($valor, 'no detectado') !== false) {
            return null;
        }

        return $valor;
    }

    private function extraerRam(string $contenido): ?string
    {
        // Cubre "Memoria RAM:", "Memoria RAM Total:" y variantes con "aprox."
        if (! preg_match('/Memoria RAM[^:]*:\s*(?:aprox\.\s*)?([\d.]+)\s*GB/i', $contenido, $m)) {
            return null;
        }

        return $m[1] . ' GB';
    }

    private function extraerAlmacenamiento(string $contenido): ?string
    {
        // El encabezado puede ser "DISCO(S) DURO(S) =====" o llevar texto
        // extra como "DISCO(S) DURO(S) - CAPACIDAD TOTAL =====".
        if (! preg_match('/DISCO\(S\)\s*DURO\(S\)[^\n=]*=====\s*\n(.*)/is', $contenido, $m)) {
            return null;
        }

        $lineas = array_filter(array_map('trim', explode("\n", trim($m[1]))));
        $discos = [];

        foreach ($lineas as $linea) {
            // "- Acer SSD FA100 1TB (953.86 GB)"
            if (preg_match('/^-\s*(.+?)\s*\(([\d.]+)\s*GB\)$/i', $linea, $d)) {
                if ((float) $d[2] > 0) {
                    $discos[] = $d[2] . ' GB';
                }

                continue;
            }

            // "- Acer SSD FA100 1TB | Capacidad total: 953.86 GB"
            if (preg_match('/^-\s*.+?\|\s*Capacidad total:\s*([\d.]+)\s*GB$/i', $linea, $d)) {
                if ((float) $d[1] > 0) {
                    $discos[] = $d[1] . ' GB';
                }

                continue;
            }

            // Encabezado de la tabla de wmic ("Model    Size")
            if (preg_match('/^Model\s+Size$/i', $linea)) {
                continue;
            }

            // Formato wmic: "Samsung SSD 970 EVO 500GB    500107862016"
            if (preg_match('/^.+?\s+(\d{6,})\s*$/', $linea, $d)) {
                $gb = round(((int) $d[1]) / 1073741824, 2);

                if ($gb > 0) {
                    $discos[] = $gb . ' GB';
                }
            }
        }

        return $discos === [] ? null : implode(' + ', $discos);
    }
}

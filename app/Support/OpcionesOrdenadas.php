<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Ordena las opciones de un select alfabéticamente por su etiqueta (sin
 * distinguir mayúsculas, acentos ni emojis del inicio), con dos excepciones:
 *
 *  - "N/A" (también "NA", "N.A." o "No aplica") va siempre al INICIO.
 *  - "Otro", "Otra", "Otros", "Otras" (con o sin texto extra) van siempre al FINAL.
 */
class OpcionesOrdenadas
{
    /**
     * @param  array<int|string, string>|Collection<int|string, string>  $opciones  valor => etiqueta
     * @return array<int|string, string>
     */
    public static function alfabetico(array|Collection $opciones): array
    {
        $opciones = $opciones instanceof Collection ? $opciones->all() : $opciones;

        uasort($opciones, function ($a, $b) {
            $rangoA = self::rango($a);
            $rangoB = self::rango($b);

            if ($rangoA !== $rangoB) {
                return $rangoA <=> $rangoB;
            }

            return strnatcasecmp(self::clave($a), self::clave($b));
        });

        return $opciones;
    }

    /** 0 = N/A (al inicio), 1 = normal, 2 = Otro/Otra (al final). */
    private static function rango(string $etiqueta): int
    {
        $clave = self::clave($etiqueta);

        return match (true) {
            (bool) preg_match('/^(n\/a|n\.a\.?|na|no aplica)$/', $clave) => 0,
            (bool) preg_match('/^otr[oa]s?\b/', $clave) => 2,
            default => 1,
        };
    }

    private static function clave(string $etiqueta): string
    {
        // Quita emojis y símbolos del inicio ("🟢 Alta" -> "Alta") y los acentos.
        $limpia = preg_replace('/^[^\p{L}\p{N}]+/u', '', $etiqueta);

        return trim(Str::ascii(Str::lower($limpia)));
    }
}

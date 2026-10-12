<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Ordena las opciones de un select alfabéticamente por su etiqueta (sin
 * distinguir mayúsculas, acentos ni emojis del inicio) y deja al final las
 * opciones comodín: "Otro", "Otra", "Otros", "Otras" (con o sin texto extra).
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
            $ultimaA = self::esComodin($a);
            $ultimaB = self::esComodin($b);

            if ($ultimaA !== $ultimaB) {
                return $ultimaA <=> $ultimaB;
            }

            return strnatcasecmp(self::clave($a), self::clave($b));
        });

        return $opciones;
    }

    private static function clave(string $etiqueta): string
    {
        // Quita emojis y símbolos del inicio ("🟢 Alta" -> "Alta") y los acentos.
        $limpia = preg_replace('/^[^\p{L}\p{N}]+/u', '', $etiqueta);

        return Str::ascii(Str::lower($limpia));
    }

    private static function esComodin(string $etiqueta): bool
    {
        return (bool) preg_match('/^otr[oa]s?\b/', self::clave($etiqueta));
    }
}

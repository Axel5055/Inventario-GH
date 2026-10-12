<?php

namespace App\Support;

use App\Models\Marca;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Alta rápida de catálogos (marca, sucursal, área, razón social) desde el "+" de
 * un select. Si el registro ya existe avisa con una notificación y deja el
 * modal abierto, en lugar de dejar que la base de datos lance el error de
 * llave duplicada.
 */
class AltaRapidaCatalogo
{
    /**
     * @param  class-string<Model>  $modelo
     * @param  array<string, mixed>  $datos  atributos del nuevo registro (debe incluir 'nombre')
     * @param  string  $etiqueta  con artículo, ej. "la marca", "el área"
     * @param  string|null  $categoriaFormulario  solo marcas: 'computo' | 'celular', para explicar si existe con otra categoría
     * @param  string  $llave  atributo que se devuelve como valor del select ('id' por defecto)
     */
    public static function crear(string $modelo, array $datos, string $etiqueta, ?string $categoriaFormulario = null, string $llave = 'id'): int|string
    {
        $datos['nombre'] = trim((string) ($datos['nombre'] ?? ''));

        // La comparación usa la collation de la base (sin distinguir mayúsculas ni
        // acentos), la misma que aplica el índice único.
        $existente = $modelo::where('nombre', $datos['nombre'])->first();

        if ($existente) {
            self::avisarDuplicado($existente, $etiqueta, $categoriaFormulario);
        }

        try {
            return $modelo::create($datos)->{$llave};
        } catch (UniqueConstraintViolationException) {
            // Alguien lo creó justo en este momento.
            self::avisarDuplicado($modelo::where('nombre', $datos['nombre'])->first() ?? new $modelo($datos), $etiqueta, $categoriaFormulario);
        }
    }

    private static function avisarDuplicado(Model $existente, string $etiqueta, ?string $categoriaFormulario): never
    {
        $nombre = $existente->nombre;
        $articulo = str_starts_with($etiqueta, 'la ') ? 'La' : 'El';
        $sustantivo = substr($etiqueta, 3);

        $detalle = 'Selecciónala de la lista en lugar de crearla de nuevo.';

        if ($existente instanceof Marca && $existente->exists) {
            if (! $existente->activo) {
                $detalle = 'Está desactivada: un administrador puede activarla en Catálogos → Marcas.';
            } elseif ($categoriaFormulario && ! in_array($existente->categoria, [$categoriaFormulario, 'ambas'], true)) {
                $categoria = match ($existente->categoria) {
                    'computo' => 'Equipo de Cómputo',
                    'celular' => 'Celular',
                    'dispositivos' => 'Solo Dispositivos Externos',
                    'pendiente' => 'Pendiente de definir',
                    default => $existente->categoria,
                };
                $detalle = "Está registrada como «{$categoria}», por eso no aparece en esta lista. Un administrador puede cambiarla a «Ambas» en Catálogos → Marcas.";
            }
        } elseif (isset($existente->activo) && ! $existente->activo) {
            $detalle = 'Está desactivada: un administrador puede activarla en Catálogos.';
        }

        Notification::make()
            ->title("{$articulo} {$sustantivo} «{$nombre}» ya existe")
            ->body($detalle)
            ->warning()
            ->send();

        throw new Halt();
    }
}

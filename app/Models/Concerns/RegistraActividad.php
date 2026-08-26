<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Envuelve LogsActivity de Spatie para generar descripciones en español
 * consistentes ("registró la marca «Dell»", "actualizó la sucursal «X»")
 * sin repetir el texto en cada modelo. Cada modelo que use este trait debe
 * implementar:
 *
 *   - activityLogName(): string   — el nombre/identificador del registro.
 *   - activityLogTipo(): string   — el tipo con artículo, ej. "la marca",
 *                                    "un equipo de cómputo".
 *   - activityLogCampos(): array  — los campos que sí se pueden guardar en
 *                                    la bitácora (nunca contraseñas/claves).
 */
trait RegistraActividad
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->activityLogCampos())
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        $tipo = $this->activityLogTipo();
        $nombre = $this->activityLogName();

        return match ($eventName) {
            'created' => "registró {$tipo} «{$nombre}»",
            'updated' => "actualizó {$tipo} «{$nombre}»",
            'deleted' => "eliminó {$tipo} «{$nombre}»",
            default => "{$eventName}: {$tipo} «{$nombre}»",
        };
    }
}

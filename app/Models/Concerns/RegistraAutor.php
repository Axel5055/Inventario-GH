<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity;

/**
 * Guarda quién registró un registro y quién lo guardó por última vez
 * (columnas creado_por_id y actualizado_por_id) para la pestaña Auditoría.
 *
 * Solo se llena cuando hay un usuario con sesión; un registro del formulario
 * público queda sin autor. Para los registros anteriores a esta función, la
 * etiqueta intenta recuperar el dato de la bitácora de actividad.
 */
trait RegistraAutor
{
    public static function bootRegistraAutor(): void
    {
        static::creating(function ($modelo) {
            if ($id = auth()->id()) {
                $modelo->creado_por_id ??= $id;
                $modelo->actualizado_por_id = $id;
            }
        });

        static::updating(function ($modelo) {
            if ($id = auth()->id()) {
                $modelo->actualizado_por_id = $id;
            }
        });
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por_id');
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por_id');
    }

    /**
     * "Nombre (rol)" de quien registró el registro, o una explicación si no hay dato.
     */
    public function etiquetaRegistradoPor(): string
    {
        if ($this->creadoPor) {
            return $this->etiquetaUsuario($this->creadoPor);
        }

        if (($this->origen_registro ?? null) === 'publico') {
            return 'Formulario público (sin usuario)';
        }

        $autor = $this->autorEnBitacora('created', 'asc');

        return $autor ? $this->etiquetaUsuario($autor) : 'Sin dato (registro anterior al control de autoría)';
    }

    /**
     * "Nombre (rol)" de quien guardó el registro por última vez.
     */
    public function etiquetaEditadoPor(): string
    {
        if ($this->actualizadoPor) {
            return $this->etiquetaUsuario($this->actualizadoPor);
        }

        $autor = $this->autorEnBitacora('updated', 'desc');

        return $autor ? $this->etiquetaUsuario($autor) : 'Sin ediciones registradas';
    }

    private function etiquetaUsuario(User $usuario): string
    {
        $roles = $usuario->getRoleNames()->implode(', ');

        return $roles !== '' ? "{$usuario->name} ({$roles})" : $usuario->name;
    }

    /**
     * Respaldo para registros viejos: usuario que aparece en la bitácora de actividad.
     */
    private function autorEnBitacora(string $evento, string $orden): ?User
    {
        $actividad = Activity::query()
            ->where('subject_type', static::class)
            ->where('subject_id', $this->getKey())
            ->where('event', $evento)
            ->whereNotNull('causer_id')
            ->orderBy('id', $orden)
            ->first();

        return $actividad?->causer instanceof User ? $actividad->causer : null;
    }
}

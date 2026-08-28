<?php

namespace App\Models;

use App\Models\Concerns\RegistraActividad;
use Illuminate\Database\Eloquent\Model;

class SuscripcionOffice365 extends Model
{
    use RegistraActividad;

    protected $table = 'suscripciones_office365';

    protected $fillable = [
        'nombre',
        'correo',
        'contrasena',
        'fecha_compra',
        'fecha_fin',
    ];

    protected $casts = [
        'fecha_compra' => 'date',
        'fecha_fin' => 'date',
    ];

    /**
     * Días que faltan para que termine la suscripción. Negativo si ya
     * venció. Se calcula siempre contra la fecha de hoy, nunca se guarda.
     */
    public function getDiasRestantesAttribute(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->fecha_fin->copy()->startOfDay(), false);
    }

    public function activityLogName(): string
    {
        return $this->nombre;
    }

    public function activityLogTipo(): string
    {
        return 'la suscripción de Office 365';
    }

    public function activityLogCampos(): array
    {
        return ['nombre', 'correo', 'fecha_compra', 'fecha_fin'];
    }
}

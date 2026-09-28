<?php

namespace App\Models;

use App\Models\Concerns\RegistraActividad;
use Illuminate\Database\Eloquent\Model;

class AccesoICloud extends Model
{
    use RegistraActividad;

    public const TIPOS = [
        'correo' => 'Correo',
        'musica' => 'Música',
        'opentable' => 'OpenTable',
    ];

    protected $table = 'accesos_icloud';

    protected $fillable = [
        'cuenta',
        'tipo',
        'correo',
        'contrasena',
        'clave_recuperacion',
        'numero_serie',
        'pin',
    ];

    public function activityLogName(): string
    {
        return $this->cuenta;
    }

    public function activityLogTipo(): string
    {
        return 'el acceso iCloud';
    }

    public function activityLogCampos(): array
    {
        // 'contrasena', 'clave_recuperacion' y 'pin' nunca se registran en la bitácora.
        return ['cuenta', 'tipo', 'correo'];
    }
}

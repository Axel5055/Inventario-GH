<?php

namespace App\Models;

use App\Models\Concerns\RegistraActividad;
use Illuminate\Database\Eloquent\Model;

class TipoDispositivo extends Model
{
    use RegistraActividad;

    protected $table = 'tipos_dispositivo';

    protected $fillable = ['nombre', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function activityLogName(): string
    {
        return $this->nombre;
    }

    public function activityLogTipo(): string
    {
        return 'el tipo de dispositivo';
    }

    public function activityLogCampos(): array
    {
        return ['nombre', 'activo'];
    }
}

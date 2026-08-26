<?php

namespace App\Models;

use App\Models\Concerns\RegistraActividad;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use RegistraActividad;

    protected $fillable = ['nombre', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    public function activityLogName(): string
    {
        return $this->nombre;
    }

    public function activityLogTipo(): string
    {
        return 'el área';
    }

    public function activityLogCampos(): array
    {
        return ['nombre', 'activo'];
    }
}

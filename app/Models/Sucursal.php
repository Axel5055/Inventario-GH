<?php

namespace App\Models;

use App\Models\Concerns\RegistraActividad;
use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    use RegistraActividad;

    protected $table = 'sucursales';

    protected $fillable = ['nombre', 'ciudad', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function activityLogName(): string
    {
        return $this->nombre;
    }

    public function activityLogTipo(): string
    {
        return 'la sucursal';
    }

    public function activityLogCampos(): array
    {
        return ['nombre', 'ciudad', 'activo'];
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\RegistraActividad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RazonSocial extends Model
{
    use RegistraActividad;

    protected $table = 'razones_sociales';

    protected $fillable = ['nombre', 'rfc', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function sucursales(): HasMany
    {
        return $this->hasMany(Sucursal::class);
    }

    public function equiposComputo(): HasMany
    {
        return $this->hasMany(EquipoComputo::class);
    }

    public function equiposCelulares(): HasMany
    {
        return $this->hasMany(EquipoCelular::class);
    }

    public function activityLogName(): string
    {
        return $this->nombre;
    }

    public function activityLogTipo(): string
    {
        return 'la razón social';
    }

    public function activityLogCampos(): array
    {
        return ['nombre', 'rfc', 'activo'];
    }
}

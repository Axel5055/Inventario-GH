<?php

namespace App\Models;

use App\Models\Concerns\RegistraActividad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Marca extends Model
{
    use RegistraActividad;

    protected $table = 'marcas';

    protected $fillable = [
        'nombre',
        'categoria',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Scope para filtrar solo marcas activas
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    // Scope para filtrar por categoría
    public function scopeDeComputo($query)
    {
        return $query->whereIn('categoria', ['computo', 'ambas']);
    }

    public function scopeDeCelular($query)
    {
        return $query->whereIn('categoria', ['celular', 'ambas']);
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
        return 'la marca';
    }

    public function activityLogCampos(): array
    {
        return ['nombre', 'categoria', 'activo'];
    }
}

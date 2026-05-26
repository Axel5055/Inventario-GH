<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipoCelular extends Model
{
    use SoftDeletes;

    protected $table = 'equipos_celulares';

    protected $fillable = [
        'tipo_movimiento',
        'fecha_entrega',
        'razon_social_id',
        'nombre_usuario',
        'sucursal_id',
        'area_id',
        'puesto',
        'tipo_equipo',
        'marca_id',
        'modelo',
        'numero_telefonico',
        'imei',
        'iccid',
        'curp',
        'observaciones',
        'responsiva_pdf',
        'usuario_referencia',
    ];

    protected $casts = [
        'fecha_entrega' => 'date',
    ];

    public function razonSocial(): BelongsTo
    {
        return $this->belongsTo(RazonSocial::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }
}

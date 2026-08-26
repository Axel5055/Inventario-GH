<?php

namespace App\Models;

use App\Models\Concerns\RegistraActividad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipoCelular extends Model
{
    use SoftDeletes, RegistraActividad;

    protected $table = 'equipos_celulares';

    protected $fillable = [
        'tipo_movimiento',
        'fecha_entrega',
        'fecha_baja',
        'razon_social_id',
        'nombre_usuario',
        'sucursal_id',
        'area_id',
        'ext',
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
        'fecha_entrega' => 'datetime',
        'fecha_baja' => 'datetime',
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

    public function activityLogName(): string
    {
        return $this->nombre_usuario;
    }

    public function activityLogTipo(): string
    {
        return 'un equipo celular';
    }

    public function activityLogCampos(): array
    {
        return ['nombre_usuario', 'tipo_movimiento', 'tipo_equipo', 'modelo', 'sucursal_id'];
    }
}

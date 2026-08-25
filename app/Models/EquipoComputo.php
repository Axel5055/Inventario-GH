<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipoComputo extends Model
{
    use SoftDeletes;

    protected $table = 'equipos_computo';

    protected $fillable = [
        'tipo_movimiento',
        'fecha_entrega',
        'fecha_baja',
        'razon_social_id',
        'nombre_usuario',
        'correo_electronico',
        'sucursal_id',
        'area_id',
        'ext',
        'tipo_equipo',
        'marca_id',
        'modelo',
        'numero_serie',
        'procesador',
        'ram',
        'almacenamiento',
        'observaciones',
        'usuario_equipo',
        'pin_password',
        'responsiva_pdf',
        'sistema_operativo',
        'windows_version',
        'windows_key',
        'office_version',
        'correo_office',
        'office_clave',
        'antivirus_nombre',
        'antivirus_fecha_instalacion',
        'antivirus_vigencia',
        'outlook_correo',
        'outlook_password',
        'outlook_correo_recuperacion',
        'bitlocker_key',
        'usuario_referencia',
    ];

    protected $casts = [
        'fecha_entrega' => 'datetime',
        'fecha_baja' => 'datetime',
        'antivirus_fecha_instalacion' => 'date',
        'antivirus_vigencia' => 'date',
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

    // Historial: todos los equipos del mismo usuario (por correo)
    // En EquipoComputo.php
    public function historialEquipos(): HasMany
    {
        return $this->hasMany(EquipoComputo::class, 'usuario_referencia', 'usuario_referencia')
            ->orderByDesc('fecha_entrega');
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\RegistraActividad;
use App\Models\Concerns\RegistraAutor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntregaDispositivo extends Model
{
    use SoftDeletes, RegistraActividad, RegistraAutor;

    protected $table = 'entregas_dispositivos';

    protected $fillable = [
        'tipo_movimiento',
        'nombre_usuario',
        'usuario_referencia',
        'razon_social_id',
        'sucursal_id',
        'area_id',
        'puesto',
        'correo_electronico',
        'fecha_entrega',
        'tipo_dispositivo',
        'descripcion',
        'marca',
        'modelo',
        'numero_serie',
        'cantidad',
        'observaciones',
        'responsiva_pdf',
        'origen_registro',
        'lote_carga',
        'estado_revision',
    ];

    protected $casts = [
        'fecha_entrega' => 'date',
        'cantidad' => 'integer',
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

    public function activityLogName(): string
    {
        return $this->nombre_usuario;
    }

    public function activityLogTipo(): string
    {
        return 'un dispositivo externo';
    }

    public function activityLogCampos(): array
    {
        return ['nombre_usuario', 'tipo_movimiento', 'tipo_dispositivo', 'descripcion', 'sucursal_id'];
    }
}

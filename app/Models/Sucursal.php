<?php

namespace App\Models;

use App\Models\Concerns\RegistraActividad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sucursal extends Model
{
    use RegistraActividad;

    protected $table = 'sucursales';

    protected $fillable = ['razon_social_id', 'nombre', 'ciudad', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function razonSocial(): BelongsTo
    {
        return $this->belongsTo(RazonSocial::class);
    }

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
        return ['razon_social_id', 'nombre', 'ciudad', 'activo'];
    }
}

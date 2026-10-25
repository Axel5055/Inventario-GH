<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Carga masiva y lectura de PDF en Dispositivos Externos: los registros se crean
 * como "pendientes" y lo que no se pueda leer queda vacío, así que razón social,
 * sucursal y área dejan de ser obligatorias en BD (el formulario de edición
 * sigue exigiéndolas al validar el registro). Se agregan el estado de revisión
 * y el origen del registro, igual que en cómputo y móviles.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE entregas_dispositivos MODIFY razon_social_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE entregas_dispositivos MODIFY sucursal_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE entregas_dispositivos MODIFY area_id BIGINT UNSIGNED NULL');

        if (! Schema::hasColumn('entregas_dispositivos', 'origen_registro')) {
            DB::statement("ALTER TABLE entregas_dispositivos ADD COLUMN origen_registro ENUM('manual', 'masivo') NOT NULL DEFAULT 'manual' AFTER usuario_referencia");
        }

        if (! Schema::hasColumn('entregas_dispositivos', 'estado_revision')) {
            DB::statement("ALTER TABLE entregas_dispositivos ADD COLUMN estado_revision ENUM('pendiente', 'completo') NOT NULL DEFAULT 'completo' AFTER origen_registro");
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE entregas_dispositivos DROP COLUMN estado_revision');
        DB::statement('ALTER TABLE entregas_dispositivos DROP COLUMN origen_registro');
        // Razón social, sucursal y área no se vuelven a marcar como obligatorias:
        // podrían existir registros pendientes sin esos datos.
    }
};

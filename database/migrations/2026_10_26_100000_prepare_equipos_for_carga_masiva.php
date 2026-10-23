<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Soporte para la carga masiva de registros (se crean como "pendientes" y el
 * personal los revisa y completa):
 *
 *  - Lo que la IA no logra leer del documento queda vacío, así que sucursal,
 *    área, razón social y marca dejan de ser obligatorias en BD (el formulario
 *    de edición sigue exigiéndolas al validar el registro).
 *  - Equipos móviles gana el estado de revisión y el origen del registro, igual
 *    que cómputo; ambos reconocen el origen "masivo".
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE equipos_computo MODIFY sucursal_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE equipos_computo MODIFY area_id BIGINT UNSIGNED NULL');
        DB::statement("ALTER TABLE equipos_computo MODIFY origen_registro ENUM('manual', 'publico', 'masivo') NOT NULL DEFAULT 'manual'");

        DB::statement('ALTER TABLE equipos_celulares MODIFY razon_social_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE equipos_celulares MODIFY sucursal_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE equipos_celulares MODIFY area_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE equipos_celulares MODIFY marca_id BIGINT UNSIGNED NULL');

        if (! Schema::hasColumn('equipos_celulares', 'origen_registro')) {
            DB::statement("ALTER TABLE equipos_celulares ADD COLUMN origen_registro ENUM('manual', 'masivo') NOT NULL DEFAULT 'manual' AFTER usuario_referencia");
        }

        if (! Schema::hasColumn('equipos_celulares', 'estado_revision')) {
            DB::statement("ALTER TABLE equipos_celulares ADD COLUMN estado_revision ENUM('pendiente', 'completo') NOT NULL DEFAULT 'completo' AFTER origen_registro");
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE equipos_celulares DROP COLUMN estado_revision');
        DB::statement('ALTER TABLE equipos_celulares DROP COLUMN origen_registro');

        DB::statement("UPDATE equipos_computo SET origen_registro = 'manual' WHERE origen_registro = 'masivo'");
        DB::statement("ALTER TABLE equipos_computo MODIFY origen_registro ENUM('manual', 'publico') NOT NULL DEFAULT 'manual'");
        // Las columnas de sucursal/área/razón social/marca no se vuelven a marcar
        // como obligatorias: podrían existir registros pendientes sin esos datos.
    }
};

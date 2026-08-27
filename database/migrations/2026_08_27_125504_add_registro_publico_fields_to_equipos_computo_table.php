<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Soporte para el autorregistro público de equipos: el formulario público
 * no captura razón social ni marca (se completan después en la revisión
 * manual), así que esas columnas dejan de ser obligatorias. También se
 * agrega el estado de revisión y el origen del registro para que el
 * personal pueda identificar y completar los registros pendientes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! $this->columnIsNullable('razon_social_id')) {
            DB::statement('ALTER TABLE equipos_computo DROP FOREIGN KEY equipos_computo_razon_social_id_foreign');
            DB::statement('ALTER TABLE equipos_computo MODIFY razon_social_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE equipos_computo ADD CONSTRAINT equipos_computo_razon_social_id_foreign FOREIGN KEY (razon_social_id) REFERENCES razones_sociales (id)');
        }

        if (! $this->columnIsNullable('marca_id')) {
            DB::statement('ALTER TABLE equipos_computo DROP FOREIGN KEY equipos_computo_marca_id_foreign');
            DB::statement('ALTER TABLE equipos_computo MODIFY marca_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE equipos_computo ADD CONSTRAINT equipos_computo_marca_id_foreign FOREIGN KEY (marca_id) REFERENCES marcas (id)');
        }

        if (! Schema::hasColumn('equipos_computo', 'marca_detectada')) {
            DB::statement("ALTER TABLE equipos_computo ADD COLUMN marca_detectada VARCHAR(255) NULL COMMENT 'Fabricante detectado automáticamente al parsear el .txt de especificaciones (Windows)' AFTER marca_id");
        }

        if (! Schema::hasColumn('equipos_computo', 'origen_registro')) {
            DB::statement("ALTER TABLE equipos_computo ADD COLUMN origen_registro ENUM('manual', 'publico') NOT NULL DEFAULT 'manual' AFTER usuario_referencia");
        }

        if (! Schema::hasColumn('equipos_computo', 'estado_revision')) {
            DB::statement("ALTER TABLE equipos_computo ADD COLUMN estado_revision ENUM('pendiente', 'completo') NOT NULL DEFAULT 'completo' AFTER origen_registro");
        }
    }

    private function columnIsNullable(string $column): bool
    {
        $row = DB::selectOne(
            'SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['equipos_computo', $column]
        );

        return $row?->IS_NULLABLE === 'YES';
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE equipos_computo DROP COLUMN estado_revision');
        DB::statement('ALTER TABLE equipos_computo DROP COLUMN origen_registro');
        DB::statement('ALTER TABLE equipos_computo DROP COLUMN marca_detectada');

        DB::statement('ALTER TABLE equipos_computo DROP FOREIGN KEY equipos_computo_marca_id_foreign');
        DB::statement('ALTER TABLE equipos_computo MODIFY marca_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE equipos_computo ADD CONSTRAINT equipos_computo_marca_id_foreign FOREIGN KEY (marca_id) REFERENCES marcas (id)');

        DB::statement('ALTER TABLE equipos_computo DROP FOREIGN KEY equipos_computo_razon_social_id_foreign');
        DB::statement('ALTER TABLE equipos_computo MODIFY razon_social_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE equipos_computo ADD CONSTRAINT equipos_computo_razon_social_id_foreign FOREIGN KEY (razon_social_id) REFERENCES razones_sociales (id)');
    }
};

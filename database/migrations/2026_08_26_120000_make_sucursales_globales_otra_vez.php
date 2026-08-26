<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Las sucursales son ubicaciones físicas compartidas entre razones sociales
 * (una misma dirección puede operar bajo distintas empresas), así que no
 * deben estar amarradas a una sola razón social. Esto revierte la migración
 * 2026_08_25_010000_add_razon_social_id_to_sucursales_table.
 *
 * Antes de quitar la columna, se fusionan sucursales duplicadas por nombre
 * (mismo nombre creado varias veces por estar ligado a razones sociales
 * distintas) hacia un único registro, re-apuntando los equipos y entregas
 * que ya las referenciaban.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE TEMPORARY TABLE sucursales_canonicas AS
            SELECT nombre, MIN(id) AS id_canonico
            FROM sucursales
            GROUP BY nombre
        ');

        foreach (['equipos_computo', 'equipos_celulares', 'entregas_dispositivos'] as $tabla) {
            DB::statement("
                UPDATE {$tabla} t
                JOIN sucursales s ON s.id = t.sucursal_id
                JOIN sucursales_canonicas sc ON sc.nombre = s.nombre
                SET t.sucursal_id = sc.id_canonico
                WHERE t.sucursal_id <> sc.id_canonico
            ");
        }

        DB::statement('
            DELETE s FROM sucursales s
            JOIN sucursales_canonicas sc ON sc.nombre = s.nombre
            WHERE s.id <> sc.id_canonico
        ');

        DB::statement('DROP TEMPORARY TABLE sucursales_canonicas');

        Schema::table('sucursales', function (Blueprint $table) {
            $table->dropForeign(['razon_social_id']);
        });

        Schema::table('sucursales', function (Blueprint $table) {
            $table->dropUnique('sucursales_razon_social_id_nombre_unique');
            $table->dropColumn('razon_social_id');
            $table->unique('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('sucursales', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
            $table->foreignId('razon_social_id')
                ->after('id')
                ->constrained('razones_sociales')
                ->cascadeOnDelete();
            $table->unique(['razon_social_id', 'nombre']);
        });
    }
};

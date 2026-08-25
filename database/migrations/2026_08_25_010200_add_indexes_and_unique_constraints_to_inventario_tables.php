<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos_computo', function (Blueprint $table) {
            $table->index('usuario_referencia');
            $table->index('tipo_movimiento');
            $table->index('fecha_entrega');
            $table->index('fecha_baja');
            $table->index('correo_electronico');
        });

        Schema::table('equipos_celulares', function (Blueprint $table) {
            $table->index('usuario_referencia');
            $table->index('tipo_movimiento');
            $table->index('fecha_entrega');
            $table->index('fecha_baja');
            $table->unique('imei');
        });

        Schema::table('entregas_dispositivos', function (Blueprint $table) {
            $table->index('usuario_referencia');
            $table->index('tipo_movimiento');
            $table->index('fecha_entrega');
        });

        Schema::table('marcas', function (Blueprint $table) {
            $table->unique('nombre');
        });

        Schema::table('areas', function (Blueprint $table) {
            $table->unique('nombre');
        });

        Schema::table('razones_sociales', function (Blueprint $table) {
            $table->unique('nombre');
        });

        Schema::table('sucursales', function (Blueprint $table) {
            $table->unique(['razon_social_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::table('equipos_computo', function (Blueprint $table) {
            $table->dropIndex(['usuario_referencia']);
            $table->dropIndex(['tipo_movimiento']);
            $table->dropIndex(['fecha_entrega']);
            $table->dropIndex(['fecha_baja']);
            $table->dropIndex(['correo_electronico']);
        });

        Schema::table('equipos_celulares', function (Blueprint $table) {
            $table->dropIndex(['usuario_referencia']);
            $table->dropIndex(['tipo_movimiento']);
            $table->dropIndex(['fecha_entrega']);
            $table->dropIndex(['fecha_baja']);
            $table->dropUnique(['imei']);
        });

        Schema::table('entregas_dispositivos', function (Blueprint $table) {
            $table->dropIndex(['usuario_referencia']);
            $table->dropIndex(['tipo_movimiento']);
            $table->dropIndex(['fecha_entrega']);
        });

        Schema::table('marcas', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
        });

        Schema::table('areas', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
        });

        Schema::table('razones_sociales', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
        });

        Schema::table('sucursales', function (Blueprint $table) {
            $table->dropUnique(['razon_social_id', 'nombre']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un mismo equipo físico se reasigna a distintas personas a lo largo del
 * tiempo (se da de baja a una persona y el equipo se recicla a otra), y
 * cada asignación debe quedar como su propio registro histórico. Por eso
 * `numero_serie` / `imei` ya no pueden ser únicos a nivel de base de datos:
 * la regla real es "único mientras el registro esté activo", y esa regla
 * se valida en el formulario (ver EquipoComputoForm / EquipoCelularForm),
 * no aquí.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos_computo', function (Blueprint $table) {
            $table->dropUnique(['numero_serie']);
            $table->index('numero_serie');
        });

        Schema::table('equipos_celulares', function (Blueprint $table) {
            $table->dropUnique(['imei']);
            $table->index('imei');
        });
    }

    public function down(): void
    {
        Schema::table('equipos_computo', function (Blueprint $table) {
            $table->dropIndex(['numero_serie']);
            $table->unique('numero_serie');
        });

        Schema::table('equipos_celulares', function (Blueprint $table) {
            $table->dropIndex(['imei']);
            $table->unique('imei');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identifica en qué carga masiva se creó cada registro, para poder revisarla o
 * deshacerla completa.
 */
return new class extends Migration
{
    private const TABLAS = ['equipos_computo', 'equipos_celulares', 'entregas_dispositivos'];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->string('lote_carga', 40)->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropIndex(['lote_carga']);
                $table->dropColumn('lote_carga');
            });
        }
    }
};

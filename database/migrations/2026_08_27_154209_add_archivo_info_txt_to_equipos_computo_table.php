<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guarda el .txt de especificaciones que el usuario sube en el
 * autorregistro público, para que el personal pueda descargarlo y
 * verificar el reporte original (igual que ya se hace con la responsiva).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos_computo', function (Blueprint $table) {
            $table->string('archivo_info_txt')->nullable()->after('responsiva_pdf');
        });
    }

    public function down(): void
    {
        Schema::table('equipos_computo', function (Blueprint $table) {
            $table->dropColumn('archivo_info_txt');
        });
    }
};

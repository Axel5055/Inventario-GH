<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Responsiva de baja (opcional): archivo aparte de la responsiva de entrega.
     */
    public function up(): void
    {
        Schema::table('equipos_computo', function (Blueprint $table) {
            $table->string('responsiva_baja_pdf')->nullable()->after('responsiva_pdf');
        });

        Schema::table('equipos_celulares', function (Blueprint $table) {
            $table->string('responsiva_baja_pdf')->nullable()->after('responsiva_pdf');
        });
    }

    public function down(): void
    {
        Schema::table('equipos_computo', function (Blueprint $table) {
            $table->dropColumn('responsiva_baja_pdf');
        });

        Schema::table('equipos_celulares', function (Blueprint $table) {
            $table->dropColumn('responsiva_baja_pdf');
        });
    }
};

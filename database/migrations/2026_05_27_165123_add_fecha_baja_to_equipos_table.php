<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos_computo', function (Blueprint $table) {
            $table->date('fecha_baja')->nullable()->after('fecha_entrega');
        });

        Schema::table('equipos_celulares', function (Blueprint $table) {
            $table->date('fecha_baja')->nullable()->after('fecha_entrega');
        });
    }

    public function down(): void
    {
        Schema::table('equipos_computo', function (Blueprint $table) {
            $table->dropColumn('fecha_baja');
        });

        Schema::table('equipos_celulares', function (Blueprint $table) {
            $table->dropColumn('fecha_baja');
        });
    }
};

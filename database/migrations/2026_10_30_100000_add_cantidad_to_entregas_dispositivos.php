<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuántas piezas se entregan en el registro (cables, mouse, etc.). Mínimo 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('entregas_dispositivos', 'cantidad')) {
            return;
        }

        Schema::table('entregas_dispositivos', function (Blueprint $table) {
            $table->unsignedInteger('cantidad')->default(1)->after('numero_serie');
        });
    }

    public function down(): void
    {
        Schema::table('entregas_dispositivos', function (Blueprint $table) {
            $table->dropColumn('cantidad');
        });
    }
};

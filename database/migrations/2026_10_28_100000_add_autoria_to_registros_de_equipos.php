<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoría: quién registró cada equipo y quién lo guardó por última vez.
 */
return new class extends Migration
{
    private const TABLAS = ['equipos_computo', 'equipos_celulares', 'entregas_dispositivos'];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreignId('creado_por_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('actualizado_por_id')->nullable()->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropConstrainedForeignId('creado_por_id');
                $table->dropConstrainedForeignId('actualizado_por_id');
            });
        }
    }
};

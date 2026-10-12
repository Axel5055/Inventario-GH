<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "dispositivos": marca clasificada por un administrador como exclusiva de
     * Dispositivos Externos (no aplica a cómputo ni a celular).
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE marcas MODIFY categoria ENUM('computo','celular','ambas','pendiente','dispositivos') NOT NULL DEFAULT 'ambas' COMMENT 'Para filtrar marcas por tipo de equipo'");
    }

    public function down(): void
    {
        DB::statement("UPDATE marcas SET categoria = 'pendiente' WHERE categoria = 'dispositivos'");
        DB::statement("ALTER TABLE marcas MODIFY categoria ENUM('computo','celular','ambas','pendiente') NOT NULL DEFAULT 'ambas' COMMENT 'Para filtrar marcas por tipo de equipo'");
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "pendiente": marca dada de alta desde Dispositivos Externos que un
     * administrador aún no clasifica como de cómputo, celular o ambas. Solo se
     * ofrece en Dispositivos Externos hasta que se defina.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE marcas MODIFY categoria ENUM('computo','celular','ambas','pendiente') NOT NULL DEFAULT 'ambas' COMMENT 'Para filtrar marcas por tipo de equipo'");
    }

    public function down(): void
    {
        DB::statement("UPDATE marcas SET categoria = 'ambas' WHERE categoria = 'pendiente'");
        DB::statement("ALTER TABLE marcas MODIFY categoria ENUM('computo','celular','ambas') NOT NULL DEFAULT 'ambas' COMMENT 'Para filtrar marcas por tipo de equipo'");
    }
};

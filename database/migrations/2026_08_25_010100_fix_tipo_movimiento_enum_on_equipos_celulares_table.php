<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El ENUM original solo permitía 'alta'/'baja', pero el formulario y las
 * vistas de Equipos Celulares siempre ofrecieron los mismos 6 movimientos
 * que Equipos de Cómputo. Guardar cualquiera de los otros 4 truena con
 * "Data truncated for column 'tipo_movimiento'".
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE equipos_celulares
            MODIFY tipo_movimiento ENUM(
                'alta',
                'baja',
                'cambio_equipo',
                'reasignacion',
                'mantenimiento',
                'prestamo_temporal'
            ) NOT NULL DEFAULT 'alta'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE equipos_celulares
            MODIFY tipo_movimiento ENUM('alta', 'baja') NOT NULL DEFAULT 'alta'
        ");
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE entregas_dispositivos MODIFY tipo_dispositivo ENUM('disco_duro','cable','accesorio','teclado','mouse','monitor','punto_venta','tablet','impresora','otro') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("UPDATE entregas_dispositivos SET tipo_dispositivo = 'otro' WHERE tipo_dispositivo IN ('punto_venta','tablet','impresora')");
        DB::statement("ALTER TABLE entregas_dispositivos MODIFY tipo_dispositivo ENUM('disco_duro','cable','accesorio','teclado','mouse','monitor','otro') NOT NULL");
    }
};

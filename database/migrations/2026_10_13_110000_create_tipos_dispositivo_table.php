<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Valor anterior (enum) => nombre en el catálogo. */
    private const EQUIVALENCIAS = [
        'disco_duro' => 'Disco Duro',
        'cable' => 'Cable',
        'accesorio' => 'Accesorio',
        'teclado' => 'Teclado',
        'mouse' => 'Mouse',
        'monitor' => 'Monitor',
        'punto_venta' => 'Punto de Venta',
        'tablet' => 'Tablet',
        'impresora' => 'Impresora',
        'otro' => 'Otro',
    ];

    /**
     * El tipo de dispositivo deja de ser un enum fijo y pasa a ser un catálogo
     * al que se pueden agregar tipos nuevos (como las marcas). La columna
     * guarda el nombre del tipo.
     */
    public function up(): void
    {
        Schema::create('tipos_dispositivo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        foreach (self::EQUIVALENCIAS as $nombre) {
            DB::table('tipos_dispositivo')->insert([
                'nombre' => $nombre,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::statement('ALTER TABLE entregas_dispositivos MODIFY tipo_dispositivo VARCHAR(100) NOT NULL');

        foreach (self::EQUIVALENCIAS as $clave => $nombre) {
            DB::table('entregas_dispositivos')->where('tipo_dispositivo', $clave)->update(['tipo_dispositivo' => $nombre]);
        }
    }

    public function down(): void
    {
        foreach (self::EQUIVALENCIAS as $clave => $nombre) {
            DB::table('entregas_dispositivos')->where('tipo_dispositivo', $nombre)->update(['tipo_dispositivo' => $clave]);
        }

        // Los tipos que no existían en el enum vuelven como "otro".
        DB::table('entregas_dispositivos')->whereNotIn('tipo_dispositivo', array_keys(self::EQUIVALENCIAS))->update(['tipo_dispositivo' => 'otro']);

        DB::statement("ALTER TABLE entregas_dispositivos MODIFY tipo_dispositivo ENUM('disco_duro','cable','accesorio','teclado','mouse','monitor','punto_venta','tablet','impresora','otro') NOT NULL");

        Schema::dropIfExists('tipos_dispositivo');
    }
};

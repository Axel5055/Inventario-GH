<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_create_entregas_dispositivos_table.php
    public function up(): void
    {
        Schema::create('entregas_dispositivos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_usuario');
            $table->string('usuario_referencia')->nullable();
            $table->foreignId('razon_social_id')->constrained('razones_sociales');
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->foreignId('area_id')->constrained('areas');
            $table->string('puesto')->nullable();
            $table->string('correo_electronico');
            $table->date('fecha_entrega');
            $table->enum('tipo_dispositivo', [
                'disco_duro',
                'cable',
                'accesorio',
                'teclado',
                'mouse',
                'monitor',
                'otro'
            ]);
            $table->string('descripcion');
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('numero_serie')->nullable();
            $table->enum('tipo_movimiento', ['entrega', 'devolucion'])->default('entrega');
            $table->text('observaciones')->nullable();
            $table->string('responsiva_pdf')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entregas_dispositivos');
    }
};

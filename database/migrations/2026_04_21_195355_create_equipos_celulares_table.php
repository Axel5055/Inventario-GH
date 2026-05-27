<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_create_equipos_celulares_table.php
    public function up(): void
    {
        Schema::create('equipos_celulares', function (Blueprint $table) {
            $table->id();

            // === TIPO DE MOVIMIENTO ===
            $table->enum('tipo_movimiento', ['alta', 'baja'])->default('alta');
            $table->datetime('fecha_entrega');

            // === DATOS DEL USUARIO ===
            $table->foreignId('razon_social_id')->constrained('razones_sociales');
            $table->string('nombre_usuario');
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->foreignId('area_id')->constrained('areas');
            $table->string('puesto')->nullable();

            // === FICHA TÉCNICA ===
            $table->enum('tipo_equipo', [
                'celular',
                'tablet',
                'ipad',
                'otro'
            ]);
            $table->foreignId('marca_id')->constrained('marcas');
            $table->string('modelo');
            $table->string('numero_telefonico')->nullable();
            $table->string('imei')->nullable();
            $table->string('iccid')->nullable()
                ->comment('Número de serie del SIM');
            $table->string('curp')->nullable();
            $table->text('observaciones')->nullable();

            // === RESPONSIVA (opcional) ===
            $table->string('responsiva_pdf')->nullable();

            // === REFERENCIA USUARIO ===
            $table->string('usuario_referencia')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipos_celulares');
    }
};

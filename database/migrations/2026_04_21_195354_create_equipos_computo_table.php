<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_create_equipos_computo_table.php
    public function up(): void
    {
        Schema::create('equipos_computo', function (Blueprint $table) {
            $table->id();

            // === TIPO DE MOVIMIENTO ===
            $table->enum('tipo_movimiento', [
                'alta',
                'baja',
                'cambio_equipo',
                'reasignacion',
                'mantenimiento',
                'prestamo_temporal'
            ])->default('alta');
            $table->datetime('fecha_entrega');

            // === DATOS DEL USUARIO ===
            $table->foreignId('razon_social_id')->constrained('razones_sociales');
            $table->string('nombre_usuario');
            $table->string('correo_electronico');
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->foreignId('area_id')->constrained('areas');
            $table->string('puesto')->nullable();

            // === FICHA TÉCNICA ===
            $table->enum('tipo_equipo', [
                'laptop',
                'desktop',
                'all_in_one',
                'workstation',
                'mini_pc'
            ]);
            $table->foreignId('marca_id')->constrained('marcas');
            $table->string('modelo');
            $table->string('numero_serie')->unique();
            $table->string('procesador')->nullable();
            $table->string('ram')->nullable();
            $table->string('almacenamiento')->nullable();
            $table->text('observaciones')->nullable();

            // === ACCESOS ===
            $table->string('usuario_equipo')->nullable();
            $table->string('pin_password')->nullable(); // Considera encriptar

            // === RESPONSIVA ===
            $table->string('responsiva_pdf')->nullable();

            // === SOFTWARE Y SEGURIDAD ===
            // Windows
            $table->string('windows_version')->nullable();
            $table->string('windows_key')->nullable();
            // Office
            $table->string('office_version')->nullable();
            $table->string('correo_office')->nullable();
            $table->string('office_clave')->nullable();
            // Antivirus
            $table->string('antivirus_nombre')->nullable();
            $table->date('antivirus_fecha_instalacion')->nullable();
            $table->date('antivirus_vigencia')->nullable();

            // === DATOS OPCIONALES ===
            // Correo Outlook
            $table->string('outlook_correo')->nullable();
            $table->string('outlook_password')->nullable();
            $table->string('outlook_correo_recuperacion')->nullable();
            // BitLocker
            $table->string('bitlocker_key')->nullable();

            // === REFERENCIA AL USUARIO (para historial) ===
            // Usamos correo como identificador de usuario para agrupar historial
            $table->string('usuario_referencia')->nullable()
                ->comment('Correo o ID para agrupar historial del mismo usuario');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipos_computo');
    }
};

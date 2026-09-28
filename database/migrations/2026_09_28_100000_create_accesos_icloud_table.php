<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accesos_icloud', function (Blueprint $table) {
            $table->id();
            $table->string('cuenta');
            $table->string('tipo', 20);
            $table->string('correo');
            $table->string('contrasena');
            $table->string('clave_recuperacion')->nullable();
            $table->string('numero_serie')->nullable();
            $table->string('pin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accesos_icloud');
    }
};

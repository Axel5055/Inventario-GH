<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_create_razones_sociales_table.php
    public function up(): void
    {
        Schema::create('razones_sociales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('rfc')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('razones_sociales');
    }
};

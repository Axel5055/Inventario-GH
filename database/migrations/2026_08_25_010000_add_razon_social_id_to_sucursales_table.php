<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sucursales', function (Blueprint $table) {
            $table->foreignId('razon_social_id')
                ->after('id')
                ->constrained('razones_sociales')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sucursales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('razon_social_id');
        });
    }
};

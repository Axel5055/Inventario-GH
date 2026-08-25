<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos_computo', function (Blueprint $table) {
            $table->string('sistema_operativo')->nullable()->after('almacenamiento');
        });

        // Los registros existentes ya traían Windows como único sistema soportado
        DB::table('equipos_computo')->update(['sistema_operativo' => 'windows']);
    }

    public function down(): void
    {
        Schema::table('equipos_computo', function (Blueprint $table) {
            $table->dropColumn('sistema_operativo');
        });
    }
};

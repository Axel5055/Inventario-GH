<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suscripciones_office365', function (Blueprint $table) {
            $table->string('clave_licencia')->nullable()->after('contrasena');
        });
    }

    public function down(): void
    {
        Schema::table('suscripciones_office365', function (Blueprint $table) {
            $table->dropColumn('clave_licencia');
        });
    }
};

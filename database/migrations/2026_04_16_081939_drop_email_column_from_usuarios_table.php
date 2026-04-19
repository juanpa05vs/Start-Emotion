<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            // Eliminamos la columna vieja que está causando el conflicto
            $table->dropColumn('email');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            // Por si necesitamos volver atrás, la recreamos
            $table->string('email')->nullable();
        });
    }
};

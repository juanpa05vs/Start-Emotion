<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta los cambios en la Capa de Datos.
     */
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            // Agregamos la columna 'correo' después del nombre
            // Si ya existe una llamada 'email', puedes comentarla o borrarla en phpMyAdmin después
            $table->string('correo')->unique()->after('nombre');
        });
    }

    /**
     * Revierte los cambios si algo sale mal.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('correo');
        });
    }
};

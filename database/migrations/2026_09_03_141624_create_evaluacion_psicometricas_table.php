<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_psicometricas', function (Blueprint $table) {
            $table->id();
            // Vinculación explícita a la tabla 'usuarios'
            $table->foreignId('user_id')->constrained('usuarios')->onDelete('cascade');

            $table->string('instrumento')->default('SISCO_ESTRES');
            $table->integer('puntaje_estresores');
            $table->integer('puntaje_sintomas');
            $table->integer('puntaje_afrontamiento');
            $table->integer('puntaje_global');

            $table->enum('nivel_estres', ['bajo', 'moderado', 'severo']);
            $table->enum('estado_afectivo_predominante', ['alegria', 'tristeza', 'ansiedad', 'frustracion', 'neutralidad']);

            $table->json('respuestas_detalle')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_psicometricas');
    }
};

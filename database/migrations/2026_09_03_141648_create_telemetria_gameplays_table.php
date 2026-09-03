<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telemetria_gameplay', function (Blueprint $table) {
            $table->id();
            // Vinculación explícita a la tabla 'usuarios'
            $table->foreignId('user_id')->constrained('usuarios')->onDelete('cascade');

            $table->string('minijuego_id')->default('codigo_anomalo');
            $table->float('latencia_promedio_ms');
            $table->float('frecuencia_tapping');
            $table->integer('tiempo_total_ms');
            $table->integer('conteo_rectificaciones');
            $table->integer('errores_diagnostico');
            $table->integer('score_final');

            $table->string('emocion_objetivo');
            $table->string('emocion_predicha');
            $table->boolean('diagnostico_correcto');

            $table->json('vector_caracteristicas');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telemetria_gameplay');
    }
};

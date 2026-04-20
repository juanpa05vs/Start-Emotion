<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * [INGENIERÍA FINAL]: Tabla completa con Bio-HUD, IA y Campos de Contexto.
     */
    public function up()
    {
        Schema::create('registros_emociones', function (Blueprint $table) {
            $table->id();

            // Relación con el operador
            $table->foreignId('user_id')->constrained('usuarios')->onDelete('cascade');

            // Datos primarios
            $table->string('emocion');
            $table->integer('energia');

            // Campos de Análisis Neural (IA)
            $table->decimal('nivel_estres_estimado', 5, 2)->nullable();
            $table->text('recomendacion')->nullable();

            // Campos de Contexto y Observaciones (Los que faltaban)
            $table->text('observaciones')->nullable();
            $table->string('contexto')->nullable();
            $table->boolean('alerta_burnout')->default(false);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('registros_emociones');
    }
};

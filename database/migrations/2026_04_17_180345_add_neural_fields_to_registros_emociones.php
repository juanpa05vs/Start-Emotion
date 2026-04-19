<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('registros_emociones', function (Blueprint $table) {
            // Nivel 2: NLP - Almacena la bitácora de pensamientos del usuario
            $table->text('observaciones')->nullable()->after('recomendacion');

            // Nivel 3: Correlación - Guarda el contexto (ej. TESVB, Prácticas en Amado Nervo)
            $table->string('contexto')->nullable()->after('observaciones');

            // Nivel 1: Predicción - Indicador binario de riesgo de Burnout
            $table->boolean('alerta_burnout')->default(false)->after('contexto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('registros_emociones', function (Blueprint $table) {
            $table->dropColumn(['observaciones', 'contexto', 'alerta_burnout']);
        });
    }
};

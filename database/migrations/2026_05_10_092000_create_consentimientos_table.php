<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consentimiento explícito del estudiante.
 *
 * Regla de oro del módulo: sin una fila con `revocado_en = NULL`, el
 * psicólogo no ve NADA de ese estudiante. Ni un registro, ni una evaluación,
 * ni una partida del juego. No hay "vista previa" ni "datos agregados": o hay
 * consentimiento vigente, o la pantalla queda vacía.
 *
 * `alcance` guarda qué eligió compartir el estudiante (registros / evaluaciones
 * / juego). Guardarlo por separado del "sí/no" permite que revise sus
 * registros diarios sin ceder la evaluación psicométrica completa.
 *
 * NO hay restricción de unicidad sobre (estudiante_id, profesional_id): las
 * revocaciones se conservan como historial en lugar de sobrescribirse, porque
 * saber cuándo se otorgó y cuándo se retiró el acceso es exactamente el
 * registro que un comité de ética va a pedir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consentimientos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('estudiante_id')
                ->constrained('usuarios')
                ->cascadeOnDelete();

            $table->foreignId('profesional_id')
                ->constrained('usuarios')
                ->cascadeOnDelete();

            $table->json('alcance');

            $table->timestamp('otorgado_en');
            $table->timestamp('revocado_en')->nullable();
            $table->text('motivo_revocacion')->nullable();

            $table->timestamps();

            // La consulta caliente es siempre "consentimientos vigentes de este
            // profesional", así que el índice va en ese orden.
            $table->index(['profesional_id', 'revocado_en']);
            $table->index(['estudiante_id', 'revocado_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimientos');
    }
};

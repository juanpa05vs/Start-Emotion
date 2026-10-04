<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invitaciones de conexión.
 *
 * El estudiante nunca ve un listado de psicólogos (ni el psicólogo ve un
 * listado de estudiantes): el profesional genera un código y se lo entrega en
 * mano. Eso hace el consentimiento explícito, voluntario y sin exponer el
 * directorio de profesionales a toda la comunidad educativa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitaciones_psicologo', function (Blueprint $table) {
            $table->id();

            $table->foreignId('profesional_id')
                ->constrained('usuarios')
                ->cascadeOnDelete();

            // Código corto, legible en voz alta y fácil de teclear.
            $table->string('codigo', 16)->unique();

            $table->timestamp('expira_en');
            $table->timestamp('usada_en')->nullable();
            $table->foreignId('usada_por')->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['profesional_id', 'expira_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitaciones_psicologo');
    }
};

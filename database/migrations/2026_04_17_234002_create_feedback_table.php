<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * [INGENIERÍA]: Estructuramos la persistencia para el sistema de feedback.
     */
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();

            // [VÍNCULO NEURAL]: Relación con la tabla de usuarios
            // Usamos 'usuarios' porque es el nombre de tu tabla principal.
            $table->foreignId('user_id')
                  ->constrained('usuarios')
                  ->onDelete('cascade'); // Si se borra el usuario, se borra su feedback.

            // [CONTENIDO]: El cuerpo del reporte o sugerencia
            $table->text('comentario');

            // [ESTADO]: Para que el Admin sepa si ya lo leyó o atendió
            $table->string('estado')->default('pendiente'); // pendiente, revisado, implementado

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};

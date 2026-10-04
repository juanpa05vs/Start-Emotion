<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            // IMPORTANTE: el índice único de 'email' debe eliminarse ANTES de la columna.
            // Si se hace al revés, queda un índice huérfano que referencie una columna
            // inexistente y rompe la migración en SQLite con:
            // "error in index usuarios_email_unique after drop column: no such column"
            $table->dropUnique(['email']);

            $table->dropColumn('email');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            // Se restaura la columna CON su unicidad original.
            $table->string('email')->nullable()->unique();
        });
    }
};

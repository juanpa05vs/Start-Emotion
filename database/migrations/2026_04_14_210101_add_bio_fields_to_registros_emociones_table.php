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
        // Añadimos las columnas para el Bio-HUD y la IA
        $table->decimal('nivel_estres_estimado', 5, 2)->nullable()->after('energia'); //
        $table->text('recomendacion')->nullable()->after('nivel_estres_estimado'); //
    });
}

public function down()
{
    Schema::table('registros_emociones', function (Blueprint $table) {
        $table->dropColumn(['nivel_estres_estimado', 'recomendacion']);
    });
}
};

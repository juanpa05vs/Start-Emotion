<?php

namespace Tests\Feature;

use App\Enums\EmocionEnum;
use App\Models\TelemetriaGameplay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Panel de Validación Científica: matriz de confusión y métricas.
 *
 * El defecto que estos tests fijan: la matriz se construía sobre un vocabulario de
 * 9 clases, pero el Random Forest solo puede emitir 5. Dos de sus salidas
 * (alegria, neutralidad) no tenían columna, así que esas muestras se descartaban
 * en silencio y el panel mostraba ceros. Una matriz de confusión exige que ambos
 * ejes compartan espacio de etiquetas.
 */
class ValidacionCientificaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('Administrador', 'web');
        $user = User::factory()->create(['rol' => 'Administrador']);
        $user->assignRole('Administrador');

        return $user;
    }

    private function telemetria(User $user, string $objetivo, string $predicha): void
    {
        $t = new TelemetriaGameplay;
        $t->minijuego_id = 'codigo_anomalo';
        $t->latencia_promedio_ms = 1200;
        $t->frecuencia_tapping = 0.8;
        $t->tiempo_total_ms = 45000;
        $t->conteo_rectificaciones = 2;
        $t->errores_diagnostico = 1;
        $t->score_final = 75;
        $t->emocion_objetivo = $objetivo;
        $t->emocion_predicha = $predicha;
        $t->diagnostico_correcto = ($objetivo === $predicha);
        $t->vector_caracteristicas = [1200, 0.8, 45000, 2, 75];
        $t->user_id = $user->id;
        $t->save();
    }

    public function test_el_vocabulario_de_la_matriz_es_el_mismo_que_emite_el_modelo(): void
    {
        $this->assertSame(
            EmocionEnum::rf(),
            EmocionEnum::validacion(),
            'La matriz debe construirse sobre las clases que el RF puede emitir.'
        );
    }

    public function test_toda_emocion_del_juego_colapsa_a_una_clase_de_la_matriz(): void
    {
        foreach (EmocionEnum::juego() as $emocion) {
            $this->assertContains(
                EmocionEnum::normalizarParaRF($emocion),
                EmocionEnum::validacion(),
                "La emoción del juego '{$emocion}' debe tener columna en la matriz."
            );
        }
    }

    /**
     * Regresión principal: con una fila por cada clase que el RF puede emitir,
     * las 5 muestras deben entrar en la matriz. Antes entraban 0.
     */
    public function test_ninguna_prediccion_posible_se_descarta(): void
    {
        $admin = $this->admin();

        foreach (EmocionEnum::rf() as $rf) {
            $this->telemetria($admin, 'ansiedad', $rf);
        }

        $this->actingAs($admin)
            ->get('/admin/validacion-cientifica')
            ->assertOk()
            ->assertViewHas('totalMuestras', 5)
            ->assertViewHas('muestrasIncluidasMatriz', 5)
            ->assertViewHas('muestrasDescartadas', 0);
    }

    public function test_las_ocho_emociones_del_juego_entran_en_la_matriz(): void
    {
        $admin = $this->admin();

        foreach (EmocionEnum::juego() as $emocion) {
            $this->telemetria($admin, $emocion, 'ansiedad');
        }

        $this->actingAs($admin)
            ->get('/admin/validacion-cientifica')
            ->assertOk()
            ->assertViewHas('totalMuestras', 8)
            ->assertViewHas('muestrasIncluidasMatriz', 8);
    }

    public function test_el_accuracy_usa_el_mismo_denominador_que_la_matriz(): void
    {
        $admin = $this->admin();

        // 2 aciertos de 4 muestras.
        $this->telemetria($admin, 'ansiedad', 'ansiedad');
        $this->telemetria($admin, 'ansiedad', 'ansiedad');
        $this->telemetria($admin, 'tristeza', 'ansiedad');
        $this->telemetria($admin, 'ansiedad', 'frustracion');

        $response = $this->actingAs($admin)->get('/admin/validacion-cientifica');

        $response->assertOk();
        $this->assertSame(4, $response->viewData('muestrasIncluidasMatriz'));
        $this->assertSame(2, $response->viewData('aciertosTotales'));
        $this->assertSame(50.0, $response->viewData('accuracy'));
    }

    public function test_una_etiqueta_desconocida_se_cuenta_como_descartada(): void
    {
        $admin = $this->admin();

        $this->telemetria($admin, 'ansiedad', 'ansiedad');
        $this->telemetria($admin, 'planeta_rojo', 'ansiedad');

        $response = $this->actingAs($admin)->get('/admin/validacion-cientifica');

        $response->assertOk();
        $this->assertSame(2, $response->viewData('totalMuestras'));
        $this->assertSame(1, $response->viewData('muestrasIncluidasMatriz'));
        $this->assertSame(1, $response->viewData('muestrasDescartadas'));
    }

    public function test_el_panel_explica_cuando_no_hay_muestras(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/admin/validacion-cientifica')
            ->assertOk()
            ->assertViewHas('totalMuestras', 0)
            ->assertSee('No hay ninguna muestra todav')
            ->assertDontSee('0.00%');
    }

    public function test_el_panel_expone_el_mapa_de_colapso_para_transparencia(): void
    {
        $admin = $this->admin();

        $mapa = $this->actingAs($admin)->get('/admin/validacion-cientifica')->viewData('mapaColapso');

        $this->assertCount(8, $mapa);
        $this->assertSame('ansiedad', $mapa['ansiedad']);
        $this->assertSame('alegria', $mapa['euforia']);
    }

    public function test_el_csv_exporta_el_dataset_de_telemetria(): void
    {
        $admin = $this->admin();
        $this->telemetria($admin, 'ansiedad', 'ansiedad');

        $res = $this->actingAs($admin)->get('/admin/validacion-cientifica/exportar');

        $res->assertOk();
        $res->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}

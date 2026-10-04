<?php

namespace Tests\Feature;

use App\Enums\EmocionEnum;
use App\Models\TelemetriaGameplay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Pipeline de telemetría del minijuego "Código Anómalo".
 *
 * Estos tests reproducen los payloads EXACTOS que construye diagnostico.blade.php.
 * El endpoint alimenta la matriz de validación, y tres caminos devolvían 422 en
 * silencio: la muestra se perdía y el panel aparecía vacío sin explicación.
 */
class DiagnosticoTelemetryTest extends TestCase
{
    use RefreshDatabase;

    /** Payload tal cual lo arma enviarTelemetriaBackend() en el juego. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'minijuego_id' => 'codigo_anomalo',
            'latencia_promedio_ms' => 1450,
            'frecuencia_tapping' => 0.5,
            'tiempo_total_ms' => 60000,
            'conteo_rectificaciones' => 2,
            'errores_diagnostico' => 1,
            'score_final' => 80,
            'emocion_objetivo' => 'ansiedad',
            'emocion_predicha' => 'ansiedad',
            'diagnostico_correcto' => true,
            'vector_caracteristicas' => [1450, 0.5, 60000, 2, 80],
        ], $overrides);
    }

    public function test_la_ruta_de_telemetria_existe_y_lleva_throttle(): void
    {
        $route = Route::getRoutes()->getByName('minijuegos.telemetria');

        $this->assertNotNull($route);
        $this->assertSame('terminal/minijuegos/telemetria', $route->uri());
        $this->assertContains('throttle:60,1', $route->gatherMiddleware());
    }

    public function test_partida_ganada_persiste_la_telemetria(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user)
            ->postJson('/terminal/minijuegos/telemetria', $this->payload())
            ->assertCreated();

        $this->assertSame(1, TelemetriaGameplay::count());
        $this->assertSame($user->id, TelemetriaGameplay::first()->user_id);
        $this->assertArrayHasKey('ia_output', $res->json());
    }

    /**
     * Regresión: al agotarse el reloj sin elegir tarjeta el JS envía 'ninguno',
     * que no estaba en la whitelist y provocaba 422. Partida válida, no payload corrupto.
     */
    public function test_timeout_sin_seleccion_persiste_la_muestra(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/terminal/minijuegos/telemetria', $this->payload([
            'emocion_predicha' => 'ninguno',
            'diagnostico_correcto' => false,
            'errores_diagnostico' => 0,
        ]))->assertCreated();

        $this->assertSame(1, TelemetriaGameplay::count());
    }

    public function test_derrota_por_puntuacion_cero_persiste_la_muestra(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/terminal/minijuegos/telemetria', $this->payload([
            'emocion_predicha' => 'ira',
            'diagnostico_correcto' => false,
            'errores_diagnostico' => 5,
            'score_final' => 0,
            'vector_caracteristicas' => [1450, 0.5, 60000, 3, 0],
        ]))->assertCreated();

        $this->assertSame(1, TelemetriaGameplay::count());
    }

    /**
     * Regresión: 'latencia_promedio_ms' mide el intervalo entre clics, así que incluye
     * el tiempo de reflexión. El cap anterior (10s) rechazaba al usuario lento.
     */
    public function test_un_usuario_que_piensa_largo_no_pierde_la_muestra(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/terminal/minijuegos/telemetria', $this->payload([
            'latencia_promedio_ms' => 45000,
            'vector_caracteristicas' => [45000, 0.5, 60000, 2, 80],
        ]))->assertCreated();

        $this->assertSame(1, TelemetriaGameplay::count());
    }

    public function test_la_cadencia_alta_no_pierde_la_muestra(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/terminal/minijuegos/telemetria', $this->payload([
            'frecuencia_tapping' => 12.5,
            'vector_caracteristicas' => [1450, 12.5, 60000, 2, 80],
        ]))->assertCreated();

        $this->assertSame(1, TelemetriaGameplay::count());
    }

    public function test_rechaza_un_payload_que_no_puede_producir_el_juego(): void
    {
        $user = User::factory()->create();

        // 'trabajo' no es una emoción del juego ni del RF: sí debe rechazarse.
        $this->actingAs($user)->postJson('/terminal/minijuegos/telemetria', $this->payload([
            'emocion_objetivo' => 'trabajo',
        ]))->assertStatus(422)->assertJsonValidationErrors('emocion_objetivo');

        $this->assertSame(0, TelemetriaGameplay::count());
    }

    public function test_rechaza_un_vector_de_caracteristicas_mal_formado(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/terminal/minijuegos/telemetria', $this->payload([
            'vector_caracteristicas' => [1, 2, 3],
        ]))->assertStatus(422)->assertJsonValidationErrors('vector_caracteristicas');
    }

    public function test_la_columna_predicha_guarda_el_veredicto_del_modelo_no_el_del_usuario(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/terminal/minijuegos/telemetria', $this->payload([
            'emocion_objetivo' => 'ansiedad',
            'emocion_predicha' => 'ira', // el usuario eligió Ira
        ]))->assertCreated();

        $registro = TelemetriaGameplay::first();

        // La matriz de confusión valida el MODELO, así que la columna predicha es la
        // salida del RF. La elección del operador se guarda aparte, en el vector.
        $this->assertContains($registro->emocion_predicha, EmocionEnum::rf());
        $this->assertSame('ira', $registro->vector_caracteristicas['seleccion_operador']);
    }

    public function test_sin_autenticacion_no_acepta_telemetria(): void
    {
        $this->postJson('/terminal/minijuegos/telemetria', $this->payload())->assertUnauthorized();

        $this->assertSame(0, TelemetriaGameplay::count());
    }
}

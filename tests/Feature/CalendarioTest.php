<?php

namespace Tests\Feature;

use App\Models\RegistroEmocion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guarda de regresión del Monitor Longitudinal.
 *
 * El calendario se rompió una vez porque la vista iteraba sobre $rangoDias y el
 * controlador dejó de pasarla. Estos tests fijan el contrato entre ambos.
 */
class CalendarioTest extends TestCase
{
    use RefreshDatabase;

    private function crearRegistro(User $user, string $fecha, int $energia): RegistroEmocion
    {
        $registro = new RegistroEmocion;
        $registro->emocion = 'productivo';
        $registro->energia = $energia;
        $registro->nivel_estres_estimado = 20;
        $registro->recomendacion = 'Sin observaciones.';
        $registro->user_id = $user->id;
        $registro->created_at = $fecha;
        $registro->updated_at = $fecha;
        $registro->save();

        return $registro;
    }

    public function test_la_vista_recibe_rango_dias_y_datos_heatmap(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/perfil/calendario')
            ->assertOk()
            ->assertViewHas('rangoDias')
            ->assertViewHas('datosHeatmap');
    }

    public function test_rango_dias_cubre_todo_el_mes_alineado_a_lunes(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/perfil/calendario');
        $rangoDias = $response->viewData('rangoDias');

        $inicio = now()->startOfMonth();
        $fin = now()->endOfMonth();

        // Celdas de relleno (null) + días reales del mes.
        $relleno = count(array_filter($rangoDias, fn ($d) => $d === null));
        $reales = count(array_filter($rangoDias, fn ($d) => $d !== null));

        $this->assertSame($inicio->dayOfWeekIso - 1, $relleno, 'El día 1 debe caer bajo su columna real.');
        $this->assertSame($inicio->daysInMonth, $reales);

        // El primer día real de la rejilla es el día 1 del mes.
        $primero = array_values(array_filter($rangoDias, fn ($d) => $d !== null))[0];
        $this->assertSame($inicio->toDateString(), $primero->toDateString());
    }

    public function test_el_heatmap_agrupa_por_dia_con_promedio_de_energia(): void
    {
        $user = User::factory()->create();
        $hoy = now()->toDateString();

        $this->crearRegistro($user, $hoy.' 09:00:00', 40);
        $this->crearRegistro($user, $hoy.' 18:00:00', 80);

        $heatmap = $this->actingAs($user)
            ->get('/perfil/calendario')
            ->viewData('datosHeatmap');

        $this->assertArrayHasKey($hoy, $heatmap);
        $this->assertEqualsWithDelta(60, $heatmap[$hoy], 0.01);
    }

    public function test_un_usuario_no_ve_el_calendario_de_otro(): void
    {
        $user = User::factory()->create();
        $otro = User::factory()->create();

        $this->crearRegistro($otro, now()->toDateString().' 10:00:00', 100);

        $heatmap = $this->actingAs($user)
            ->get('/perfil/calendario')
            ->viewData('datosHeatmap');

        $this->assertCount(0, $heatmap, 'El heatmap debe estar acotado al usuario autenticado.');
    }
}

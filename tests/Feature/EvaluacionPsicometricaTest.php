<?php

namespace Tests\Feature;

use App\Models\EvaluacionPsicometrica;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guarda de regresión del inventario SISCO.
 *
 * Cuando 'user_id' se sacó de $fillable, este controller seguía pasándolo por
 * create(); Eloquent lo descartaba en silencio y MySQL reventaba con
 * "Field 'user_id' doesn't have a default value". Estos tests fijan la FK.
 */
class EvaluacionPsicometricaTest extends TestCase
{
    use RefreshDatabase;

    /** Respuestas en el mínimo de la escala Likert (todo = 1). */
    private function respuestasMinimas(): array
    {
        return [
            'e_sobrecarga' => 1, 'e_evaluaciones' => 1, 'e_tiempo' => 1, 'e_profesores' => 1,
            's_fatiga' => 1, 's_ansiedad' => 1, 's_concentracion' => 1, 's_frustracion' => 1,
            'a_resolucion' => 1, 'a_comunicacion' => 1,
        ];
    }

    public function test_guarda_la_evaluacion_con_la_fk_del_usuario_autenticado(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/evaluacion-psicometrica', $this->respuestasMinimas())
            ->assertRedirect('/dashboard');

        $evaluacion = EvaluacionPsicometrica::first();

        $this->assertNotNull($evaluacion, 'La evaluación debe persistirse.');
        $this->assertSame($user->id, $evaluacion->user_id);
        $this->assertSame('SISCO_ESTRES', $evaluacion->instrumento);
    }

    public function test_la_evaluacion_queda_acotada_al_usuario_autenticado(): void
    {
        $user = User::factory()->create();
        $otro = User::factory()->create();

        $this->actingAs($user)
            ->post('/evaluacion-psicometrica', $this->respuestasMinimas())
            ->assertRedirect('/dashboard');

        $this->assertSame(1, $user->evaluacionesPsicometricas()->count());
        $this->assertSame(0, $otro->evaluacionesPsicometricas()->count());
    }

    public function test_el_minimo_de_la_escala_produce_estres_bajo_y_buen_afrontamiento(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/evaluacion-psicometrica', $this->respuestasMinimas());

        $evaluacion = EvaluacionPsicometrica::first();

        // Todo en 1 => subescalas normalizadas a 0.
        $this->assertSame(0, $evaluacion->puntaje_estresores);
        $this->assertSame(0, $evaluacion->puntaje_sintomas);
        $this->assertSame(0, $evaluacion->puntaje_afrontamiento);
        $this->assertSame('bajo', $evaluacion->nivel_estres);
    }

    public function test_el_maximo_de_la_escala_produce_estres_severo(): void
    {
        $user = User::factory()->create();

        $respuestas = array_map(fn () => 5, $this->respuestasMinimas());
        $respuestas['a_resolucion'] = 1;
        $respuestas['a_comunicacion'] = 1;

        $this->actingAs($user)->post('/evaluacion-psicometrica', $respuestas);

        $evaluacion = EvaluacionPsicometrica::first();

        $this->assertSame(100, $evaluacion->puntaje_estresores);
        $this->assertSame(100, $evaluacion->puntaje_sintomas);
        $this->assertSame('severo', $evaluacion->nivel_estres);
    }

    public function test_rechaza_un_reactivo_fuera_de_la_escala_likert(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/evaluacion-psicometrica', array_merge($this->respuestasMinimas(), ['s_ansiedad' => 9]))
            ->assertSessionHasErrors('s_ansiedad');
    }

    public function test_exige_los_diez_reactivos_del_inventario(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/evaluacion-psicometrica', ['e_sobrecarga' => 3])
            ->assertSessionHasErrors([
                'e_evaluaciones', 'e_tiempo', 'e_profesores',
                's_fatiga', 's_ansiedad', 's_concentracion', 's_frustracion',
                'a_resolucion', 'a_comunicacion',
            ]);
    }

    public function test_el_ultimo_registro_se_muestra_al_usuario_correcto(): void
    {
        $user = User::factory()->create();
        $otro = User::factory()->create();

        $this->actingAs($otro)
            ->post('/evaluacion-psicometrica', array_merge($this->respuestasMinimas(), ['e_tiempo' => 5]));

        $vista = $this->actingAs($user)->get('/evaluacion-psicometrica');

        $vista->assertOk();
        $this->assertNull(
            $vista->viewData('ultimaEvaluacion'),
            'No debe mostrarse la evaluación de otro usuario.'
        );
    }

    /**
     * Regresión del resumen de «tu última encuesta».
     *
     * Ese bloque solo se dibuja cuando el estudiante ya respondió alguna vez, así
     * que ninguna prueba anterior lo alcanzaba: la pantalla reabierta devolvía 500
     * con «Too few arguments to function EmocionEnum::etiqueta()». El resumen se
     * leía bien la primera vez y reventaba la segunda, que es justo el camino que
     * recorre quien vuelve a la encuesta.
     */
    public function test_la_encuesta_reabierta_muestra_el_resumen_sin_fallar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/evaluacion-psicometrica', array_merge(
            $this->respuestasMinimas(),
            ['s_ansiedad' => 5, 'e_tiempo' => 5]
        ))->assertRedirect('/dashboard');

        $this->actingAs($user)
            ->get('/evaluacion-psicometrica')
            ->assertOk()
            ->assertSee('Tu última encuesta')
            ->assertSee('Lo que más pesó')
            // Traducido: la columna guarda «ansiedad», que es una clave.
            ->assertSee('Ansiedad');
    }

    public function test_el_resumen_traduce_las_cinco_emociones_posibles(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/evaluacion-psicometrica', $this->respuestasMinimas());

        // Una sola evaluación que se va cambiando: si se creara una por valor, dos
        // filas caerían en el mismo segundo y 'latest()' dejaría de ser fiable.
        $evaluacion = EvaluacionPsicometrica::latest('id')->first();

        $etiquetas = [
            'ansiedad' => 'Ansiedad',
            'frustracion' => 'Frustración',
            'alegria' => 'Alegría',
            'tristeza' => 'Tristeza',
            'neutralidad' => 'Neutralidad',
        ];

        foreach ($etiquetas as $clave => $palabra) {
            $evaluacion->update(['estado_afectivo_predominante' => $clave]);

            $this->assertSame($palabra, $evaluacion->fresh()->estadoAfectivoEtiqueta());

            $this->actingAs($user)
                ->get('/evaluacion-psicometrica')
                ->assertOk()
                ->assertSee($palabra);
        }
    }

    public function test_sin_emocion_registrada_no_se_inventa_una_etiqueta(): void
    {
        $evaluacion = new EvaluacionPsicometrica;

        // La columna es NOT NULL, pero la vista no debe depender de eso: si algún
        // día llega vacía, cada pantalla decide qué pone en el hueco.
        $evaluacion->estado_afectivo_predominante = '';
        $this->assertNull($evaluacion->estadoAfectivoEtiqueta());

        $evaluacion->estado_afectivo_predominante = null;
        $this->assertNull($evaluacion->estadoAfectivoEtiqueta());
    }
}

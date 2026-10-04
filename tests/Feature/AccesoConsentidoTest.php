<?php

namespace Tests\Feature;

use App\Enums\AlcanceConsentimiento;
use App\Models\Consentimiento;
use App\Models\EvaluacionPsicometrica;
use App\Models\TelemetriaGameplay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Comprueba la regla que sostiene todo el módulo: el profesional ve
 * exactamente lo que el estudiante autorizó, ni una fila más.
 *
 * Estos tests son la diferencia entre una herramienta de apoyo y un acceso
 * indiscriminado a datos de salud mental. Si alguno falla, hay una fuga.
 */
class AccesoConsentidoTest extends TestCase
{
    use RefreshDatabase;

    private function psicologo(): User
    {
        return User::factory()->psicologo()->create(['nombre' => 'Dra. Ana Ruiz']);
    }

    private function estudianteConDatos(User $profesional, array $alcance): array
    {
        $estudiante = User::factory()->create(['rol' => 'estudiante', 'nombre' => 'Luis Pérez']);

        // Relación y no create(): `user_id` está fuera de $fillable a propósito,
        // así que asignarlo en masa lo descartaría en silencio.
        $estudiante->emociones()->create([
            'emocion' => 'ansiedad',
            // Va de 1 a 100, igual que la barra del registro. Aquí ponía 3, que
            // era de cuando el máximo era 10: con un valor así, cualquier prueba
            // que mirara la energía de esta ficha pasaría con un dato que ya no
            // se puede guardar.
            'energia' => 86,
            'nivel_estres_estimado' => 78,
            'contexto' => 'Exámenes',
            'observaciones' => 'No he podido dormir en tres días por los exámenes finales.',
        ]);

        $e = new EvaluacionPsicometrica;
        $e->user_id = $estudiante->id;
        $e->instrumento = 'SISCO_ESTRES';
        $e->puntaje_estresores = 14;
        $e->puntaje_sintomas = 9;
        $e->puntaje_afrontamiento = 18;
        $e->puntaje_global = 33;
        $e->nivel_estres = 'severo';
        $e->estado_afectivo_predominante = 'ansiedad';
        $e->respuestas_detalle = ['indice_total' => 33];
        $e->save();

        $t = new TelemetriaGameplay;
        $t->user_id = $estudiante->id;
        $t->minijuego_id = 'codigo_anomalo';
        $t->latencia_promedio_ms = 2400;
        $t->frecuencia_tapping = 4;
        $t->tiempo_total_ms = 60000;
        $t->conteo_rectificaciones = 1;
        $t->errores_diagnostico = 0;
        $t->score_final = 320;
        $t->emocion_objetivo = 'ansiedad';
        $t->emocion_predicha = 'ansiedad';
        $t->diagnostico_correcto = true;
        $t->vector_caracteristicas = [];
        $t->save();

        $c = new Consentimiento;
        $c->alcance = array_map(fn ($x) => $x->value, $alcance);
        $c->estudiante_id = $estudiante->id;
        $c->profesional_id = $profesional->id;
        $c->otorgado_en = now();
        $c->save();

        return [$estudiante, $c];
    }

    public function test_sin_consentimiento_el_profesional_no_ve_nada(): void
    {
        $profesional = $this->psicologo();

        // El estudiante existe y tiene datos, pero nunca autorizó nada.
        $estudiante = User::factory()->create(['rol' => 'estudiante', 'nombre' => 'Nadie Autorizó']);
        $estudiante->emociones()->create([
            'emocion' => 'tristeza', 'energia' => 2, 'estres' => 9, 'contexto' => 'Exámenes',
        ]);

        // Se comprueba contra el nombre y contra el código real del
        // participante, no contra el prefijo antiguo `TESVB-SIST-`: ese
        // prefijo ya no existe en el sistema, así que la comprobación
        // pasaría siempre y no probaría nada.
        $html = $this->actingAs($profesional)
            ->get('/psicologia/pacientes')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Nadie Autorizó', $html);
        $this->assertStringNotContainsString($estudiante->codigo_anonimo, $html);

        $this->assertSame(0, Consentimiento::vigentes()->deProfesional($profesional->id)->count());
    }

    public function test_con_consentimiento_parcial_solo_se_ve_lo_autorizado(): void
    {
        $profesional = $this->psicologo();
        [$estudiante, $c] = $this->estudianteConDatos($profesional, [AlcanceConsentimiento::Registros]);

        $this->assertTrue($c->incluye(AlcanceConsentimiento::Registros));
        $this->assertFalse($c->incluye(AlcanceConsentimiento::Evaluaciones));
        $this->assertFalse($c->incluye(AlcanceConsentimiento::Juego));

        $this->actingAs($profesional)
            ->get("/psicologia/pacientes/{$c->id}")
            ->assertOk()
            ->assertSee('Luis Pérez')
            // El registro emocional SÍ está autorizado.
            ->assertSee('ansiedad')
            // La evaluación NO: debe decirlo explícitamente.
            ->assertSee('No autorizó compartir sus evaluaciones');
    }

    public function test_autorizar_registros_no_arrastra_la_evaluacion(): void
    {
        $profesional = $this->psicologo();
        [$estudiante, $c] = $this->estudianteConDatos($profesional, [AlcanceConsentimiento::Registros]);

        $this->actingAs($profesional)
            ->get("/psicologia/pacientes/{$c->id}")
            ->assertOk()
            // La sección dice por qué está vacía, en vez de insinuar que no hay datos.
            ->assertSee('No autorizó compartir sus evaluaciones')
            // Y ningún valor de la evaluación se cuela en la página. El nivel se
            // muestra traducido («Severo»), no como lo guarda la columna.
            ->assertDontSee('Estrés Severo')
            ->assertDontSee('Afrontamiento')
            // El registro emocional, que sí está autorizado, sí aparece.
            ->assertSee('No he podido dormir en tres días');
    }

    /**
     * La energía se muestra como porcentaje, no sobre 10.
     *
     * La celda decía `{{ $r->energia }}/10`, de una versión anterior de la barra
     * en la que el máximo era 10. Con la barra actual, que va de 1 a 100, la
     * ficha del psicólogo leía «86/10».
     *
     * Es el único sitio donde la energía se muestra en una vista que no es la
     * propia, y por eso era el único que nadie miraba: el registro, el historial,
     * el informe y el panel ya ponían el porcentaje.
     */
    public function test_la_energia_de_la_ficha_no_se_divide_entre_diez(): void
    {
        $profesional = $this->psicologo();
        // La ruta va por el consentimiento, no por el estudiante.
        [, $c] = $this->estudianteConDatos($profesional, AlcanceConsentimiento::cases());

        $respuesta = $this->actingAs($profesional)
            ->get("/psicologia/pacientes/{$c->id}")
            ->assertOk();

        // El `%` va en un `<span>` aparte, para poder atenuarlo, así que el texto
        // que se lee no aparece entero en el HTML: hay que quitar las etiquetas
        // antes de mirar. Es lo mismo que vería quien lee la ficha.
        $texto = trim(preg_replace('/\s+/', ' ', strip_tags($respuesta->getContent())) ?? '');

        $this->assertMatchesRegularExpression(
            '/\b86\s*%/',
            $texto,
            'La energía de la ficha no se muestra como porcentaje.'
        );

        // El patrón excluye lo que viene detrás de un «/10», y con eso no se
        // confunde con las fechas, que en esta ficha salen como `04/10/2026`:
        // un divisor de diez detrás de una cifra de dos o tres dígitos, y sin
        // otra cifra detrás.
        $this->assertDoesNotMatchRegularExpression(
            '#\d{2,3}\s*/\s*10(?![\d/])#',
            $texto,
            'La energía de la ficha se sigue dividiendo entre diez.'
        );
    }

    public function test_todo_autorizado_muestra_las_tres_secciones(): void
    {
        $profesional = $this->psicologo();
        [$estudiante, $c] = $this->estudianteConDatos($profesional, AlcanceConsentimiento::cases());

        $this->actingAs($profesional)
            ->get("/psicologia/pacientes/{$c->id}")
            ->assertOk()
            ->assertSee('Registros diarios')
            ->assertSee('Evaluaciones psicológicas')
            ->assertSee('Actividad en el juego')
            ->assertSee('Estrés Severo')
            // La emoción predominante también se muestra traducida («Ansiedad»),
            // no con la clave que guarda la columna.
            ->assertSee('Ansiedad');
    }

    public function test_re_autorizar_reaparece_en_la_lista(): void
    {
        $profesional = $this->psicologo();
        [$estudiante, $c] = $this->estudianteConDatos($profesional, [AlcanceConsentimiento::Registros]);

        $this->actingAs($profesional)->get('/psicologia/pacientes')->assertOk()->assertSee('Luis Pérez');

        $c->revocar();

        $this->actingAs($profesional)
            ->get('/psicologia/pacientes')
            ->assertOk()
            ->assertDontSee('Luis Pérez');
    }

    public function test_la_ficha_de_un_consentimiento_revocado_da_403(): void
    {
        $profesional = $this->psicologo();
        [$estudiante, $c] = $this->estudianteConDatos($profesional, [AlcanceConsentimiento::Registros]);
        $c->revocar();

        // Conservar el enlace antiguo en el navegador no debe servir de nada.
        $this->actingAs($profesional)
            ->get("/psicologia/pacientes/{$c->id}")
            ->assertForbidden();
    }

    public function test_un_profesional_no_abre_la_ficha_de_otro(): void
    {
        $profesionalA = $this->psicologo();
        $profesionalB = $this->psicologo();
        [$estudiante, $c] = $this->estudianteConDatos($profesionalA, [AlcanceConsentimiento::Registros]);

        $this->actingAs($profesionalB)
            ->get("/psicologia/pacientes/{$c->id}")
            ->assertForbidden()
            ->assertDontSee('Luis Pérez');
    }

    /**
     * Ser administrador NO da acceso al contenido de salud mental.
     *
     * Antes esta prueba pedía un 200 con la lista vacía, porque el
     * administrador entraba al espacio clínico y simplemente no veía a nadie
     * sin consentimiento. Ahora ni siquiera entra: la respuesta es 403.
     *
     * La diferencia importa. Con un 200, proteger el dato dependía de que
     * cada consulta del espacio clínico filtrara por consentimiento, y bastaba
     * una consulta nueva que se olvidara del filtro para abrir una fuga. Con un
     * 403 la barrera es el rol, y no hay forma de saltársela por descuido.
     */
    public function test_el_administrador_no_entra_al_espacio_clinico(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $admin->assignRole(Role::findOrCreate('Administrador', 'web'));

        $estudiante = User::factory()->create(['rol' => 'estudiante', 'nombre' => 'Ana Solís']);
        $estudiante->emociones()->create([
            'emocion' => 'frustracion', 'energia' => 4, 'estres' => 7, 'contexto' => 'Proyecto',
        ]);

        $this->actingAs($admin)
            ->get('/psicologia/pacientes')
            ->assertForbidden()
            ->assertDontSee('Ana Solís');
    }

    public function test_el_estudiante_no_puede_entrar_al_espacio_del_profesional(): void
    {
        $estudiante = User::factory()->create(['rol' => 'estudiante']);

        $this->actingAs($estudiante)->get('/psicologia/pacientes')->assertForbidden();
        $this->actingAs($estudiante)->get('/psicologia/invitaciones')->assertForbidden();
    }

    public function test_la_lista_de_pacientes_no_filtra_consentimientos_de_otros_profesionales(): void
    {
        $profesionalA = $this->psicologo();
        $profesionalB = $this->psicologo();

        [$estudianteA, $cA] = $this->estudianteConDatos($profesionalA, [AlcanceConsentimiento::Registros]);

        $otro = User::factory()->create(['rol' => 'estudiante', 'nombre' => 'Carlos Díaz']);
        $cB = new Consentimiento;
        $cB->alcance = [AlcanceConsentimiento::Registros->value];
        $cB->estudiante_id = $otro->id;
        $cB->profesional_id = $profesionalB->id;
        $cB->otorgado_en = now();
        $cB->save();

        // A ve su paciente, no el de B.
        $this->actingAs($profesionalA)
            ->get('/psicologia/pacientes')
            ->assertOk()
            ->assertSee('Luis Pérez')
            ->assertDontSee('Carlos Díaz');
    }
}

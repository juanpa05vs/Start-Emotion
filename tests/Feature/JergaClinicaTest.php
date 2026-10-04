<?php

namespace Tests\Feature;

use App\Enums\AlcanceConsentimiento;
use App\Models\Consentimiento;
use App\Models\InvitacionPsicologo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * El comité de evaluación pidió dos cosas concretas sobre el lenguaje:
 * que el estudiante no tenga que descifrar terminología, y que quien
 * prescribe pueda confiar en la herramienta.
 *
 * Estas pruebas son mecánicas ybusters: no miden qué tan bien suena el texto,
 * sino que los términos que el comité marcó literalmente no vuelvan a aparecer
 * donde el usuario los lee.
 */
class JergaClinicaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Términos señalados por el área de psicología, con la razón por la que
     * estorban. La clave es la forma exacta que aparecía en las vistas.
     */
    private const PROHIBIDOS = [
        'Operadores' => 'en salud mental "operador" suena a vigilante, no a persona atendida',
        'Nivel Alpha' => 'jerga interna de sistema expuesto al usuario',
        'Sector Alpha' => 'jerga interna de sistema expuesto al usuario',
        'Alfa' => 'jerarquía interna de sistema que el usuario no debe ver',
        'purgar' => 'vocabulario de sistema, no de salud',
        'Sincronización Neural' => 'no significa nada para un paciente',
        'Diccionario Neural' => 'suena a marketing, no a evaluación',
        'Análisis Neural' => 'el análisis no es neuronal',
        'certidumbre matemática' => 'no significa nada para un paciente ni para un clínico',
        'ENSAMBLE DE BOSQUES ALEATORIOS' => 'jerga de manual de machine learning',
        'RANDOM_FOREST_ACTIVE' => 'un clínico no puede leer esto como estado del sistema',
        'Zona Recreativa' => 'etiqueta la actividad del juego como mero ocio',
        'Gold Standard' => 'el estudiante no sabe qué es y suena publicitario',
        'Terminal Sincronizada' => 'el mensaje de "perfil actualizado" debe decirlo en claro',
        'purgado' => 'vocabulario de sistema, no de salud',
        'BAREMACIÓN' => 'el estudiante no puede saber qué significa su propio resultado',
        'CALIBRACIÓN' => 'calibrar es jargon de laboratorio: aquí se responde una encuesta',
        'GUARDAR Y CALIBRAR' => 'el botón de enviar debe decir qué va a hacer',
        'reactivo' => 'vocabulario de manual de metodología, no de cuestionario',
        'GROUND TRUTH' => 'el estudiante no puede verificar una "verdad fundamental"',
    ];

    private function htmlDe(string $vista, array $datos = []): string
    {
        return view($vista, $datos)->render();
    }

    private function assertSinJerga(string $html, string $contexto): void
    {
        foreach (self::PROHIBIDOS as $termino => $motivo) {
            $this->assertStringNotContainsString(
                $termino,
                $html,
                "En {$contexto} aparece «{$termino}» ({$motivo})."
            );
        }
    }

    public function test_la_pantalla_de_registro_no_usa_jerga(): void
    {
        // Se pide por HTTP y no con `view()`: la vista extiende un layout que
        // espera el estado de sesión, que un render aislado no tiene.
        $html = $this->get('/registrar')->assertOk()->getContent();

        $this->assertSinJerga($html, 'el formulario de registro');
    }

    public function test_la_politica_de_privacidad_esta_en_lenguaje_llano(): void
    {
        $this->assertSinJerga($this->htmlDe('privacidad.politica'), 'la política de privacidad');
    }

    public function test_el_registro_de_un_estudiante_no_usa_jerga(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'estudiante']))
            ->get('/mi-privacidad')
            ->assertOk()
            ->assertDontSee('Operadores');
    }

    public function test_la_lista_de_pacientes_usa_lenguaje_clinico(): void
    {
        $profesional = User::factory()->create(['rol' => 'Psicólogo']);
        $profesional->assignRole(Role::findOrCreate('Psicólogo', 'web'));

        $this->actingAs($profesional)
            ->get('/psicologia/pacientes')
            ->assertOk()
            ->assertSee('Mis estudiantes')
            ->assertDontSee('Operadores');
    }

    public function test_el_menu_no_expone_jerga_de_sistema(): void
    {
        $html = $this->actingAs(User::factory()->create(['rol' => 'estudiante']))
            ->get('/dashboard')
            ->assertOk()
            ->getContent();

        $this->assertSinJerga($html, 'el menú lateral');
    }

    /**
     * El minijuego es la pantalla donde más jerga se había colado, y ninguna
     * prueba la revisaba: el rótulo del modelo en la cabecera y el resumen del
     * resultado seguían usando «RANDOM_FOREST_ACTIVE», «ensamble de bosques
     * aleatorios» y «certidumbre matemática». El texto lo escribe JavaScript
     * desde la propia vista, así que hay que pedir la página por HTTP y no
     * renderizar la plantilla suelta.
     */
    public function test_el_minijuego_no_anuncia_el_modelo_en_lenguaje_de_manual(): void
    {
        $html = $this->actingAs(User::factory()->create(['rol' => 'estudiante']))
            ->get('/terminal/minijuegos/diagnostico')
            ->assertOk()
            ->getContent();

        $this->assertSinJerga($html, 'la actividad Código Anómalo');

        // El resumen que aparece al enviar la muestra vive en el mismo guion,
        // así que el mismo HTML lo cubre.
        $this->assertStringNotContainsString('PREDICCIÓN IA', $html);
        $this->assertStringNotContainsString('CERTEZA', $html);
    }

    public function test_el_menu_ofrece_la_pantalla_de_privacidad(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'estudiante']))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Mis datos');
    }

    /**
     * La encuesta SISCO era la pantalla con más jerga y ninguna prueba la miraba.
     *
     * Se titulaba «Calibración de Estrés Académico» y llevaba dos etiquetas —
     * «GOLD STANDARD // INVENTARIO SISCO» y «BAREMACIÓN PSICOMÉTRICA»— que no
     * significan nada para quien contesta. El botón de enviar pedía «guardar y
     * calibrar base psicométrica», y el mensaje de confirmación que salía al
     * terminar repetía la misma palabra tres veces. La lista de términos
     * existed, pero ninguna prueba renderizaba esta vista.
     */
    public function test_la_encuesta_de_estres_se_presenta_en_lenguaje_llano(): void
    {
        $html = $this->actingAs(User::factory()->create(['rol' => 'estudiante']))
            ->get('/evaluacion-psicometrica')
            ->assertOk()
            ->getContent();

        $this->assertSinJerga($html, 'la encuesta de estrés');

        // Y los términos van en mayúsculas en la vista, así que la comprobación
        // literal anterior no los habría visto. Esta es la que de verdad protege.
        $this->assertStringNotContainsStringIgnoringCase('calibración', $html);
        $this->assertStringNotContainsStringIgnoringCase('baremación', $html);
        $this->assertStringNotContainsStringIgnoringCase('gold standard', $html);

        // El nombre del instrumento puede aparecer; es el que usa el centro.
        $this->assertStringContainsString('Encuesta de estrés', $html);
    }

    /**
     * El mensaje que aparece al enviar la encuesta es lo único que ve quien
     * acaba de contestar diez preguntas sobre su estado de ánimo. Celebrar el
     * guardado con «completada con éxito» no le dice nada, y darle la etiqueta
     * del baremo sin explicar qué significa tampoco.
     */
    public function test_el_mensaje_de_la_encuesta_explica_el_resultado(): void
    {
        $mensaje = $this->actingAs(User::factory()->create(['rol' => 'estudiante']))
            ->post('/evaluacion-psicometrica', $this->respuestasMinimas())
            ->assertRedirect('/dashboard')
            ->getSession()
            ->get('success');

        $this->assertIsString($mensaje);
        $this->assertStringContainsString('Respuesta guardada', $mensaje);
        $this->assertStringNotContainsString('éxito', $mensaje);
        $this->assertGreaterThan(40, mb_strlen($mensaje), 'El mensaje no explica el resultado.');
    }

    /** Todas las respuestas al mínimo de la escala (1 = nunca). */
    private function respuestasMinimas(): array
    {
        $reactivos = [
            'e_sobrecarga', 'e_evaluaciones', 'e_tiempo', 'e_profesores',
            's_fatiga', 's_ansiedad', 's_concentracion', 's_frustracion',
            'a_resolucion', 'a_comunicacion',
        ];

        return array_fill_keys($reactivos, 1);
    }

    public function test_el_profesional_ve_su_seccion_en_el_menu(): void
    {
        $profesional = User::factory()->create(['rol' => 'Psicólogo']);
        $profesional->assignRole(Role::findOrCreate('Psicólogo', 'web'));

        // El panel del estudiante es del estudiante: un profesional que solo tiene
        // ese rol ya no entra ahí, sino a su propio espacio de atención.
        $this->actingAs($profesional)
            ->get('/psicologia/pacientes')
            ->assertOk()
            ->assertSee('Mis estudiantes');
    }

    public function test_la_etiqueta_del_alcance_se_puede_leer_de_una_sentencia(): void
    {
        // Si estas frases necesitan un manual, el estudiante no las va a entender.
        foreach (AlcanceConsentimiento::cases() as $caso) {
            $this->assertNotSame('', $caso->etiqueta());
            $this->assertNotSame('', $caso->descripcion());

            // Sin claves internas tipo `registros` o snake_case en la interfaz.
            $this->assertDoesNotMatchRegularExpression(
                '/[a-z]+_[a-z]+/',
                $caso->etiqueta().' '.$caso->etiquetaCorta(),
                "La etiqueta de {$caso->value} muestra una clave interna al usuario."
            );
        }
    }

    public function test_el_estudiante_recibe_mensajes_en_lugar_de_codigos(): void
    {
        $profesional = User::factory()->create(['rol' => 'Psicólogo']);
        $profesional->assignRole(Role::findOrCreate('Psicólogo', 'web'));

        $inv = new InvitacionPsicologo;
        $inv->codigo = 'REALLY1';
        $inv->profesional_id = $profesional->id;
        $inv->expira_en = now()->addDay();
        $inv->save();

        $estudiante = User::factory()->create(['rol' => 'estudiante']);

        $this->actingAs($estudiante)
            ->post('/mi-privacidad/conectar', [
                'codigo' => 'REALLY1',
                'alcance' => [AlcanceConsentimiento::Registros->value],
            ])
            ->assertSessionHas('success');

        $mensaje = session('success');

        // El mensaje de éxito explica qué pasó, no escupe un identificador.
        $this->assertStringNotContainsString('SUCCESS', $mensaje);
        $this->assertStringNotContainsString('OK', $mensaje);
        $this->assertGreaterThan(30, mb_strlen($mensaje), 'El mensaje es demasiado escueto para el usuario.');
    }

    public function test_el_error_de_codigo_explica_que_hacer(): void
    {
        $estudiante = User::factory()->create(['rol' => 'estudiante']);

        $this->actingAs($estudiante)
            ->post('/mi-privacidad/conectar', [
                'codigo' => 'NOEXISTE',
                'alcance' => [AlcanceConsentimiento::Registros->value],
            ])
            ->assertSessionHasErrors('codigo');

        $error = session('errors')->first('codigo');

        // Un error que no dice qué hacer deja al usuario atascado.
        $this->assertStringContainsString('venció', $error);
        $this->assertStringContainsString('Pídele', $error);
    }

    public function test_revocar_el_consentimiento_explica_el_efecto(): void
    {
        $profesional = User::factory()->create(['rol' => 'Psicólogo', 'nombre' => 'Dra. Ana']);
        $profesional->assignRole(Role::findOrCreate('Psicólogo', 'web'));

        $estudiante = User::factory()->create(['rol' => 'estudiante']);

        $c = new Consentimiento;
        $c->alcance = [AlcanceConsentimiento::Registros->value];
        $c->estudiante_id = $estudiante->id;
        $c->profesional_id = $profesional->id;
        $c->otorgado_en = now();
        $c->save();

        $this->actingAs($estudiante)
            ->post("/mi-privacidad/consentimientos/{$c->id}/revocar")
            ->assertSessionHas('success');

        $mensaje = session('success');

        $this->assertStringContainsString('ya no puede ver', $mensaje);
    }
}

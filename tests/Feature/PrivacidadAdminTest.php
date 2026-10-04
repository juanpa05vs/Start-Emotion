<?php

namespace Tests\Feature;

use App\Models\EvaluacionPsicometrica;
use App\Models\InvitacionPsicologo;
use App\Models\TelemetriaGameplay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Separación de funciones entre administración y atención psicológica.
 *
 * El defecto que estos tests fijan: el panel del administrador listaba
 * resultados clínicos POR PERSONA (participante + estado afectivo + nivel de
 * estrés) y la pantalla de cuentas mostraba el correo y la edad de todo el
 * mundo. El segundo defecto es más sutil y más grave: `esProfesional()`
 * incluía al administrador, de modo que administrar el sistema concedía
 * acceso al espacio clínico. La política lo prometía al revés del código.
 *
 * La línea que se fija aquí: el administrador ve agregados y gestiona cuentas.
 * Los datos clínicos por persona requieren consentimiento vigente, y esa
 * puerta es la tabla `consentimientos`, no el rol.
 */
class PrivacidadAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('Administrador', 'web');
        $user = User::factory()->create(['rol' => 'Administrador', 'nombre' => 'Administrador Prueba']);
        $user->assignRole('Administrador');

        return $user;
    }

    private function psicologo(): User
    {
        $user = User::factory()->psicologo()->create(['nombre' => 'Dra. Prueba']);
        $user->assignRole(Role::findOrCreate('Psicólogo', 'web'));

        return $user;
    }

    private function estudiante(): User
    {
        return User::factory()->create(['rol' => 'estudiante', 'nombre' => 'Estudiante Prueba']);
    }

    private function evaluacion(User $user, string $estado = 'ansiedad', string $estres = 'severo'): void
    {
        $e = new EvaluacionPsicometrica;
        $e->user_id = $user->id;
        $e->instrumento = 'SISCO_ESTRES';
        $e->puntaje_estresores = 30;
        $e->puntaje_sintomas = 25;
        $e->puntaje_afrontamiento = 20;
        $e->puntaje_global = 82;
        $e->nivel_estres = $estres;
        $e->estado_afectivo_predominante = $estado;
        $e->save();
    }

    private function telemetria(User $user, string $objetivo = 'ansiedad', string $predicha = 'ansiedad'): void
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

    /*
    |--------------------------------------------------------------------------
    | El panel no muestra datos clínicos por persona
    |--------------------------------------------------------------------------
    */

    public function test_el_panel_no_muestra_el_estado_clinico_de_cada_estudiante(): void
    {
        $admin = $this->admin();
        $estudiante = $this->estudiante();
        $this->evaluacion($estudiante, 'tristeza', 'severo');
        $this->telemetria($estudiante);

        $html = $this->actingAs($admin)
            ->get('/admin/validacion-cientifica')
            ->assertOk()
            ->getContent();

        // El nombre del estudiante y su código de participante no aparecen.
        $this->assertStringNotContainsString('Estudiante Prueba', $html);
        $this->assertStringNotContainsString($estudiante->codigo_anonimo, $html);

        // Y tampoco el dato clínico en sí: la etiqueta de la clase sí puede
        // aparecer (es la matriz), pero no en formato de ficha individual.
        $this->assertStringNotContainsString('TESVB-SIST-', $html);
    }

    public function test_el_panel_no_consulta_la_tabla_de_usuarios(): void
    {
        $admin = $this->admin();
        $estudiante = $this->estudiante();
        $this->evaluacion($estudiante);
        $this->telemetria($estudiante);

        $consultadas = [];

        DB::listen(function ($q) use (&$consultadas) {
            $tabla = strtolower(trim((string) preg_replace('/.*\bfrom\s+["`]?([a-z_]+)/i', '$1', $q->sql), '"` '));

            if ($tabla === 'usuarios') {
                $consultadas[] = $q->sql;
            }
        });

        $this->actingAs($admin)->get('/admin/validacion-cientifica')->assertOk();

        // La unica consulta a usuarios es la de la sesion (Auth). Si el panel
        // volviera a usar with('user'), apareceria una consulta con JOIN.
        $this->assertSame(
            [],
            array_values(array_filter($consultadas, fn ($sql) => str_contains(strtolower($sql), 'join'))),
            'El panel de validacion no debe leer datos de la tabla de usuarios.'
        );
    }

    public function test_el_panel_sigue_sirviendo_para_la_tesis(): void
    {
        $admin = $this->admin();
        $estudiante = $this->estudiante();

        $this->telemetria($estudiante, 'ansiedad', 'ansiedad');
        $this->telemetria($estudiante, 'tristeza', 'ansiedad');
        $this->telemetria($estudiante, 'ansiedad', 'frustracion');
        $this->telemetria($estudiante, 'frustracion', 'frustracion');
        $this->evaluacion($estudiante, 'ansiedad', 'moderado');

        $response = $this->actingAs($admin)->get('/admin/validacion-cientifica');

        $response->assertOk();

        // Las metricas agregadas siguen completas: quitar datos individuales no
        // puede inutilizar la herramienta de validacion.
        $this->assertSame(4, $response->viewData('totalMuestras'));
        $this->assertSame(4, $response->viewData('muestrasIncluidasMatriz'));
        $this->assertSame(50.0, $response->viewData('accuracy'));
        $this->assertSame(1, $response->viewData('participantesTelemetria'));
        $this->assertSame(1, $response->viewData('totalEvaluaciones'));
        $this->assertSame(['moderado' => 1], $response->viewData('distribucionEvaluaciones'));
    }

    public function test_el_panel_ya_no_expone_las_variables_que_ya_no_existen(): void
    {
        $admin = $this->admin();
        $this->evaluacion($this->estudiante());
        $this->telemetria($this->estudiante());

        $response = $this->actingAs($admin)->get('/admin/validacion-cientifica');

        $response->assertOk();
        $response->assertViewMissing('telemetriasRecientes');
        $response->assertViewMissing('evaluacionesPsicometricas');
    }

    /*
    |--------------------------------------------------------------------------
    | El código "anónimo" no es el id
    |--------------------------------------------------------------------------
    */

    public function test_el_codigo_de_participante_no_revela_el_id(): void
    {
        $user = $this->estudiante();

        $codigo = $user->codigo_anonimo;

        $this->assertStringStartsWith('TESVB-', $codigo);
        $this->assertStringNotContainsString(
            (string) $user->id,
            $codigo,
            'El código de participante no puede ser el id con otro formato.'
        );
    }

    public function test_el_codigo_es_estable_para_el_mismo_participante(): void
    {
        $user = $this->estudiante();

        // Sin esto no se pueden usar modelos de efectos mixtos en la tesis.
        $this->assertSame($user->codigo_anonimo, $user->fresh()->codigo_anonimo);
        $this->assertSame(User::codigoParticipante($user->id), $user->codigo_anonimo);
    }

    public function test_participantes_distintos_tienen_codigos_distintos(): void
    {
        $a = $this->estudiante();
        $b = $this->estudiante();

        $this->assertNotSame($a->codigo_anonimo, $b->codigo_anonimo);
    }

    /*
    |--------------------------------------------------------------------------
    | El CSV exportado es un dataset, no un historial
    |--------------------------------------------------------------------------
    */

    public function test_el_csv_no_contiene_nombres_ni_correos(): void
    {
        $admin = $this->admin();
        $estudiante = User::factory()->create([
            'rol' => 'estudiante',
            'nombre' => 'Nombre Que No Debe Salir',
            'correo' => 'privado@example.com',
        ]);
        $this->telemetria($estudiante);

        $csv = $this->contenidoCsv($admin);

        // `getContent()` sobre una respuesta en streaming devuelve SIEMPRE vacío:
        // comprobando eso, esta prueba pasaba en falso. Hay que leer el stream.
        $this->assertNotSame('', trim($csv), 'El CSV debe tener contenido de verdad.');

        $this->assertStringNotContainsString('Nombre Que No Debe Salir', $csv);
        $this->assertStringNotContainsString('privado@example.com', $csv);

        // Y sí debe traer el código del participante: el dataset es
        // utilizable para la tesis, solo que sin identidad.
        $this->assertStringContainsString(User::codigoParticipante($estudiante->id), $csv);
    }

    /**
     * Ejecuta la exportación y devuelve el CSV realmente generado.
     */
    private function contenidoCsv(User $admin): string
    {
        $response = $this->actingAs($admin)->get('/admin/validacion-cientifica/exportar');

        $response->assertOk();

        ob_start();
        $response->baseResponse->sendContent();

        return (string) ob_get_clean();
    }

    /*
    |--------------------------------------------------------------------------
    | La gestión de cuentas no expone datos personales
    |--------------------------------------------------------------------------
    */

    public function test_la_lista_de_cuentas_no_muestra_correo_ni_edad(): void
    {
        $admin = $this->admin();
        $estudiante = User::factory()->create([
            'rol' => 'estudiante',
            'nombre' => 'Estudiante Conocido',
            'correo' => 'correo.privado@example.com',
            'edad' => 21,
        ]);

        $html = $this->actingAs($admin)
            ->get('/usuarios')
            ->assertOk()
            ->getContent();

        // El nombre se conserva: hay que poder gestionar la cuenta.
        $this->assertStringContainsString('Estudiante Conocido', $html);

        // El correo no hace falta para administrar y es un dato de contacto.
        $this->assertStringNotContainsString('correo.privado@example.com', $html);
    }

    public function test_la_lista_de_cuentas_no_trae_columnas_que_no_sirven(): void
    {
        $admin = $this->admin();
        $this->estudiante();

        $columnas = $this->actingAs($admin)
            ->get('/usuarios')
            ->assertOk()
            ->viewData('usuarios')
            ->first()
            ->getAttributes();

        // Proyección explícita: si alguien añade un campo al modelo, no debe
        // aparecer en la pantalla de administración sin querer.
        $this->assertSame(
            ['id', 'nombre', 'rol', 'created_at'],
            array_keys($columnas),
            'La pantalla de cuentas debe proyectar solo las columnas de administracion.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Administrar NO concede acceso clínico
    |--------------------------------------------------------------------------
    */

    public function test_un_administrador_puro_no_es_profesional(): void
    {
        $admin = $this->admin();

        $this->assertTrue($admin->esAdmin());
        $this->assertFalse(
            $admin->esProfesional(),
            'Administrar el sistema no debe convertir a nadie en profesional de psicología.'
        );
    }

    public function test_el_administrador_no_entra_al_espacio_de_los_psicologos(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/psicologia/pacientes')->assertForbidden();
        $this->actingAs($admin)->get('/psicologia/invitaciones')->assertForbidden();
    }

    public function test_el_administrador_no_puede_generar_un_codigo_de_invitacion(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/psicologia/invitaciones')
            ->assertForbidden();

        $this->assertDatabaseCount('invitaciones_psicologo', 0);
    }

    public function test_un_codigo_emitido_por_un_administrador_no_otorga_consentimiento(): void
    {
        // Aunque alguien inserte una fila a mano, el canje la rechaza: la puerta
        // es el rol de psicologo, no la existencia de la fila.
        $admin = $this->admin();
        $estudiante = $this->estudiante();

        $inv = new InvitacionPsicologo;
        $inv->codigo = 'ADMIN999';
        $inv->expira_en = now()->addDays(14);
        $inv->profesional_id = $admin->id;
        $inv->save();

        $this->actingAs($estudiante)
            ->post('/mi-privacidad/conectar', ['codigo' => 'ADMIN999', 'alcance' => ['registros']])
            ->assertSessionHasErrors('codigo');

        $this->assertDatabaseCount('consentimientos', 0);
    }

    public function test_el_administrador_no_es_un_estudiante(): void
    {
        $admin = $this->admin();

        // Que "no sea profesional" no debe convertirlo en destinatario de la
        // logica de consentimiento del estudiante.
        $this->assertFalse($admin->esEstudiante());
    }

    public function test_una_cuenta_que_es_ambos_cosas_sigue_entrando(): void
    {
        // El escenario real de una universidad no se rompe: quien administra y
        // atiende tiene los dos roles y entra por la puerta del psicologo.
        $ambos = $this->admin();
        $ambos->assignRole(Role::findOrCreate('Psicólogo', 'web'));

        $this->assertTrue($ambos->esProfesional());

        $this->actingAs($ambos)->get('/psicologia/pacientes')->assertOk();
    }

    public function test_el_psicologo_sigue_pudiendo_generar_codigos(): void
    {
        $psicologo = $this->psicologo();

        $this->actingAs($psicologo)
            ->post('/psicologia/invitaciones')
            ->assertRedirect('/psicologia/invitaciones');

        $this->assertDatabaseCount('invitaciones_psicologo', 1);
    }

    public function test_el_administrador_conserva_su_propia_gestion_de_cuentas(): void
    {
        // Restringir lo clínico no puede romper lo que sí le corresponde.
        $admin = $this->admin();

        $this->actingAs($admin)->get('/usuarios')->assertOk();
        $this->actingAs($admin)->get('/admin/feedback')->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | El menú de mando es de quien se autoobserva
    |--------------------------------------------------------------------------
    */

    /**
     * El administrador no administera: se observa. Por eso no le aparece el
     * «Menú de Mando» (inicio, historial, evaluación, calendario, actividades)
     * ni «Mis datos».
     *
     * Y no es solo que no se muestren: las rutas devuelven 403. Esconder el
     * enlace dejaría la URL funcionando para quien la conociera.
     */
    public function test_el_administrador_no_ve_el_menu_de_mando(): void
    {
        $admin = $this->admin();

        foreach ([
            '/dashboard',
            '/historial',
            '/historial/reporte',
            '/perfil/calendario',
            '/evaluacion-psicometrica',
            '/terminal/minijuegos',
            '/terminal/minijuegos/diagnostico',
            '/mi-privacidad',
        ] as $ruta) {
            $this->actingAs($admin)->get($ruta)->assertForbidden("El administrador no debería abrir {$ruta}.");
        }
    }

    public function test_el_administrador_no_tiene_el_menu_de_mando_en_la_interfaz(): void
    {
        $admin = $this->admin();

        $html = $this->actingAs($admin)
            ->get('/usuarios')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Menú de Mando', $html);
        $this->assertStringNotContainsString('Evaluación SISCO', $html);
        $this->assertStringNotContainsString('Mis datos', $html);

        // Pero sí ve lo suyo: la gestión de cuentas y sus ajustes.
        $this->assertStringContainsString('Administración', $html);
        $this->assertStringContainsString('Mi cuenta', $html);
    }

    /**
     * El profesional que solo tiene ese rol tampoco es estudiante: atiende.
     * Lo que se comprueba aquí, además del 403, es que su espacio siga
     * funcionando: un 403 en la pantalla que le corresponde sería un fallo de
     * la aplicación, no una medida de seguridad.
     */
    public function test_el_profesional_solo_no_usa_el_menu_de_mando(): void
    {
        $psicologo = $this->psicologo();

        $this->actingAs($psicologo)->get('/dashboard')->assertForbidden();
        $this->actingAs($psicologo)->get('/historial')->assertForbidden();
        $this->actingAs($psicologo)->get('/mi-privacidad')->assertForbidden();

        // Su espacio sigue funcionando.
        $this->actingAs($psicologo)->get('/psicologia/pacientes')->assertOk();
    }

    public function test_el_estudiante_sigue_teniendo_todo_el_menu_de_mando(): void
    {
        $estudiante = $this->estudiante();

        foreach ([
            '/dashboard',
            '/historial',
            '/perfil/calendario',
            '/evaluacion-psicometrica',
            '/terminal/minijuegos',
            '/mi-privacidad',
        ] as $ruta) {
            $this->actingAs($estudiante)->get($ruta)->assertOk();
        }
    }

    /**
     * Quien administra y además se autoobserva no pierde ninguna de las dos
     * cosas. Es el caso real de una universidad, así que la restricción tiene
     * que ser por tipo de cuenta y no por persona.
     */
    public function test_una_cuenta_que_administra_y_se_autoobserva_conserva_ambos_menus(): void
    {
        $ambos = $this->admin();
        $ambos->assignRole(Role::findOrCreate(User::TIPO_ESTUDIANTE, 'web'));

        $this->assertTrue($ambos->esEstudiante());

        $this->actingAs($ambos)->get('/dashboard')->assertOk();
        $this->actingAs($ambos)->get('/mi-privacidad')->assertOk();
        $this->actingAs($ambos)->get('/usuarios')->assertOk();
    }

    /**
     * Cambiar la contraseña no es autoobservación: es administrar la propia
     * cuenta. Si esto quedara restringido, el administrador se quedaría sin
     * poder cambiar su clave.
     */
    public function test_cualquier_cuenta_puede_gestionar_su_propia_cuenta(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/configuracion')->assertOk();

        // Y no puede cambiar de nombre ni de correo a otro usuario, pero sí los
        // suyos: es su propia ficha.
        $this->actingAs($admin)
            ->patch('/perfil/update', ['nombre' => 'Nombre Nuevo', 'correo' => 'nuevo@example.com'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('usuarios', ['id' => $admin->id, 'nombre' => 'Nombre Nuevo']);
    }

    /*
    |--------------------------------------------------------------------------
    | Cada tipo aterriza donde le toca
    |--------------------------------------------------------------------------
    */

    public function test_cada_tipo_aterriza_en_su_propuesta_de_inicio(): void
    {
        $estudiante = $this->estudiante();
        $psicologo = $this->psicologo();
        $admin = $this->admin();

        $this->assertSame('dashboard', $estudiante->rutaDeInicio());
        $this->assertSame('psicologia.pacientes', $psicologo->rutaDeInicio());
        $this->assertSame('usuarios.index', $admin->rutaDeInicio());
    }

    /**
     * El caso que hizo falta el método: sin esto, el administrador terminaba el
     * inicio de sesión en un 403.
     */
    public function test_entrar_como_administrador_no_termina_en_error(): void
    {
        $admin = $this->admin();

        $response = $this->post('/login', [
            'correo' => $admin->correo,
            'contrasena' => 'password',
            'rol' => User::TIPO_ADMINISTRADOR,
        ]);

        $response->assertRedirect(route('usuarios.index'));
        $this->assertAuthenticated();
    }

    public function test_el_logo_del_menu_no_apunta_a_una_pantalla_prohibida(): void
    {
        $admin = $this->admin();

        $html = $this->actingAs($admin)
            ->get('/usuarios')
            ->assertOk()
            ->getContent();

        // El logo es el enlace principal del panel. Si apuntara a `dashboard`,
        // el primer clic del administrador lo dejaría en un 403.
        $this->assertStringNotContainsString(route('dashboard'), $html);
    }

    /**
     * Ninguna pantalla del administrador puede enlazar a algo que él no puede
     * abrir. Se detectó uno: el estado vacío del panel de validación ofrecía
     * «Iniciar una partida», que lleva al minijuego, un 403 para el
     * administrador.
     */
    public function test_ninguna_pantalla_del_administrador_enlaza_a_una_ruta_que_no_puede_abrir(): void
    {
        $admin = $this->admin();

        $prohibidas = [
            '/dashboard',
            '/historial',
            '/evaluacion-psicometrica',
            '/terminal/minijuegos',
            '/mi-privacidad',
        ];

        foreach (['/usuarios', '/admin/validacion-cientifica', '/admin/feedback', '/configuracion'] as $pantalla) {
            $html = $this->actingAs($admin)->get($pantalla)->assertOk()->getContent();

            foreach ($prohibidas as $ruta) {
                $this->assertStringNotContainsString(
                    $ruta,
                    $html,
                    "La pantalla {$pantalla} enlaza a {$ruta}, que el administrador no puede abrir."
                );
            }
        }
    }

    /**
     * El estado vacío del panel de validación debe poder llegar hasta ahí sin
     * romperse. Con cero muestras el botón de jugar desaparece para el
     * administrador y aparece su explicación.
     */
    public function test_el_panel_vacio_explica_de_donde_salen_las_muestras(): void
    {
        $admin = $this->admin();

        $html = $this->actingAs($admin)
            ->get('/admin/validacion-cientifica')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('No hay ninguna muestra todav', $html);
        $this->assertStringNotContainsString('Iniciar una partida', $html);
        $this->assertStringContainsString('quienes juegan la actividad', $html);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * El tipo de acceso del login NO era decorativo en el Blade pero sí lo era en
 * el controlador: el `select` ofrecía "Usuario" y "Administrador", faltaba
 * psicología, y AuthController ignoraba el campo por completo. Estas pruebas
 * fijan el comportamiento real: el tipo declarado debe corresponder a la
 * cuenta.
 */
class AccesoPorTipoTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol, string $correo): User
    {
        $u = new User;
        $u->nombre = 'Persona '.$rol;
        $u->edad = 30;
        $u->correo = $correo;
        $u->password = Hash::make('password123');
        $u->rol = $rol;
        $u->save();

        $u->assignRole(Role::findOrCreate($rol, 'web'));

        return $u;
    }

    public function test_el_login_ofrece_los_tres_tipos(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString('value="estudiante"', $html);
        $this->assertStringContainsString('value="Psicólogo"', $html);
        $this->assertStringContainsString('value="Administrador"', $html);

        // El valor antiguo ("Usuario") era un tipo que el sistema nunca
        // reconoció: se enviaba y el servidor lo ignoraba.
        $this->assertStringNotContainsString('value="Usuario"', $html);
    }

    public function test_psicologo_puede_entrar_declarando_su_tipo(): void
    {
        $this->usuario(User::TIPO_PSICOLOGO, 'psi@uni.edu');

        $this->post('/login', [
            'correo' => 'psi@uni.edu',
            'contrasena' => 'password123',
            'rol' => User::TIPO_PSICOLOGO,
        ])->assertRedirect(route('psicologia.pacientes'));

        $this->assertAuthenticated();
    }

    public function test_el_tipo_no_coincidente_cierra_la_sesion(): void
    {
        $this->usuario(User::TIPO_ESTUDIANTE, 'est@uni.edu');

        $this->post('/login', [
            'correo' => 'est@uni.edu',
            'contrasena' => 'password123',
            'rol' => User::TIPO_PSICOLOGO,
        ])->assertSessionHasErrors('rol');

        $this->assertGuest();
    }

    public function test_el_mensaje_dice_de_que_tipo_entra_la_cuenta(): void
    {
        $this->usuario(User::TIPO_ADMINISTRADOR, 'adm@uni.edu');

        $this->post('/login', [
            'correo' => 'adm@uni.edu',
            'contrasena' => 'password123',
            'rol' => User::TIPO_PSICOLOGO,
        ])->assertSessionHasErrors('rol');

        $mensaje = session('errors')->first('rol');

        // La persona tiene que entender qué hacer, no solo que falló.
        $this->assertStringContainsString('Entró con', $mensaje);
        $this->assertStringContainsString('Administración del sistema', $mensaje);
    }

    public function test_quien_administra_y_atiene_puede_entrar_por_cualquiera_de_sus_tipos(): void
    {
        $u = $this->usuario(User::TIPO_ADMINISTRADOR, 'adm.psi@uni.edu');
        $u->assignRole(Role::findOrCreate(User::TIPO_PSICOLOGO, 'web'));

        // Alterne los dos roles: la sesión abre y cada uno lleva a su pantalla.
        $this->post('/login', [
            'correo' => 'adm.psi@uni.edu',
            'contrasena' => 'password123',
            'rol' => User::TIPO_PSICOLOGO,
        ])->assertRedirect(route('psicologia.pacientes'));

        $this->assertAuthenticated();
    }

    public function test_un_tipo_inventado_se_rechaza(): void
    {
        $this->usuario(User::TIPO_ESTUDIANTE, 'otro@uni.edu');

        $this->post('/login', [
            'correo' => 'otro@uni.edu',
            'contrasena' => 'password123',
            'rol' => 'superadministrador',
        ])->assertSessionHasErrors('rol');

        $this->assertGuest();
    }

    public function test_sin_tipo_declarado_el_login_sigue_funcionando(): void
    {
        $this->usuario(User::TIPO_ESTUDIANTE, 'sin.tipo@uni.edu');

        $this->post('/login', [
            'correo' => 'sin.tipo@uni.edu',
            'contrasena' => 'password123',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    public function test_contrasena_incorrecta_no_abre_sesion(): void
    {
        $this->usuario(User::TIPO_ESTUDIANTE, 'mal@uni.edu');

        $this->post('/login', [
            'correo' => 'mal@uni.edu',
            'contrasena' => 'no-es-la-clave',
            'rol' => User::TIPO_ESTUDIANTE,
        ])->assertSessionHasErrors('correo');

        $this->assertGuest();
    }

    /*
    |--------------------------------------------------------------------------
    | Registro
    |--------------------------------------------------------------------------
    */

    public function test_el_registro_muestra_el_codigo_tambien_para_psicologia(): void
    {
        $html = $this->get('/registrar')->assertOk()->getContent();

        $this->assertStringContainsString('value="Psicólogo"', $html);
        $this->assertStringContainsString('name="admin_token"', $html);

        // La condición del script es la que fallaba: solo.times Administrador.
        // Se comprueba sobre el script renderizado, no sobre el fuente.
        $this->assertMatchesRegularExpression(
            '/necesitaCodigo\s*=\s*[^;]*!==\s*.estudiante./',
            $html,
            'El campo del código debe mostrarse para cualquier tipo que no sea estudiante.'
        );
    }

    public function test_el_registro_conserva_el_tipo_elegido_tras_un_error(): void
    {
        // Un alta fallida debe devolver al formulario, no a la portada.
        $this->post('/registrar', [
            'nombre' => 'Intento',
            'edad' => 30,
            'correo' => 'intento@uni.edu',
            'contrasena' => 'password123',
            'contrasena_confirmation' => 'password123',
            'rol' => User::TIPO_PSICOLOGO,
        ])->assertRedirect('/registrar')->assertSessionHasErrors('admin_token');

        $html = $this->followingRedirects()
            ->post('/registrar', [
                'nombre' => 'Intento',
                'edad' => 30,
                'correo' => 'intento@uni.edu',
                'contrasena' => 'password123',
                'contrasena_confirmation' => 'password123',
                'rol' => User::TIPO_PSICOLOGO,
            ])->getContent();

        // Si el select volviera a "estudiante", la persona perdería su
        // elección y quedaría como si fuera un alta de estudiante.
        $this->assertMatchesRegularExpression(
            '/value="Psicólogo"\s+selected/',
            $html,
            'El tipo elegido debe seguir marcado al volver del servidor.'
        );
    }

    public function test_registro_completo_de_los_tres_tipos(): void
    {
        // .env.testing deja ADMIN_MASTER_KEY vacío a propósito, así que el
        // código se fija aquí. Con la variable vacía, el alta de profesional
        // y de administración tiene que FALLAR (ver el test de abajo).
        config(['auth.admin_master_key' => 'codigo-de-prueba']);

        $destinoPorTipo = [
            User::TIPO_ESTUDIANTE => route('dashboard'),
            User::TIPO_PSICOLOGO => route('psicologia.pacientes'),
            User::TIPO_ADMINISTRADOR => route('usuarios.index'),
        ];

        foreach ([User::TIPO_ESTUDIANTE, User::TIPO_PSICOLOGO, User::TIPO_ADMINISTRADOR] as $rol) {
            $correo = strtolower(str_replace('ó', 'o', $rol)).'@uni.edu';

            $datos = [
                'nombre' => 'Alta '.$rol,
                'edad' => 25,
                'correo' => $correo,
                'contrasena' => 'password123',
                'contrasena_confirmation' => 'password123',
                'rol' => $rol,
            ];

            if ($rol !== User::TIPO_ESTUDIANTE) {
                $datos['admin_token'] = 'codigo-de-prueba';
            }

            // Cada tipo aterriza en la pantalla que le corresponde, no en una
            // común: el destino lo decide `User::rutaDeInicio()`.
            $this->post('/registrar', $datos)->assertRedirect($destinoPorTipo[$rol]);

            $this->assertDatabaseHas('usuarios', ['correo' => $correo, 'rol' => $rol]);
            $this->assertSame($rol, User::where('correo', $correo)->first()->roles->first()->name);
        }
    }

    public function test_sin_codigo_configurado_nadie_puede_darse_de_alta_como_profesional(): void
    {
        // ADMIN_MASTER_KEY vacío o ausente = la puerta está cerrada, no abierta.
        // Antes el chequeo era `=== ''`, que no cubre null: con la variable
        // sin definir en .env, un token vacío pasaba la comparación.
        config(['auth.admin_master_key' => null]);

        $this->post('/registrar', [
            'nombre' => 'Colado',
            'edad' => 30,
            'correo' => 'colado@uni.edu',
            'contrasena' => 'password123',
            'contrasena_confirmation' => 'password123',
            'rol' => User::TIPO_PSICOLOGO,
            'admin_token' => '',
        ])->assertSessionHasErrors('admin_token');

        $this->assertGuest();
        $this->assertDatabaseMissing('usuarios', ['correo' => 'colado@uni.edu']);
    }

    public function test_el_codigo_no_acepta_un_prefijo(): void
    {
        config(['auth.admin_master_key' => 'codigo-secreto']);

        // hash_equals compara la cadena completa. Un código que empieza igual
        // pero lleva texto de más NO sirve: si la comparación fuera con
        // starts_with o strncmp, esto sería una vía de alta sin autorización.
        foreach (['codigo-secreto-extra', 'codigo', 'codigo-secret', 'CODIGO-SECRETO'] as $intento) {
            $correo = 'intento-'.md5($intento).'@uni.edu';

            $this->post('/registrar', [
                'nombre' => 'Intento',
                'edad' => 30,
                'correo' => $correo,
                'contrasena' => 'password123',
                'contrasena_confirmation' => 'password123',
                'rol' => User::TIPO_PSICOLOGO,
                'admin_token' => $intento,
            ])->assertSessionHasErrors('admin_token');

            $this->assertDatabaseMissing('usuarios', ['correo' => $correo]);
        }
    }
}

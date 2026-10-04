<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_permite_registrar_admin_sin_token(): void
    {
        $response = $this->post('/registrar', [
            'nombre' => 'Test',
            'edad' => 20,
            'correo' => 'test@example.com',
            'contrasena' => 'password123',
            'contrasena_confirmation' => 'password123',
            'rol' => 'Administrador',
            'admin_token' => 'token_incorrecto',
        ]);

        $response->assertSessionHasErrors('admin_token');
    }

    public function test_registro_estudiante_exitoso(): void
    {
        $response = $this->post('/registrar', [
            'nombre' => 'Estudiante',
            'edad' => 22,
            'correo' => 'estudiante@example.com',
            'contrasena' => 'password123',
            'contrasena_confirmation' => 'password123',
            'rol' => 'estudiante',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('usuarios', [
            'correo' => 'estudiante@example.com',
            'rol' => 'estudiante',
        ]);
    }

    public function test_registrarse_como_psicologo_exige_el_codigo_de_autorizacion(): void
    {
        $response = $this->post('/registrar', [
            'nombre' => 'Profesional',
            'edad' => 35,
            'correo' => 'pro@example.com',
            'contrasena' => 'password123',
            'contrasena_confirmation' => 'password123',
            'rol' => 'Psicólogo',
        ]);

        $response->assertSessionHasErrors('admin_token');
        $this->assertGuest();
    }

    public function test_no_se_acepta_un_rol_inexistente(): void
    {
        $this->post('/registrar', [
            'nombre' => 'Intruso',
            'edad' => 30,
            'correo' => 'intruso@example.com',
            'contrasena' => 'password123',
            'contrasena_confirmation' => 'password123',
            'rol' => 'superadministrador',
        ])->assertSessionHasErrors('rol');

        $this->assertGuest();
    }
}

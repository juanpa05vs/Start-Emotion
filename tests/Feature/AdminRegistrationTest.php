<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.admin_master_key' => 'admin']);
    }

    public function test_registro_admin_con_token_correcto(): void
    {
        $response = $this->post('/registrar', [
            'nombre' => 'Admin Alfa',
            'edad' => 30,
            'correo' => 'alfa@example.com',
            'contrasena' => 'password123',
            'contrasena_confirmation' => 'password123',
            'rol' => 'Administrador',
            'admin_token' => 'admin',
        ]);

        // El administrador aterriza en la gestión de cuentas, no en el panel del
        // estudiante: el `dashboard` es de quien se autoobserva.
        $response->assertRedirect('/usuarios');
        $this->assertAuthenticated();

        $user = User::where('correo', 'alfa@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Administrador', $user->rol);
        $this->assertTrue($user->hasRole('Administrador'));
        $this->assertTrue($user->esAdmin());
    }

    public function test_registro_admin_con_token_incorrecto_es_rechazado(): void
    {
        $response = $this->post('/registrar', [
            'nombre' => 'Intruso',
            'edad' => 30,
            'correo' => 'intruso@example.com',
            'contrasena' => 'password123',
            'contrasena_confirmation' => 'password123',
            'rol' => 'Administrador',
            'admin_token' => 'clave-mala',
        ]);

        $response->assertSessionHasErrors('admin_token');
        $this->assertGuest();
        $this->assertDatabaseMissing('usuarios', ['correo' => 'intruso@example.com']);
    }

    public function test_registro_admin_sin_master_key_configurado_es_bloqueado(): void
    {
        // Simula una instalación donde ADMIN_MASTER_KEY quedó vacío.
        config(['auth.admin_master_key' => '']);

        $response = $this->post('/registrar', [
            'nombre' => 'Sin Clave',
            'edad' => 30,
            'correo' => 'sinclave@example.com',
            'contrasena' => 'password123',
            'contrasena_confirmation' => 'password123',
            'rol' => 'Administrador',
            'admin_token' => 'admin',
        ]);

        $response->assertSessionHasErrors('admin_token');
        $this->assertDatabaseMissing('usuarios', ['correo' => 'sinclave@example.com']);
    }
}

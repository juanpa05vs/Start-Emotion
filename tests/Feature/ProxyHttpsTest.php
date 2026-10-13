<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Detección de HTTPS detrás del proxy TLS de Render.
 *
 * Render termina el TLS en su proxy de borde y reenvía la petición al
 * contenedor como HTTP, añadiendo X-Forwarded-Proto: https. Si Laravel no
 * confía en ese proxy, cree que la petición es HTTP y genera URLs con esquema
 * http:// (los formularios salen con action="http://…" y el navegador avisa
 * de envío inseguro).
 *
 * En los tests el proxy inmediato es 127.0.0.1 (REMOTE_ADDR por defecto), que
 * es el único en el que se confía con la configuración '*' por defecto.
 */
class ProxyHttpsTest extends TestCase
{
    use RefreshDatabase;

    private function peticionDetrasDelProxy(): static
    {
        return $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders([
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Port' => '443',
            ]);
    }

    public function test_login_genera_action_https_detras_del_proxy(): void
    {
        $response = $this->peticionDetrasDelProxy()->get('/login');

        $response->assertOk();
        $response->assertSee('action="https://localhost/login"', false);
        $response->assertDontSee('action="http://localhost/login"', false);
    }

    public function test_registro_genera_action_https_detras_del_proxy(): void
    {
        $response = $this->peticionDetrasDelProxy()->get('/registrar');

        $response->assertOk();
        $response->assertSee('action="https://localhost/registrar"', false);
        $response->assertDontSee('action="http://localhost/registrar"', false);
    }

    public function test_sin_proxy_las_urls_siguen_siendo_http_en_local(): void
    {
        // Sin cabeceras del proxy (p. ej. php artisan serve en 127.0.0.1:8000)
        // el esquema debe seguir siendo http. Esto protege el entorno local.
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('action="http://localhost/login"', false);
        $response->assertDontSee('action="https://localhost/login"', false);
    }

    public function test_los_helpers_de_url_usan_https_detras_del_proxy(): void
    {
        $this->peticionDetrasDelProxy()->get('/login');

        $this->assertStringStartsWith('https://', route('login'));
        $this->assertStringStartsWith('https://', route('register'));
        $this->assertStringStartsWith('https://', url('/cualquiera'));
        $this->assertStringStartsWith('https://', secure_url('/cualquiera'));
        $this->assertStringStartsWith('https://', asset('build/app.js'));
    }

    public function test_un_x_forwarded_host_falsificado_no_redirige_las_urls_generadas(): void
    {
        // Un cliente no debe poder llevar las URLs generadas a otro dominio
        // inyectando X-Forwarded-Host (ni puerto) a través del proxy. El host
        // válido es el de la petición (Host: localhost en los tests).
        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders([
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Host' => 'evil.example.com',
                'X-Forwarded-Port' => '6666',
            ])
            ->get('/login');

        $response->assertOk();
        $response->assertSee('action="https://localhost/login"', false);
        $response->assertDontSee('evil.example.com');

        $this->assertSame('https://localhost/login', route('login'));
        $this->assertStringNotContainsString(':6666', route('login'));
    }

    public function test_redireccion_de_ruta_protegida_apunta_al_login_https(): void
    {
        $response = $this->peticionDetrasDelProxy()->get('/dashboard');

        $response->assertRedirect('https://localhost/login');
    }

    public function test_post_login_con_credenciales_invalidas_redirige_en_https(): void
    {
        $response = $this->peticionDetrasDelProxy()->post('/login', [
            'correo' => 'nadie@example.com',
            'contrasena' => 'incorrecta',
            'rol' => 'estudiante',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
        $this->assertStringStartsWith('https://', (string) $response->headers->get('Location'));
    }

    public function test_post_registro_incompleto_redirige_en_https(): void
    {
        $response = $this->peticionDetrasDelProxy()->post('/registrar', [
            'nombre' => '',
        ]);

        $response->assertSessionHasErrors();
        $this->assertStringStartsWith('https://', (string) $response->headers->get('Location'));
    }

    public function test_logout_redirige_en_https(): void
    {
        $user = User::factory()->create();

        $response = $this->peticionDetrasDelProxy()
            ->actingAs($user)
            ->post('/logout');

        $response->assertRedirect();
        $this->assertStringStartsWith('https://', (string) $response->headers->get('Location'));
        $this->assertGuest();
    }

    public function test_las_rutas_publicas_no_entran_en_bucle_de_redirecciones(): void
    {
        $this->peticionDetrasDelProxy()->get('/login')->assertOk();
        $this->peticionDetrasDelProxy()->get('/')->assertOk();
    }

    public function test_en_entorno_no_productivo_la_cookie_de_sesion_no_es_secure(): void
    {
        // El default es Secure en producción y no-Secure fuera de ella, para
        // que http://127.0.0.1:8000 siga funcionando en local y en tests.
        $this->assertFalse(config('session.secure'));
    }
}

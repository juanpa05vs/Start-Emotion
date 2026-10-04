<?php

namespace Tests\Feature;

use App\Enums\EmocionEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Guarda de sintaxis del JavaScript inline de cada página.
 *
 * Un SyntaxError de JavaScript es invisible para PHP: la vista compila, la
 * respuesta es 200, los tests HTTP pasan, y en el navegador la página carga
 * pero queda sin comportamiento. Pasó con el minijuego "Código Anómalo" — un
 * fragmento JSX pegado dentro del <script> impidió poblar el tablero y, en
 * cascada, dejó la matriz de validación sin una sola muestra.
 *
 * Aquí se pide cada página como lo haría el navegador, se extraen los <script>
 * sin atributo `src` del HTML final y se pasan por `node --check`.
 */
class BladeScriptsSyntaxTest extends TestCase
{
    use RefreshDatabase;

    private static function nodeDisponible(): bool
    {
        exec('node --version 2>&1', $out, $code);

        return $code === 0;
    }

    /** [uri, requiereAdmin] */
    public static function paginas(): array
    {
        return [
            'welcome' => ['/', false],
            'login' => ['/login', false],
            'registro' => ['/registrar', false],
            'dashboard' => ['/dashboard', false],
            'historial' => ['/historial', false],
            'calendario' => ['/perfil/calendario', false],
            'configuracion' => ['/configuracion', false],
            'sisco' => ['/evaluacion-psicometrica', false],
            'minijuegos' => ['/terminal/minijuegos', false],
            'minijuego diagnostico' => ['/terminal/minijuegos/diagnostico', false],
            'admin validacion' => ['/admin/validacion-cientifica', true],
            'admin feedback' => ['/admin/feedback', true],
            'admin usuarios' => ['/usuarios', true],
        ];
    }

    #[DataProvider('paginas')]
    public function test_el_javascript_de_la_pagina_es_valido(string $uri, bool $admin): void
    {
        if (! self::nodeDisponible()) {
            $this->markTestSkipped('Node no está instalado: no se puede validar la sintaxis JS.');
        }

        $html = $this->obtenerHtml($uri, $admin);

        // Solo los <script> inline; los de @vite llevan src y no se validan aquí.
        // Una página puede no tener JS inline (p. ej. welcome.blade.php es una
        // plantilla HTML independiente); en ese caso no hay nada que comprobar.
        //
        // Se descartan los que declaran un tipo que NO es JavaScript. Un bloque
        // `<script type="application/json">` es un portador de datos: el
        // navegador no lo ejecuta, y pasarlo por `node --check` no comprueba
        // nada, solo se queja de que un objeto literal suelto no es una
        // sentencia. Le pasaba igual a `welcome.blade.php` con su
        // `application/ld+json`.
        preg_match_all('/<script(?![^>]*\bsrc=)(?![^>]*\btype=)([^>]*)>(.*?)<\/script>/s', $html, $m);

        foreach ($m[2] as $i => $js) {
            if (trim($js) === '') {
                continue;
            }

            $tmp = tempnam(sys_get_temp_dir(), 'bladejs').'.mjs';
            file_put_contents($tmp, $js);

            exec('node --check '.escapeshellarg($tmp).' 2>&1', $salida, $codigo);
            unlink($tmp);

            $this->assertSame(
                0,
                $codigo,
                "{$uri} — <script> #{$i} tiene un error de sintaxis JavaScript:\n"
                    .implode("\n", array_slice($salida, 0, 6))
            );
        }
    }

    /**
     * Regresión concreta del fallo que rompió el minijuego.
     */
    public function test_el_minijuego_puebla_el_tablero_al_arrancar(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)->get('/terminal/minijuegos/diagnostico')
            ->assertOk()
            ->getContent();

        // El tablero se inyecta por JS: debe existir el contenedor y el hook de arranque.
        $this->assertStringContainsString('id="emotion-matrix"', $html);
        $this->assertStringContainsString("addEventListener('DOMContentLoaded', initGame)", $html);

        // Y el fragmento JSX que lo rompía no debe estar presente.
        $this->assertStringNotContainsString('modalFeedback', $html);
    }

    /**
     * El endpoint valida `emocion_objetivo` contra EmocionEnum::juego(). Si el
     * banco del tablero se desincroniza del enum, TODAS las partidas devolverían 422
     * y la matriz de validación se quedaría vacía sin error visible.
     */
    public function test_el_banco_del_tablero_coincide_con_el_enum_de_la_taxonomia(): void
    {
        $ruta = dirname(__DIR__, 2).'/resources/views/minijuegos/diagnostico.blade.php';
        $src = file_get_contents($ruta);

        preg_match_all("/id:\s*'([a-z_]+)',\s*nombre:/", $src, $m);

        $delJuego = $m[1];
        $delEnum = EmocionEnum::juego();

        $this->assertNotEmpty($delJuego, 'No se pudo leer el banco de emociones del tablero.');

        sort($delJuego);
        $ordenado = $delEnum;
        sort($ordenado);

        $this->assertSame(
            $ordenado,
            $delJuego,
            'El banco del tablero y EmocionEnum::juego() divergieron. El endpoint rechazaría '
                .'las partidas con 422 y el panel de validación nunca recibiría muestras.'
        );
    }

    private function obtenerHtml(string $uri, bool $admin): string
    {
        if (! $admin) {
            return $this->actingAs(User::factory()->create())->get($uri)
                ->assertOk()
                ->getContent();
        }

        Role::findOrCreate('Administrador', 'web');
        $user = User::factory()->create(['rol' => 'Administrador']);
        $user->assignRole('Administrador');

        return $this->actingAs($user)->get($uri)->assertOk()->getContent();
    }
}

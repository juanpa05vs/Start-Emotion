<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Guardas de la ETAPA 6 (auditoría integral): las correcciones de accesibilidad
 * y de armazón que salieron de la auditoría y que son del tipo que desaparece
 * en silencio.
 *
 * Las cuatro correcciones tienen la misma naturaleza que las del resto de la
 * suite: si alguien las revierte, nada falla a la vista. Un checkbox sin nombre
 * accesible se sigue marcando, una meta descripción que falta no rompe la
 * página, un `<h3>` donde tocaba `<h2>` solo lo nota un lector de pantalla, y el
 * recorte de una tabla en pantalla estrecha no se ve en el escritorio donde se
 * desarrolla. Por eso cada una tiene aquí su comprobación.
 */
class AccesibilidadEtapa6Test extends TestCase
{
    use RefreshDatabase;

    /** Un estudiante con una anotación, para que el historial tenga una fila. */
    private function estudianteConRegistros(): User
    {
        $usuario = User::factory()->create();

        foreach (['alegria', 'tristeza', 'ansiedad'] as $indice => $emocion) {
            $usuario->emociones()->create([
                'emocion' => $emocion,
                'energia' => 60 + $indice,
                'nivel_estres_estimado' => 30 + $indice,
                'observaciones' => 'Anotación de prueba.',
                'contexto' => 'Prueba',
                'recomendacion' => 'Recomendación de prueba.',
                'alerta_burnout' => false,
            ]);
        }

        return $usuario;
    }

    /**
     * Cada checkbox del historial tiene un nombre que lo identifica.
     *
     * Los `ids[]` de la selección múltiple no llevaban etiqueta: para quien usa
     * un lector de pantalla, la lista era una fila de «casilla de verificación»
     * sin decir de qué anotación. El nombre es único por fila —fecha, hora y
     * emoción—, así que además de existir no se repite.
     */
    public function test_los_checkboxes_del_historial_tienen_nombre_accesible_unico(): void
    {
        $html = $this->actingAs($this->estudianteConRegistros())
            ->get('/historial')
            ->assertOk()
            ->getContent();

        preg_match_all('#<input[^>]*\brecord-checkbox\b[^>]*>#', $html, $cajas);

        $this->assertCount(3, $cajas[0], 'Se esperaban tres casillas de selección, una por anotación.');

        $etiquetas = [];

        foreach ($cajas[0] as $caja) {
            $this->assertSame(
                1,
                preg_match('#\baria-label="([^"]+)"#', $caja, $nombre),
                'La casilla de selección no tiene `aria-label`: quien no ve la fila no sabe qué está marcando.'
            );

            $this->assertNotSame('', trim($nombre[1]), 'La etiqueta de la casilla está vacía.');

            $etiquetas[] = $nombre[1];
        }

        $this->assertSame(
            $etiquetas,
            array_values(array_unique($etiquetas)),
            'Dos casillas comparten etiqueta: el lector de pantalla no puede distinguir una fila de otra.'
        );
    }

    /**
     * El historial declara su propia descripción, y el armazón tiene una por
     * defecto para el resto de pantallas.
     *
     * El historial no tenía `meta description` —el único hallazgo de SEO de la
     * auditoría— y ninguna otra pantalla tampoco. Ahora el armazón sirve una por
     * defecto y las vistas pueden afinar la suya; el historial lo hace.
     */
    public function test_la_descripcion_existe_y_el_historial_afina_la_suya(): void
    {
        $historial = $this->actingAs($this->estudianteConRegistros())
            ->get('/historial')
            ->assertOk()
            ->getContent();

        $this->assertSame(
            1,
            preg_match('#<meta name="description" content="([^"]*)">#', $historial, $coincidencia),
            'El historial no declara una meta descripción.'
        );

        $this->assertStringContainsString(
            'anotaciones emocionales',
            $coincidencia[1],
            'El historial ha perdido su descripción propia y ha caído a la genérica del armazón.'
        );

        // El login usa el otro armazón (`app_auth`) y también tiene que declararla.
        $login = $this->get('/login')->assertOk()->getContent();

        $this->assertSame(
            1,
            preg_match('#<meta name="description" content="([^"]*)">#', $login, $coincidenciaLogin),
            'El armazón de acceso no declara una meta descripción.'
        );

        $this->assertNotSame('', trim($coincidenciaLogin[1]), 'La descripción del armazón de acceso está vacía.');
    }

    /**
     * El estado vacío no rompe la jerarquía de encabezados de la pantalla.
     *
     * El componente salía siempre como `<h3>`, que en una pantalla cuyo título es
     * `<h1>` y no tiene más encabezados deja un salto de nivel. Ahora el nivel lo
     * decide quien lo usa: por defecto `<h2>` —el caso de una pantalla sin
     * secciones—, y `<h3>` donde vive dentro de una sección que ya tiene su
     * `<h2>`.
     */
    public function test_el_estado_vacio_respeta_el_nivel_de_encabezado(): void
    {
        $porDefecto = Blade::render('<x-estado-vacio titulo="Prueba de nivel" />');
        $anidado = Blade::render('<x-estado-vacio titulo="Prueba de nivel" :nivel="3" />');

        $this->assertStringContainsString('<h2', $porDefecto, 'Por defecto el estado vacío debería salir como `<h2>`.');
        $this->assertStringContainsString('</h2>', $porDefecto, 'El encabezado por defecto no cierra como `<h2>`.');

        $this->assertStringContainsString('<h3', $anidado, 'Con `nivel=3` el estado vacío debería salir como `<h3>`.');
        $this->assertStringContainsString('</h3>', $anidado, 'El encabezado anidado no cierra como `<h3>`.');
        $this->assertStringNotContainsString('<h2', $anidado, 'Con `nivel=3` no debería quedar ningún `<h2>` en el componente.');
    }

    /**
     * La pista bloqueada del minijuego se distingue sin atenuar su texto.
     *
     * El estado bloqueado bajaba la opacidad de toda la caja a 0.25, y el texto
     * gris quedaba en 1.73:1 — muy por debajo del 4.5:1 que la WCAG 2.2 AA pide
     * para texto normal. La distinción pasa ahora por el borde discontinuo y un
     * fondo más tenue, que no tocan el contraste del texto.
     */
    public function test_el_minijuego_no_atenua_el_texto_de_la_pista_bloqueada(): void
    {
        $diagnostico = (string) file_get_contents(resource_path('views/minijuegos/diagnostico.blade.php'));

        $this->assertStringNotContainsString(
            'opacity-25',
            $diagnostico,
            'La pista bloqueada vuelve a atenuar toda la caja, y eso deja su texto por debajo de 4.5:1.'
        );

        $this->assertStringContainsString(
            'border-dashed',
            $diagnostico,
            'La pista bloqueada ha perdido la marca visual que la distingue de la disponible.'
        );

        $this->assertStringContainsString('bloquearPista', $diagnostico, 'Falta el bloqueo de pistas del guion.');
        $this->assertStringContainsString('desbloquearPista', $diagnostico, 'Falta el desbloqueo de pistas del guion.');
    }

    /**
     * El contenido principal puede encogerse para que las tablas no recorten la
     * página.
     *
     * `<main>` es un hijo flexible con `flex-1` y, sin `min-w-0`, no baja de su
     * ancho de contenido: en una pantalla estrecha el armazón entero desborda en
     * horizontal en vez de dejar que la tabla se desplace dentro de su
     * contenedor. `min-w-0` es el ajuste mínimo que devuelve el desplazamiento al
     * sitio correcto.
     */
    public function test_el_contenido_principal_puede_encogerse(): void
    {
        $layout = (string) file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertSame(
            1,
            preg_match('/<main[^>]*class="[^"]*\bmin-w-0\b[^"]*"/', $layout),
            'El `<main>` del armazón no lleva `min-w-0`: en pantalla estrecha la página entera desborda en horizontal.'
        );
    }
}

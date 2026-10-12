<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guardas del cajón lateral responsive (H-5b).
 *
 * La barra de navegación es una columna fija en escritorio y un cajón
 * superpuesto por debajo de `lg` (1024 px). Casi todo lo que hace correcto ese
 * cajón es invisible cuando falta: un disparador sin `aria-controls` se sigue
 * pulsando, un cajón que se esconde solo con `transform` se sigue tabulando sin
 * que se vea, y un botón que desaparece sin JavaScript deja la navegación
 * inalcanzable. Nada de eso rompe la pantalla, así que nada de eso lo nota otra
 * prueba. Estas comprobaciones sí.
 *
 * Se miran contra el marcado renderizado y contra la fuente del CSS y del
 * guion, que es exactamente donde vive el contrato.
 */
class MenuLateralResponsiveTest extends TestCase
{
    use RefreshDatabase;

    private function armazon(): string
    {
        return (string) file_get_contents(resource_path('views/layouts/app.blade.php'));
    }

    private function hoja(): string
    {
        $ruta = resource_path('css/app.css');

        $this->assertFileExists($ruta, 'Falta la hoja de estilos del tema.');

        return (string) file_get_contents($ruta);
    }

    private function guion(): string
    {
        $ruta = resource_path('js/app.js');

        $this->assertFileExists($ruta, 'Falta resources/js/app.js.');

        return (string) file_get_contents($ruta);
    }

    /**
     * El disparador, el velo y la barra existen y están enlazados.
     *
     * Sin `aria-controls` el botón no dice qué abre; sin `aria-expanded` no dice
     * si está abierto; sin nombre accesible es «botón» a secas. Y el cajón
     * necesita un `id` estable que sea el que el disparador nombra.
     */
    public function test_el_disparador_el_velo_y_la_barra_estan_marcados_y_enlazados(): void
    {
        $html = $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->getContent();

        // El disparador: dentro de la cabecera móvil, con nombre y descendiente
        // de la barra declarados, y con un icono que no se anuncia.
        $this->assertMatchesRegularExpression(
            '/<button[^>]*id="menu-lateral-disparador"[^>]*aria-controls="barra-lateral"[^>]*aria-expanded="false"[^>]*aria-label="[^"]*Abrir[^"]*"[^>]*>/s',
            $html,
            'El disparador del cajón ha perdido su `aria-controls`, su `aria-expanded` o su nombre accesible.'
        );

        // La barra: es el destino que el disparador nombra y conserva su etiqueta.
        $this->assertMatchesRegularExpression(
            '/<aside[^>]*id="barra-lateral"[^>]*aria-label="[^"]+"[^>]*>/s',
            $html,
            'La barra lateral ha perdido su `id` o su `aria-label`.'
        );

        // El velo empieza oculto y marcado como decorativo.
        $this->assertMatchesRegularExpression(
            '/<div[^>]*id="menu-lateral-velo"[^>]*class="[^"]*menu-velo[^"]*"[^>]*hidden[^>]*aria-hidden="true"/s',
            $html,
            'El velo del cajón no arranca oculto y `aria-hidden`.'
        );

        // Y hay un cierre explícito para el dedo, que es el foco al abrir.
        $this->assertMatchesRegularExpression(
            '/<button[^>]*id="menu-lateral-cerrar"[^>]*data-menu-foco-inicial[^>]*aria-label="[^"]*Cerrar[^"]*"[^>]*>/s',
            $html,
            'Falta el botón de cierre del cajón, que es el foco inicial al abrir.'
        );
    }

    /**
     * El cajón cerrado sale de la pantalla y del foco.
     *
     * Es el punto entero de la corrección de accesibilidad: con un `translateX`
     * a secas los enlaces siguen en el orden de tabulación y un lector de
     * pantalla los sigue leyendo, aunque no se vean. El desplazamiento lo pone
     * el CSS; sacar el cajón del foco y del árbol lo hace `inert`, que el guion
     * pone y quita. La prueba exige las dos mitades.
     */
    public function test_el_cajon_cerrado_sale_de_la_pantalla_y_del_foco(): void
    {
        $css = $this->hoja();
        $js = $this->guion();

        $this->assertSame(
            1,
            preg_match('/@media\s*\(max-width:\s*63\.999rem\)\s*\{/', $css),
            'El cajón no está atado al mismo umbral que `lg` en Tailwind (`64rem`).'
        );

        // Regla del estado cerrado: dentro de la consulta, la barra se sale.
        $this->assertSame(
            1,
            preg_match('/html\.js\s+\.menu-lateral\s*\{[^{}]*translateX\(-100%\)/s', $css),
            'El cajón cerrado no se sale de la pantalla.'
        );

        $this->assertSame(
            1,
            preg_match("/html\.js\s+\.menu-lateral\[data-abierto='true'\]\s*\{[^{}]*translateX\(0\)/s", $css),
            'El cajón abierto no vuelve a su sitio con el mismo umbral de `data-abierto`.'
        );

        // Y `inert` es lo que lo aparta del teclado y del árbol de accesibilidad.
        $this->assertMatchesRegularExpression(
            '/barra\.inert\s*=\s*!\s*escritorio\.matches/',
            $js,
            'Cerrar el cajón en móvil no lo aparta del foco: sus enlaces seguirían siendo tabulables.'
        );

        $this->assertMatchesRegularExpression(
            '/barra\.inert\s*=\s*false/',
            $js,
            'Abrir el cajón no lo devuelve al foco.'
        );
    }

    /**
     * Sin JavaScript la barra no queda encerrada tras un cajón que no se abre.
     *
     * El cajón completo depende de la marca `html.js`. Si el navegador no
     * ejecuta el guion, el disparador desaparece y la barra vuelve a fluir como
     * bloque de navegación: la página se sigue recorriendo.
     */
    public function test_sin_javascript_la_navegacion_sigue_siendo_alcanzable(): void
    {
        $armazon = $this->armazon();
        $css = $this->hoja();

        $this->assertStringContainsString(
            "classList.add('js')",
            $armazon,
            'El armazón no marca `html.js`: sin esa marca el cajón no se comporta y la hoja no puede distinguir el caso.'
        );

        $this->assertStringContainsString(
            'html:not(.js) .menu-lateral-disparador',
            $css,
            'Sin JavaScript el disparador no se oculta y queda un botón que no hace nada.'
        );

        $this->assertSame(
            1,
            preg_match('/html:not\(\.js\)\s+\.menu-lateral-disparador\s*\{[^{}]*display:\s*none/s', $css),
            'La regla que apaga el disparador cuando no hay JavaScript no lo esconde.'
        );

        // Y la barra sin guion se devuelve al flujo como bloque completo.
        $this->assertSame(
            1,
            preg_match('/html:not\(\.js\)\s+\.menu-lateral\s*\{[^{}]*width:\s*100%[^{}]*height:\s*auto/s', $css),
            'Sin JavaScript la barra no vuelve a fluir como bloque y se queda como columna o como cajón vacío.'
        );
    }

    /**
     * El cajón se anuncia como diálogo modal solo mientras está abierto.
     *
     * Mientras atrapa el foco y oscurece el fondo, el rol correcto es `dialog`
     * con `aria-modal`. Cerrado no puede quedarse con ese rol colgando, porque
     * entonces deja de ser el `aside` de siempre. Lo hace el guion, y por eso se
     * comprueba sobre el guion.
     */
    public function test_el_cajon_toma_semantica_de_dialogo_solo_mientras_esta_abierto(): void
    {
        $js = $this->guion();

        $this->assertMatchesRegularExpression(
            "/setAttribute\('role',\s*'dialog'\)/",
            $js,
            'El cajón abierto no se anuncia como diálogo.'
        );

        $this->assertMatchesRegularExpression(
            "/setAttribute\('aria-modal',\s*'true'\)/",
            $js,
            'El cajón abierto no se marca como modal.'
        );

        $this->assertMatchesRegularExpression(
            "/removeAttribute\('role'\)/",
            $js,
            'El cajón no se deshace del rol de diálogo al cerrarse.'
        );

        $this->assertMatchesRegularExpression(
            "/removeAttribute\('aria-modal'\)/",
            $js,
            'El cajón no se deshace de `aria-modal` al cerrarse.'
        );
    }

    /**
     * El controlador del cajón existe y está conectado al arranque.
     *
     * Es una guarda de presencia, no de comportamiento: comprobar que el cuerpo
     * de `iniciar()` llama a la función no distingue una llamada viva de una
     * comentada (el texto queda dentro igual). Lo que sí atrapa es que alguien
     * borre la función entera o la deje fuera sin querer.
     */
    public function test_el_controlador_del_cajon_existe_y_esta_conectado(): void
    {
        $js = $this->guion();

        $this->assertMatchesRegularExpression(
            '/function\s+conectarMenuLateral\s*\(/',
            $js,
            'Ha desaparecido `conectarMenuLateral()` de resources/js/app.js.'
        );

        $this->assertMatchesRegularExpression(
            '/conectarMenuLateral\(\)\s*;/',
            $js,
            'Nadie llama a `conectarMenuLateral()`: el cajón no se activa.'
        );
    }

    /**
     * El cajón solo anima `transform`, y no vuelve a esconderse con `visibility`.
     *
     * Es la misma disciplina que el resto del movimiento: nada que re-maquete el
     * contenido vecino en cada fotograma. Con `transform` el cajón se desliza
     * sin empujar la página de al lado, y con el movimiento reducido la sección 8
     * de la hoja ya deja esa transición instantánea. `visibility` se descartó a
     * propósito: la transición retrasada que dejaría ver el deslizamiento de
     * salida no es fiable en todos los motores, y aquí la barra quedaba visible
     * tras cerrarse.
     */
    public function test_el_cajon_solo_anima_transform(): void
    {
        $css = $this->hoja();

        $this->assertSame(
            1,
            preg_match('/\.menu-lateral\s*\{[^{}]*transition:\s*transform\b/s', $css),
            'El cajón no se mueve con una transición de `transform`.'
        );

        $this->assertSame(
            0,
            preg_match('/\.menu-lateral[^{}]*\{[^{}]*visibility\s*:/s', $css),
            'El cajón ha vuelto a esconderse con `visibility`, que en este motor no es fiable: se aparta con `inert`.'
        );
    }
}

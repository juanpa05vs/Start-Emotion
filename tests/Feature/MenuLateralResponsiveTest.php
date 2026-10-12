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
     * Especificidad CSS de un selector, como tripleta (ids, clases, tipos).
     *
     * Suficiente para los selectores propios de esta prueba; no cubre todos los
     * casos del estándar (`:is()`, pseudo-elementos anidados…), que aquí no se
     * usan. Dos especificidades se comparan por tripleta, en orden lexicográfico.
     */
    private function especificidad(string $selector): array
    {
        $ids = $clases = $tipos = 0;

        // `strspn` recibe un CONJUNTO LITERAL de caracteres, no un rango: la
        // máscara `'a-zA-Z0-9_-'` solo deja avanzar sobre esos once caracteres
        // y el índice se queda clavado en la primera letra que no esté en el
        // conjunto. Por eso se enumera el alfabeto entero.
        $identificador = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';
        $letras = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-';

        // Divide los selectores simples separados por combinadores y comas.
        $compuestos = preg_split('/[\s>+~]+|,/', $selector, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($compuestos as $compuesto) {
            $longitud = strlen($compuesto);
            $i = 0;

            // Un nombre de elemento solo suma si abre el selector simple: en
            // `html.js` el `html` cuenta como tipo y el `.js` como clase.
            $abre = true;

            while ($i < $longitud) {
                $ch = $compuesto[$i];

                if ($ch === '#') {
                    $ids++;
                    $i += 1 + strspn(substr($compuesto, $i + 1), $identificador);
                } elseif ($ch === '.') {
                    $clases++;
                    $i += 1 + strspn(substr($compuesto, $i + 1), $identificador);
                } elseif ($ch === '[') {
                    $clases++;
                    $cierra = strpos($compuesto, ']', $i);
                    $i = $cierra === false ? $longitud : $cierra + 1;
                } elseif ($ch === ':') {
                    // Los pseudo-elementos (`::`) no suman; las pseudo-clases
                    // cuentan como una clase.
                    if (($compuesto[$i + 1] ?? '') === ':') {
                        $i += 2 + strspn(substr($compuesto, $i + 2), $letras);
                    } else {
                        $clases++;
                        $i += 1 + strspn(substr($compuesto, $i + 1), $letras);

                        // `:not()` y similares: salta hasta el cierre.
                        $abreParen = strpos($compuesto, '(', $i);
                        if ($abreParen !== false) {
                            $cierraParen = strpos($compuesto, ')', $abreParen);
                            $i = $cierraParen === false ? $longitud : $cierraParen + 1;
                        }
                    }
                } elseif ($abre) {
                    $tipos++;
                    $i += strspn(substr($compuesto, $i), $identificador);
                } else {
                    // Carácter suelto que no abre selector: no suma, pero el
                    // índice tiene que avanzar. Sin esto, cualquier combinación
                    // inesperada colgaría la prueba en un bucle infinito.
                    $i++;
                }

                $abre = false;
            }
        }

        return [$ids, $clases, $tipos];
    }

    /**
     * La transición del cajón dentro del umbral móvil.
     *
     * Captura el cuerpo de la regla `html.js .menu-lateral` que vive dentro de
     * `@media (max-width: 63.999rem)`, la que debe ganar la cascada.
     */
    private function reglaCajonMovil(): string
    {
        $css = $this->hoja();

        if (! preg_match(
            '/@media\s*\(max-width:\s*63\.999rem\)\s*\{.*?html\.js\s+\.menu-lateral\s*\{([^{}]*)\}/s',
            $css,
            $m
        )) {
            $this->fail('No se encuentra la regla `html.js .menu-lateral` dentro del umbral móvil.');
        }

        return $m[1];
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
     * El deslizamiento se declara en la regla que gana la cascada, no en una
     * que el navegador se traga.
     *
     * El defecto de H-5b era justo el caso en el que el test anterior pasaba y
     * el cajón no se deslizaba: `aside.sidebar-alpha` (0-0-1-1) gana a
     * `.menu-lateral` (0-0-1-0) y la transición de `transform` nunca llegaba al
     * navegador. Aquí se comprueba el resultado de la cascada: la regla móvil
     * `html.js .menu-lateral` (0-0-2-1) gana a `aside.sidebar-alpha`, declara
     * `transform` con los tokens compartidos de movimiento, conserva las
     * transiciones de color de los temas y no mete ninguna otra propiedad de
     * maquetación en la transición.
     *
     * PHPUnit no calcula estilos computados: esto comprueba la cascada
     * determinista sobre la fuente. El `transition-property` calculado en un
     * navegador real se verifica por separado, porque aquí no hay motor de
     * estilos.
     */
    public function test_el_cajon_se_desliza_ganando_la_cascada(): void
    {
        // Las dos reglas enfrentadas y sus pesos.
        $ganadora = $this->especificidad('html.js .menu-lateral');
        $perdedora = $this->especificidad('aside.sidebar-alpha');

        $this->assertSame([0, 2, 1], $ganadora);
        $this->assertSame([0, 1, 1], $perdedora);
        $this->assertGreaterThan(
            0,
            $ganadora <=> $perdedora,
            'El deslizamiento se declara en una regla que pierde contra `aside.sidebar-alpha`: la cascada se lo traga.'
        );

        $cuerpo = $this->reglaCajonMovil();

        $this->assertStringContainsString(
            'transform var(--movimiento-duracion) var(--movimiento-curva)',
            $cuerpo,
            'El desplazamiento no usa los tokens compartidos de duración y curva.'
        );

        $this->assertStringContainsString(
            'background-color 0.4s ease',
            $cuerpo,
            'La transición de color del cajón (temas claro/oscuro) no se conserva.'
        );

        $this->assertStringContainsString(
            'border-color 0.4s ease',
            $cuerpo,
            'La transición del filete del cajón no se conserva.'
        );

        // Solo `transform` mueve el cajón, y los colores lo pintan: ninguna
        // propiedad de maquetación entra en la transición, para no repintar el
        // contenido vecino en cada fotograma.
        preg_match('/transition\s*:\s*(.*)$/s', $cuerpo, $m);
        $transicion = $m[1] ?? '';

        foreach (['left', 'top', 'width', 'height', 'margin', 'padding'] as $propiedad) {
            $this->assertStringNotContainsString(
                $propiedad,
                $transicion,
                'El cajón no debe animar '.$propiedad.', solo `transform` y los colores.'
            );
        }
    }

    /**
     * La transición de color de los temas sigue en `aside.sidebar-alpha`.
     *
     * El cambio claro ↔ oscuro del cajón pinta el fondo y el filete con una
     * transición; ahí seguía viviendo en escritorio y no debe perderse: el fix
     * solo da más peso a la regla móvil, no descarta la de color.
     */
    public function test_el_cajon_conserva_la_transicion_de_color_de_los_temas(): void
    {
        $css = $this->hoja();

        $this->assertMatchesRegularExpression(
            '/aside\.sidebar-alpha\s*\{[^{}]*transition:[^{}]*background-color[^{}]*border-color/s',
            $css,
            '`aside.sidebar-alpha` ya no conserva las transiciones de color de los temas.'
        );
    }

    /**
     * El cajón no vuelve a esconderse con `visibility`.
     *
     * La transición retrasada de `visibility` que dejaría ver el deslizamiento
     * de salida no es fiable en todos los motores, y aquí la barra quedaba
     * visible tras cerrarse. Se aparta con `inert`, que es una garantía.
     */
    public function test_el_cajon_sigue_sin_esconderse_con_visibility(): void
    {
        $css = $this->hoja();

        $this->assertSame(
            0,
            preg_match('/\.menu-lateral[^{}]*\{[^{}]*visibility\s*:/s', $css),
            'El cajón ha vuelto a esconderse con `visibility`: se aparta con `inert`.'
        );
    }

    /**
     * El movimiento reducido deja el deslizamiento instantáneo.
     *
     * La sección 8 anula `transition-duration` de TODO el documento con
     * `!important`, así que la especificidad de la regla ganadora no puede
     * contrarrestarla: quien pida menos movimiento ve el cajón aparecer sin
     * deslizarse, que es exactamente lo que pide.
     */
    public function test_el_movimiento_reducido_deja_el_cajon_instantaneo(): void
    {
        $css = $this->hoja();

        $this->assertSame(
            1,
            preg_match(
                '/@media\s*\(prefers-reduced-motion:\s*reduce\)\s*\{.*?transition-duration:\s*0\.001ms\s*!important/s',
                $css
            ),
            'El bloque de movimiento reducido ya no deja el deslizamiento sin duración.'
        );
    }
}

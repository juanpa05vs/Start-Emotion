/* ───────────────────────────────────────────────────────────────────────────
   Start-Emotion · interacciones de página

   Aquí solo hay dos cosas, y las dos se resuelven sin dependencias: alternar
   módulos y avisar de los envíos que Tardan. Todo lo demás vive en atributos
   `data-*` del HTML, para que el comportamiento se pueda leer leyendo la
   plantilla.

   No hay Alpine.js ni Vue ni nada parecido en `package.json`. Añadir una
   biblioteca de reactividad para esto sería pagar una dependencia completa por
   unos treinta event listeners.
   ─────────────────────────────────────────────────────────────────────────── */

/** Respeta la preferencia del sistema. Se consulta en cada cambio y no se
 *  cachea, porque alguien puede activarla desde el sistema operativo con la
 *  pestaña ya abierta. */
const sinMovimiento = () =>
  window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const DURACION_MODULO = 200;

/* La curva es la MISMA que `--movimiento-curva` en `resources/css/app.css`: el
 * lenguaje de movimiento de la aplicación tiene una sola curva, y este es el
 * único sitio que la reescribe porque WAAPI necesita el valor en JavaScript, no
 * puede leer una variable CSS. Si cambia allí, cambia aquí; por eso tiene
 * nombre propio en lugar de ir suelta dentro de la llamada. */
const CURVA_MODULO = 'cubic-bezier(0.16, 1, 0.3, 1)';

/* Aviso de que el módulo ya es visible y medible.
 *
 * Sin esto, cualquier cosa que necesite dimensiones reales —una gráfica, un
 * mapa, un contador de líneas— se inicializa con 0×0 si su módulo no era el
 * primero, y no vuelve a dibujarse sola.
 *
 * Va en su propia función porque tiene que salir en los DOS caminos: con
 * animación y sin ella. Cuando se escribía solo al final del camino animado,
 * quien tuviera «reducir movimiento» activado en el sistema se quedaba con la
 * gráfica en blanco y no tenía forma de recuperarla sin recargar. */
const avisarVisible = (panel) =>
  panel.dispatchEvent(new CustomEvent('modulo:visible', { bubbles: false }));

/* ── Módulos ────────────────────────────────────────────────────────────────
 *
 * Un grupo de pestañas y sus paneles. La marca para saber qué panel pertenece a
 * qué pestaña es `data-modulo="<nombre>"` en los dos lados: la pestaña lo lleva
 * en `data-modulo-panel` apuntando al panel que controla, y el panel lleva su
 * propio nombre en `data-modulo`. Es la misma información puesta en dos sitios,
 * pero evita depender de que el orden del HTML coincida con el de las pestañas,
 * que es el error clásico al reordenar una lista dentro de medio año.
 *
 * El estado visible lo lleva el atributo `hidden`, no una clase: es lo que leen
 * el lector de pantalla y la búsqueda por teclado. Por eso el panel inicial se
 * establece en el HTML y no al hacer clic, y un módulo mal puesto se vería desde
 * el principio en lugar de desaparecer de golpe.
 */
function conectarModulos() {
  document.querySelectorAll('[data-modulos]').forEach((grupo) => {
    const pestanas = Array.from(
      grupo.querySelectorAll('[data-modulo-panel]')
    ).filter((el) => el.matches('button, a, [role="tab"]'));

    if (pestanas.length < 2) return;

    const paneles = new Map();
    pestanas.forEach((pestana) => {
      const panel = grupo.querySelector(
        `[data-modulo="${CSS.escape(pestana.dataset.moduloPanel)}"]`
      );
      if (panel) paneles.set(pestana, panel);
    });

    if (paneles.size < 2) return;

    let animacionActual = null;

    const mostrar = (pestana) => {
      if (pestana.getAttribute('aria-selected') === 'true') return;

      // Una animación en curso se cancela antes de empezar la siguiente. Sin
      // esto, si se pulsan dos pestañas seguidas, la primera sigue adelante
      // después de la segunda y deja a la vista el panel equivocado.
      if (animacionActual) animacionActual.cancel();

      pestanas.forEach((otra) => {
        const activa = otra === pestana;
        otra.setAttribute('aria-selected', activa ? 'true' : 'false');
        // `roving tabindex`: el grupo es un solo punto de entrada al teclado y
        // dentro se navega con las flechas. Si todas las pestanas fueran
        // tabulables, Tab recorreria el grupo entero antes de llegar al panel.
        otra.setAttribute('tabindex', activa ? '0' : '-1');
      });

      paneles.forEach((panel, otraPestana) => {
        if (otraPestana === pestana) return;
        panel.hidden = true;
        panel.getAnimations?.().forEach((a) => a.cancel());
      });

      const entrante = paneles.get(pestana);
      entrante.hidden = false;

      if (sinMovimiento()) {
        // Sin animación no hay a quién esperar, así que el aviso sale ahora.
        avisarVisible(entrante);
        return;
      }

      // La entrada es opacidad más un desplazamiento corto hacia arriba. Se usa
      // `Element.animate()` y no una clase CSS porque alternar dos bloques pide
      // saber cuándo ha terminado la salida antes de decidir cuál queda: eso es
      // una promesa, y con WAAPI el navegador limpia la animación al terminar
      // sin dejar clases que luego haya que quitar a mano.
      animacionActual = entrante.animate(
        [
          { opacity: 0, transform: 'translateY(8px)' },
          { opacity: 1, transform: 'none' },
        ],
        {
          duration: DURACION_MODULO,
          easing: CURVA_MODULO,
        }
      );

      animacionActual.finished
        .catch(() => {})
        .then(() => avisarVisible(entrante));
    };

    pestanas.forEach((pestana) => {
      const activa = pestana.getAttribute('aria-selected') === 'true';
      pestana.setAttribute('aria-selected', activa ? 'true' : 'false');
      if (!pestana.hasAttribute('tabindex')) {
        pestana.setAttribute('tabindex', activa ? '0' : '-1');
      }

      pestana.addEventListener('click', (evento) => {
        evento.preventDefault();
        mostrar(pestana);
        // El foco se queda en la pestaña que se acaba de pulsar: es donde está
        // la persona. Moverlo al panel la obligaría a recorrerlo entero con Tab
        // para volver.
        pestana.focus();
      });
    });

    // Flechas izquierda y derecha: es lo que espera quien ya conoce el patrón de
    // pestañas, y con teclado es la única forma de recorrerlas sin tabular por
    // todas una a una.
    grupo.addEventListener('keydown', (evento) => {
      const desplazamiento =
        evento.key === 'ArrowRight' ? 1 : evento.key === 'ArrowLeft' ? -1 : 0;

      if (desplazamiento === 0) return;

      const actual = pestanas.findIndex(
        (p) => p.getAttribute('aria-selected') === 'true'
      );
      if (actual < 0) return;

      evento.preventDefault();
      const siguiente =
        pestanas[(actual + desplazamiento + pestanas.length) % pestanas.length];
      mostrar(siguiente);
      siguiente.focus();
    });

    // Los paneles que no sean el inicial se ocultan sin animar, con estado
    // inicial. Un panel oculto no necesita transición, y animarlo al arrancar
    // produciría un parpadeo en cada carga de página.
    const inicial =
      pestanas.find((p) => p.getAttribute('aria-selected') === 'true') ??
      pestanas[0];
    paneles.forEach((panel, pestana) => {
      panel.hidden = pestana !== inicial;
    });
  });
}

/* ── Envíos que tardan ──────────────────────────────────────────────────────
 *
 * Un envío que toca la base de datos y recalcula una evaluación puede tardar
 * dos o tres segundos. Sin aviso, quien está esperando vuelve a pulsar y
 * duplica la fila.
 *
 * Solo se aplica a los formularios marcados con `data-envio-unico`. No se
 * detecta por heurística: adivinar qué envíos son lentos desde el HTML es
 * fragile, y un formulario que se bloquea por error —uno que falla la
 * validación del servidor y hay que reenviar— deja la pantalla muerta.
 */
function conectarEnviosUnicos() {
  document.addEventListener('submit', (evento) => {
    const formulario = evento.target;
    if (!(formulario instanceof HTMLFormElement)) return;
    if (!formulario.matches('[data-envio-unico]')) return;
    if (formulario.dataset.envioEnCurso === '1') return;

    formulario.dataset.envioEnCurso = '1';

    const boton = formulario.querySelector(
      'button[type="submit"], .btn--principal'
    );
    if (!boton) return;

    boton.dataset.etiquetaOriginal = boton.textContent.trim();
    boton.setAttribute('aria-busy', 'true');
    boton.disabled = true;
    boton.textContent = 'Guardando…';
  });
}

/* ── Barra de intensidad ─────────────────────────────────────────────────────
 *
 * La barra del registro emocional. Antes vivía en dos atributos `oninput` y
 * `onchange` escritos a mano dentro de la plantilla: uno pintaba el número y otro
 * cambiaba los rótulos según la emoción. Los dos desaparecen al llegar aquí.
 *
 * LA ESCALERA son diez peldaños sobre el rango que declare el propio input, no
 * sobre números fijos: se reparte entre `min` y `max`, así que si algún día la
 * barra pasa a 1-10 en lugar de 1-100 los diez peldaños se estiran solos y no hay
 * que tocar ni esta tabla ni el cálculo. Con el `min="1"` que lleva ahora, el
 * primer peldaño es 1-10 y el `Math.max` de abajo cubre el 0 por si alguien
 * pusiera `min="0"` más adelante.
 *
 * El ancho de la escalera son diez palabras y NO diez colores: en diez puntos
 * seguidos no se distinguen dos rosados, y un color que aparece en un solo
 * control deja de ser de la marca. De los tres tonos, solo el alto late.
 */
const NIVELES_INTENSIDAD = [
  'Mínima',
  'Muy baja',
  'Baja',
  'Moderada baja',
  'Media',
  'Media alta',
  'Moderada alta',
  'Alta',
  'Muy alta',
  'Máxima',
];

const TONOS_INTENSIDAD = [
  'bajo',
  'bajo',
  'bajo',
  'bajo',
  'bajo',
  'medio',
  'medio',
  'medio',
  'alto',
  'alto',
];

/* Lo que se lee mientras no haya ninguna emoción elegida.
 *
 * No es un texto de reserva ante un error: es el estado NORMAL de la pantalla al
 * abrirla, porque el campo de emoción es obligatorio y no lleva valor por defecto.
 * La barra está ahí y se puede mover, pero qué mide todavía no está dicho, y estos
 * tres rótulos no affirmed nada que no se haya elegido.
 *
 * Los tres aparecen también escritos en la plantilla, y tienen que ser los mismos
 * caracteres: si el marcado dijera una cosa y esto otra, la página cambiaría de
 * texto sola al cargar el guion. `IntensidadBarraTest` lo comprueba sobre el HTML
 * real, no comparando estas dos listas consigo mismas. */
const LECTURA_SIN_EMOCION = {
  titulo: 'Con qué intensidad',
  min: 'Intensidad mínima',
  max: 'Intensidad máxima',
};

/* El significado de la escala depende de la emoción elegida, y ese texto vive en
 * la vista: es contenido, no comportamiento. Va en un bloque JSON que la
 * plantilla coloca junto a la barra, y se lee una sola vez al conectar. */
function leerLecturas(raiz) {
  const bloque = raiz.querySelector('script[data-lecturas-escala]');

  if (!bloque) return null;

  try {
    return JSON.parse(bloque.textContent);
  } catch (error) {
    // Un JSON mal escrito no puede dejar la barra sin cifras: se avisa en
    // consola y el resto de la conexión sigue adelante.
    console.error('No se han podido leer las lecturas de la escala de intensidad.', error);
    return null;
  }
}

function conectarIntensidad() {
  document.querySelectorAll('[data-intensidad]').forEach((raiz) => {
    const barra = raiz.querySelector('input[type="range"]');

    if (!barra) return;

    const cifras = raiz.querySelector('[data-intensidad-cifra]');
    const nivel = raiz.querySelector('[data-intensidad-nivel]');
    const titulo = raiz.querySelector('[data-intensidad-titulo]');
    const minimo = raiz.querySelector('[data-intensidad-minimo]');
    const maximo = raiz.querySelector('[data-intensidad-maximo]');
    const lecturas = leerLecturas(raiz);

    const aplicarLectura = (nombre) => {
      // Una familia que no esté escrita en el bloque JSON cae también aquí: es
      // preferible un rótulo que no afirma nada a uno que afirma la emoción
      // equivocada.
      const lectura = (lecturas && lecturas[nombre]) || LECTURA_SIN_EMOCION;

      if (titulo) titulo.textContent = lectura.titulo;
      if (minimo) minimo.textContent = lectura.min;
      if (maximo) maximo.textContent = lectura.max;
    };

    const actualizar = () => {
      const valor = Number(barra.value);
      const min = Number(barra.min || 0);
      const max = Number(barra.max || 100);
      const peldaños = NIVELES_INTENSIDAD.length;
      const ancho = (max - min + 1) / peldaños;

      const indice = Math.max(
        0,
        Math.min(peldaños - 1, Math.floor((valor - min) / ancho))
      );

      const palabra = NIVELES_INTENSIDAD[indice];
      const porcentaje = Math.round(((valor - min) / (max - min)) * 100);

      if (cifras) cifras.textContent = `${valor}%`;
      if (nivel) {
        nivel.textContent = palabra;
        // El tono se marca con un atributo y no con una clase: es lo que ya hace
        // `.dato-estado` en el juego, y es la forma correcta de que un cambio de
        // aspecto sea un cambio de estado y no un reemplazo de nodo.
        nivel.dataset.nivel = TONOS_INTENSIDAD[indice];
      }

      // El degradado de la pista se corta aquí, en el mismo momento que cambia
      // la palabra. Si se escribiera en otro sitio, el número y la barra podrían
      // contradecirse un instante.
      barra.style.setProperty('--avance', `${porcentaje}%`);

      // `aria-valuetext`, no una región `aria-live`. Una región viva se anuncia
      // esté o no esté enfocada, y esta cambia en cada `input`: arrastrar la
      // barra encolaría decenas de mensajes. `aria-valuetext` es el atributo que
      // el propio range usa para decir en palabras lo que vale, y el lector lo
      // pronuncia al cambiar el valor, que es lo que se quiere oír.
      barra.setAttribute('aria-valuetext', `${palabra}, ${valor} por ciento`);
    };

    barra.addEventListener('input', actualizar);

    // Las opciones de emoción NO están dentro de `[data-intensidad]`: viven en el
    // `<fieldset>` de arriba, y el widget es una de las tres columnas de la fila
    // de al lado. Buscarlas con `raiz.querySelectorAll` devolvía una lista vacía,
    // que no da error: el bucle no iteraba, el oyente `change` no se instalaba en
    // ninguna de las nueve y cambiar de emoción no repintaba un solo rótulo. La
    // página parecía funcionar porque todo lo demás del widget sí estaba conectado.
    //
    // Lo que comparte las dos mitades es el formulario, así que es el formulario
    // el ámbito. Con un solo widget por formulario da igual; si algún día hubiera
    // dos, este código los cruzaría y habría que pasar a emparejarlos por otro
    // identificador.
    const ambito = raiz.closest('form') || raiz;

    ambito.querySelectorAll('[data-escala]').forEach((opcion) => {
      opcion.addEventListener('change', () => {
        // Solo cambian los rótulos. La medida no se repinta, y no por descuido:
        // el valor de la barra no ha cambiado, así que `actualizar()` volvería a
        // escribir las mismas cuatro cosas que ya están escritas. Se comprobó
        // quitando esta llamada, y la barra quedó idéntica.
        //
        // La palabra del nivel tampoco depende de la emoción: los diez peldaños
        // son los mismos para las cuatro familias. Lo que cambia con la emoción es
        // QUÉ mide la escala —sobrecarga, desgano, energía, calma—, y eso vive en
        // el título y en los dos extremos, que es lo que se repinta aquí.
        aplicarLectura(opcion.dataset.escala);
      });
    });

    // El estado inicial se aplica al conectar, no se espera al primer movimiento.
    //
    // Al cargar NO hay ninguna opción marcada: el campo es `required` a propósito
    // y sin valor por defecto, porque prefijar «felicidad» haría que quien solo
    // pulse enviar quedara registrado como feliz. El navegador no marca la primera
    // hasta que valida el envío, así que en esta pantalla inicial no hay ninguna.
    //
    // Por eso `aplicarLectura` recibe `null` y escribe la lectura genérica. Antes
    // el marcado traía los rótulos de la primera emoción y esta llamada los
    // pisaba al conectar: durante un instante la página decía una cosa y luego
    // otra. Los dos textos tienen que ser el mismo, y el que gana es el genérico.
    const marcada = ambito.querySelector('[data-escala]:checked');
    aplicarLectura(marcada ? marcada.dataset.escala : null);
    actualizar();
  });
}

/* ── Paneles plegables ──────────────────────────────────────────────────────
 * El detalle de una anotación del historial se abre y se cierra con una
 * animación de altura. Se escribe con `Element.animate()` y no en CSS por el
 * mismo motivo que las pestañas: hay que esperar a que la altura llegue a cero
 * antes de esconder la fila, y eso es una promesa.
 *
 * Solo se animan la altura y la opacidad; el ancho no se toca. El alto de
 * partida se MIDE antes de animar, de modo que el panel se adapta a su
 * contenido —una nota corta o larga— sin un valor fijo que se quede corto.
 *
 * Con movimiento reducido no hay animación: la fila se muestra u oculta de
 * golpe, que es justo lo que ha pedido quien tiene activada esa preferencia. */
function conectarPlegables() {
  document.querySelectorAll('[data-plegable]').forEach((boton) => {
    const id = boton.dataset.plegableObjetivo;
    const panel = id ? document.getElementById(id) : null;
    if (!panel) return;

    const cuerpo = panel.querySelector('[data-plegable-cuerpo]') ?? panel;

    // El estado se lee de la clase `hidden` que ya trae la fila en el marcado
    // —no del atributo booleano `hidden`: el proyecto oculta con la clase de
    // Tailwind, y el atributo nunca llega a ponerse.
    const plegado = () => panel.classList.contains('hidden');
    let animacion = null;

    // Cancelar y limpiar deja el cuerpo como recién plegado, sin alturas en
    // línea que sobrevivan al siguiente clic.
    const despejar = () => {
      if (animacion) {
        animacion.cancel();
        animacion = null;
      }
      cuerpo.style.height = '';
      cuerpo.style.overflow = '';
    };

    const animar = (desde, hasta, alTerminar) => {
      const actual = cuerpo.animate(
        [
          { height: `${desde}px`, opacity: desde === 0 ? 0 : 1 },
          { height: `${hasta}px`, opacity: hasta === 0 ? 0 : 1 },
        ],
        { duration: DURACION_MODULO, easing: CURVA_MODULO, fill: 'both' }
      );

      animacion = actual;

      actual.finished
        .catch(() => {})
        .then(() => {
          // Si otra animación ha tomado el relevo, esta ya no manda: solo
          // limpia y esconde quien sigue siendo la actual.
          if (animacion !== actual) return;
          cuerpo.style.height = '';
          cuerpo.style.overflow = '';
          animacion = null;
          actual.cancel();
          if (alTerminar) alTerminar();
        });
    };

    const abrir = () => {
      despejar();
      panel.classList.remove('hidden');
      boton.setAttribute('aria-expanded', 'true');

      if (sinMovimiento()) return;

      const alto = cuerpo.offsetHeight;
      cuerpo.style.overflow = 'hidden';
      cuerpo.style.height = '0px';
      animar(0, alto);
    };

    const cerrar = () => {
      despejar();
      boton.setAttribute('aria-expanded', 'false');

      if (sinMovimiento()) {
        panel.classList.add('hidden');
        return;
      }

      const alto = cuerpo.offsetHeight;
      cuerpo.style.overflow = 'hidden';
      animar(alto, 0, () => {
        panel.classList.add('hidden');
      });
    };

    boton.addEventListener('click', () => {
      if (plegado()) abrir();
      else cerrar();
    });
  });
}

/* ── Cajón lateral ─────────────────────────────────────────────────────────
 *
 * Por debajo de `lg` la barra de navegación se vuelve un cajón modal: se
 * superpone al contenido, lo oscurece con un velo, bloquea el scroll del
 * documento y contiene el foco dentro mientras está abierto. En `lg` recupera
 * su sitio de columna fija y todo esto se apaga.
 *
 * El estado cerrado no es solo un desplazamiento: en el CSS la barra sale de
 * la pantalla, pero lo que de verdad la aparta del teclado y del lector de
 * pantalla es `inert`, que aquí se pone y se quita. Con el cajón cerrado sus
 * enlaces no reciben Tab ni se anuncian; abierto, vuelven a estar disponibles.
 * El resto lo resuelve la hoja de estilo, y por eso abrir y cerrar deprisa no
 * puede dejar ningún estado a medias.
 *
 * Toda la gestión de foco —entrar al abrir, contenerlo con Tab, devolverlo al
 * cerrar— vive aquí, porque es lo único que el CSS no puede hacer.
 */
function conectarMenuLateral() {
  const disparador = document.getElementById('menu-lateral-disparador');
  const cerrarBoton = document.getElementById('menu-lateral-cerrar');
  const barra = document.getElementById('barra-lateral');
  const velo = document.getElementById('menu-lateral-velo');

  if (!disparador || !barra || !velo) return;

  // El mismo umbral que `lg` en Tailwind (`64rem`): una sola frontera.
  const escritorio = window.matchMedia('(min-width: 64rem)');
  const primerFoco = cerrarBoton ?? barra;

  let abierto = false;

  const enfocables = () =>
    Array.from(
      barra.querySelectorAll(
        'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
      )
    );

  const bloquearScroll = (bloquear) => {
    document.documentElement.style.overflow = bloquear ? 'hidden' : '';
  };

  /* Vuelve al estado cerrado. Es idempotente a propósito: se llama al cruzar el
   * umbral, al pulsar el disparador dos veces seguidas y al cerrar durante una
   * animación, y en ningún caso debe quedar velo, foco atrapado o scroll
   * bloqueado colgando. */
  const limpiar = ({ devolverFoco = false } = {}) => {
    abierto = false;

    barra.removeAttribute('data-abierto');
    barra.removeAttribute('role');
    barra.removeAttribute('aria-modal');
    velo.hidden = true;
    disparador.setAttribute('aria-expanded', 'false');
    /* Cerrado en móvil, el cajón sale del foco y del árbol de accesibilidad:
     * no basta con sacarlo de la pantalla, porque sus enlaces seguirían
     * recibiendo Tab. En escritorio es una columna normal y no se aparta. */
    barra.inert = !escritorio.matches;
    bloquearScroll(false);

    if (devolverFoco && !escritorio.matches) {
      disparador.focus({ preventScroll: true });
    }
  };

  const abrir = () => {
    if (abierto || escritorio.matches) return;
    abierto = true;

    barra.setAttribute('data-abierto', 'true');
    barra.inert = false;
    /* Semántica de diálogo modal solo mientras está abierto: el cajón atrapa el
     * foco y oscurece el fondo, así que se anuncia como tal. Cerrado vuelve a
     * ser el `aside` de siempre, sin un rol de diálogo colgando. */
    barra.setAttribute('role', 'dialog');
    barra.setAttribute('aria-modal', 'true');

    velo.hidden = false;
    disparador.setAttribute('aria-expanded', 'true');
    bloquearScroll(true);
    primerFoco.focus({ preventScroll: true });
  };

  disparador.addEventListener('click', () => {
    if (abierto) limpiar({ devolverFoco: true });
    else abrir();
  });

  velo.addEventListener('click', () => limpiar({ devolverFoco: true }));
  cerrarBoton?.addEventListener('click', () => limpiar({ devolverFoco: true }));

  // Elegir un enlace cierra el cajón. No se devuelve el foco: la navegación
  // recarga la página y el foco iría a un botón que ya no existe.
  barra.addEventListener('click', (evento) => {
    if (evento.target.closest('a[href]')) limpiar();
  });

  document.addEventListener('keydown', (evento) => {
    if (!abierto) return;

    if (evento.key === 'Escape') {
      evento.preventDefault();
      limpiar({ devolverFoco: true });
      return;
    }

    if (evento.key !== 'Tab') return;

    const destinos = enfocables();

    if (destinos.length === 0) {
      evento.preventDefault();
      barra.focus({ preventScroll: true });
      return;
    }

    const primero = destinos[0];
    const ultimo = destinos[destinos.length - 1];
    const activo = document.activeElement;

    if (evento.shiftKey && (activo === primero || activo === barra)) {
      evento.preventDefault();
      ultimo.focus({ preventScroll: true });
    } else if (!evento.shiftKey && activo === ultimo) {
      evento.preventDefault();
      primero.focus({ preventScroll: true });
    }
  });

  /* Al cruzar el umbral se limpia el estado en los dos sentidos. Si al pasar a
   * móvil el foco estaba dentro de la barra, se devuelve al disparador para no
   * dejarlo en un elemento que acaba de quedar `visibility: hidden`. */
  const alCambiarUmbral = () => {
    limpiar({
      devolverFoco: !escritorio.matches && barra.contains(document.activeElement),
    });
  };

  if (typeof escritorio.addEventListener === 'function') {
    escritorio.addEventListener('change', alCambiarUmbral);
  } else {
    escritorio.addListener(alCambiarUmbral);
  }

  limpiar();
}

function iniciar() {
  conectarModulos();
  conectarEnviosUnicos();
  conectarIntensidad();
  conectarPlegables();
  conectarMenuLateral();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', iniciar, { once: true });
} else {
  iniciar();
}
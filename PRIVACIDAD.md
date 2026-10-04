# Política de Privacidad — S-Emotion

> Documento de referencia. La versión que la persona usuaria lee dentro de la
> aplicación está en `/privacidad` (`resources/views/privacidad/politica.blade.php`).
> Si divergen, **manda la vista**: es la que se muestra en la práctica.
>
> Última actualización: 2026-05-10

---

## Principio rector

> Los datos de salud mental pertenecen a la persona que los registró.
> Se usan para que ella vea cómo está, y se comparten con un profesional
> **solo si ella lo autoriza** y mientras siga autorizado.

Todo lo que sigue son las reglas técnicas que hacen vrai ese principio. Ninguna
existe solo en este documento: cada una está cubierta por una prueba automática
en `tests/Feature/ConsentimientoTest.php` y `tests/Feature/AccesoConsentidoTest.php`.

---

## 1. Qué se guarda

| Origen | Campos | Tabla |
|---|---|---|
| Cuenta | nombre, correo, edad, contraseña (cifrada) | `usuarios` |
| Registro diario | emoción, energía, estrés, contexto, nota escrita | `registros_emociones` |
| Evaluación SISCO | puntajes por dimensión, nivel de estrés, respuestas | `evaluaciones_psicometricas` |
| Actividad del juego | latencia, ritmo de respuesta, duración, aciertos | `telemetria_gameplay` |
| Conexión con profesional | quién autorizó a quién, qué eligió compartir, fechas | `consentimientos` |

**No** se guarda: ubicación, mensajes, navegación, ni datos de terceros.
No hay herramientas de analítica ni publicidad.

---

## 2. Quién ve qué

| Persona | Alcance |
|---|---|
| El estudiante | Todo lo suyo, siempre. |
| Un profesional de psicología | **Solo** las categorías autorizadas, y solo mientras el consentimiento esté vigente. |
| El administrador del sistema | Gestión de cuentas y configuración. Ve **cifras agregadas** (matriz de confusión, accuracy, número de participantes). **No** consulta ningún resultado clínico individual: para ver datos de un estudiante necesita un consentimiento vigente, igual que cualquier otro profesional. |

Las tres filas son deliberadas y están fijadas por pruebas.

### El administrador no tiene puerta al espacio clínico

`User::esProfesional()` devuelve `true` **solo** con el rol `Psicólogo`. El
administrador no entra por ahí, y el grupo de rutas de `/psicologia/*` está
protegido con `role:Psicólogo` a secas.

Antes el administrador sí entraba, con el argumento de que en una universidad
el responsable del sistema suele ser la misma persona que da la atención. El
problema no es que eso ocurra en la realidad: es que en el código era una
**puerta**. Bastaba con ser administrador para poder generar códigos de
invitación y convertirse en destinatario de historiales clínicos, y la política
de privacidad prometía lo contrario de lo que el código hacía. Quien haga las
dos cosas tiene los dos roles en su cuenta y entra por la puerta del
psicólogo; no hay atajo.

### Agregado sí, individual no

El panel de validación científica del administrador conserva todo lo que una
tesis necesita —matriz de confusión, accuracy, macro-F1, latencias— porque
esas métricas se calculan sobre la población y **no identifican a nadie**. Lo
que se retiró fueron los dos paneles por persona: las últimas evaluaciones
SISCO con participante, estado afectivo y nivel de estrés, y las últimas
sesiones de juego con la emoción predicha frente a la real. Esa información
sigue existiendo y sigue siendo consultable, pero por la puerta del
consentimiento.

La regla no es «ocultar columnas en la vista»: `ValidacionCientificaController`
no carga las relaciones `user` en absoluto, así que los datos personales no
llegan a existir para esa pantalla. Agrega el contador de cuántos casos hay de
cada nivel de estrés, que sirve igual para calibrar.

### El código de participante no es el identificador

El dataset de investigación identificaba a cada participante con
`TESVB-SIST-` seguido de su id de usuario. Eso no era un pseudónimo: era el
identificador de la fila con otro formato, y el administrador que podía ver
el listado de cuentas lo cruzaba sin esfuerzo.

Ahora el código es un HMAC del id con la clave de la aplicación. Es estable
—las sesiones del mismo participante siguen agrupándose, que es lo que
necesitan los modelos de efectos mixtos— pero no se puede deshacer sin conocer
la clave y no permite ordenar por antigüedad de la cuenta. Sigue siendo un
pseudónimo y no un anonimato: quien tenga acceso al servidor puede calcularlo.

### La gestión de cuentas no es un directorio de personas

La pantalla `/usuarios` proyecta solo `id`, `nombre`, `rol` y `created_at`. Sin
correo, sin edad y sin actividad. El correo se eliminó porque no hace falta
para dar de alta, cambiar un rol o dar de baja una cuenta, y la edad porque
suma información sobre cada persona concreta sin aportar nada a la
administración. La proyección es explícita a propósito: añadir un campo al
modelo no debe hacerlo visible en esta pantalla por accidente.

### El «Menú de Mando» es de quien se autoobserva

Inicio, historial, evaluación SISCO, calendario, actividades y privacidad son
la herramienta de trabajo de quien se está observando a sí misma. Bajo el
middleware `estudiante`, el administrador **no** entra ahí: administra cuentas.
El profesional que solo tiene ese rol tampoco, porque atiende en vez de
autoobservarse.

Dos decisiones detrás de esto:

- **La restricción está en la ruta, no en el menú.** Esconder el enlace deja
  la URL funcionando para quien la conozca. `EsEstudiante` devuelve 403, y el
  menú solo avisa de qué pantallas existen.
- **`/configuracion` queda fuera a propósito.** Cambiar la contraseña o el
  tema es administrar la propia cuenta y le sirve a los tres tipos. Si
  estuviera dentro del bloque de autoobservación, el administrador se quedaría
  sin poder cambiar su clave.

Quien haga las dos cosas lleva los dos roles y conserva ambos espacios. La
restricción es por tipo de cuenta, no por persona.

### Cada tipo entra por su propia pantalla

`User::rutaDeInicio()` decide dónde aterriza cada quien: el estudiante en su
panel, el psicólogo en sus estudiantes, el administrador en la gestión de
cuentas. Sin esto el inicio de sesión terminaba siempre en `/dashboard`, que
para dos de los tres tipos es un 403 — un fallo justo después de autenticarse.

---

## 3. Cómo funciona el consentimiento

El estudiante nunca es "agregado" por el profesional. El flujo es siempre
iniciado por el estudiante:

```
Profesional                    Estudiante
    │                            │
    ├─ genera un código ────────►│
    │   (vence en 14 días,       │
    │    un solo uso)            │
    │                            │
    │                  introduce el código
    │                  y ELIGE qué compartir
    │                  (registros / evaluaciones / juego)
    │                            │
    │◄───── ve solo eso ─────────┤
    │                            │
    │                  puede retirarlo cuando quiera
    │                  → el acceso termina en el acto
```

### Decisiones de diseño y por qué

- **Código de invitación, no lista de estudiantes.** Un listado permitiría al
  profesional "elegir" a quién contactar. El código garantiza que la conexión la
  acepta quien tiene el código.

- **Alfabeto sin caracteres ambiguos.** El código se lee en voz alta o se teclea
  mirando un papel. Se excluyeron `I`, `O`, `0`, `1`, `L`, `S`: un `8` por un `B`
  no puede fallar en silencio.

- **El alcance se guarda separado del sí/no.** Poder compartir los registros
  diarios sin ceder la evaluación psicométrica completa es una diferencia real
  para quien está en terapia.

- **La revocación no borra la fila.** Conserva fechas (quién, cuándo, hasta
  cuándo), no contenido. Es el registro auditable que un comité de ética pide, y
  es también lo que permite al estudiante comprobar a posteriori quién tuvo
  acceso.

- **Reconectar actualiza el alcance en vez de duplicar.** "Lo que comparto" es
  una respuesta, no un registro de eventos. Sin `unique` sobre
  `(estudiante_id, profesional_id)` porque el historial sí se conserva.

---

## 4. El juego como actividad asignada

El minijuego «Código Anómalo» es una **actividad de autoobservación que el
profesional puede pedir**, no un juego con puntaje. Se usa para practicar poner
nombre a lo que uno siente.

Su telemetría no se comparte con nadie salvo que el estudiante autorice la
categoría `juego`. Esto significa que el estudiante puede practicar libremente
sin que su profesional vea nada: **el juego no es una vía de espionaje del
consentimiento**.

---

## 5. Sobre la evaluación automática

El sistema analiza patrones de uso y muestra una orientación general. **No es un
diagnóstico**, no determina ninguna condición psicológica y no reemplaza una
evaluación profesional.

Además, la salida de ese análisis **no se comparte con el profesional** aunque el
estudiante haya autorizado sus registros diarios: el análisis automático es
propiedad del estudiante por defecto. Para que un profesional lo consultara haría
falta una decisión de producto explícita, no un efecto colateral del
consentimiento.

---

## 6. Derechos del estudiante

| Derecho | Dónde se ejerce |
|---|---|
| Saber qué compartí, con quién y desde cuándo | `/mi-privacidad` → «Lo que compartes ahora» + histórico |
| Cambiar las categorías compartidas | `/mi-privacidad` → reenviar un código nuevo |
| Retirar el consentimiento | `/mi-privacidad` → «Retirar acceso», efecto inmediato |
| Eliminar su cuenta y sus datos | Solicitud al área responsable |

Retirar el consentimiento **no pide justificación y no exige una segunda
confirmación**: poner una barrera extra sería una forma sutil de impedir que la
persona ejerza su derecho.

---

## 7. Seguridad

- Contraseñas con hash; nunca se muestran ni se registran en logs.
- Códigos de autorización para los roles de profesional y administrador
  (`ADMIN_MASTER_KEY`), comparados con `hash_equals` para evitar ataques por
  temporización.
- Límites de peticiones: 10/min en inicio de sesión, 20/min en registro,
  60/min en envío de telemetría. El de registro es más alto porque cada
  intento fallido usa uno y hace falta poder probar los tres tipos de cuenta
  sin quedarse sin margen a mitad de la primera.
- Restricción de acceso a los registros clínicos por consentimiento vigente,
  verificada con `abort_unless` en cada ruta, no por ocultar enlaces.

---

## 8. Responsabilidad

S-Emotion es una herramienta de apoyo y autoobservación. **No sustituye la
atención psicológica ni psiquiátrica.**

---

## Mantenimiento de este documento

Si cambias una regla de acceso, actualiza en el mismo commit:

1. La vista `resources/views/privacidad/politica.blade.php` (lo que se lee).
2. Este archivo (lo que explica el porqué).
3. La prueba correspondiente: `tests/Feature/ConsentimientoTest.php` para el
   consentimiento, `tests/Feature/PrivacidadAdminTest.php` para lo que ve el
   administrador.

Un cambio de reglas sin las tres cosas deja el sistema por debajo de su propia
documentación.

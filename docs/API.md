# API (Rutas web)

> S-Emotion no expone API REST JSON pública independiente fuera de las rutas web. La mayoría de endpoints son formularios HTTP con redirects/flash. El único endpoint JSON es `POST /terminal/minijuegos/telemetria` (telemetría del minijuego).

Todas las rutas requieren autenticación (`auth`) salvo `GET /`, `GET /privacidad`, `GET /login`, `GET /registrar`, `POST /login`, `POST /registrar`. Middleware/throttling aplicados según sección. Roles: `estudiante` (middleware personalizado), `Psicólogo` (`role:Psicólogo`), `Administrador` (`role:Administrador`).

## 1. Públicas

| Método | Ruta | Controlador/Método | Descripción | Params/Notas |
|---|---|---|---|---|
| GET | `/` | Closure | Página de bienvenida |  |
| GET | `/privacidad` | `PrivacidadController@politica` | Política de privacidad (pública) | Debe leerse antes de registrarse |
| GET | `/login` | `AuthController@showLogin` | Formulario login |  |
| POST | `/login` | `AuthController@login` | Autenticación | throttle:10,1. Body: `correo` (email), `contrasena` (string), `rol` (nullable: `estudiante` \| `Psicólogo` \| `Administrador`). Verifica tipo declarado vs cuenta real; regenerates session; redirect a `rutaDeInicio()` |
| GET | `/registrar` | `AuthController@showRegister` | Formulario registro |  |
| POST | `/registrar` | `AuthController@register` | Crear cuenta | throttle:20,1. Body: `nombre` (string max100), `edad` (int 15-99), `correo` (email unique), `contrasena` (min8 confirmed), `rol` (required in tipos), `admin_token` (required si no estudiante, max255). Valida ADMIN_MASTER_KEY con `hash_equals`. Asigna rol (campo `rol` explícito + Spatie). Login automático y redirect a inicio. |

## 2. Autenticado (`auth`)

### 2.1 Espacio de autoobservación (middleware `estudiante`)
Protege: `/dashboard`, `/emociones`, `/historial*`, `/perfil/calendario`, `/evaluacion-psicometrica`, `/terminal/minijuegos*`, `/mi-privacidad*`.

| Método | Ruta | Controlador/Método | Descripción | Params/Notas |
|---|---|---|---|---|
| GET | `/dashboard` | Closure (web.php) | Panel estudiante | Carga último registro emoción (`$user->emociones()->latest()->first()`) |
| POST | `/emociones` | `EmocionController@store` | Registrar emoción | Body: `emocion` (in: felicidad,entusiasta,productivo,relajado,tristeza,melancolia,agotado,ansioso,ira), `energia` (int 1-100), `observaciones` (string max1000 nullable), `contexto` (nullable in: General,Exámenes,Proyecto,Clases). Calcula análisis (stress+recomendación+burnout). Crea vía relación `auth()->user()->emociones()`. Redirect dashboard con status. |
| DELETE | `/emociones/{id}` | `EmocionController@destroy` | Eliminar registro propio | Elimina registro del usuario autenticado (`findOrFail` + relación). Back. |
| GET | `/historial` | `EmocionController@index` | Historial emociones | `auth()->user()->emociones()->latest()->get()` |
| GET | `/historial/reporte` | `EmocionController@generarPDF` | Descargar PDF reporte | DomPDF: `reportes.emociones` con historial+user. `Reporte_Neural_S-Emotion_{nombre}.pdf` |
| GET | `/perfil/calendario` | `EmocionController@verCalendario` | Calendario/heatmap energía | Agrupa por día (PHP, formato Y-m-d). Rango mes actual con huecos iniciales (dayOfWeekIso). |
| DELETE | `/historial/reiniciar` | `EmocionController@reiniciarHistorial` | Borrar todo historial | `emociones()->delete()` (soft cascade por FK). Back. |
| POST | `/historial/eliminar-seleccionados` | `EmocionController@eliminarSeleccionados` | Borrar selección | Body: `ids` (array int, exists registros_emociones,id). Solo registros propios (`whereIn` + relación). Back. |
| GET | `/evaluacion-psicometrica` | `EvaluacionPsicometricaController@create` | Formulario SISCO |  |
| POST | `/evaluacion-psicometrica` | `EvaluacionPsicometricaController@store` | Guardar evaluación SISCO | Calcula puntajes/nivel estrés/estado afectivo; guarda `respuestas_detalle` JSON + relación user. Redirect dashboard. |
| GET | `/terminal/minijuegos` | `DiagnosticoController@index` | Listado minijuegos |  |
| GET | `/terminal/minijuegos/diagnostico` | `DiagnosticoController@diagnostico` | Vista minijuego "Código Anómalo" |  |
| POST | `/terminal/minijuegos/telemetria` | `DiagnosticoController@guardarTelemetria` | Guardar telemetría (JSON) | **throttle:60,1**. Body JSON: `minijuego_id` (string), `latencia_promedio_ms` (numeric 0-120000), `frecuencia_tapping` (numeric 0-30), `tiempo_total_ms` (int 0-300000), `conteo_rectificaciones` (int 0-1000), `errores_diagnostico` (int 0-1000), `score_final` (int 0-100), `emocion_objetivo` (in juego), `emocion_predicha` (in juego∪rf∪['ninguno']), `diagnostico_correcto` (bool), `vector_caracteristicas` (array size5, cada elem validado 0-max). Llama `RandomForestService@predecir`, guarda `TelemetriaGameplay` con `vector_caracteristicas` mergeado (confianza_ia, probabilidades, seleccion_operador), user_id=Auth::id(). **Respuesta 201 JSON**: `{ status, message, id, ia_output:{ prediccion, confianza, probabilidades, etiqueta, ... } }` |
| GET | `/mi-privacidad` | `PrivacidadController@index` | Panel privacidad/consentimientos | Carga `consentimientosOtorgados` (vigentes+revocados) con profesional, `historico` revocados, `opciones = AlcanceConsentimiento::cases()` |
| POST | `/mi-privacidad/conectar` | `PrivacidadController@conectar` | Canjear código + otorgar/actualizar consentimiento | Body: `codigo` (string max16), `alcance` (array min1), `alcance.*` (in AlcanceConsentimiento::valores()). Busca invitación vigente (`buscarVigente`, case-insensitive, no usada, no expirada). Rechaza si profesional destino no es Psicólogo. Si existe consentimiento vigente entre ellos -> **actualiza alcance** (no duplica). Marca invitación `usada_en` + `usada_por`. Redirect `/mi-privacidad`. Errores en `codigo`/`alcance`. |
| POST | `/mi-privacidad/consentimientos/{consentimiento}/revocar` | `PrivacidadController@revocar` | Revocar consentimiento | Body opcional: `motivo` (string max500 nullable). Verifica propiedad (`estudiante_id === user->id`) -> `consentimiento->revocar(motivo)` (set `revocado_en=now()`, guarda motivo). Redirect `/mi-privacidad`. |

### 2.2 Espacio profesional (middleware `role:Psicólogo`)
Solo rol `Psicólogo`. Verificación estricta de consentimiento.

| Método | Ruta | Controlador/Método | Descripción | Params/Notas |
|---|---|---|---|---|
| GET | `/psicologia/pacientes` | `PsicologiaController@pacientes` | Listado pacientes con consentimiento vigente | `Consentimiento::vigentes()->deProfesional($prof->id)->with('estudiante')->latest('otorgado_en')` |
| GET | `/psicologia/invitaciones` | `PsicologiaController@invitaciones` | Códigos de invitación del profesional | Orden por `created_at desc`, `with('usadaPor')` |
| POST | `/psicologia/invitaciones` | `PsicologiaController@crearInvitacion` | Generar código invitación | Crea `InvitacionPsicologo`: código único (alfabeto sin ambiguos, 8 chars), `expira_en = now()+14d`, `profesional_id`. Redirect `/psicologia/invitaciones`. |
| DELETE | `/psicologia/invitaciones/{invitacion}` | `PsicologiaController@revocarInvitacion` | Anular código no usado | Verifica propiedad (`profesional_id === prof->id`). Si `usada_en===null` -> DELETE, else no borra (permanece trazabilidad). Redirect. |
| GET | `/psicologia/pacientes/{consentimiento}` | `PsicologiaController@paciente` | Ficha paciente por consentimiento | `abort_unless` profesional propietario + consentimiento vigente. Carga bloques **solo si** `incluye` alcance: `registros` (limit 60 latest), `evaluaciones` (20), `telemetrias` (40). Nunca carga todo si no autorizado. |

### 2.3 Perfil (autenticado, fuera bloque estudiante)
Disponible para todos autenticados.

| Método | Ruta | Controlador/Método | Descripción | Params/Notas |
|---|---|---|---|---|
| GET | `/configuracion` | `UsuarioController@configuracion` | Configuración cuenta (perfil/tema/avatar) |  |
| PATCH | `/perfil/update` | `UsuarioController@updatePerfil` | Actualizar perfil | Body: `nombre` (max255), `correo` (email único), `avatar` (image mimes jpeg/png/jpg/gif max2MB), `tema` (in:blue,rose,amber,purple). No cambia edad ni contraseña. |
| POST | `/perfil/feedback` | `UsuarioController@storeFeedback` | Enviar feedback | Body: `mensaje` (string min3 max1000). Guarda `Feedback` con `comentario`; `user_id` es obligatorio y se elimina en cascada con la cuenta. |

### 2.4 Administración (middleware `role:Administrador`)
Solo rol `Administrador`.

| Método | Ruta | Controlador/Método | Descripción | Params/Notas |
|---|---|---|---|---|
| GET | `/usuarios` | `UsuarioController@index` | Listado usuarios | Proyección explícita (`id,nombre,rol,created_at`) según política (sin correo/edad/actividad en listado). |
| DELETE | `/usuarios/{user}` | `UsuarioController@destroy` | Eliminar usuario |  |
| GET | `/usuarios/{user}/rol` | Closure redirect | Redirect a index | Evita acceso GET directo a updateRole. |
| PATCH | `/usuarios/{user}/rol` | `UsuarioController@updateRole` | Cambiar rol usuario | Body: `rol` (valores válidos roles). Actualiza `rol` + Spatie roles. |
| GET | `/admin/feedback` | `UsuarioController@verFeedback` | Listado feedback |  |
| DELETE | `/admin/feedback/{feedback}` | `UsuarioController@destroyFeedback` | Eliminar feedback |  |
| PATCH | `/admin/feedback/{feedback}/status` | `UsuarioController@updateFeedbackStatus` | Actualizar estado feedback | Body: `estado`. |
| GET | `/admin/validacion-cientifica` | `ValidacionCientificaController@index` | Panel validación científica (agregado) | **Sin `with('user')`**. Calcula matriz confusión sobre `TelemetriaGameplay` (normaliza con `EmocionEnum::normalizarParaRF`, clasifica descartadas), accuracy, macro-F1/Precision/Recall, métricas por clase, distribución evaluaciones (agrupada por `nivel_estres`), participantes únicos (`distinct user_id`), muestras incluidas/descartadas + mapa colapso. |
| GET | `/admin/validacion-cientifica/exportar` | `ValidacionCientificaController@exportarCSV` | Exportar dataset telemetría (CSV) | StreamedResponse. Cabeceras CSV + BOM UTF8. Chunk 500 registros. **No carga tabla usuarios**; columna `ID_Anonimizado` = `User::codigoParticipante(row->user_id)` (HMAC). Columnas: ID_Anonimizado, Minijuego, Latencia_Promedio_MS, Frecuencia_Tapping_Hz, Tiempo_Total_MS, Conteo_Rectificaciones, Errores_Cometidos, Puntaje_Integridad, Emocion_Objetivo_Real, Prediccion_RandomForest, Diagnostico_Correcto, Timestamp_Captura. |

## 3. Logout

| Método | Ruta | Controlador/Método | Descripción |
|---|---|---|---|
| POST | `/logout` | `AuthController@logout` | Logout, invalida sesión, regenerar token CSRF. Redirect `/` |

## 4. Flujos clave (resumen)

| Flujo | Pasos | Validaciones/Reglas |
|---|---|---|
| Registro (no estudiante) | GET registrar → POST /registrar con `admin_token` → valida `ADMIN_MASTER_KEY` (hash_equals) → crea user con rol explícito + Spatie → login → redirect inicio | `admin_token` requerido si rol!=estudiante; blank() protege si clave ausente |
| Login con tipo | POST /login (correo+contrasena+rol opcional) → Auth::attempt → si rol declarado y no coincide con `tiposDeCuenta()` → logout + error `rol` → else session regenerate → redirect `rutaDeInicio()` | Verificación de espacio de acceso (evita abrir psicología por error) |
| Consentimiento | Profesional genera código (POST invitaciones) → Estudiante ve `/mi-privacidad`, POST conectar (codigo+alcance) → verifica invitación vigente + profesional es Psicólogo → crea o actualiza consentimiento vigente (alcance) → marca invitación usada | Alcance min1 + valores enum válidos; código único/uso/vigencia; sin duplicado vigente |
| Revocación | Estudiante POST revocar consentimiento propio → set `revocado_en=now()` + motivo (opcional) → acceso inmediato | Propiedad obligatoria; no borra fila (histórico) |
| Acceso psicólogo | GET ficha paciente/{consentimiento} → abort_unless propietario+vigente → carga bloques según `incluye(alcance)` | Filtrado por alcance (principio mínimo necesario) |
| Telemetría | Juego POST JSON `/terminal/minijuegos/telemetria` (throttle 60/min) → valida topes numéricos + enums → `RandomForestService@predecir` → crea `TelemetriaGameplay` con vector enriquecido → 201 JSON | Guardas anti-corrupción (límites superiores amplios para reflejar partidas reales), `emocion_predicha` acepta `ninguno` |

## 5. Notas técnicas

- **Formularios web**: respuestas típicas `redirect()->route()` + `with('status'|'success'|'errors')`. 
- **Throttling**: login 10,1; registro 20,1; telemetría 60,1 (Route middleware). 
- **Validación ES**: mensajes personalizados en `lang/es/validation.php`. 
- **Privacidad por construcción**: endpoints clínicos exigen consentimiento vigente + propiedad + alcance. 
- **Export agregado**: CSV sin carga de usuarios (solo HMAC). Panel admin sin `with('user')`. 
- **Telemetría JSON**: único endpoint no-redirect con respuesta estructurada (201). 
- **Middleware personalizado**: `estudiante` (alias) protege bloque autoobservación (no role:estudiante de Spatie por compatibilidad con factories/tests). 
- **Códigos invitación**: alfabeto `ABCDEFGHJKMNPQRTUVWXY2346789` (omite I,O,0,1,L,S) para legibilidad/voz.
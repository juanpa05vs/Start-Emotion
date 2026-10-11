# S-Emotion

## Descripción general

S-Emotion es una aplicación web de autorregistro emocional y apoyo a la autoobservación, orientada a estudiantes. Permite registrar el estado afectivo, energía y contexto diario, completar la evaluación psicométrica SISCO (estrés académico), participar en un minijuego conductual ("Código Anómalo") con telemetría y un módulo de inferencia basado en Random Forest para explorar patrones comportamentales. 

El sistema integra un mecanismo de consentimiento explícito, granular y reversible: un estudiante puede autorizar a un profesional de psicología a ver solo las categorías que elija (registros diarios, evaluaciones psicométricas o telemetría del juego), mientras conserva el control total y puede revocar ese acceso en cualquier momento. 

La validación científica funciona únicamente con datos agregados y anonimización por HMAC, sin exponer historiales clínicos individuales al panel de administración.

## Propósito

- Promover la autoobservación emocional (registro diario: emoción, energía, contexto, observaciones, nivel de estrés estimado y recomendaciones).
- Facilitar la conexión con un profesional mediante códigos de invitación de un solo uso y consentimiento por alcance.
- Recoger telemetría conductual (latencia, tapping, rectificaciones, errores, score) para análisis y validación científica.
- Garantizar la separación entre espacio de autoobservación (estudiante), atención (psicólogo, solo con consentimiento vigente) y gestión (administrador, sin acceso clínico individual).

## Stack tecnológico principal

| Componente | Tecnología | Versión |
|---|---|---|
| Lenguaje | PHP | ^8.2 |
| Framework | Laravel | ^11.0 |
| ORM | Eloquent (Laravel) | 11.x |
| Autenticación/RBAC | spatie/laravel-permission | ^6.0 |
| Reportes PDF | barryvdh/laravel-dompdf | ^3.0 |
| Frontend build | Vite + Laravel Vite Plugin | ^8.0 / ^3.0 |
| CSS | Tailwind CSS | ^4.2.2 |
| Testing | PHPUnit | ^11.0 |
| Node.js | Node | >=20 (build Docker) |

## Requisitos previos

- PHP 8.2+ con extensiones: pdo, sqlite/mysql, mbstring, xml, json, curl
- Composer 2.x
- Node.js 20+ y npm
- Base de datos: SQLite (desarrollo recomendado) o MySQL/MariaDB/PostgreSQL
- (Opcional) Docker: imagen `richarvey/nginx-php-fpm:3.1.6` + Node 20 para build de assets

## Guía paso a paso (entorno de desarrollo)

### 1. Clonar e instalar dependencias

```bash
git clone <repo-url> Start-Emotion
cd Start-Emotion

# PHP
composer install

# Node (assets)
npm install
```

### 2. Configurar variables de entorno

Copiar `.env.example` a `.env`:

```bash
cp .env.example .env  # Linux/macOS
# copy .env.example .env en Windows (CMD/PowerShell)
```

Variables relevantes:

| Variable | Descripción | Valor sugerido (dev) |
|---|---|---|
| APP_NAME | Nombre de la app | S-Emotion |
| APP_ENV | Entorno | local |
| APP_DEBUG | Depuración | true |
| APP_URL | URL base | http://localhost:8000 |
| APP_LOCALE | Idioma | es |
| DB_CONNECTION | Conexión BD | sqlite (por defecto en .env.example) |
| SESSION_DRIVER | Sesión | database |
| QUEUE_CONNECTION | Cola | database |
| CACHE_STORE | Caché | database |
| FILESYSTEM_DISK | Disco | local |
| MAIL_MAILER | Correo (dev) | log |
| ADMIN_MASTER_KEY | Clave maestra para registrar Psicólogo/Administrador | Definir en .env (usar valor seguro) |

> Si usas MySQL: descomentar `DB_HOST/PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD` y cambiar `DB_CONNECTION=mysql`.

### 3. Generar clave, migraciones, seeders y assets

```bash
php artisan key:generate

# Ejecutar migraciones y seeders (crea roles/permisos)
php artisan migrate --seed

# Compilar assets (dev con hot reload o build)
npm run dev        # Vite dev server
# o
npm run build      # Build producción
```

Comando combinado (serve + queue + logs + vite): `composer run dev`

### 4. Ejecutar servidor

```bash
php artisan serve
```

Abrir `http://localhost:8000`.

### 5. Pruebas

```bash
php artisan test
```

## Estructura general del proyecto

| Carpeta | Responsabilidad |
|---|---|
| `app/` | Núcleo de la aplicación: `Http/Controllers`, `Http/Middleware`, `Models`, `Enums`, `Services` (p. ej. `RandomForestService`), `Providers`. |
| `app/Enums/` | Tipados de dominio: `AlcanceConsentimiento`, `EmocionEnum`. |
| `app/Models/` | Eloquent: User (tabla `usuarios`), RegistroEmocion, EvaluacionPsicometrica, TelemetriaGameplay, Consentimiento, InvitacionPsicologo, Feedback. |
| `app/Http/Controllers/` | Controladores web: Auth, Emocion, EvaluacionPsicometrica, Diagnostico, Privacidad, Psicologia, Usuario, ValidacionCientifica. |
| `app/Http/Middleware/` | `EsEstudiante` (reserva espacio de autoobservación). |
| `bootstrap/` | Arranque app (registro de middlewares/aliases). |
| `config/` | Configuración Laravel + `permission.php`, `auth.php` (`admin_master_key`). |
| `database/migrations/` | Esquema BD (usuarios, registros, consentimiento, invitaciones, evaluaciones, telemetría, permisos). |
| `database/seeders/` | `RoleSeeder` (roles/permisos), `DatabaseSeeder`. |
| `database/factories/` | Factories para tests. |
| `lang/es/` | Traducciones/validación en español. |
| `resources/` | Vistas Blade, CSS/JS, assets. |
| `routes/` | `web.php` (rutas principales), `console.php`. |
| `docs/` | Documentación técnica (`ARCHITECTURE.md`, `DATABASE.md`, `API.md`, `diagramas/`). |
| `tests/Feature/` | Tests funcionales (consentimiento, privacidad admin, validación científica, etc.). |
| `public/` | `index.php`, `build/` (assets compilados). |

## Notas de seguridad/privacidad

- Política pública `/privacidad`. 
- Consentimiento vigente obligatorio para ver datos clínicos (espacio `Psicólogo`). 
- Código anónimo: `User::codigoParticipante()` usa HMAC-SHA256 con `APP_KEY` (pseudónimo, no anonimato irreversible). 
- Panel validación: solo agregado, sin cargar relaciones `user` en ese controlador; CSV exporta código anónimo. 
- Throttling: login 10/min, registro 20/min, telemetría 60/min. 
- ADMIN_MASTER_KEY validado con `hash_equals`. 
- Espacio autoobservación protegido por middleware `estudiante` (no solo enlaces).
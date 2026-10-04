<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DiagnosticoController;
use App\Http\Controllers\EmocionController;
use App\Http\Controllers\EvaluacionPsicometricaController;
use App\Http\Controllers\PrivacidadController;
use App\Http\Controllers\PsicologiaController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ValidacionCientificaController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// La política de privacidad es pública: tiene que poder leerse ANTES de
// registrarse, que es cuando la persona decide si acepta o no.
Route::get('/privacidad', [PrivacidadController::class, 'politica'])->name('privacidad.politica');

// Autenticación y Registro (Gestión de Acceso)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('/registrar', [AuthController::class, 'showRegister'])->name('register');
// 20/min. Antes eran 5: con solo intentar dar de alta los tres tipos de
// cuenta (un intento por tipo) ya se agotaba el límite y Laravel devolvía un
// 429 con página en inglés, que se veía como "el registro no funciona".
Route::post('/registrar', [AuthController::class, 'register'])->middleware('throttle:20,1');

Route::middleware(['auth'])->group(function () {

    // ─────────────────────────────────────────────────────────────────────────
    // ESPACIO DE AUTOOBSERVACIÓN  ·  middleware `estudiante`
    //
    // Inicio, historial, evaluación SISCO, calendario, actividades y
    // privacidad son la herramienta de quien se está observando a sí mismo.
    //
    // El administrador no entra: administra cuentas, no se observa. El
    // profesional que solo tiene ese rol tampoco, porque atiende en vez de
    // autoobservarse. Quien haga ambas cosas lleva los dos roles y entra por
    // aquí igual que cualquier estudiante.
    //
    // `/configuracion` (contraseña, tema, avatar) queda FUERA a propósito: es
    // administrar la propia cuenta y le sirve a todo el mundo. Sin eso el
    // administrador se quedaría sin poder cambiar su clave.
    // ─────────────────────────────────────────────────────────────────────────
    Route::middleware(['estudiante'])->group(function () {

        Route::get('/dashboard', function () {
            /** @var User|null $user */
            $user = Auth::user();
            $ultimoRegistro = $user ? $user->emociones()->latest()->first() : null;

            return view('dashboard', compact('ultimoRegistro'));
        })->name('dashboard');

        // Emociones
        Route::post('/emociones', [EmocionController::class, 'store'])->name('emociones.store');
        Route::delete('/emociones/{id}', [EmocionController::class, 'destroy'])->name('emociones.destroy');

        // Historial + Reportes + Calendario
        Route::get('/historial', [EmocionController::class, 'index'])->name('historial.index');
        Route::get('/historial/reporte', [EmocionController::class, 'generarPDF'])->name('emociones.reporte');
        Route::get('/perfil/calendario', [EmocionController::class, 'verCalendario'])->name('perfil.calendario');
        Route::delete('/historial/reiniciar', [EmocionController::class, 'reiniciarHistorial'])->name('emociones.reiniciar');
        Route::post('/historial/eliminar-seleccionados', [EmocionController::class, 'eliminarSeleccionados'])->name('emociones.eliminarSeleccionados');

        // Psicometría
        Route::get('/evaluacion-psicometrica', [EvaluacionPsicometricaController::class, 'create'])->name('psicometria.create');
        Route::post('/evaluacion-psicometrica', [EvaluacionPsicometricaController::class, 'store'])->name('psicometria.store');

        // Minijuegos
        Route::get('/terminal/minijuegos', [DiagnosticoController::class, 'index'])->name('minijuegos.index');
        Route::get('/terminal/minijuegos/diagnostico', [DiagnosticoController::class, 'diagnostico'])->name('minijuegos.diagnostico');
        Route::post('/terminal/minijuegos/telemetria', [DiagnosticoController::class, 'guardarTelemetria'])
            ->name('minijuegos.telemetria')
            ->middleware('throttle:60,1'); // ~60 envíos/min por usuario (ajústalo si quieres)

        // Privacidad y consentimiento: el derecho a ver y retirar lo que
        // comparte. Solo tiene sentido para quien tiene algo que compartir, y
        // el administrador no se conecta a ningún profesional.
        Route::get('/mi-privacidad', [PrivacidadController::class, 'index'])->name('privacidad.index');
        Route::post('/mi-privacidad/conectar', [PrivacidadController::class, 'conectar'])->name('privacidad.conectar');
        Route::post('/mi-privacidad/consentimientos/{consentimiento}/revocar', [PrivacidadController::class, 'revocar'])
            ->name('privacidad.revocar');
    });

    // Espacio del profesional de psicología.
    // OJO: en spatie/laravel-permission los roles se separan con `|`. El segundo
    // parámetro de `role:` NO es otro rol, es el nombre del guard de auth, así que
    // `role:Psicólogo,Administrador` produce un 500 ("Auth guard
    // [Administrador] is not defined") en lugar de denegar el acceso.
    //
    // Solo `Psicólogo`. El administrador NO entra aquí aunque en una universidad
    // suela ser la misma persona: mantenerlo fuera es lo que hace que "el
    // administrador no ve datos clínicos" sea una regla del sistema y no una
    // promesa. Quien haga ambas cosas usa una cuenta con el rol de psicólogo.
    Route::middleware(['role:Psicólogo'])->group(function () {
        Route::get('/psicologia/pacientes', [PsicologiaController::class, 'pacientes'])->name('psicologia.pacientes');
        Route::get('/psicologia/invitaciones', [PsicologiaController::class, 'invitaciones'])->name('psicologia.invitaciones');
        Route::post('/psicologia/invitaciones', [PsicologiaController::class, 'crearInvitacion'])->name('psicologia.invitaciones.crear');
        Route::delete('/psicologia/invitaciones/{invitacion}', [PsicologiaController::class, 'revocarInvitacion'])->name('psicologia.invitaciones.revocar');
        Route::get('/psicologia/pacientes/{consentimiento}', [PsicologiaController::class, 'paciente'])->name('psicologia.paciente');
    });

    // Perfil
    Route::get('/configuracion', [UsuarioController::class, 'configuracion'])->name('perfil.config');
    Route::patch('/perfil/update', [UsuarioController::class, 'updatePerfil'])->name('perfil.update');
    Route::post('/perfil/feedback', [UsuarioController::class, 'storeFeedback'])->name('perfil.feedback');

    // Admin
    Route::middleware(['role:Administrador'])->group(function () {
        Route::resource('usuarios', UsuarioController::class)->only(['index', 'destroy']);
        Route::patch('/usuarios/{user}/rol', [UsuarioController::class, 'updateRole'])->name('usuarios.updateRole');
        Route::get('/admin/feedback', [UsuarioController::class, 'verFeedback'])->name('admin.feedback');
        Route::delete('/admin/feedback/{feedback}', [UsuarioController::class, 'destroyFeedback'])->name('feedback.destroy');
        Route::patch('/admin/feedback/{feedback}/status', [UsuarioController::class, 'updateFeedbackStatus'])->name('feedback.updateStatus');
        Route::get('/admin/validacion-cientifica', [ValidacionCientificaController::class, 'index'])->name('admin.validacion');
        Route::get('/admin/validacion-cientifica/exportar', [ValidacionCientificaController::class, 'exportarCSV'])->name('admin.validacion.exportar');

        Route::get('/usuarios/{user}/rol', function () {
            return redirect()->route('usuarios.index');
        });
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

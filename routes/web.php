<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmocionController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\DiagnosticoController;
use App\Http\Controllers\EvaluacionPsicometricaController;
use App\Http\Controllers\ValidacionCientificaController;

/*
|--------------------------------------------------------------------------
| Rutas Públicas: Capa de Acceso Inicial
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Autenticación y Registro (Gestión de Acceso)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/registrar', [AuthController::class, 'showRegister'])->name('register');
Route::post('/registrar', [AuthController::class, 'register']);

/*
|--------------------------------------------------------------------------
| Rutas Protegidas: Núcleo de Operaciones (Bio-Monitoreo)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    /**
     * --- DASHBOARD: Monitor Primario ---
     */
    Route::get('/dashboard', function () {
        /** @var \App\Models\user|null $user */
        $user = Auth::user();
        $ultimoRegistro = $user ? $user->emociones()->latest()->first() : null;
        return view('dashboard', compact('ultimoRegistro'));
    })->name('dashboard');

    /**
     * --- GESTIÓN DE EMOCIONES: Captura de Datos ---
     */
    Route::post('/emociones', [EmocionController::class, 'store'])->name('emociones.store');
    Route::delete('/emociones/{id}', [EmocionController::class, 'destroy'])->name('emociones.destroy');

    /**
     * --- HISTORIAL Y ANÁLISIS: Reportes y Calendario ---
     */
    Route::get('/historial', [EmocionController::class, 'index'])->name('historial.index');
    Route::get('/historial/reporte', [EmocionController::class, 'generarPDF'])->name('emociones.reporte');
    Route::get('/perfil/calendario', [EmocionController::class, 'verCalendario'])->name('perfil.calendario');

    // Herramientas de Purga de Datos
    Route::delete('/historial/reiniciar', [EmocionController::class, 'reiniciarHistorial'])->name('emociones.reiniciar');
    Route::post('/historial/eliminar-seleccionados', [EmocionController::class, 'eliminarSeleccionados'])->name('emociones.eliminarSeleccionados');

    /**
     * --- EVALUACIÓN PSICOMÉTRICA: Gold Standard (Inventario SISCO) ---
     */
    Route::get('/evaluacion-psicometrica', [EvaluacionPsicometricaController::class, 'create'])->name('psicometria.create');
    Route::post('/evaluacion-psicometrica', [EvaluacionPsicometricaController::class, 'store'])->name('psicometria.store');

    /**
     * --- SECTOR RECREATIVO: Minijuegos de Recalibración (Gamificación) ---
     */
    // Menú Principal / Catálogo de Minijuegos
    Route::get('/terminal/minijuegos', [DiagnosticoController::class, 'index'])->name('minijuegos.index');

    // Juego 1: Adivina Quién Emocional (Código Anómalo)
    Route::get('/terminal/minijuegos/diagnostico', [DiagnosticoController::class, 'diagnostico'])->name('minijuegos.diagnostico');

    // Endpoint de Telemetría Implícita (Latencia / Tapping / Inferencia Random Forest)
    Route::post('/terminal/minijuegos/telemetria', [DiagnosticoController::class, 'guardarTelemetria'])->name('minijuegos.telemetria');

    /**
     * --- CONFIGURACIÓN DE IDENTIDAD: Terminal de Usuario ---
     */
    Route::get('/configuracion', [UsuarioController::class, 'configuracion'])->name('perfil.config');
    Route::patch('/perfil/update', [UsuarioController::class, 'updatePerfil'])->name('perfil.update');
    Route::post('/perfil/feedback', [UsuarioController::class, 'storeFeedback'])->name('perfil.feedback');

    /*
    |----------------------------------------------------------------------
    | SECTOR ADMINISTRATIVO: Control Nivel Alpha (Restringido)
    |----------------------------------------------------------------------
    |*/
    Route::middleware(['role:Administrador'])->group(function () {

        // 1. Gestión de Operadores (CRUD de Usuarios)
        Route::resource('usuarios', UsuarioController::class);
        Route::patch('/usuarios/{user}/rol', [UsuarioController::class, 'updateRole'])->name('usuarios.updateRole');

        // 2. MONITOR DE FEEDBACK: Gestión de Reportes Alpha
        Route::get('/admin/feedback', [UsuarioController::class, 'verFeedback'])->name('admin.feedback');
        Route::delete('/admin/feedback/{feedback}', [UsuarioController::class, 'destroyFeedback'])->name('feedback.destroy');
        Route::patch('/admin/feedback/{feedback}/status', [UsuarioController::class, 'updateFeedbackStatus'])->name('feedback.updateStatus');

        // 3. VALIDACIÓN DE IA: Matriz de Confusión, Métricas de Desempeño y Exportación de Datasets
        Route::get('/admin/validacion-cientifica', [ValidacionCientificaController::class, 'index'])->name('admin.validacion');
        Route::get('/admin/validacion-cientifica/exportar', [ValidacionCientificaController::class, 'exportarCSV'])->name('admin.validacion.exportar');

        // Seguridad: Evitar acceso GET a rutas de procesamiento
        Route::get('/usuarios/{user}/rol', function () {
            return redirect()->route('usuarios.index');
        });
    });

    // CIERRE DE SESIÓN SEGURO
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

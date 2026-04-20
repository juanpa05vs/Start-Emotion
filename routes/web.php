<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmocionController;
use App\Http\Controllers\UsuarioController;

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
        $ultimoRegistro = auth()->user()->emociones()->latest()->first();
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
     * --- CONFIGURACIÓN DE IDENTIDAD: Terminal de Usuario ---
     */
    Route::get('/configuracion', [UsuarioController::class, 'configuracion'])->name('perfil.config');
    Route::patch('/perfil/update', [UsuarioController::class, 'updatePerfil'])->name('perfil.update');
    Route::post('/perfil/feedback', [UsuarioController::class, 'storeFeedback'])->name('perfil.feedback');

    /*
    |----------------------------------------------------------------------
    | SECTOR ADMINISTRATIVO: Control Nivel Alpha (Restringido)
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:Administrador'])->group(function () {

        // 1. Gestión de Operadores (CRUD de Usuarios)
        Route::resource('usuarios', UsuarioController::class);
        Route::patch('/usuarios/{user}/rol', [UsuarioController::class, 'updateRole'])->name('usuarios.updateRole');

        // 2. MONITOR DE FEEDBACK: Gestión de Reportes Alpha
        // [NUEVO]: Rutas para gestión interactiva de feedback
        Route::get('/admin/feedback', [UsuarioController::class, 'verFeedback'])->name('admin.feedback');
        Route::delete('/admin/feedback/{feedback}', [UsuarioController::class, 'destroyFeedback'])->name('feedback.destroy');
        Route::patch('/admin/feedback/{feedback}/status', [UsuarioController::class, 'updateFeedbackStatus'])->name('feedback.updateStatus');

        // Seguridad: Evitar acceso GET a rutas de procesamiento
        Route::get('/usuarios/{user}/rol', function () {
            return redirect()->route('usuarios.index');
        });
    });

    // CIERRE DE SESIÓN SEGURO
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

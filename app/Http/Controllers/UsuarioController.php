<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class UsuarioController extends Controller
{
    /**
     * Muestra la lista de todos los usuarios registrados.
     */
    public function index()
    {
        if (!auth()->user()->esAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Nivel Alpha requerido.');
        }

        $usuarios = User::all();
        return view('usuarios.index', compact('usuarios'));
    }

    /**
     * GESTIÓN DE PRIVILEGIOS: Sincronización de Rangos.
     */
    public function updateRole(Request $request, User $user)
    {
        if (auth()->id() === $user->id) {
            return redirect()->route('usuarios.index')->with('error', 'No puedes auto-degradarte.');
        }

        $request->validate(['rol' => 'required|in:Administrador,jugador']);

        try {
            $user->rol = $request->rol;
            $user->save();

            if (method_exists($user, 'syncRoles')) {
                $user->syncRoles([$request->rol]);
            }

            return redirect()->route('usuarios.index')->with('success', "Rango actualizado exitosamente.");
        } catch (\Exception $e) {
            return redirect()->route('usuarios.index')->with('error', 'Fallo en la sincronización de protocolos.');
        }
    }

    /**
     * PURGA DE OPERADOR: Eliminación de registros.
     */
    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return redirect()->route('usuarios.index')->with('error', 'Auto-eliminación denegada.');
        }

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();
        return redirect()->route('usuarios.index')->with('success', 'Operador purgado del sistema.');
    }

    /**
     * TERMINAL DE CONFIGURACIÓN.
     */
    public function configuracion()
    {
        return view('perfil.configuracion');
    }

    /**
     * ACTUALIZACIÓN DE PERFIL: Gestión de Identidad.
     */
    public function updatePerfil(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'nombre' => 'nullable|string|max:255',
            'correo' => 'nullable|email|unique:usuarios,correo,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'tema'   => 'nullable|in:blue,rose,amber,purple'
        ]);

        if ($request->has('tema')) { $user->tema = $request->tema; }
        if ($request->has('nombre')) { $user->nombre = $request->nombre; }
        if ($request->has('correo')) { $user->correo = $request->correo; }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->save();

        return redirect()->route('perfil.config')->with('success', 'TERMINAL SINCRONIZADA: Identidad actualizada.');
    }

    /**
     * PROTOCOLO DE MEJORA: Persistencia de Feedback.
     */
    public function storeFeedback(Request $request)
    {
        $request->validate([
            'mensaje' => 'required|string|min:3|max:1000'
        ]);

        Feedback::create([
            'user_id'    => auth()->id(),
            'comentario' => $request->mensaje,
            'estado'     => 'PENDIENTE'
        ]);

        return redirect()->route('perfil.config')->with('success', 'REPORTE INDEXADO: El Sector Alpha lo revisará pronto.');
    }

    /**
     * MONITOR DE FEEDBACK: Visualización para Administradores.
     */
    public function verFeedback()
    {
        if (!auth()->user()->esAdmin()) {
            return redirect()->route('dashboard');
        }

        $reportes = Feedback::with('user')->latest()->get();
        return view('admin.feedback', compact('reportes'));
    }

    /**
     * PURGA DE FEEDBACK: Elimina un reporte del monitor.
     */
    public function destroyFeedback(Feedback $feedback)
    {
        if (!auth()->user()->esAdmin()) { return abort(403); }

        $feedback->delete();
        return back()->with('success', 'Reporte eliminado del monitor.');
    }

    /**
     * ACTUALIZACIÓN DE PROTOCOLO: Marca un feedback como resuelto.
     */
    public function updateFeedbackStatus(Feedback $feedback)
    {
        if (!auth()->user()->esAdmin()) { return abort(403); }

        $feedback->update(['estado' => 'resuelto']);
        return back()->with('success', 'Protocolo finalizado y archivado.');
    }
}

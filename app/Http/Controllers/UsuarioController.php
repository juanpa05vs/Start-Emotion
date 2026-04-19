<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Feedback; // 👈 Importamos el nuevo modelo
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    /**
     * Muestra la lista de todos los usuarios registrados.
     * [OPTIMIZACIÓN]: Eager Loading de roles para evitar el problema N+1.
     */
    public function index()
    {
        if (!auth()->user()->esAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Nivel Alpha requerido.');
        }

        $usuarios = User::with('roles')->get();
        return view('usuarios.index', compact('usuarios'));
    }

    /**
     * GESTIÓN DE PRIVILEGIOS: Sincronización de Spatie.
     */
    public function updateRole(Request $request, User $user)
    {
        if (auth()->id() === $user->id) {
            return redirect()->route('usuarios.index')->with('error', 'No puedes auto-degradarte.');
        }

        $request->validate(['rol' => 'required|in:Administrador,jugador']);

        try {
            $user->update(['rol' => $request->rol]);
            $user->syncRoles([$request->rol]);

            return redirect()->route('usuarios.index')
                             ->with('success', "Rango de {$user->nombre} actualizado.");
        } catch (\Exception $e) {
            return redirect()->route('usuarios.index')->with('error', 'Error en sincronización.');
        }
    }

    /**
     * PURGA DE OPERADOR: Eliminación física y lógica.
     */
    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return redirect()->route('usuarios.index')->with('error', 'Auto-eliminación denegada.');
        }

        if ($user->avatar) {
            Storage::delete('public/' . $user->avatar);
        }

        $user->delete();
        return redirect()->route('usuarios.index')->with('success', 'Operador purgado.');
    }

    /**
     * TERMINAL DE CONFIGURACIÓN: Carga de UI.
     */
    public function configuracion()
    {
        return view('perfil.configuracion');
    }

    /**
     * ACTUALIZACIÓN DE PERFIL: Gestión de Identidad y Estética.
     */
    public function updatePerfil(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|unique:usuarios,correo,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'tema'   => 'nullable|in:blue,rose,amber,purple'
        ]);

        $data = $request->only(['nombre', 'correo', 'tema']);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::delete('public/' . $user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        return redirect()->route('perfil.config')->with('success', 'TERMINAL ACTUALIZADA: Protocolos sincronizados.');
    }

    /**
     * PROTOCOLO DE MEJORA: Persistencia de Feedback en DB.
     */
    public function storeFeedback(Request $request)
    {
        $request->validate(['comentario' => 'required|string|min:10']);

        // [INGENIERÍA] Guardamos el reporte vinculado al ID del usuario actual
        Feedback::create([
            'user_id' => auth()->id(),
            'comentario' => $request->comentario,
            'estado' => 'pendiente'
        ]);

        return redirect()->route('perfil.config')->with('success', 'REPORTE INDEXADO: Tu sugerencia ha sido guardada en la base de datos.');
    }

    /**
     * MONITOR DE FEEDBACK (Vista Alpha): Solo para Administradores.
     */
    public function verFeedback()
    {
        if (!auth()->user()->esAdmin()) {
            return redirect()->route('dashboard');
        }

        $reportes = Feedback::with('user')->latest()->get();
        return view('admin.feedback', compact('reportes'));
    }
}

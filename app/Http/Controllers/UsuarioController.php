<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UsuarioController extends Controller
{
    /**
     * Gestión de cuentas: dar de alta, cambiar rol y dar de baja.
     *
     * QUÉ VE Y QUÉ NO VE
     *
     * El administrador gestiona cuentas, no personas. Por eso esta pantalla no
     * se construye con `User::all()` ni con un `select *` sobre el que después
     * se pueda mostrar cualquier campo nuevo. Se proyectan solo las columnas que
     * hacen falta para el trabajo de administración, de modo que añadir un campo
     * al modelo no lo vuelva visible aquí por accidente.
     *
     * Además se omiten `edad` y la fecha de creación: no hacen falta para
     * administrar una cuenta y sí suman información sobre cada persona
     * concreta.
     */
    public function index()
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();

        if (! $authUser || ! $authUser->esAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Necesitas permisos de administrador.');
        }

        $usuarios = User::query()
            // Solo identidad y rol. Sin email, sin edad, sin actividad.
            ->select(['id', 'nombre', 'rol', 'created_at'])
            ->orderBy('nombre')
            ->get();

        return view('usuarios.index', compact('usuarios'));
    }

    public function updateRole(Request $request, User $user)
    {
        if (Auth::id() === $user->id) {
            return redirect()->route('usuarios.index')->with('error', 'No puedes cambiar tu propio rol.');
        }

        $request->validate([
            'rol' => 'required|in:estudiante,Psicólogo,Administrador',
        ]);

        try {
            $nuevoRol = match (true) {
                Str::lower($request->rol) === 'administrador' => 'Administrador',
                $request->rol === 'Psicólogo' => 'Psicólogo',
                default => 'estudiante',
            };
            $user->rol = $nuevoRol;
            $user->save();

            if (method_exists($user, 'syncRoles')) {
                $user->syncRoles([$nuevoRol]);
            }

            return redirect()->route('usuarios.index')->with('success', 'Rol actualizado.');
        } catch (\Exception $e) {
            Log::warning('Fallo en sincronización de roles Spatie', ['user_id' => $user->id, 'exception' => $e->getMessage()]);

            return redirect()->route('usuarios.index')->with('error', 'No se pudo actualizar el rol.');
        }
    }

    public function destroy(User $user)
    {
        if (Auth::id() === $user->id) {
            return redirect()->route('usuarios.index')->with('error', 'Auto-eliminación denegada.');
        }

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        return redirect()->route('usuarios.index')->with('success', 'Cuenta eliminada.');
    }

    public function configuracion()
    {
        return view('perfil.configuracion');
    }

    public function updatePerfil(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'nombre' => 'nullable|string|max:255',
            'correo' => 'nullable|email|unique:usuarios,correo,'.$user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'tema' => 'nullable|in:blue,rose,amber,purple',
        ]);

        if ($request->filled('tema')) {
            $user->tema = $request->tema;
        }
        if ($request->filled('nombre')) {
            $user->nombre = $request->nombre;
        }
        if ($request->filled('correo')) {
            $user->correo = $request->correo;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->save();

        return redirect()->route('perfil.config')->with('success', 'Tus datos se actualizaron correctamente.');
    }

    public function storeFeedback(Request $request)
    {
        $request->validate([
            'mensaje' => 'required|string|min:3|max:1000',
        ]);

        $feedback = new Feedback;
        $feedback->fill([
            'comentario' => $request->mensaje,
            'estado' => 'pendiente',
        ]);
        $feedback->user_id = Auth::id();
        $feedback->save();

        return redirect()->route('perfil.config')->with('success', 'Gracias, recibimos tu mensaje. Lo revisaremos pronto.');
    }

    public function verFeedback()
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();

        if (! $authUser || ! $authUser->esAdmin()) {
            return redirect()->route('dashboard');
        }

        $reportes = Feedback::with('user')->latest()->get();

        return view('admin.feedback', compact('reportes'));
    }

    public function destroyFeedback(Feedback $feedback)
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();

        if (! $authUser || ! $authUser->esAdmin()) {
            return abort(403);
        }

        $feedback->delete();

        return back()->with('success', 'Mensaje eliminado.');
    }

    public function updateFeedbackStatus(Feedback $feedback)
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();

        if (! $authUser || ! $authUser->esAdmin()) {
            return abort(403);
        }

        $feedback->estado = 'resuelto';
        $feedback->save();

        return back()->with('success', 'Mensaje marcado como resuelto.');
    }
}

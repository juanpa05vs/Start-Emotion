<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function showLogin() {
        return view('auth.login');
    }

    public function login(Request $request) {
        $request->validate([
            'correo' => 'required|email',
            'contrasena' => 'required',
        ]);

        $credentials = [
            'correo'   => $request->correo,
            'password' => $request->contrasena,
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended('dashboard')
                ->with('status', '¡Bienvenido de nuevo, ' . Auth::user()->nombre . '!');
        }

        return back()->withErrors([
            'correo' => 'Las credenciales no coinciden. Revisa tu correo o contraseña.',
        ])->withInput($request->only('correo'));
    }

    public function showRegister() {
        return view('auth.register');
    }

    /**
     * Proceso de Registro Sincronizado.
     * [CORRECCIÓN]: Se cambió 'Usuario' por 'jugador' para coincidir con el RoleSeeder.
     */
    public function register(Request $request) {
        $request->validate([
            'nombre'     => 'required|string|max:100',
            'edad'       => 'required|integer|min:15|max:99',
            'correo'     => 'required|email|unique:usuarios,correo',
            'contrasena' => 'required|min:8|confirmed',
            'rol'        => 'required|in:jugador,Administrador', // Sincronizado con Seeder
        ]);

        // 1. Definimos el rol inicial
        $rolFinal = 'jugador';

        // 2. Verificación de Rango Alpha
        if ($request->rol === 'Administrador') {
            $masterKey = env('ADMIN_MASTER_KEY', 'emotion');

            if ($request->admin_token !== $masterKey) {
                return back()
                    ->withErrors(['admin_token' => 'Código de autorización de administrador incorrecto.'])
                    ->withInput();
            }
            $rolFinal = 'Administrador';
        }

        // 3. Crear el usuario
        // [REPARACIÓN]: Se añade 'rol' al create para que la columna en la DB no quede vacía.
        $user = User::create([
            'nombre'   => $request->nombre,
            'edad'     => $request->edad,
            'correo'   => $request->correo,
            'password' => Hash::make($request->contrasena),
            'rol'      => $rolFinal, // <--- Sincronización visual en la tabla usuarios
        ]);

        // 4. Asignar rol de Spatie (Motor de Seguridad)
        $user->assignRole($rolFinal);

        // 5. Autologin para mejorar la experiencia de usuario (UX)
        Auth::login($user);

        return redirect()->route('dashboard')->with('status', "¡Registro exitoso! Nivel de acceso: $rolFinal.");
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}

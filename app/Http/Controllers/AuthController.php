<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    /**
     * Muestra la interfaz de acceso.
     */
    public function showLogin() {
        return view('auth.login');
    }

    /**
     * Proceso de autenticación optimizado.
     */
    public function login(Request $request) {
        // 1. Validamos los datos que vienen del formulario
        $request->validate([
            'correo' => 'required|email',
            'contrasena' => 'required',
        ]);

        // --- MODO DEBUG (Descomenta la línea de abajo si sigue fallando) ---
        //dd($request->all());

        // 2. Mapeamos manualmente el input 'contrasena' a la columna 'password'
        $credentials = [
            'correo'   => $request->correo,
            'password' => $request->contrasena,
        ];

        // 3. Intentamos el login
        // Usamos el helper Auth::attempt que ya probamos en Tinker
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Redirigir al dashboard con un saludo
            return redirect()->intended('dashboard')
                ->with('status', '¡Bienvenido de nuevo, ' . Auth::user()->nombre . '!');
        }

        // 4. Si falla, regresamos con el error y mantenemos el correo escrito
        return back()->withErrors([
            'correo' => 'Las credenciales no coinciden. Revisa tu correo o contraseña.',
        ])->withInput($request->only('correo'));
    }

    /**
     * Muestra el Registro.
     */
    public function showRegister() {
        return view('auth.register');
    }

    /**
     * Proceso de Registro con validación previa de seguridad.
     */
    public function register(Request $request) {
        $request->validate([
            'nombre'     => 'required|string|max:100',
            'edad'       => 'required|integer|min:15|max:99',
            'correo'     => 'required|email|unique:usuarios,correo',
            'contrasena' => 'required|min:8|confirmed',
            'rol'        => 'required|in:Usuario,Administrador',
        ]);

        $rolFinal = 'Usuario';

        if ($request->rol === 'Administrador') {
            $masterKey = env('ADMIN_MASTER_KEY', 'emotion');

            if ($request->admin_token !== $masterKey) {
                return back()
                    ->withErrors(['admin_token' => 'Código de autorización de administrador incorrecto.'])
                    ->withInput();
            }
            $rolFinal = 'Administrador';
        }

        // Crear el usuario con la estructura correcta
        $user = User::create([
            'nombre'   => $request->nombre,
            'edad'     => $request->edad,
            'correo'   => $request->correo,
            'password' => Hash::make($request->contrasena),
        ]);

        // Asignar rol de Spatie
        $user->assignRole($rolFinal);

        return redirect()->route('login')->with('status', "¡Registro exitoso! Ya puedes ingresar como $rolFinal.");
    }

    /**
     * Logout limpio.
     */
    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}

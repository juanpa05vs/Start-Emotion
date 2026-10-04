<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // Igual que en el registro: una excepción de validación vuelve a
        // url()->previous(), que sin referer es la portada. Quien falla al
        // entrar tiene que volver al formulario, no a la página de inicio.
        $validator = Validator::make($request->all(), [
            'correo' => 'required|email',
            'contrasena' => 'required|string',
            'rol' => 'nullable|in:'.User::TIPO_ESTUDIANTE.','.User::TIPO_PSICOLOGO.','.User::TIPO_ADMINISTRADOR,
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('login')
                ->withErrors($validator)
                ->withInput($request->only('correo', 'rol'));
        }

        $credentials = [
            'correo' => $request->correo,
            'password' => $request->contrasena,
        ];

        if (! Auth::attempt($credentials)) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'correo' => 'Las credenciales no coinciden. Revisa tu correo o contraseña.',
                ])
                ->withInput($request->only('correo', 'rol'));
        }

        // El selector de tipo de acceso no es decorativo: dice con qué espacio
        // se va a entrar. Si no coincide con la cuenta real se cierra la sesión
        // de inmediato, para que en un equipo compartido nadie abra por error
        // el espacio de psicología pensando que es el suyo.
        $tipoDeclarado = $request->input('rol');
        $cuenta = Auth::user();

        if ($tipoDeclarado !== null
            && $cuenta->tiposDeCuenta() !== []
            && ! $cuenta->tieneTipoDeCuenta($tipoDeclarado)) {
            Auth::logout();

            $otros = collect($cuenta->tiposDeCuenta())
                ->map(fn ($tipo) => $this->nombreDeTipo($tipo))
                ->join(', ');

            return redirect()
                ->route('login')
                ->withErrors([
                    'rol' => 'Esta cuenta no es de tipo '.strtolower($this->nombreDeTipo($tipoDeclarado))
                        .'. Entró con: '.$otros.'.',
                ])
                ->withInput($request->only('correo', 'rol'));
        }

        $request->session()->regenerate();

        // Cada tipo de cuenta aterriza en su propia pantalla. Con un destino
        // fijo (`dashboard`) el administrador y el psicólogo entrarían a una
        // pantalla que no es suya y recibirían un 403 justo al iniciar sesión.
        //
        // `intended()` recibe una URL, no un nombre de ruta: hay que pasar el
        // resultado de `route()`. Sin eso, `psicologia.pacientes` se usaría
        // como si fuera una URL y el navegador pediría esa ruta inexistente.
        return redirect()->intended(route(Auth::user()->rutaDeInicio()))
            ->with('status', '¡Bienvenido de nuevo, '.Auth::user()->nombre.'!');
    }

    /** Nombre legible del tipo de cuenta, para los mensajes de la interfaz. */
    private function nombreDeTipo(string $tipo): string
    {
        return match ($tipo) {
            User::TIPO_PSICOLOGO => 'Profesional de psicología',
            User::TIPO_ADMINISTRADOR => 'Administración del sistema',
            default => 'Estudiante',
        };
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Proceso de Registro Sincronizado.
     */
    public function register(Request $request)
    {
        $esEstudiante = $request->input('rol') === User::TIPO_ESTUDIANTE;

        // Todas las reglas se aplican en una sola pasada para que la persona
        // vea el formulario con TODOS sus errores de una vez, y no corrigiendo
        // uno detrás de otro.
        //
        // El código de autorización se exige aquí y no en un validate()
        // aparte a propósito: una excepción de validación se redirige a
        // url()->previous(), que sin referer es la portada. Eso hacía que un
        // alta fallida pareciera no hacer nada.
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'edad' => 'required|integer|min:15|max:99',
            'correo' => 'required|email|unique:usuarios,correo',
            'contrasena' => 'required|min:8|confirmed',
            'rol' => 'required|in:'.User::TIPO_ESTUDIANTE.','.User::TIPO_PSICOLOGO.','.User::TIPO_ADMINISTRADOR,
            'admin_token' => $esEstudiante ? 'nullable' : 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('register')
                ->withErrors($validator)
                ->withInput($request->except('admin_token'));
        }

        $rolFinal = User::TIPO_ESTUDIANTE;

        // blank() y no `=== ''`: si ADMIN_MASTER_KEY no está definida en .env
        // el valor es null, y comparar solo contra '' dejaba pasar el chequeo
        // con un token vacío. Con un master key ausente, NADIE puede crear
        // cuentas de profesional o de administración.
        $masterKey = config('auth.admin_master_key') ?? config('app.admin_master_key');

        // El rol determina qué datos ve la persona, así que los tres exigen
        // autorización: no hay ninguna forma de auto-asignarse acceso a datos
        // de salud mental de terceros escribiendo un `rol` en el formulario.
        if (! $esEstudiante) {
            if (blank($masterKey) || ! hash_equals((string) $masterKey, (string) $request->admin_token)) {
                return redirect()
                    ->route('register')
                    ->withErrors([
                        'admin_token' => 'El código de autorización no es correcto. Pídeselo a quien administre la plataforma.',
                    ])
                    ->withInput($request->except('admin_token'));
            }

            // Se preserva la mayúscula con la que el sistema la reconoce.
            $rolFinal = $request->rol === User::TIPO_PSICOLOGO
                ? User::TIPO_PSICOLOGO
                : User::TIPO_ADMINISTRADOR;
        }

        // 'rol' NO está en $fillable (a propósito: evita asignación masiva de
        // privilegios). Por eso se asigna como propiedad explícita y no en create().
        $user = new User;
        $user->nombre = $request->nombre;
        $user->edad = $request->edad;
        $user->correo = $request->correo;
        $user->password = Hash::make($request->contrasena);
        $user->rol = $rolFinal;
        $user->save();

        if (method_exists($user, 'assignRole')) {
            // Se crea el rol si aún no existe (evita un 500 si falta ejecutar db:seed).
            $user->assignRole(Role::findOrCreate($rolFinal, 'web'));
        }

        Auth::login($user);

        return redirect()->route($user->rutaDeInicio())
            ->with('status', 'Cuenta creada. Puedes leer la política de privacidad en el menú de tu cuenta.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

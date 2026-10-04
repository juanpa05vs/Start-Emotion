<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'edad',
        'correo',
        'password',
        'avatar',
        'tema',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'edad' => 'integer',
        ];
    }

    public function emociones()
    {
        return $this->hasMany(RegistroEmocion::class, 'user_id');
    }

    public function evaluacionesPsicometricas()
    {
        return $this->hasMany(EvaluacionPsicometrica::class, 'user_id');
    }

    public function telemetriasGameplay()
    {
        return $this->hasMany(TelemetriaGameplay::class, 'user_id');
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'user_id');
    }

    /** Consentimientos que este usuario ha concedido a terceros. */
    public function consentimientosOtorgados()
    {
        return $this->hasMany(Consentimiento::class, 'estudiante_id');
    }

    /** Consentimientos que otros estudiantes le han concedido a este usuario. */
    public function consentimientosRecibidos()
    {
        return $this->hasMany(Consentimiento::class, 'profesional_id');
    }

    public function invitaciones()
    {
        return $this->hasMany(InvitacionPsicologo::class, 'profesional_id');
    }

    /**
     * Código de participante para los documentos de investigación.
     *
     * NO es el id. Antes se componía como `TESVB-SIST-` + el id rellenado con
     * ceros, lo que no era un código anónimo sino el id de la fila con otro
     * formato: el administrador que ve el listado de cuentas podía cruzarlo sin
     * esfuerzo y saber a quién pertenecía cada muestra.
     *
     * Ahora es un HMAC del id con la clave de la aplicación. Es estable (las
     * sesiones del mismo participante siguen agrupándose, que es lo que
     * necesitan los modelos de efectos mixtos) pero no se puede deshacer sin
     * conocer la clave, y no permite ordenar por antigüedad de la cuenta.
     *
     * Sigue siendo un pseudónimo, no un anonimato: quien tenga acceso al
     * servidor puede calcularlo. Para poder decir "anónimo" de verdad habría que
     * decidir qué hace el sistema cuando no existe el vínculo con la identidad,
     * y eso es una decisión de proyecto, no un detalle de implementación.
     */
    public static function codigoParticipante(int|string|null $id): string
    {
        if ($id === null) {
            return 'TESVB-SIN-IDENTIFICAR';
        }

        $huella = hash_hmac('sha256', 'participante:'.$id, (string) config('app.key'));

        return 'TESVB-'.strtoupper(substr($huella, 0, 8));
    }

    public function getCodigoAnonimoAttribute(): string
    {
        return self::codigoParticipante($this->getKey());
    }

    /**
     * Los tres tipos de cuenta que el sistema reconoce. Se usan en el login
     * para comprobar que quien entra dice ser lo que realmente es.
     */
    public const TIPO_ESTUDIANTE = 'estudiante';

    public const TIPO_PSICOLOGO = 'Psicólogo';

    public const TIPO_ADMINISTRADOR = 'Administrador';

    /**
     * Todos los tipos que esta cuenta realmente tiene. Una misma persona puede
     * tener varios: quien administra la plataforma en la universidad suele
     * además atender.
     *
     * @return list<string>
     */
    public function tiposDeCuenta(): array
    {
        $tipos = [];

        foreach ([self::TIPO_ESTUDIANTE, self::TIPO_PSICOLOGO, self::TIPO_ADMINISTRADOR] as $tipo) {
            if ($this->rol === $tipo || $this->hasRole($tipo)) {
                $tipos[] = $tipo;
            }
        }

        return $tipos;
    }

    public function tieneTipoDeCuenta(string $tipo): bool
    {
        return in_array($tipo, $this->tiposDeCuenta(), true);
    }

    public function esAdmin(): bool
    {
        if ($this->rol && trim(strtolower($this->rol)) === 'administrador') {
            return true;
        }

        return $this->hasRole('Administrador');
    }

    /**
     * Puerta al espacio clínico. Solo la abre el rol de psicólogo.
     *
     * Antes el administrador entraba aquí también, porque en una universidad el
     * responsable del sistema suele ser la misma persona que da la atención. Eso
     * es cómodo pero rompe la separación de funciones que la política de
     * privacidad promete: quien administra el sistema no debería poder
     * convertirse en destinatario de datos clínicos por la vía del rol.
     *
     * Quien haga las dos cosas las hace con DOS cuentas, o pide que se le añada
     * el rol de psicólogo a la suya. Lo que no existe es un atajo que convierta
     * "administrar el sistema" en "acceder a historiales clínicos".
     */
    public function esProfesional(): bool
    {
        return $this->rol === 'Psicólogo' || $this->hasRole('Psicólogo');
    }

    /**
     * Persona que usa la herramienta para autoobservarse.
     *
     * No es "todo lo que no es profesional": el administrador no es un
     * estudiante. La complementación anterior convertía al administrador en
     * destinatario de la lógica de consentimiento, que es justo lo que se
     * quiere evitar.
     */
    public function esEstudiante(): bool
    {
        return $this->rol === self::TIPO_ESTUDIANTE || $this->hasRole(self::TIPO_ESTUDIANTE);
    }

    /**
     * Pantalla a la que aterriza cada tipo de cuenta al entrar.
     *
     * Existe porque las tres pantallas de inicio son distintas y no todas le
     * pertenecen a todos: el panel del estudiante solo es del estudiante, el
     * espacio de atención solo del psicólogo y la gestión de cuentas solo del
     * administrador. Sin esto, entrar al sistema significaba caer siempre en
     * `/dashboard`, que es un 403 para dos de los tres tipos.
     *
     * El orden importa cuando una cuenta tiene varios roles: se prioriza el
     * autoobservación, que es la actividad de la que más superficie tiene, y
     * después atención y administración.
     */
    public function rutaDeInicio(): string
    {
        return match (true) {
            $this->esEstudiante() => 'dashboard',
            $this->esProfesional() => 'psicologia.pacientes',
            $this->esAdmin() => 'usuarios.index',
            default => 'dashboard',
        };
    }
}

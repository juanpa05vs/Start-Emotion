<?php

namespace Tests\Feature;

use App\Enums\AlcanceConsentimiento;
use App\Models\Consentimiento;
use App\Models\InvitacionPsicologo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El módulo de consentimiento es la parte del sistema donde un error no sale
 * un 500: sale expuesta la vida íntima de una persona. Estos tests fijan las
 * reglas que no se pueden negociar.
 */
class ConsentimientoTest extends TestCase
{
    use RefreshDatabase;

    private function estudiante(array $attrs = []): User
    {
        return User::factory()->create($attrs + ['rol' => 'estudiante']);
    }

    private function psicologo(): User
    {
        return User::factory()->psicologo()->create(['nombre' => 'Dra. Prueba']);
    }

    private function invitacion(User $profesional, array $attrs = []): InvitacionPsicologo
    {
        $inv = new InvitacionPsicologo;
        $inv->codigo = $attrs['codigo'] ?? 'ABCDEFGH';
        $inv->expira_en = $attrs['expira_en'] ?? now()->addDays(14);
        $inv->usada_en = $attrs['usada_en'] ?? null;
        $inv->usada_por = $attrs['usada_por'] ?? null;
        // `profesional_id` está fuera de $fillable a propósito: se asigna
        // como propiedad explícita, no en masa.
        $inv->profesional_id = $profesional->id;
        $inv->save();

        return $inv;
    }

    public function test_la_politica_de_privacidad_es_publica(): void
    {
        $this->get('/privacidad')->assertOk();
    }

    public function test_la_politica_se_lee_antes_de_registarse(): void
    {
        // Sin sesión: es el momento exacto en que alguien decide si se registra.
        $this->get('/privacidad')->assertOk();
    }

    public function test_el_estudiante_otorga_consentimiento_con_un_codigo_valido(): void
    {
        $profesional = $this->psicologo();
        $invitacion = $this->invitacion($profesional, ['codigo' => 'K7MQ2XPD']);
        $estudiante = $this->estudiante();

        $response = $this->actingAs($estudiante)->post('/mi-privacidad/conectar', [
            'codigo' => 'k7mq2xpd',
            'alcance' => [AlcanceConsentimiento::Registros->value],
        ]);

        $response->assertRedirect('/mi-privacidad');

        $this->assertDatabaseHas('consentimientos', [
            'estudiante_id' => $estudiante->id,
            'profesional_id' => $profesional->id,
            'revocado_en' => null,
        ]);

        // El código es de un solo uso.
        $this->assertNotNull($invitacion->fresh()->usada_en);
        $this->assertSame($estudiante->id, $invitacion->fresh()->usada_por);
    }

    public function test_un_codigo_vencido_no_otorga_consentimiento(): void
    {
        $profesional = $this->psicologo();
        $this->invitacion($profesional, ['codigo' => 'OLDCODE1', 'expira_en' => now()->subDay()]);
        $estudiante = $this->estudiante();

        $this->actingAs($estudiante)
            ->post('/mi-privacidad/conectar', [
                'codigo' => 'OLDCODE1',
                'alcance' => [AlcanceConsentimiento::Registros->value],
            ])
            ->assertSessionHasErrors('codigo');

        $this->assertDatabaseCount('consentimientos', 0);
    }

    public function test_un_codigo_ya_usado_no_se_puede_reutilizar(): void
    {
        $profesional = $this->psicologo();
        $otro = $this->estudiante();
        $this->invitacion($profesional, [
            'codigo' => 'USEDCODE',
            'usada_en' => now()->subHour(),
            'usada_por' => $otro->id,
        ]);
        $estudiante = $this->estudiante();

        $this->actingAs($estudiante)
            ->post('/mi-privacidad/conectar', [
                'codigo' => 'USEDCODE',
                'alcance' => [AlcanceConsentimiento::Registros->value],
            ])
            ->assertSessionHasErrors('codigo');

        $this->assertDatabaseCount('consentimientos', 0);
    }

    public function test_no_se_puede_otorgar_consentimiento_sin_elegir_alcance(): void
    {
        $profesional = $this->psicologo();
        $this->invitacion($profesional, ['codigo' => 'NOALCAN01']);
        $estudiante = $this->estudiante();

        $this->actingAs($estudiante)
            ->post('/mi-privacidad/conectar', ['codigo' => 'NOALCAN01', 'alcance' => []])
            ->assertSessionHasErrors('alcance');

        $this->assertDatabaseCount('consentimientos', 0);
    }

    public function test_un_valor_de_alcance_inventado_se_rechaza(): void
    {
        $profesional = $this->psicologo();
        $this->invitacion($profesional, ['codigo' => 'HACKSCOPE']);
        $estudiante = $this->estudiante();

        $this->actingAs($estudiante)
            ->post('/mi-privacidad/conectar', [
                'codigo' => 'HACKSCOPE',
                'alcance' => ['todo_el_sistema'],
            ])
            ->assertSessionHasErrors('alcance.0');

        $this->assertDatabaseCount('consentimientos', 0);
    }

    public function test_el_estudiante_puede_retirar_el_consentimiento(): void
    {
        $profesional = $this->psicologo();
        $estudiante = $this->estudiante();

        $c = new Consentimiento;
        $c->alcance = [AlcanceConsentimiento::Registros->value];
        $c->estudiante_id = $estudiante->id;
        $c->profesional_id = $profesional->id;
        $c->otorgado_en = now();
        $c->save();

        $this->actingAs($estudiante)
            ->post("/mi-privacidad/consentimientos/{$c->id}/revocar")
            ->assertRedirect('/mi-privacidad');

        $this->assertNotNull($c->fresh()->revocado_en);
    }

    public function test_no_se_puede_revocar_el_consentimiento_de_otro(): void
    {
        $profesional = $this->psicologo();
        $victima = $this->estudiante();
        $atacante = $this->estudiante();

        $c = new Consentimiento;
        $c->alcance = [AlcanceConsentimiento::Registros->value];
        $c->estudiante_id = $victima->id;
        $c->profesional_id = $profesional->id;
        $c->otorgado_en = now();
        $c->save();

        $this->actingAs($atacante)
            ->post("/mi-privacidad/consentimientos/{$c->id}/revocar")
            ->assertForbidden();

        // El consentimiento ajeno sigue intacto: un 403 no debe mutar nada.
        $this->assertNull($c->fresh()->revocado_en);
    }

    public function test_la_revocacion_queda_en_el_historico(): void
    {
        $profesional = $this->psicologo();
        $estudiante = $this->estudiante();

        $c = new Consentimiento;
        $c->alcance = [AlcanceConsentimiento::Registros->value];
        $c->estudiante_id = $estudiante->id;
        $c->profesional_id = $profesional->id;
        $c->otorgado_en = now();
        $c->save();

        $c->revocar('ya no lo necesito');

        $this->assertFalse($c->estaVigente());
        $this->assertSame('ya no lo necesito', $c->motivo_revocacion);
        // La fila NO se borra: es la constancia auditable del acceso.
        $this->assertDatabaseHas('consentimientos', ['id' => $c->id]);
        $this->assertNotNull($c->fresh()->revocado_en);
    }

    public function test_reconectar_actualiza_el_alcance_en_vez_de_duplicar(): void
    {
        $profesional = $this->psicologo();
        $estudiante = $this->estudiante();

        $this->invitacion($profesional, ['codigo' => 'FIRSTCOD']);
        $this->actingAs($estudiante)->post('/mi-privacidad/conectar', [
            'codigo' => 'FIRSTCOD',
            'alcance' => [AlcanceConsentimiento::Registros->value],
        ]);

        $this->invitacion($profesional, ['codigo' => 'SECONDCOD']);
        $this->actingAs($estudiante)->post('/mi-privacidad/conectar', [
            'codigo' => 'SECONDCOD',
            'alcance' => [AlcanceConsentimiento::Evaluaciones->value, AlcanceConsentimiento::Juego->value],
        ]);

        // "Lo que comparto" es una respuesta, no un registro de eventos.
        $this->assertSame(1, Consentimiento::where('estudiante_id', $estudiante->id)->count());

        $vigente = Consentimiento::vigenteEntre($estudiante->id, $profesional->id);
        $this->assertNotNull($vigente);
        $this->assertFalse($vigente->incluye(AlcanceConsentimiento::Registros));
        $this->assertTrue($vigente->incluye(AlcanceConsentimiento::Juego));
    }

    public function test_un_profesional_no_puede_generar_codigos(): void
    {
        $estudiante = $this->estudiante();

        $this->actingAs($estudiante)
            ->get('/psicologia/invitaciones')
            ->assertForbidden();
    }

    public function test_un_codigo_de_una_cuenta_no_profesional_no_otorga_consentimiento(): void
    {
        // Alguien que no es profesional no debe poder ser destinatario de datos
        // clínicos, aunque riusa llegar hasta el punto de generar un código.
        $noProfesional = $this->estudiante(['nombre' => 'Impostor']);
        $invitacion = $this->invitacion($noProfesional, ['codigo' => 'FALSEPRF']);
        $estudiante = $this->estudiante();

        $this->actingAs($estudiante)
            ->post('/mi-privacidad/conectar', [
                'codigo' => 'FALSEPRF',
                'alcance' => [AlcanceConsentimiento::Registros->value],
            ])
            ->assertSessionHasErrors('codigo');

        $this->assertDatabaseCount('consentimientos', 0);
    }

    public function test_un_codigo_creado_para_uno_no_lo_puede_anular_otro(): void
    {
        $profesionalA = $this->psicologo();
        $profesionalB = $this->psicologo();

        $inv = $this->invitacion($profesionalA, ['codigo' => 'PRIVATE1']);

        $this->actingAs($profesionalB)
            ->delete("/psicologia/invitaciones/{$inv->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('invitaciones_psicologo', ['id' => $inv->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Pantalla de códigos del profesional
    |--------------------------------------------------------------------------
    |
    | Estas pruebas cubren el recorrido COMPLETO de la pantalla. Antes solo se
    | visitaba /psicologia/invitaciones con una cuenta estudiante, que recibe un
    | 403 antes de tocar la base de datos: por eso una columna inexistente en el
    | ORDER BYSeeder colarse en producción sin que 113 tests lo notaran.
    |
    */

    public function test_el_profesional_puede_abrir_su_pantalla_de_codigos(): void
    {
        $profesional = $this->psicologo();

        $this->actingAs($profesional)
            ->get('/psicologia/invitaciones')
            ->assertOk();
    }

    public function test_generar_un_codigo_abre_la_pantalla_lista(): void
    {
        $profesional = $this->psicologo();

        // Generar redirige a la misma pantalla: si el listado revienta, el
        // profesional recibe un 500 en lugar de su código.
        $this->actingAs($profesional)
            ->post('/psicologia/invitaciones')
            ->assertRedirect('/psicologia/invitaciones')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('invitaciones_psicologo', 1);

        $this->actingAs($profesional)
            ->followingRedirects()
            ->post('/psicologia/invitaciones')
            ->assertOk();

        $this->assertDatabaseCount('invitaciones_psicologo', 2);
    }

    public function test_la_pantalla_muestra_los_codigos_mas_recientes_primero(): void
    {
        $profesional = $this->psicologo();

        $viejo = $this->invitacion($profesional, ['codigo' => 'VIEJO123']);
        $viejo->forceFill(['created_at' => now()->subDays(5)])->save();

        $nuevo = $this->invitacion($profesional, ['codigo' => 'NUEVO123']);
        $nuevo->forceFill(['created_at' => now()])->save();

        $html = $this->actingAs($profesional)
            ->get('/psicologia/invitaciones')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('NUEVO123', $html);
        $this->assertStringContainsString('VIEJO123', $html);

        // El ORDER BY debe usar la columna que existe de verdad.
        $this->assertLessThan(
            strpos($html, 'VIEJO123'),
            strpos($html, 'NUEVO123'),
            'El código más reciente tiene que aparecer primero.'
        );
    }

    public function test_la_pantalla_de_un_profesional_no_muestra_los_codigos_de_otro(): void
    {
        $profesionalA = $this->psicologo();
        $profesionalB = $this->psicologo();

        $this->invitacion($profesionalA, ['codigo' => 'SOLOA999']);

        $html = $this->actingAs($profesionalB)
            ->get('/psicologia/invitaciones')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('SOLOA999', $html);
    }

    public function test_anular_un_codigo_lo_devuelve_a_la_pantalla(): void
    {
        $profesional = $this->psicologo();
        $inv = $this->invitacion($profesional, ['codigo' => 'ANULAR12']);

        $this->actingAs($profesional)
            ->followingRedirects()
            ->delete("/psicologia/invitaciones/{$inv->id}")
            ->assertOk();

        $this->assertDatabaseMissing('invitaciones_psicologo', ['id' => $inv->id]);
    }
}

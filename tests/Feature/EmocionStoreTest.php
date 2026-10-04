<?php

namespace Tests\Feature;

use App\Models\RegistroEmocion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Fija el contrato del motor de estrés.
 *
 * Este motor tiene dos entradas que deben coincidir con la UI:
 *  - `contexto` con los <option> de dashboard.blade.php: General|Exámenes|Proyecto|Clases
 *  - el léxico estudiantil que el formulario anuncia como "Diccionario Neural"
 *
 * Ambas se rompieron en silencio una vez (valores en inglés + léxico eliminado),
 * por eso se cubren con tests en lugar de confiar en la vista.
 */
class EmocionStoreTest extends TestCase
{
    use RefreshDatabase;

    private function registrar(User $user, array $overrides = []): RegistroEmocion
    {
        $payload = array_merge([
            'emocion' => 'ansioso',
            'energia' => 50,
            'observaciones' => null,
            'contexto' => 'General',
        ], $overrides);

        $this->actingAs($user)->post('/emociones', $payload)->assertRedirect('/dashboard');

        return $user->emociones()->latest()->first();
    }

    public function test_el_lexico_estudiantil_suma_estres(): void
    {
        $user = User::factory()->create();

        $neutro = $this->registrar($user, [
            'emocion' => 'productivo',
            'energia' => 50,
            'observaciones' => 'Todo tranquilo en el proyecto.',
            'contexto' => 'General',
        ]);

        $user->emociones()->delete();

        $cargado = $this->registrar($user, [
            'emocion' => 'productivo',
            'energia' => 50,
            'observaciones' => 'Presión alta, podría reprobar el examen de cálculo.',
            'contexto' => 'General',
        ]);

        $this->assertGreaterThan(
            (float) $neutro->nivel_estres_estimado,
            (float) $cargado->nivel_estres_estimado,
            'El léxico estudiantil (examen/presión/reprobar) debe elevar el estrés.'
        );
    }

    public function test_el_contexto_examenes_agrega_estres(): void
    {
        $user = User::factory()->create();

        $general = $this->registrar($user, ['contexto' => 'General']);
        $user->emociones()->delete();
        $examenes = $this->registrar($user, ['contexto' => 'Exámenes']);

        $this->assertSame(15, (int) $examenes->nivel_estres_estimado - (int) $general->nivel_estres_estimado);
    }

    #[DataProvider('contextosValidos')]
    public function test_cada_contexto_del_formulario_produce_una_recomendacion(string $contexto, string $fragmento): void
    {
        $user = User::factory()->create();

        $registro = $this->registrar($user, ['contexto' => $contexto]);

        $this->assertStringContainsString(
            $fragmento,
            $registro->recomendacion,
            "El contexto '{$contexto}' debe producir su cierre característico."
        );
    }

    public static function contextosValidos(): array
    {
        return [
            'Exámenes' => ['Exámenes', 'memoria temporal'],
            'Proyecto' => ['Proyecto', 'commits pequeños'],
            'Clases' => ['Clases', 'apuntes estructurados'],
            'General' => ['General', 'Optimiza tus bloques de tiempo'],
        ];
    }

    public function test_un_contexto_fuera_del_formulario_es_rechazado(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/emociones', [
            'emocion' => 'productivo',
            'energia' => 50,
            'contexto' => 'contexto_inventado',
        ])->assertSessionHasErrors('contexto');
    }

    public function test_la_energia_se_acota_al_rango_del_slider(): void
    {
        $user = User::factory()->create();

        // El <input type="range"> de dashboard.blade.php va de 1 a 100.
        $this->actingAs($user)->post('/emociones', [
            'emocion' => 'productivo',
            'energia' => 0,
        ])->assertSessionHasErrors('energia');
    }

    /**
     * Toda familia de la barra de intensidad tiene su lectura escrita.
     *
     * Cada opción de emoción lleva `data-escala` y el texto de cada familia vive
     * en un bloque JSON al lado de la barra. Si a una de las dos mitades le falta
     * la contraparte, el guion NO falla: cae a la lectura por defecto y el rótulo
     * del campo pasa a decir «Con qué intensidad», que era el texto genérico de
     * antes de que esto existiera.
     *
     * Es un fallo silencioso y de los que cuesta ver, porque solo aparece en una
     * de las cuatro familias mientras las otras tres siguen bien. Una de las dos
     * mitades se lee del HTML que sale —donde `data-escala` ya trae el valor
     * resuelto— y la otra del bloque JSON de la propia plantilla.
     */
    public function test_toda_familia_de_la_barra_tiene_su_lectura_escrita(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)->get('/dashboard')->assertOk()->getContent();

        preg_match_all('/data-escala="([^"]+)"/', $html, $coincidencias);
        $familias = array_values(array_unique($coincidencias[1]));

        $this->assertNotEmpty($familias, 'No se ha encontrado ningún `data-escala` en la página.');

        $this->assertSame(
            1,
            preg_match('#<script type="application/json" data-lecturas-escala>(.*?)</script>#s', $html, $bloque),
            'El bloque JSON con las lecturas de la escala no está en la página.'
        );

        $lecturas = json_decode(trim($bloque[1]), true);

        $this->assertIsArray($lecturas, 'El bloque de lecturas de la escala no es JSON válido.');

        $huerfanas = array_values(array_diff($familias, array_keys($lecturas)));

        $this->assertSame(
            [],
            $huerfanas,
            "Estas familias no tienen texto propio, así que su rótulo cae al genérico:\n  - "
            .implode("\n  - ", $huerfanas)
        );

        // Y al revés: una lectura que ninguna opción usa es texto que alguien
        // escribió y nadie va a ver.
        $this->assertSame(
            [],
            array_values(array_diff(array_keys($lecturas), $familias)),
            'Hay lecturas de escala que ninguna emoción usa.'
        );
    }

    public function test_el_registro_queda_acotado_al_usuario_autenticado(): void
    {
        $user = User::factory()->create();

        $registro = $this->registrar($user);

        $this->assertSame($user->id, $registro->user_id);
        $this->assertInstanceOf(RegistroEmocion::class, $registro);
    }
}

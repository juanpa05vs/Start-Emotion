<?php

namespace Tests\Feature;

use App\Models\Consentimiento;
use App\Models\InvitacionPsicologo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Comprueba que ninguna consulta del sistema nombre una columna que no existe.
 *
 * POR QUÉ HACE FALTA ESTA PRUEBA
 *
 * SQLite NO da error cuando un ORDER BY (o un WHERE, o un SELECT) nombra una
 * columna inexistente: la consulta sale con menos datos de los esperados y sin
 * avisar nada. MySQL sí lo rechaza, con el error 1054 "Unknown column".
 *
 * Consecuencia práctica: una columna inventada pasa la suite completa en verde
 * y revienta con un 500 en la máquina de desarrollo, que es donde se descubre.
 * Ya ha pasado con `orderByDesc('creado_en')` sobre una tabla creada con
 * `$table->timestamps()`, que se llama `created_at`.
 *
 * Aquí no se confía en que el motor proteste: se lee el SQL que sale realmente
 * hacia la base y se compara cada identificador citado contra las columnas que
 * existen. Así el fallo se ve igual en los dos motores.
 */
class ColumnasDeOrdenTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pantallas cuyo SQL se audita, y con qué tipo de cuenta se recorren.
     *
     * Se recorren de verdad, con una sesión real, para capturar exactamente el
     * SQL que genera el código y no una copia que se desincroniza en cuanto
     * alguien reescribe la consulta.
     *
     * @return list<array{string, string}>
     */
    private function pantallasADepurar(): array
    {
        return [
            // espacio del profesional
            ['/psicologia/invitaciones', 'professional'],
            ['/psicologia/pacientes', 'professional'],
            // espacio del estudiante
            ['/privacidad', 'student'],
        ];
    }

    public function test_ninguna_pantalla_nombra_una_columna_que_no_existe(): void
    {
        $profesional = User::factory()->psicologo()->create();
        $estudiante = User::factory()->create(['rol' => 'estudiante']);

        // Las columnas se leen ANTES de registrar el listener. Consultar el
        // esquema desde dentro del listener dispararía consultas nuevas, que
        // volverían a entrar en el listener, y así hasta agotar la memoria.
        $columnas = $this->columnasDeTodasLasTablas();

        $problemas = [];

        foreach ($this->pantallasADepurar() as [$ruta, $tipo]) {
            $this->actingAs($tipo === 'professional' ? $profesional : $estudiante);

            $this->auditarPeticion($ruta, $columnas, $problemas);
        }

        $this->assertSame(
            [],
            $problemas,
            "Columnas inexistentes en consultas reales:\n".implode("\n", $problemas)
        );
    }

    /**
     * Mapa tabla -> columnas, leído una sola vez.
     *
     * @return array<string, list<string>>
     */
    private function columnasDeTodasLasTablas(): array
    {
        $mapa = [];

        foreach (Schema::getTableListing() as $tabla) {
            // SQLite devuelve el nombre con el prefijo del esquema ("main.usuarios")
            // mientras que el SQL generado por Eloquent lo dice sin él. Se
            // comparan solo con el nombre corto.
            $corta = strtolower(str_contains($tabla, '.') ? substr($tabla, strrpos($tabla, '.') + 1) : $tabla);

            $mapa[$corta] = array_map(
                fn ($c) => strtolower(is_array($c) ? $c['name'] : $c->name),
                Schema::getColumns($tabla)
            );
        }

        return $mapa;
    }

    /**
     * Recorre una ruta y anota, en $problemas, cada columna citada que no exista.
     *
     * @param  array<string, list<string>>  $columnas
     * @param  list<string>  $problemas
     */
    private function auditarPeticion(string $ruta, array $columnas, array &$problemas): void
    {
        DB::listen(function ($query) use (&$problemas, $ruta, $columnas) {
            $sql = $query->sql;

            // Las consultas con JOIN citan columnas de varias tablas y el FROM no
            // basta para saber a cuál pertenece cada nombre. Se auditan solo las
            // consultas de una sola tabla, que son las que cubren un ORDER BY
            // inventado.
            if (str_contains(strtolower($sql), ' join ')) {
                return;
            }

            $tabla = $this->tablaDelSql($sql, array_keys($columnas));

            if ($tabla === null) {
                return;
            }

            foreach ($this->columnasCitadas($sql) as $columna) {
                if (! in_array($columna, $columnas[$tabla], true)) {
                    $problemas[] = sprintf(
                        '%s -> %s.%s  (en MySQL esto es el error 1054)',
                        $ruta,
                        $tabla,
                        $columna
                    );
                }
            }
        });

        $this->get($ruta);
    }

    /**
     * Nombre de la tabla de la que lee la consulta, o null si no se reconoce.
     *
     * @param  array<string>  $tablasConocidas
     */
    private function tablaDelSql(string $sql, array $tablasConocidas = []): ?string
    {
        // La cita de apertura puede ser acento grave (MySQL) o comilla doble
        // (SQLite). Si el patrón no acepta las dos, la consulta se descarta
        // entera y la auditoría pasa en falso, que es justo el fallo que
        // estamos tratando de detectar.
        if (preg_match('/\bfrom\s+(?:`|")?([a-zA-Z_][a-zA-Z0-9_]*)/i', $sql, $m)) {
            $tabla = strtolower($m[1]);

            if ($tablasConocidas === [] || in_array($tabla, $tablasConocidas, true)) {
                return $tabla;
            }
        }

        return null;
    }

    /**
     * Extrae los nombres de columna citados en el SQL.
     *
     * Cubre las dos formas de citar que usan los motores: acentos graves en
     * MySQL, comillas dobles en SQLite.
     *
     * @return list<string>
     */
    private function columnasCitadas(string $sql): array
    {
        $citadas = [];

        $cita = '(?:`([^`]+)`|"([^"]+)")';

        // "tabla"."columna" -> la columna es el SEGUNDO nombre, nunca el
        // primero. Tomar el primero hacía que el propio nombre de la tabla
        // apareciera como columna inventada.
        if (preg_match_all('/'.$cita.'\s*\.\s*'.$cita.'/i', $sql, $m, PREG_SET_ORDER)) {
            foreach ($m as $par) {
                $citadas[] = $this->normalizar(($par[3] ?? '') ?: ($par[4] ?? ''));
            }
        }

        // order by "columna" / group by "columna"
        if (preg_match_all('/\b(?:order|group)\s+by\s+'.$cita.'/i', $sql, $m2, PREG_SET_ORDER)) {
            foreach ($m2 as $par) {
                $citadas[] = $this->normalizar(($par[1] ?? '') ?: ($par[2] ?? ''));
            }
        }

        return array_values(array_unique(array_filter($citadas)));
    }

    private function normalizar(string $nombre): string
    {
        return strtolower(trim($nombre, '`" '));
    }

    /**
     * @return list<string>
     */
    private function columnasDe(string $tabla): array
    {
        return array_map(
            fn ($c) => strtolower(is_array($c) ? $c['name'] : $c->name),
            Schema::getColumns($tabla)
        );
    }

    public function test_las_tablas_nuevas_usan_los_nombres_de_marca_de_laravel(): void
    {
        // Las columnas con sufijo _en (otorgado_en, expira_en, usada_en) son
        // marcas del dominio y están bien. Las que crea $table->timestamps() se
        // llaman created_at / updated_at en TODAS las migraciones del proyecto;
        // inventarles otro nombre rompe en MySQL.
        $this->assertContains('created_at', $this->columnasDe('invitaciones_psicologo'));
        $this->assertContains('updated_at', $this->columnasDe('invitaciones_psicologo'));
        $this->assertContains('created_at', $this->columnasDe('consentimientos'));

        // Y los modelos no deben declarar la marca de creación con otro nombre.
        foreach ([InvitacionPsicologo::class, Consentimiento::class] as $modelo) {
            $this->assertSame(
                'created_at',
                (new $modelo)->getCreatedAtColumn(),
                "{$modelo} declara la columna de creación con un nombre que su tabla no tiene."
            );
        }
    }

    public function test_el_extractor_de_columnas_se_da_cuenta_de_un_nombre_inventado(): void
    {
        // Prueba del propio extractor: si esto falla, la auditoría de arriba no
        // está mirando nada y passingía en falso.
        $inventado = 'select * from `invitaciones_psicologo` order by `creado_en` desc';

        $this->assertContains('creado_en', $this->columnasCitadas($inventado));
        $this->assertNotContains('creado_en', $this->columnasDe('invitaciones_psicologo'));

        $real = 'select * from `invitaciones_psicologo` order by `created_at` desc';

        $this->assertContains('created_at', $this->columnasCitadas($real));
        $this->assertContains('created_at', $this->columnasDe('invitaciones_psicologo'));
    }
}

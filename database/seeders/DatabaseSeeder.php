<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * [INGENIERÍA]: Seed principal del sistema.
     * Coordina la creación de roles y usuarios iniciales.
     */
    public function run(): void
    {
        // 1. LLAMADA AL SECTOR DE SEGURIDAD (Roles y Permisos)
        // [ALFA]: Es vital que esto ocurra antes de crear cualquier usuario.
        $this->call(RoleSeeder::class);

        // 2. CREACIÓN DE USUARIO DE PRUEBA (Opcional)
        // [CORRECCIÓN]: Sincronizamos con las columnas 'nombre' y 'correo'.
        /*
        User::factory()->create([
            'nombre' => 'Test User',
            'correo' => 'test@example.com',
            'password' => bcrypt('password'), // Siempre define una pass si usas factory
            'rol' => 'jugador',
        ]);
        */

        // Nota: Si prefieres registrarte tú mismo desde la web,
        // puedes dejar el User::factory comentado.
    }
}

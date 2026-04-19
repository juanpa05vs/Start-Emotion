<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. REINICIO DE MEMORIA (Limpiar caché de Spatie)
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. DEFINICIÓN DE CAPACIDADES (Permisos)
        // [INGENIERÍA]: Definimos las acciones atómicas del sistema
        $permisos = [
            'ver.dashboard',
            'gestionar.usuarios',
            'ver.historial',
            'registrar.emocion',
            'ver.calendario', // Añadimos este para tu nuevo módulo 04
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        // 3. CONFIGURACIÓN DE ROLES

        // ROL ALPHA: Administrador
        // [PSICOLOGÍA]: El administrador tiene control total para reducir la fricción de gestión.
        $admin = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all()); // Le damos TODO

        // ROL BETA: jugador (Cambiamos 'Usuario' por 'jugador' para consistencia técnica)
        $jugador = Role::firstOrCreate(['name' => 'jugador', 'guard_name' => 'web']);
        $jugador->syncPermissions([
            'ver.dashboard',
            'ver.historial',
            'registrar.emocion',
            'ver.calendario',
        ]);
    }
}

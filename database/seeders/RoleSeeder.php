<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
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
            'ver.calendario',
            'ver.pacientes',
            'gestionar.consentimientos',
            'gestionar.invitaciones',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        // 3. CONFIGURACIÓN DE ROLES
        //
        // Tres roles con propósitos distintos:
        //   - estudiante: usa la herramienta para sí mismo. Es dueño de sus datos.
        //   - Psicólogo:  atiende estudiantes. Solo ve lo que le compartan explícitamente.
        //   - Administrador: gestiona el sistema (cuentas, configuración). No es
        //     una figura clínica y por eso su acceso a los datos de salud mental
        //     también pasa por consentimiento, no por ser administrador.

        $admin = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $estudiante = Role::firstOrCreate(['name' => 'estudiante', 'guard_name' => 'web']);
        $estudiante->syncPermissions([
            'ver.dashboard',
            'ver.historial',
            'registrar.emocion',
            'ver.calendario',
        ]);

        // El profesional NO recibe los permisos del estudiante. Puede consultar
        // lo que un paciente autorizó, pero no puede registrar emociones "en su
        // nombre" ni alterar su historial: los datos del paciente son suyos.
        $psicologo = Role::firstOrCreate(['name' => 'Psicólogo', 'guard_name' => 'web']);
        $psicologo->syncPermissions([
            'ver.pacientes',
            'gestionar.consentimientos',
            'gestionar.invitaciones',
        ]);
    }
}

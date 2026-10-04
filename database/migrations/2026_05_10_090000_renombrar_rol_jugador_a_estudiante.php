<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * El proyecto pasa a ser una herramienta para el psicólogo. El rol `jugador`
 * ("¿jugador de qué?") es terminología de videojuego aplicada a la salud
 * mental, y queda reemplazado por `estudiante`, que describe a la persona
 * real que usa el sistema.
 *
 * `model_has_roles` referencia `roles.id`, así que renombrar la columna `name`
 * no rompe las asignaciones ya hechas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('usuarios', 'rol')) {
            DB::table('usuarios')
                ->whereIn('rol', ['jugador', 'Jugador'])
                ->update(['rol' => 'estudiante']);
        }

        if (Schema::hasTable('roles')) {
            DB::table('roles')
                ->whereIn('name', ['jugador', 'Jugador'])
                ->update(['name' => 'estudiante']);
        }

        // Spatie guarda los roles resueltos en caché: sin esto seguiría
        // concediendo el rol viejo hasta el siguiente `optimize:clear`.
        if (app()->bound(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('usuarios', 'rol')) {
            DB::table('usuarios')->where('rol', 'estudiante')->update(['rol' => 'jugador']);
        }

        if (Schema::hasTable('roles')) {
            DB::table('roles')->where('name', 'estudiante')->update(['name' => 'jugador']);
        }

        if (app()->bound(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};

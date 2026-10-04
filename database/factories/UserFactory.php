<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'correo' => fake()->unique()->safeEmail(),
            'edad' => fake()->numberBetween(18, 60),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'tema' => fake()->randomElement(['blue', 'rose', 'amber', 'purple']),
            'rol' => 'estudiante',
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => 'Administrador',
        ]);
    }

    /** Profesional de psicología. Asigna también el rol de Spatie. */
    public function psicologo(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => 'Psicólogo',
        ])->afterCreating(function (User $user) {
            $user->assignRole(Role::findOrCreate('Psicólogo', 'web'));
        });
    }
}

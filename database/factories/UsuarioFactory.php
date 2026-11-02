<?php

namespace Database\Factories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'cedula' => (string) fake()->unique()->numberBetween(1000000, 9999999),
            'nombre' => fake()->name(),
            'password' => static::$password ??= Hash::make('password'),
            'rol' => 'alumno',
            'activo' => true,
        ];
    }

    public function administrador(): static
    {
        return $this->state(fn () => ['rol' => 'administrador']);
    }

    public function profesor(): static
    {
        return $this->state(fn () => ['rol' => 'profesor']);
    }

    public function alumno(): static
    {
        return $this->state(fn () => ['rol' => 'alumno']);
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}

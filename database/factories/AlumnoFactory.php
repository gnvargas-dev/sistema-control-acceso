<?php

namespace Database\Factories;

use App\Models\Alumno;
use App\Models\Curso;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alumno>
 */
class AlumnoFactory extends Factory
{
    protected $model = Alumno::class;

    public function definition(): array
    {
        return [
            'usuario_id' => Usuario::factory()->alumno(),
            'curso_id' => Curso::factory(),
            'legajo' => '2026-' . fake()->unique()->numberBetween(1000, 9999),
            'activo' => true,
        ];
    }
}

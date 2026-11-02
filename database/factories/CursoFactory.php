<?php

namespace Database\Factories;

use App\Models\Curso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Curso>
 */
class CursoFactory extends Factory
{
    protected $model = Curso::class;

    private static int $contador = 0;

    public function definition(): array
    {
        $n = self::$contador++;

        // (anio, nombre) es único: se recorren combinaciones 1°A, 2°A ... 6°A, 1°B ...
        return [
            'anio' => ($n % 6) + 1,
            'nombre' => chr(65 + intdiv($n, 6) % 26),
        ];
    }
}

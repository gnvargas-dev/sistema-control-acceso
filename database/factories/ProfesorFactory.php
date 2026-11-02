<?php

namespace Database\Factories;

use App\Models\Profesor;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profesor>
 */
class ProfesorFactory extends Factory
{
    protected $model = Profesor::class;

    public function definition(): array
    {
        return [
            'usuario_id' => Usuario::factory()->profesor(),
            'activo' => true,
        ];
    }
}

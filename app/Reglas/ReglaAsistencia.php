<?php

declare(strict_types=1);

namespace App\Reglas;

use App\Enums\EstadoAsistencia;

/**
 * Strategy: define qué estados cuentan como "asistida" al calcular un porcentaje.
 *
 * El sistema usa hoy varias reglas distintas según la pantalla; cada una es una
 * implementación de esta interfaz para conservar el resultado exacto de cada lugar.
 */
interface ReglaAsistencia
{
    public function cuenta(EstadoAsistencia $estado): bool;
}

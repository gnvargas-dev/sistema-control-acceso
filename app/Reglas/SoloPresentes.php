<?php

declare(strict_types=1);

namespace App\Reglas;

use App\Enums\EstadoAsistencia;

/** Regla del panel de administrador, del inicio del alumno y del resumen del profesor. */
final class SoloPresentes implements ReglaAsistencia
{
    public function cuenta(EstadoAsistencia $estado): bool
    {
        return $estado === EstadoAsistencia::Presente;
    }
}

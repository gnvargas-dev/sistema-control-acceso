<?php

declare(strict_types=1);

namespace App\Reglas;

use App\Enums\EstadoAsistencia;

/** Regla de la API (GET api/asistencias-propias/porcentaje). */
final class PresentesTardanzasYJustificados implements ReglaAsistencia
{
    public function cuenta(EstadoAsistencia $estado): bool
    {
        return $estado !== EstadoAsistencia::Ausente;
    }
}

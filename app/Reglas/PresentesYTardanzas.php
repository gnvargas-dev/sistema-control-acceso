<?php

declare(strict_types=1);

namespace App\Reglas;

use App\Enums\EstadoAsistencia;

/** Regla de la pantalla "Mi asistencia" del alumno. */
final class PresentesYTardanzas implements ReglaAsistencia
{
    public function cuenta(EstadoAsistencia $estado): bool
    {
        return $estado === EstadoAsistencia::Presente || $estado === EstadoAsistencia::Tarde;
    }
}

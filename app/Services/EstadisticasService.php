<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EstadoAsistencia;
use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\Nota;
use App\Models\Profesor;
use App\Valores\PorcentajeAsistencia;

/** Números del panel de inicio del administrador (solo lectura). */
final class EstadisticasService
{
    /** @return array<string, int|float> variables de la vista admin.inicio */
    public function resumenInstitucional(): array
    {
        return [
            'totalAlumnos' => Alumno::count(),
            'totalProfesores' => Profesor::count(),
            'promedioGeneral' => $this->promedioGeneral(),
            'asistenciaGeneral' => $this->asistenciaGeneral(),
        ];
    }

    public function promedioGeneral(): float
    {
        return round((float) Nota::query()->avg('valor'), 2);
    }

    /** Porcentaje de presentes sobre todos los registros (regla SoloPresentes, 1 decimal). */
    public function asistenciaGeneral(): float
    {
        return PorcentajeAsistencia::deConteos(
            Asistencia::query()->where('estado', EstadoAsistencia::Presente->value)->count(),
            Asistencia::count(),
        )->redondeadoOCero(1);
    }
}

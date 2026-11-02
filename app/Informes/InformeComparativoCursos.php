<?php

declare(strict_types=1);

namespace App\Informes;

use App\Models\Curso;

final class InformeComparativoCursos extends Informe
{
    public function tipo(): int
    {
        return 2;
    }

    public function datos(array $filtros): array
    {
        return [
            'cursos' => Curso::query()
                ->orderBy('nombre')
                ->orderBy('id')
                ->get()
                ->map(fn ($curso) => $this->reportes->estadisticasCurso($curso, $filtros)),
        ];
    }
}

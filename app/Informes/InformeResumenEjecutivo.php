<?php

declare(strict_types=1);

namespace App\Informes;

final class InformeResumenEjecutivo extends Informe
{
    public function tipo(): int
    {
        return 1;
    }

    public function datos(array $filtros): array
    {
        return [
            'resumen' => $this->reportes->resumen($filtros),
            'distribucion' => $this->reportes->distribucionPromedios($filtros),
        ];
    }
}

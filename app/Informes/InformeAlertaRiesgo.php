<?php

declare(strict_types=1);

namespace App\Informes;

final class InformeAlertaRiesgo extends Informe
{
    public function tipo(): int
    {
        return 4;
    }

    public function datos(array $filtros): array
    {
        return ['riesgo' => $this->reportes->alertaRiesgo($filtros)];
    }
}

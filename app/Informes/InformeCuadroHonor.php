<?php

declare(strict_types=1);

namespace App\Informes;

final class InformeCuadroHonor extends Informe
{
    public function tipo(): int
    {
        return 3;
    }

    public function datos(array $filtros): array
    {
        return ['cuadroHonor' => $this->reportes->cuadroDeHonor($filtros)];
    }
}

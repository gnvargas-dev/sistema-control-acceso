<?php

declare(strict_types=1);

namespace App\Informes;

use App\Services\ReporteService;

/** Un tipo de "Exportar reporte": cada hija aporta su número y los datos que necesita su vista. */
abstract class Informe
{
    public function __construct(protected readonly ReporteService $reportes)
    {
    }

    /** Número del tipo en el formulario de exportación (1 a 4). */
    abstract public function tipo(): int;

    /**
     * Variables propias de este informe para la vista admin.reporte-pdf.
     *
     * @param  array<string, mixed>  $filtros
     * @return array<string, mixed>
     */
    abstract public function datos(array $filtros): array;

    public function titulo(): string
    {
        return ReporteService::TITULOS[$this->tipo()];
    }
}

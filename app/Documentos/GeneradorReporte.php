<?php

declare(strict_types=1);

namespace App\Documentos;

use Barryvdh\DomPDF\PDF as DomPdf;

/** Reportes institucionales: A4 vertical. */
final class GeneradorReporte extends GeneradorPdf
{
    public function vista(): string
    {
        return 'admin.reporte-pdf';
    }

    protected function configurar(DomPdf $pdf): void
    {
        $pdf->setPaper('a4', 'portrait');
    }
}

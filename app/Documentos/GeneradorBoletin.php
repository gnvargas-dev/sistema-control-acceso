<?php

declare(strict_types=1);

namespace App\Documentos;

/** Boletín individual: usa el papel por defecto de DomPDF, igual que antes. */
final class GeneradorBoletin extends GeneradorPdf
{
    public function vista(): string
    {
        return 'admin.boletin-pdf';
    }
}

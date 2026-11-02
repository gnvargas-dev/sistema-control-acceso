<?php

declare(strict_types=1);

namespace App\Documentos;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

/** Base de los generadores: fija el algoritmo y deja a cada hija la vista y el ajuste del papel. */
abstract class GeneradorPdf implements GeneradorDocumento
{
    final public function generar(array $datos): DomPdf
    {
        $pdf = Pdf::loadView($this->vista(), $datos);

        $this->configurar($pdf);

        return $pdf;
    }

    /** Punto de extensión: por defecto no cambia la configuración de DomPDF. */
    protected function configurar(DomPdf $pdf): void
    {
    }
}

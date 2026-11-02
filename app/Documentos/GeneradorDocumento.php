<?php

declare(strict_types=1);

namespace App\Documentos;

use Barryvdh\DomPDF\PDF;

/**
 * Estrategia común para producir un documento PDF a partir de una vista y sus datos.
 * Cada tipo de documento (boletín, reporte...) la implementa a su manera.
 */
interface GeneradorDocumento
{
    /** Nombre de la vista Blade que dibuja el documento. */
    public function vista(): string;

    /**
     * Arma el PDF con los datos recibidos. Devuelve el objeto de DomPDF para que
     * quien llama decida si lo guarda (output) o lo descarga (download).
     *
     * @param  array<string, mixed>  $datos
     */
    public function generar(array $datos): PDF;
}

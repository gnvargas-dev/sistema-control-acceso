<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Tipo de una evaluación (columna evaluaciones.tipo).
 */
enum TipoEvaluacion: string
{
    case Parcial = 'parcial';
    case Examen = 'examen';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Parcial => 'Parcial',
            self::Examen => 'Examen final',
        };
    }
}

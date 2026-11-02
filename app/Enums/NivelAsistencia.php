<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Nivel con el que la pantalla "Mi asistencia" clasifica un porcentaje.
 */
enum NivelAsistencia: string
{
    case Excelente = 'excelente';
    case Bueno = 'bueno';
    case Regular = 'regular';
    case Malo = 'malo';

    public static function desdePorcentaje(float $porcentaje): self
    {
        return match (true) {
            $porcentaje >= 90 => self::Excelente,
            $porcentaje >= 79 => self::Bueno,
            $porcentaje >= 70 => self::Regular,
            default => self::Malo,
        };
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::Excelente => 'Excelente',
            self::Bueno => 'Bueno',
            self::Regular => 'Regular',
            self::Malo => 'Malo',
        };
    }

    /** Formato que esperan las vistas: ['label' => ..., 'clase' => ...]. */
    public function comoArreglo(): array
    {
        return ['label' => $this->etiqueta(), 'clase' => $this->value];
    }
}

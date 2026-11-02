<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Estados posibles de una asistencia (columna asistencias.estado).
 */
enum EstadoAsistencia: string
{
    case Presente = 'presente';
    case Ausente = 'ausente';
    case Tarde = 'tarde';
    case Justificado = 'justificado';

    /**
     * Simulación de sobrecarga: PHP no la tiene, así que un solo método
     * acepta dos tipos (union type) y devuelve siempre el enum.
     *
     * @throws \ValueError si el texto no es un estado válido
     */
    public static function desde(self|string $estado): self
    {
        if ($estado instanceof self) {
            return $estado;
        }

        return self::from(strtolower(trim($estado)));
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::Presente => 'Presente',
            self::Ausente => 'Ausente',
            self::Tarde => 'Tardanza',
            self::Justificado => 'Justificado',
        };
    }
}

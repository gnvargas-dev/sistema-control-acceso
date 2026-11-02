<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Estado del legajo de un alumno según los documentos entregados.
 * Los valores son los textos que ya usan las vistas y el filtro "estado".
 */
enum EstadoLegajo: string
{
    case SinDocumentos = 'sin_documentos';
    case Incompleto = 'incompleto';
    case Completo = 'completo';

    public static function desdeConteo(int $entregados, int $total): self
    {
        return match (true) {
            $entregados <= 0 => self::SinDocumentos,
            $entregados >= $total => self::Completo,
            default => self::Incompleto,
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Valores posibles de usuarios.rol (enum de la base de datos).
 */
enum RolUsuario: string
{
    case Administrador = 'administrador';
    case Profesor = 'profesor';
    case Alumno = 'alumno';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Profesor => 'Profesor',
            self::Alumno => 'Alumno',
        };
    }
}

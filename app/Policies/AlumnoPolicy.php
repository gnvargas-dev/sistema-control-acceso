<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Alumno;
use App\Models\Materia;
use App\Models\Usuario;

class AlumnoPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return $usuario->rolObjeto()->veListadoDeAlumnos();
    }

    public function viewForMateria(
        Usuario $usuario,
        Materia $materia
    ): bool {
        return $usuario->rolObjeto()->veAlumnosDeMateria($usuario, $materia);
    }

    public function view(Usuario $usuario, Alumno $alumno): bool
    {
        return $usuario->rolObjeto()->veAlumno($usuario, $alumno);
    }
}

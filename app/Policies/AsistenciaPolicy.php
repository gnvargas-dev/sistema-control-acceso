<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Asistencia;
use App\Models\Usuario;

class AsistenciaPolicy
{
    public function view(Usuario $usuario, Asistencia $asistencia): bool
    {
        return $usuario->rolObjeto()->esTitularDelRegistro($usuario, $asistencia->alumno_id);
    }

    public function create(Usuario $usuario, array $datos): bool
    {
        return $usuario->rolObjeto()->cargaDatosDeMateria($usuario, $datos['materia_id']);
    }

    public function update(Usuario $usuario, Asistencia $asistencia): bool
    {
        return $usuario->rolObjeto()->cargaDatosDeMateria($usuario, $asistencia->materia_id);
    }
}

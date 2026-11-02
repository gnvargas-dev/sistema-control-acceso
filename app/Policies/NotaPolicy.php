<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Nota;
use App\Models\Usuario;

class NotaPolicy
{
    public function view(Usuario $usuario, Nota $nota): bool
    {
        return $usuario->rolObjeto()->esTitularDelRegistro($usuario, $nota->alumno_id);
    }

    public function create(Usuario $usuario, array $datos): bool
    {
        return $usuario->rolObjeto()->cargaDatosDeMateria($usuario, $datos['materia_id']);
    }

    public function update(Usuario $usuario, Nota $nota): bool
    {
        return $usuario->rolObjeto()->cargaDatosDeMateria($usuario, $nota->materia_id);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Profesor extends Persona
{
    protected $table = 'profesores';

    // usuario_id y activo se heredan de Persona.

    public function identificador(): string
    {
        return $this->cedula();
    }

    public function descripcion(): string
    {
        $cantidad = $this->materias->count();

        return $cantidad . ($cantidad === 1 ? ' materia asignada' : ' materias asignadas');
    }

    public function materias(): BelongsToMany
    {
        return $this->belongsToMany(
            Materia::class,
            'profesor_materia',
            'profesor_id',
            'materia_id'
        );
    }
}

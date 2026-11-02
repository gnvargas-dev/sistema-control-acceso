<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Alumno;
use App\Models\Materia;
use App\Models\Usuario;
use Illuminate\Support\Collection;

/**
 * Qué materias y alumnos puede trabajar un profesor. Comparten esta lógica
 * la carga de notas, la de asistencias y las pantallas del profesor.
 */
final class DocenciaService
{
    /**
     * Materias del profesor, con su curso y ordenadas por nombre.
     *
     * @return Collection<int, Materia>
     */
    public function materiasDe(Usuario $usuario): Collection
    {
        $profesor = $usuario->profesor;

        abort_unless($profesor, 403);

        return $profesor->materias()->with('curso')->orderBy('nombre')->orderBy('materias.id')->get();
    }

    /** Igual que materiasDe(), con los alumnos de cada curso ya cargados. */
    public function materiasConAlumnos(Usuario $usuario): Collection
    {
        return $this->materiasDe($usuario)->load(['curso.alumnos' => fn ($query) => $query->orderBy('alumnos.id'), 'curso.alumnos.usuario']);
    }

    public function materiaAutorizada(Usuario $usuario, int $materiaId): Materia
    {
        return $this->materiasDe($usuario)->firstWhere('id', $materiaId)
            ?? abort(403, 'Materia no asignada al profesor.');
    }

    public function alumnoDeMateria(Usuario $usuario, int $alumnoId, int $materiaId): Alumno
    {
        $alumno = Alumno::findOrFail($alumnoId);

        abort_unless($alumno->curso_id === $this->materiaAutorizada($usuario, $materiaId)->curso_id, 403);

        return $alumno;
    }

    /**
     * Una fila por alumno y materia (pantalla "Mis alumnos"), ordenada por nombre.
     *
     * @return Collection<int, object{alumno: Alumno, materia: Materia}>
     */
    public function filasDeAlumnos(Usuario $usuario): Collection
    {
        return $this->materiasConAlumnos($usuario)
            ->flatMap(fn ($materia) => $materia->curso->alumnos->map(fn ($alumno) => (object) [
                'alumno' => $alumno,
                'materia' => $materia,
            ]))
            ->sortBy(fn ($fila) => $fila->alumno->usuario->nombre ?? '');
    }
}

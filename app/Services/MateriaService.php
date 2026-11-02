<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Curso;
use App\Models\Materia;
use App\Models\Profesor;
use Illuminate\Support\Collection;

/** Altas, bajas y consultas de materias (panel del administrador y API). */
final class MateriaService
{
    /** @return array<string, mixed> variables de la vista admin.materias */
    public function datosDeIndice(): array
    {
        $materias = Materia::query()
            ->with(['curso', 'profesores.usuario'])
            ->orderBy('nombre')
            ->orderBy('id')
            ->get();

        $cursos = Curso::query()
            ->orderBy('nombre')
            ->orderBy('id')
            ->get();

        $profesores = Profesor::query()
            ->with('usuario')
            ->whereHas('usuario')
            ->orderBy('id')
            ->get()
            ->sortBy(fn ($profesor) => $profesor->usuario->nombre ?? '')
            ->values();

        return compact('materias', 'cursos', 'profesores');
    }

    /** Guarda la materia del formulario del panel y reemplaza sus profesores. */
    public function guardarDesdePanel(Materia $materia, array $datos): void
    {
        $materia->nombre = $datos['nombre'];
        $materia->curso_id = $datos['curso_id'];
        $materia->save();

        $profesorIds = collect($datos['profesor_ids'] ?? [])->filter()->unique()->values();

        $materia->profesores()->sync($profesorIds);
    }

    public function eliminar(Materia $materia): void
    {
        $materia->delete();
    }

    /** Materias activas con su curso (API). */
    public function activas(): Collection
    {
        return Materia::with('curso')->where('activa', true)->get();
    }

    /** Materia con su curso y sus profesores (API). */
    public function conDetalle(Materia $materia): Materia
    {
        return $materia->load('curso', 'profesores');
    }

    public function crear(array $datos): Materia
    {
        return Materia::create($datos)->load('curso');
    }

    public function actualizar(Materia $materia, array $datos): Materia
    {
        $materia->update($datos);

        return $materia->fresh();
    }

    /** Baja lógica: la materia queda inactiva pero conserva sus notas y asistencias. */
    public function darDeBaja(Materia $materia): void
    {
        $materia->update(['activa' => false]);
    }

    /** Alumnos activos del curso de la materia, ordenados por legajo (API). */
    public function alumnosActivos(Materia $materia): Collection
    {
        return $materia->curso
            ->alumnos()
            ->with('usuario:id,nombre,cedula')
            ->where('activo', true)
            ->orderBy('legajo')
            ->orderBy('id')
            ->get();
    }
}

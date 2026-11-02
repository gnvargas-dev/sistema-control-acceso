<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Alumno;
use App\Models\Curso;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/** Listado y mantenimiento de alumnos desde el panel del administrador. */
final class AlumnoService
{
    /**
     * Listado paginado (7 por página). Filtros: curso, estado ('activo' o cualquier otro = inactivo)
     * y búsqueda por nombre, cédula o legajo.
     */
    public function listado(?string $cursoId, ?string $estado, ?string $busqueda): LengthAwarePaginator
    {
        return Alumno::query()
            ->with(['usuario', 'curso'])
            ->join('usuarios', 'usuarios.id', '=', 'alumnos.usuario_id')
            ->when($cursoId !== null, function ($query) use ($cursoId) {
                $query->where('alumnos.curso_id', $cursoId);
            })
            ->when($estado !== null, function ($query) use ($estado) {
                $query->where('alumnos.activo', $estado === 'activo');
            })
            ->when($busqueda !== null, function ($query) use ($busqueda) {
                $query->where(function ($sub) use ($busqueda) {
                    $sub->where('usuarios.nombre', 'like', '%' . $busqueda . '%')
                        ->orWhere('usuarios.cedula', 'like', '%' . $busqueda . '%')
                        ->orWhere('alumnos.legajo', 'like', '%' . $busqueda . '%');
                });
            })
            ->orderBy('usuarios.nombre')
            ->orderBy('alumnos.id')
            ->select('alumnos.*')
            ->paginate(7)
            ->withQueryString();
    }

    /** @return array<string, int> */
    public function estadisticas(): array
    {
        return [
            'total' => Alumno::count(),
            'activos' => Alumno::where('activo', true)->count(),
            'inactivos' => Alumno::where('activo', false)->count(),
            'nuevos_este_mes' => Alumno::query()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];
    }

    /** @param  array<string, mixed>  $datos  datos ya validados */
    public function actualizar(Alumno $alumno, array $datos): void
    {
        $alumno->update($datos);
    }

    public function eliminar(Alumno $alumno): void
    {
        $alumno->delete();
    }

    /** Cursos para el filtro del listado, en su orden natural. */
    public function cursos(): Collection
    {
        return Curso::query()->get();
    }
}

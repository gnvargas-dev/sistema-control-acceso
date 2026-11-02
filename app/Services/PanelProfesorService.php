<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Nota;
use App\Models\Usuario;

final class PanelProfesorService
{
    public function __construct(private readonly DocenciaService $docencia)
    {
    }

    /** @return array<string, mixed> variables de la vista profesor.inicio */
    public function inicio(Usuario $profesor): array
    {
        $materias = $this->docencia->materiasDe($profesor);

        $cursosACargo = $materias->pluck('curso_id')->unique()->count();

        $alumnosPorMateria = $materias->mapWithKeys(function ($materia) {
            return [$materia->id => $materia->curso->alumnos()->with('usuario')->orderBy('id')->get()];
        });

        $alumnosTotal = $alumnosPorMateria->flatten()->pluck('id')->unique()->count();

        $pendientesPorMateria = $materias->map(function ($materia) use ($alumnosPorMateria) {
            $alumnoIds = $alumnosPorMateria[$materia->id]->pluck('id');

            $conNota = Nota::query()
                ->where('materia_id', $materia->id)
                ->whereIn('alumno_id', $alumnoIds)
                ->distinct()
                ->pluck('alumno_id');

            $conAsistenciaHoy = Asistencia::query()
                ->where('materia_id', $materia->id)
                ->where('fecha', now()->toDateString())
                ->whereIn('alumno_id', $alumnoIds)
                ->pluck('alumno_id');

            return (object) [
                'materia' => $materia,
                'sin_notas' => $alumnoIds->diff($conNota)->count(),
                'sin_asistencia_hoy' => $alumnoIds->diff($conAsistenciaHoy)->count(),
            ];
        });

        return [
            'materias' => $materias,
            'alumnosPorMateria' => $alumnosPorMateria,
            'cursosACargo' => $cursosACargo,
            'alumnosTotal' => $alumnosTotal,
            'pendientesPorMateria' => $pendientesPorMateria,
            'totalPendientes' => $pendientesPorMateria->sum(fn ($item) => $item->sin_notas + $item->sin_asistencia_hoy),
            'actividadReciente' => $this->actividadReciente($profesor),
        ];
    }

    private function actividadReciente(Usuario $profesor)
    {
        $notasCargadas = $profesor->notasCargadas()
            ->with('materia', 'alumno.usuario')
            ->latest()
            ->latest('id')
            ->take(5)
            ->get()
            ->map(fn ($nota) => (object) [
                'tipo' => 'nota',
                'texto' => 'Cargaste una nota en ' . ($nota->materia->nombre ?? 'una materia'),
                'fecha' => $nota->created_at,
            ]);

        $asistenciasCargadas = $profesor->asistenciasCargadas()
            ->with('materia')
            ->latest()
            ->latest('id')
            ->take(5)
            ->get()
            ->map(fn ($asistencia) => (object) [
                'tipo' => 'asistencia',
                'texto' => 'Registraste asistencia en ' . ($asistencia->materia->nombre ?? 'una materia'),
                'fecha' => $asistencia->created_at,
            ]);

        return $notasCargadas
            ->concat($asistenciasCargadas)
            ->sortByDesc('fecha')
            ->take(6)
            ->values();
    }
}

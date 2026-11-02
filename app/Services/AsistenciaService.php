<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EstadoAsistencia;
use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\Materia;
use App\Models\Usuario;
use Illuminate\Support\Collection;

final class AsistenciaService
{
    public function __construct(private readonly DocenciaService $docencia)
    {
    }

    /**
     * Datos de la pantalla "Cargar asistencias".
     *
     * $materiaId null = la primera materia del profesor; 0 no tiene materia
     * (como hasta ahora, no vuelve a la primera).
     *
     * @return array<string, mixed>
     */
    public function pantallaDeCarga(Usuario $profesor, ?int $materiaId, ?string $fecha): array
    {
        $materias = $this->docencia->materiasDe($profesor);

        $materiaId ??= $materias->first()?->id;

        $materia = $materiaId ? $this->docencia->materiaAutorizada($profesor, $materiaId) : null;
        $fecha = $fecha ?: now()->toDateString();

        $alumnos = collect();
        $asistenciasExistentes = collect();
        $resumen = ['presentes' => 0, 'ausentes' => 0, 'tardanzas' => 0, 'total' => 0, 'porcentaje' => 0];

        if ($materia) {
            $alumnos = $materia->curso->alumnos()
                ->with('usuario')
                ->orderBy('id')
                ->get()
                ->sortBy(fn ($alumno) => $alumno->usuario->nombre ?? '')
                ->values();

            $asistenciasExistentes = Asistencia::query()
                ->where('materia_id', $materia->id)
                ->where('fecha', $fecha)
                ->get()
                ->keyBy('alumno_id');

            $presentes = $asistenciasExistentes->where('estado', EstadoAsistencia::Presente->value)->count();
            $total = $alumnos->count();

            // Se divide por los alumnos del curso (no por los registros): puede haber más presentes que alumnos.
            $resumen = [
                'presentes' => $presentes,
                'ausentes' => $asistenciasExistentes->where('estado', EstadoAsistencia::Ausente->value)->count(),
                'tardanzas' => $asistenciasExistentes->where('estado', EstadoAsistencia::Tarde->value)->count(),
                'total' => $total,
                'porcentaje' => $total > 0 ? round(($presentes / $total) * 100, 2) : 0,
            ];
        }

        return [
            'materias' => $materias,
            'materia' => $materia,
            'fecha' => $fecha,
            'alumnos' => $alumnos,
            'asistenciasExistentes' => $asistenciasExistentes,
            'resumen' => $resumen,
        ];
    }

    /**
     * Guarda las asistencias de un día. Ignora a los alumnos que no son del curso de la materia.
     *
     * @param  array<int|string, string>       $estados
     * @param  array<int|string, string|null>|null  $observaciones
     */
    public function guardarLote(Usuario $profesor, int $materiaId, string $fecha, array $estados, ?array $observaciones): Materia
    {
        $materia = $this->docencia->materiaAutorizada($profesor, $materiaId);
        $alumnosValidos = $materia->curso->alumnos()->pluck('id');

        foreach ($estados as $alumnoId => $estado) {
            if (! $alumnosValidos->contains((int) $alumnoId)) {
                continue;
            }

            Asistencia::updateOrCreate(
                [
                    'alumno_id' => $alumnoId,
                    'materia_id' => $materia->id,
                    'fecha' => $fecha,
                ],
                [
                    'estado' => EstadoAsistencia::desde($estado)->value,
                    'observacion' => $observaciones[$alumnoId] ?? null,
                    'cargado_por' => $profesor->id,
                ]
            );
        }

        return $materia;
    }

    /**
     * Una fila por materia y fecha, de la más reciente a la más antigua.
     *
     * @param  Collection<int, Materia>  $materias
     */
    public function historial(Collection $materias, ?int $materiaId): Collection
    {
        return Asistencia::query()
            ->whereIn('materia_id', $materias->pluck('id'))
            ->when($materiaId, fn ($query) => $query->where('materia_id', $materiaId))
            ->orderByDesc('fecha')
            ->orderBy('materia_id')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($asistencia) => $asistencia->materia_id . '|' . $asistencia->fecha->toDateString())
            ->map(function ($grupo) use ($materias) {
                $primero = $grupo->first();

                return (object) [
                    'materia' => $materias->firstWhere('id', $primero->materia_id),
                    'fecha' => $primero->fecha,
                    'presentes' => $grupo->where('estado', EstadoAsistencia::Presente->value)->count(),
                    'ausentes' => $grupo->where('estado', EstadoAsistencia::Ausente->value)->count(),
                    'tardanzas' => $grupo->where('estado', EstadoAsistencia::Tarde->value)->count(),
                    'total' => $grupo->count(),
                ];
            })
            ->sortByDesc(fn ($registro) => $registro->fecha->format('Y-m-d'))
            ->values();
    }

    /** Asistencias del alumno (API), las más recientes primero. */
    public function deAlumno(Alumno $alumno): Collection
    {
        return Asistencia::with('materia:id,nombre')
            ->where('alumno_id', $alumno->id)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Porcentaje de asistencia del alumno para la API: cuentan como presentes
     * las asistencias "presente", "tarde" y "justificado".
     *
     * @return array{total: int, presentes: int, porcentaje: int|float}
     */
    public function porcentajeDe(Alumno $alumno): array
    {
        $total = Asistencia::where('alumno_id', $alumno->id)->count();

        $presentes = Asistencia::where('alumno_id', $alumno->id)
            ->whereIn('estado', ['presente', 'tarde', 'justificado'])
            ->count();

        return [
            'total' => $total,
            'presentes' => $presentes,
            'porcentaje' => $total > 0
                ? round(($presentes / $total) * 100, 2)
                : 0,
        ];
    }

    /** Alta de una asistencia desde la API; el permiso ya lo resolvió la policy. */
    public function crear(array $datos, int|string $cargadoPor): Asistencia
    {
        return Asistencia::create([
            ...$datos,
            'cargado_por' => $cargadoPor,
        ]);
    }

    /** Modificación de una asistencia desde la API. */
    public function actualizar(Asistencia $asistencia, array $datos): Asistencia
    {
        $asistencia->update($datos);

        return $asistencia->fresh();
    }
}

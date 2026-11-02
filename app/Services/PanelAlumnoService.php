<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NivelAsistencia;
use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\HistorialAcademico;
use App\Models\Materia;
use App\Models\Nota;
use App\Models\Usuario;
use App\Reglas\PresentesYTardanzas;
use App\Reglas\SoloPresentes;
use App\Valores\Calificacion;
use App\Valores\PorcentajeAsistencia;
use Illuminate\Support\Collection;

/**
 * Datos de las pantallas del alumno. Cada método devuelve las variables que
 * recibe la vista, con los mismos nombres de siempre.
 */
final class PanelAlumnoService
{
    public function alumnoDe(Usuario $usuario): Alumno
    {
        $alumno = $usuario->alumno;

        abort_unless($alumno, 403, 'El usuario autenticado no tiene un perfil de alumno.');

        return $alumno;
    }

    /** @return array<string, mixed> */
    public function inicio(Alumno $alumno): array
    {
        $alumno->load('curso', 'usuario');

        $materias = Materia::query()
            ->where('curso_id', $alumno->curso_id)
            ->with(['profesores' => fn ($query) => $query->orderBy('profesores.id'), 'profesores.usuario'])
            ->orderBy('nombre')
            ->orderBy('id')
            ->get();

        $notas = Nota::query()->where('alumno_id', $alumno->id)->get();
        $asistencias = Asistencia::query()->where('alumno_id', $alumno->id)->get();
        $soloPresentes = new SoloPresentes();

        $materiasResumen = $materias->map(function ($materia) use ($notas, $asistencias, $soloPresentes) {
            $notasMateria = $notas->where('materia_id', $materia->id);
            $asistenciasMateria = $asistencias->where('materia_id', $materia->id);

            $profesor = $materia->profesores->first();

            return (object) [
                'materia' => $materia,
                'profesor' => $profesor?->usuario?->nombre,
                'promedio' => $notasMateria->isNotEmpty() ? round((float) $notasMateria->avg('valor'), 2) : null,
                'asistencia' => PorcentajeAsistencia::deEstados($asistenciasMateria->pluck('estado'), $soloPresentes)->redondeado(1),
            ];
        });

        $promedioGeneral = $notas->isNotEmpty() ? round((float) $notas->avg('valor'), 2) : 0;

        $asistenciaGeneral = PorcentajeAsistencia::deEstados($asistencias->pluck('estado'), $soloPresentes)->redondeadoOCero(1);

        $materiasAprobadas = $materiasResumen
            ->filter(fn ($item) => $item->promedio !== null && $item->promedio >= Calificacion::APROBACION)
            ->count();

        $historial = HistorialAcademico::query()
            ->where('alumno_id', $alumno->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take(6)
            ->get();

        $periodos = $notas->pluck('periodo')
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        return [
            'alumno' => $alumno,
            'materiasResumen' => $materiasResumen,
            'promedioGeneral' => $promedioGeneral,
            'asistenciaGeneral' => $asistenciaGeneral,
            'totalMaterias' => $materias->count(),
            'materiasAprobadas' => $materiasAprobadas,
            'historial' => $historial,
            'periodos' => $periodos,
        ];
    }

    /** @return array<string, mixed> */
    public function asistencia(Alumno $alumno): array
    {
        $alumno->load('curso', 'usuario');

        $materias = Materia::query()
            ->where('curso_id', $alumno->curso_id)
            ->orderBy('nombre')
            ->orderBy('id')
            ->get();

        $asistencias = Asistencia::query()
            ->where('alumno_id', $alumno->id)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        $regla = new PresentesYTardanzas();

        $materiasAsistencia = $materias->map(function ($materia) use ($asistencias, $regla) {
            $registros = $asistencias->where('materia_id', $materia->id);
            $porcentaje = PorcentajeAsistencia::deEstados($registros->pluck('estado'), $regla);
            $redondeado = $porcentaje->redondeado(2);

            return (object) [
                'materia' => $materia,
                'clases' => $porcentaje->total(),
                'asistidas' => $porcentaje->contadas(),
                'inasistencias' => $registros->where('estado', 'ausente')->count(),
                'porcentaje' => $redondeado,
                'estado' => $redondeado !== null ? NivelAsistencia::desdePorcentaje($redondeado)->comoArreglo() : null,
            ];
        });

        $general = PorcentajeAsistencia::deEstados($asistencias->pluck('estado'), $regla);
        $porcentajeGeneral = $general->redondeadoOCero(2);

        $resumenGeneral = [
            'porcentaje' => $porcentajeGeneral,
            'asistidas' => $general->contadas(),
            'inasistencias' => $asistencias->where('estado', 'ausente')->count(),
            'total' => $general->total(),
            'estado' => NivelAsistencia::desdePorcentaje($porcentajeGeneral)->comoArreglo(),
        ];

        $detalleInasistencias = $asistencias
            ->where('estado', 'ausente')
            ->map(fn ($registro) => (object) [
                'fecha' => $registro->fecha,
                'materia' => $materias->firstWhere('id', $registro->materia_id),
            ])
            ->values();

        $diasConClase = $asistencias
            ->groupBy(fn ($registro) => $registro->fecha->toDateString())
            ->map(function ($registros) {
                $tieneAusente = $registros->contains('estado', 'ausente');

                return (object) [
                    'fecha' => $registros->first()->fecha,
                    'estado' => $tieneAusente ? 'ausente' : 'asistio',
                ];
            })
            ->sortByDesc(fn ($dia) => $dia->fecha->format('Y-m-d'))
            ->take(20)
            ->sortBy(fn ($dia) => $dia->fecha->format('Y-m-d'))
            ->values();

        return [
            'alumno' => $alumno,
            'materiasAsistencia' => $materiasAsistencia,
            'resumenGeneral' => $resumenGeneral,
            'detalleInasistencias' => $detalleInasistencias,
            'diasConClase' => $diasConClase,
            'totalMaterias' => $materias->count(),
        ];
    }

    /** Registros del historial para la pantalla "Mi historial", los más nuevos primero. */
    public function registrosDeHistorial(Alumno $alumno): Collection
    {
        return HistorialAcademico::query()
            ->where('alumno_id', $alumno->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /** Historial del alumno para la API, ordenado por período descendente. */
    public function historialPorPeriodo(Alumno $alumno): Collection
    {
        return $alumno->historialAcademico()
            ->orderByDesc('periodo')
            ->orderByDesc('id')
            ->get();
    }
}

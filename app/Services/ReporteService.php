<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EstadoAsistencia;
use App\Informes\Informe;
use App\Informes\InformeAlertaRiesgo;
use App\Informes\InformeComparativoCursos;
use App\Informes\InformeCuadroHonor;
use App\Informes\InformeResumenEjecutivo;
use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Nota;
use App\Models\Profesor;
use App\Valores\Calificacion;
use App\Valores\PorcentajeAsistencia;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cálculos de la pantalla de reportes y de los PDF de "Exportar reporte" (solo lectura).
 *
 * Los filtros son un arreglo con el rango de fechas, el curso y el período anterior
 * de igual duración (para la variación porcentual de los indicadores).
 */
final class ReporteService
{
    public const TITULOS = [
        1 => 'Resumen Ejecutivo Institucional',
        2 => 'Reporte Comparativo por Curso',
        3 => 'Cuadro de Honor y Alumnos Destacados',
        4 => 'Alerta de Riesgo Académico y Ausentismo',
    ];

    /** @return array<string, mixed> variables de la vista admin.reportes */
    public function pantalla(array $filtros): array
    {
        $cursosPaginados = Curso::query()
            ->orderBy('nombre')
            ->orderBy('id')
            ->paginate(5)
            ->withQueryString();

        $cursosPaginados->setCollection(
            $cursosPaginados->getCollection()
                ->map(fn ($curso) => $this->estadisticasCurso($curso, $filtros))
        );

        return [
            'filtros' => $filtros,
            'resumen' => $this->resumen($filtros),
            'cursosFiltro' => Curso::query()->orderBy('nombre')->orderBy('id')->get(),
            'resumenPorCurso' => $cursosPaginados,
            'promedioPorCursoChart' => $this->promedioPorCurso($filtros),
            'distribucion' => $this->distribucionPromedios($filtros),
        ];
    }

    public function titulo(int $tipo): string
    {
        return self::TITULOS[$tipo];
    }

    /**
     * Datos de la vista del PDF según el tipo de reporte:
     * 1 = Resumen ejecutivo, 2 = Comparativo por curso, 3 = Cuadro de honor, 4 = Alerta de riesgo.
     *
     * @return array<string, mixed>
     */
    public function contextoDePdf(int $tipo, array $filtros): array
    {
        $informe = $this->informe($tipo);

        return [
            'tipo' => $tipo,
            'titulo' => $informe->titulo(),
            'filtros' => $filtros,
            'generadoEl' => now(),
            ...$informe->datos($filtros),
        ];
    }

    /** Elige la estrategia de informe según el tipo del formulario. */
    private function informe(int $tipo): Informe
    {
        return match ($tipo) {
            1 => new InformeResumenEjecutivo($this),
            2 => new InformeComparativoCursos($this),
            3 => new InformeCuadroHonor($this),
            4 => new InformeAlertaRiesgo($this),
        };
    }

    public function nombreDeArchivo(int $tipo): string
    {
        return 'reporte-' . str($this->titulo($tipo))->slug() . '-' . now()->format('Y-m-d') . '.pdf';
    }

    /**
     * Normaliza el rango de fechas recibido por query string y calcula
     * automáticamente el período anterior de igual duración, usado para
     * mostrar la variación porcentual ("vs. período anterior") en los KPIs.
     */
    public function filtros(mixed $desde, mixed $hasta, mixed $cursoId): array
    {
        $hasta = Carbon::parse($hasta ?: now()->toDateString())->endOfDay();
        $desde = Carbon::parse($desde ?: now()->startOfMonth()->toDateString())->startOfDay();

        if ($hasta->lt($desde)) {
            [$desde, $hasta] = [$hasta->copy()->startOfDay(), $desde->copy()->endOfDay()];
        }

        $dias = $desde->diffInDays($hasta) + 1;

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'curso_id' => $cursoId ?: null,
            'desde_anterior' => $desde->copy()->subDays($dias),
            'hasta_anterior' => $desde->copy()->subDay()->endOfDay(),
        ];
    }

    public function resumen(array $filtros): array
    {
        $totalAlumnos = Alumno::query()
            ->when($filtros['curso_id'], fn ($q) => $q->where('curso_id', $filtros['curso_id']))
            ->count();

        $promedioActual = $this->promedioEnPeriodo($filtros['desde'], $filtros['hasta'], $filtros['curso_id']);
        $promedioAnterior = $this->promedioEnPeriodo($filtros['desde_anterior'], $filtros['hasta_anterior'], $filtros['curso_id']);

        $asistenciaActual = $this->asistenciaEnPeriodo($filtros['desde'], $filtros['hasta'], $filtros['curso_id']);
        $asistenciaAnterior = $this->asistenciaEnPeriodo($filtros['desde_anterior'], $filtros['hasta_anterior'], $filtros['curso_id']);

        return [
            'total_alumnos' => $totalAlumnos,
            'total_profesores' => Profesor::count(),
            'promedio_general' => $promedioActual,
            'promedio_variacion' => $this->variacion($promedioActual, $promedioAnterior),
            'asistencia_general' => $asistenciaActual,
            'asistencia_variacion' => $this->variacion($asistenciaActual, $asistenciaAnterior),
        ];
    }

    private function variacion(float $actual, float $anterior): ?float
    {
        if ($anterior <= 0) {
            return null;
        }

        return round((($actual - $anterior) / $anterior) * 100, 1);
    }

    private function promedioEnPeriodo(Carbon $desde, Carbon $hasta, $cursoId = null): float
    {
        $promedio = Nota::query()
            ->join('alumnos', 'alumnos.id', '=', 'notas.alumno_id')
            ->whereBetween('notas.created_at', [$desde, $hasta])
            ->when($cursoId, fn ($q) => $q->where('alumnos.curso_id', $cursoId))
            ->avg('notas.valor');

        return round((float) $promedio, 2);
    }

    private function asistenciaEnPeriodo(Carbon $desde, Carbon $hasta, $cursoId = null): float
    {
        $query = Asistencia::query()
            ->join('alumnos', 'alumnos.id', '=', 'asistencias.alumno_id')
            ->whereBetween('asistencias.fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->when($cursoId, fn ($q) => $q->where('alumnos.curso_id', $cursoId));

        $total = (clone $query)->count();

        if ($total === 0) {
            return 0;
        }

        $presentes = (clone $query)->where('asistencias.estado', EstadoAsistencia::Presente->value)->count();

        return PorcentajeAsistencia::deConteos($presentes, $total)->redondeadoOCero(1);
    }

    private function promedioAlumnoEnPeriodo(int $alumnoId, Carbon $desde, Carbon $hasta): ?float
    {
        $promedio = Nota::query()
            ->where('alumno_id', $alumnoId)
            ->whereBetween('created_at', [$desde, $hasta])
            ->avg('valor');

        return $promedio !== null ? round((float) $promedio, 2) : null;
    }

    private function asistenciaAlumnoEnPeriodo(int $alumnoId, Carbon $desde, Carbon $hasta): ?float
    {
        $total = Asistencia::where('alumno_id', $alumnoId)
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->count();

        if ($total === 0) {
            return null;
        }

        $presentes = Asistencia::where('alumno_id', $alumnoId)
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->where('estado', EstadoAsistencia::Presente->value)
            ->count();

        return PorcentajeAsistencia::deConteos($presentes, $total)->redondeado(1);
    }

    /**
     * Matrícula, % de asistencia, promedio y mejor/menor promedio (con
     * nombre y nota) de un curso puntual, para la tabla "Resumen por curso"
     * y para el reporte comparativo en PDF.
     *
     * Desempate: los alumnos llegan ordenados por id y el ordenamiento de la colección es
     * estable, así que ante promedios iguales "mejor" y "menor" son el de menor id.
     */
    public function estadisticasCurso(Curso $curso, array $filtros): array
    {
        $alumnos = Alumno::query()
            ->where('curso_id', $curso->id)
            ->with('usuario')
            ->orderBy('id')
            ->get()
            ->map(function ($alumno) use ($filtros) {
                return [
                    'nombre' => $alumno->usuario->nombre ?? 'Alumno sin nombre',
                    'promedio' => $this->promedioAlumnoEnPeriodo($alumno->id, $filtros['desde'], $filtros['hasta']),
                ];
            });

        $conNota = $alumnos->filter(fn ($item) => $item['promedio'] !== null);

        return [
            'curso' => $curso,
            'matricula' => $alumnos->count(),
            'asistencia' => $this->asistenciaEnPeriodo($filtros['desde'], $filtros['hasta'], $curso->id),
            'promedio' => $conNota->isEmpty() ? 0 : round($conNota->avg('promedio'), 2),
            'mejor' => $conNota->sortByDesc('promedio')->first(),
            'menor' => $conNota->sortBy('promedio')->first(),
        ];
    }

    public function promedioPorCurso(array $filtros): Collection
    {
        return Curso::query()
            ->orderBy('nombre')
            ->orderBy('id')
            ->get()
            ->map(fn ($curso) => [
                'curso' => $curso->nombre,
                'promedio' => $this->promedioEnPeriodo($filtros['desde'], $filtros['hasta'], $curso->id),
            ]);
    }

    /**
     * Cuántos alumnos caen en cada franja de promedio (4.5-5, 3.5-4.4, etc.),
     * para el gráfico de dona "Distribución de promedios".
     */
    public function distribucionPromedios(array $filtros): array
    {
        $promedios = Alumno::query()
            ->when($filtros['curso_id'], fn ($q) => $q->where('curso_id', $filtros['curso_id']))
            ->get()
            ->map(fn ($alumno) => $this->promedioAlumnoEnPeriodo($alumno->id, $filtros['desde'], $filtros['hasta']))
            ->filter(fn ($promedio) => $promedio !== null);

        $rangos = [
            '4.5 - 5' => fn ($p) => $p >= 4.5,
            '3.5 - 4.4' => fn ($p) => $p >= 3.5 && $p < 4.5,
            '2.5 - 3.4' => fn ($p) => $p >= 2.5 && $p < 3.5,
            '1.5 - 2.4' => fn ($p) => $p >= 1.5 && $p < 2.5,
            '1 - 1.4' => fn ($p) => $p < 1.5,
        ];

        $total = $promedios->count();
        $resultado = [];

        foreach ($rangos as $etiqueta => $condicion) {
            $cantidad = $promedios->filter($condicion)->count();

            $resultado[] = [
                'rango' => $etiqueta,
                'cantidad' => $cantidad,
                'porcentaje' => $total > 0 ? round(($cantidad / $total) * 100) : 0,
            ];
        }

        return ['total' => $total, 'rangos' => $resultado];
    }

    /**
     * Top de alumnos por promedio, a nivel institucional y el mejor de
     * cada curso, para el "Cuadro de honor" (opción 3 de exportación).
     *
     * Desempate: con promedios iguales manda el menor id de alumno (consulta ordenada por id
     * + ordenamiento estable), así el corte del puesto $limite y el "mejor de cada curso" son fijos.
     */
    public function cuadroDeHonor(array $filtros, int $limite = 20): array
    {
        $alumnos = Alumno::query()
            ->with(['usuario', 'curso'])
            ->when($filtros['curso_id'], fn ($q) => $q->where('curso_id', $filtros['curso_id']))
            ->orderBy('id')
            ->get()
            ->map(function ($alumno) use ($filtros) {
                return [
                    'nombre' => $alumno->usuario->nombre ?? 'Alumno sin nombre',
                    'curso' => $alumno->curso->nombre ?? 'Sin curso',
                    'promedio' => $this->promedioAlumnoEnPeriodo($alumno->id, $filtros['desde'], $filtros['hasta']),
                ];
            })
            ->filter(fn ($item) => $item['promedio'] !== null)
            ->sortByDesc('promedio')
            ->values();

        return [
            'institucional' => $alumnos->take($limite),
            'por_curso' => $alumnos->groupBy('curso')->map(
                fn ($grupo) => $grupo->sortByDesc('promedio')->first()
            ),
        ];
    }

    /**
     * Alumnos con promedio menor a la nota de aprobación (2) y/o asistencia menor al mínimo (AsistenciaMetricasService::MINIMO, 70%) en el
     * período seleccionado, para la alerta de riesgo académico (opción 4).
     */
    public function alertaRiesgo(array $filtros): Collection
    {
        return Alumno::query()
            ->with(['usuario', 'curso'])
            ->when($filtros['curso_id'], fn ($q) => $q->where('curso_id', $filtros['curso_id']))
            ->orderBy('id')
            ->get()
            ->map(function ($alumno) use ($filtros) {
                $promedio = $this->promedioAlumnoEnPeriodo($alumno->id, $filtros['desde'], $filtros['hasta']);
                $asistencia = $this->asistenciaAlumnoEnPeriodo($alumno->id, $filtros['desde'], $filtros['hasta']);

                $motivos = collect([
                    $promedio !== null && $promedio < Calificacion::APROBACION ? 'Promedio bajo' : null,
                    $asistencia !== null && $asistencia < AsistenciaMetricasService::MINIMO ? 'Ausentismo' : null,
                ])->filter()->implode(' + ');

                return [
                    'nombre' => $alumno->usuario->nombre ?? 'Alumno sin nombre',
                    'curso' => $alumno->curso->nombre ?? 'Sin curso',
                    'promedio' => $promedio,
                    'asistencia' => $asistencia,
                    'motivo' => $motivos,
                ];
            })
            ->filter(function ($item) {
                return ($item['promedio'] !== null && $item['promedio'] < Calificacion::APROBACION)
                    || ($item['asistencia'] !== null && $item['asistencia'] < AsistenciaMetricasService::MINIMO);
            })
            // Primero los alumnos sin promedio, luego de menor a mayor; los empates conservan el orden por id.
            ->sort(fn (array $a, array $b) => [$a['promedio'] !== null, $a['promedio']]
                <=> [$b['promedio'] !== null, $b['promedio']])
            ->values();
    }

    /**
     * Cantidad de notas y promedio por materia (API de reportes); $periodo vacío = todos.
     *
     * @return Collection<int, Nota>
     */
    public function notasPorMateria(?string $periodo = null): Collection
    {
        return Nota::query()
            ->select(
                'materias.nombre',
                DB::raw('COUNT(notas.id) as cantidad'),
                DB::raw('ROUND(AVG(notas.valor), 2) as promedio')
            )
            ->join('materias', 'materias.id', '=', 'notas.materia_id')
            ->when($periodo, fn ($query) => $query->where('periodo', $periodo))
            ->groupBy('materias.id', 'materias.nombre')
            ->orderBy('materias.nombre')
            ->orderBy('materias.id')
            ->get();
    }

    /**
     * Total, presentes y ausentes por materia (API de reportes).
     *
     * @return Collection<int, Asistencia>
     */
    public function asistenciasPorMateria(): Collection
    {
        return Asistencia::query()
            ->select(
                'materias.nombre',
                DB::raw('COUNT(asistencias.id) as total'),
                DB::raw("SUM(CASE WHEN estado = 'presente' THEN 1 ELSE 0 END) as presentes"),
                DB::raw("SUM(CASE WHEN estado = 'ausente' THEN 1 ELSE 0 END) as ausentes")
            )
            ->join('materias', 'materias.id', '=', 'asistencias.materia_id')
            ->groupBy('materias.id', 'materias.nombre')
            ->orderBy('materias.nombre')
            ->orderBy('materias.id')
            ->get();
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Documentos\GeneradorBoletin;
use App\Models\Alumno;
use App\Models\BoletinGenerado;
use App\Models\Curso;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Boletines en PDF: generación, historial y descarga. */
final class BoletinService
{
    public function __construct(private readonly GeneradorBoletin $generador = new GeneradorBoletin())
    {
    }

    /**
     * Datos de la pantalla de boletines.
     *
     * @return array<string, mixed>
     */
    public function pantalla(?string $cursoId, ?string $periodo, ?string $busqueda): array
    {
        return [
            'alumnos' => Alumno::query()
                ->with(['usuario', 'curso'])
                ->join('usuarios', 'usuarios.id', '=', 'alumnos.usuario_id')
                ->orderBy('usuarios.nombre')
                ->orderBy('alumnos.id')
                ->select('alumnos.*')
                ->get(),
            'cursos' => Curso::query()->orderBy('anio')->orderBy('nombre')->orderBy('id')->get(),
            'periodos' => BoletinGenerado::query()
                ->select('periodo')
                ->distinct()
                ->orderByDesc('periodo')
                ->pluck('periodo'),
            'historial' => $this->historial($cursoId, $periodo, $busqueda),
            'estadisticas' => $this->estadisticas(),
        ];
    }

    private function historial(?string $cursoId, ?string $periodo, ?string $busqueda): LengthAwarePaginator
    {
        return BoletinGenerado::query()
            ->with(['alumno.usuario', 'curso', 'generadoPor'])
            ->when($cursoId !== null, function ($query) use ($cursoId) {
                $query->where('curso_id', $cursoId);
            })
            ->when($periodo !== null, function ($query) use ($periodo) {
                $query->where('periodo', $periodo);
            })
            ->when($busqueda !== null, function ($query) use ($busqueda) {
                $query->whereHas('alumno.usuario', function ($sub) use ($busqueda) {
                    $sub->where('nombre', 'like', '%' . $busqueda . '%');
                });
            })
            ->latest('id')
            ->paginate(5)
            ->withQueryString();
    }

    /** @return array<string, int> */
    private function estadisticas(): array
    {
        return [
            'total' => BoletinGenerado::count(),
            'este_trimestre' => BoletinGenerado::query()
                ->whereBetween('created_at', [now()->startOfQuarter(), now()->endOfQuarter()])
                ->count(),
            'alumnos_evaluados' => BoletinGenerado::query()
                ->distinct('alumno_id')
                ->count('alumno_id'),
            'descargas' => (int) BoletinGenerado::sum('descargas'),
        ];
    }

    /**
     * Alumnos a los que corresponde un boletín según el alumno y/o el curso elegidos
     * (un valor vacío no filtra).
     *
     * @return Collection<int, Alumno>
     */
    public function alumnosParaBoletin(mixed $alumnoId, mixed $cursoId): Collection
    {
        $consulta = Alumno::query()
            ->with(['usuario', 'curso', 'notas.materia']);

        if (! empty($alumnoId)) {
            $consulta->where('id', $alumnoId);
        }

        if (! empty($cursoId)) {
            $consulta->where('curso_id', $cursoId);
        }

        return $consulta->orderBy('id')->get();
    }

    /**
     * Genera y guarda un PDF por alumno y deja el registro en el historial.
     *
     * @param  Collection<int, Alumno>  $alumnos
     * @return int cantidad de boletines generados
     */
    public function generar(Collection $alumnos, string $periodo, int|string|null $generadoPor): int
    {
        Storage::disk('local')->makeDirectory('boletines');

        foreach ($alumnos as $alumno) {
            $pdf = $this->generador->generar([
                'alumnos' => collect([$alumno]),
                'periodo' => $periodo,
            ]);

            $sufijo = now()->format('Ymd_His') . '_' . Str::random(6);
            $nombreAlumno = Str::slug($alumno->usuario->nombre ?? 'alumno', '');
            $nombreCurso = $alumno->curso
                ? Str::slug(($alumno->curso->anio ?? '') . $alumno->curso->nombre, '')
                : 'sc';

            $nombreArchivo = "boletines/Boletin_{$nombreAlumno}_{$nombreCurso}_{$sufijo}.pdf";

            Storage::disk('local')->put($nombreArchivo, $pdf->output());

            BoletinGenerado::create([
                'alumno_id' => $alumno->id,
                'curso_id' => $alumno->curso_id,
                'periodo' => $periodo,
                'archivo' => $nombreArchivo,
                'generado_por' => $generadoPor,
            ]);
        }

        return $alumnos->count();
    }

    public function existeArchivo(BoletinGenerado $boletin): bool
    {
        return Storage::disk('local')->exists($boletin->archivo);
    }

    /** Descarga el PDF y suma una descarga al contador. */
    public function descargar(BoletinGenerado $boletin): StreamedResponse
    {
        $boletin->increment('descargas');

        $nombreDescarga = 'Boletin_' . Str::slug($boletin->alumno->usuario->nombre ?? 'alumno', '_') . '.pdf';

        return Storage::disk('local')->download($boletin->archivo, $nombreDescarga);
    }

    public function ver(BoletinGenerado $boletin): BinaryFileResponse
    {
        return response()->file(Storage::disk('local')->path($boletin->archivo));
    }
}

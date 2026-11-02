<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Alumno;
use App\Models\Nota;
use App\Models\Usuario;
use App\Valores\Calificacion;
use Illuminate\Support\Collection;

final class NotaService
{
    public function __construct(private readonly DocenciaService $docencia)
    {
    }

    /**
     * Guarda la nota de un alumno en una materia y período (si ya existía, la reemplaza).
     *
     * $nota admite int, float o texto numérico (simulación de sobrecarga: la
     * resuelve Calificacion::de(), que además valida que sea un entero de 1 a 5).
     */
    public function registrar(Usuario $profesor, int $materiaId, int $alumnoId, string $periodo, int|float|string $nota): Nota
    {
        $this->docencia->materiaAutorizada($profesor, $materiaId);
        $this->docencia->alumnoDeMateria($profesor, $alumnoId, $materiaId);

        return Nota::updateOrCreate(
            [
                'alumno_id' => $alumnoId,
                'materia_id' => $materiaId,
                'periodo' => $periodo,
            ],
            [
                'valor' => Calificacion::de($nota)->valor(),
                'cargado_por' => $profesor->id,
            ]
        );
    }

    /** Notas del alumno (API), ordenadas por período; $periodo vacío = todas. */
    public function deAlumno(Alumno $alumno, ?string $periodo = null): Collection
    {
        return Nota::with('materia:id,nombre')
            ->where('alumno_id', $alumno->id)
            ->when($periodo, fn ($query) => $query->where('periodo', $periodo))
            ->orderBy('periodo')
            ->orderBy('id')
            ->get();
    }

    /** Promedio simple de todas las notas del alumno (0 si no tiene). */
    public function promedioDe(Alumno $alumno): float
    {
        return round((float) Nota::where('alumno_id', $alumno->id)->avg('valor'), 2);
    }

    /** Alta de una nota desde la API; el permiso ya lo resolvió la policy. */
    public function crear(array $datos, int|string $cargadoPor): Nota
    {
        $nota = Nota::create([
            ...$datos,
            'cargado_por' => $cargadoPor,
        ]);

        return $nota->load(['alumno', 'materia']);
    }

    /** Modificación de una nota desde la API (solo los campos que acepta la validación). */
    public function actualizar(Nota $nota, array $datos): Nota
    {
        $nota->update([
            'alumno_id' => $datos['alumno_id'],
            'materia_id' => $datos['materia_id'],
            'valor' => $datos['valor'],
            'periodo' => $datos['periodo'],
            'observacion' => $datos['observacion'] ?? null,
        ]);

        return $nota->fresh();
    }
}

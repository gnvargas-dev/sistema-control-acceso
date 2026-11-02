<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EstadoLegajo;
use App\Models\Alumno;
use App\Models\Curso;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/** Legajos de los alumnos: estado de la documentación entregada. */
final class LegajoService
{
    private const POR_PAGINA = 7;

    /**
     * Datos de la pantalla de legajos. Los filtros llegan ya normalizados
     * (null = sin filtro) y $url / $query sirven para armar los enlaces de paginación.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed> variables de la vista admin.legajos
     */
    public function pantalla(?string $busqueda, ?string $cursoId, ?string $estado, int $pagina, string $url, array $query): array
    {
        $totalDocumentos = $this->totalDocumentos();

        $alumnos = Alumno::query()
            ->select('alumnos.*')
            ->with(['usuario', 'curso', 'documentos'])
            ->withCount('documentos')
            ->join('usuarios', 'usuarios.id', '=', 'alumnos.usuario_id')
            ->when($busqueda, function ($consulta) use ($busqueda) {
                $consulta->where(function ($sub) use ($busqueda) {
                    $sub->where('usuarios.nombre', 'like', '%' . $busqueda . '%')
                        ->orWhere('alumnos.legajo', 'like', '%' . $busqueda . '%');
                });
            })
            ->when($cursoId !== null, function ($consulta) use ($cursoId) {
                $consulta->where('alumnos.curso_id', $cursoId);
            })
            ->orderBy('usuarios.nombre')
            ->orderBy('alumnos.id')
            ->get()
            ->map(function ($alumno) use ($totalDocumentos) {
                $entregados = (int) $alumno->documentos_count;

                $alumno->documentos_entregados = $entregados;
                $alumno->documentos_total = $totalDocumentos;
                $alumno->estado_legajo = EstadoLegajo::desdeConteo($entregados, $totalDocumentos)->value;

                $ultimoDocumento = $alumno->documentos->max('updated_at');
                $alumno->ultima_actualizacion = $ultimoDocumento ?? $alumno->updated_at;

                return $alumno;
            });

        if ($estado !== null) {
            $alumnos = $alumnos
                ->filter(fn ($alumno) => $alumno->estado_legajo === $estado)
                ->values();
        }

        $alumnosPaginados = new LengthAwarePaginator(
            $alumnos->forPage($pagina, self::POR_PAGINA)->values(),
            $alumnos->count(),
            self::POR_PAGINA,
            $pagina,
            ['path' => $url, 'query' => $query]
        );

        return [
            'alumnos' => $alumnosPaginados,
            'busqueda' => $busqueda,
            'estadisticas' => $this->estadisticas($totalDocumentos),
            'cursos' => Curso::query()->orderBy('nombre')->orderBy('id')->get(),
            'alumnosParaSelect' => Alumno::query()
                ->with(['usuario', 'curso'])
                ->join('usuarios', 'usuarios.id', '=', 'alumnos.usuario_id')
                ->orderBy('usuarios.nombre')
                ->orderBy('alumnos.id')
                ->select('alumnos.*')
                ->get(),
            'categoriasDocumentos' => config('legajo.categorias'),
            'totalDocumentos' => $totalDocumentos,
        ];
    }

    /** @return array<string, int|float> */
    private function estadisticas(int $totalDocumentos): array
    {
        $todos = Alumno::withCount('documentos')->get();
        $totalAlumnos = $todos->count();
        $completos = $todos->where('documentos_count', '>=', $totalDocumentos)->count();
        $sinDocumentos = $todos->where('documentos_count', '<=', 0)->count();
        $incompletos = $totalAlumnos - $completos - $sinDocumentos;

        return [
            'total' => $totalAlumnos,
            'completos' => $completos,
            'incompletos' => $incompletos,
            'sin_documentos' => $sinDocumentos,
            'completos_pct' => $totalAlumnos ? round($completos / $totalAlumnos * 100, 1) : 0,
            'incompletos_pct' => $totalAlumnos ? round($incompletos / $totalAlumnos * 100, 1) : 0,
            'sin_documentos_pct' => $totalAlumnos ? round($sinDocumentos / $totalAlumnos * 100, 1) : 0,
        ];
    }

    /** Documentos del alumno agrupados por categoría, marcando los entregados (respuesta JSON del modal). */
    public function documentosDe(Alumno $alumno): array
    {
        $alumno->load('usuario', 'curso');

        $marcados = $alumno->documentos()->pluck('documento')->all();

        $categorias = collect(config('legajo.categorias'))->map(function ($categoria) use ($marcados) {
            return [
                'clave' => $categoria['clave'],
                'nombre' => $categoria['nombre'],
                'items' => collect($categoria['items'])
                    ->map(function ($nombre, $slug) use ($marcados) {
                        return [
                            'slug' => $slug,
                            'nombre' => $nombre,
                            'marcado' => in_array($slug, $marcados, true),
                        ];
                    })
                    ->values(),
            ];
        })->values();

        return [
            'alumno' => [
                'id' => $alumno->id,
                'nombre' => $alumno->usuario->nombre ?? 'Alumno sin nombre',
                'curso' => $alumno->curso
                    ? (($alumno->curso->anio ?? '') . '° ' . $alumno->curso->nombre)
                    : 'Sin curso',
            ],
            'categorias' => $categorias,
            'total' => $this->totalDocumentos(),
            'entregados' => count($marcados),
        ];
    }

    /**
     * Reemplaza los documentos entregados por la selección. Los códigos que no
     * están en el catálogo no se guardan, pero sí se cuentan en el resultado.
     *
     * @param  array<int, string>  $documentos
     * @return int cantidad de documentos recibidos
     */
    public function guardarDocumentos(Alumno $alumno, array $documentos): int
    {
        $seleccionados = collect($documentos)->unique()->values();

        $catalogo = collect(config('legajo.categorias'))
            ->flatMap(function ($categoria) {
                return collect($categoria['items'])
                    ->keys()
                    ->mapWithKeys(fn ($slug) => [$slug => $categoria['clave']]);
            });

        DB::transaction(function () use ($alumno, $seleccionados, $catalogo) {
            $alumno->documentos()->delete();

            foreach ($seleccionados as $slug) {
                if (! $catalogo->has($slug)) {
                    continue;
                }

                $alumno->documentos()->create([
                    'categoria' => $catalogo[$slug],
                    'documento' => $slug,
                    'fecha_entrega' => now(),
                ]);
            }
        });

        return $seleccionados->count();
    }

    public function totalDocumentos(): int
    {
        return collect(config('legajo.categorias'))
            ->sum(fn ($categoria) => count($categoria['items']));
    }
}

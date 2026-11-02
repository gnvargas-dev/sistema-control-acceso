<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TipoEvaluacion;
use App\Models\Alumno;
use App\Models\Evaluacion;
use App\Models\Materia;
use App\Models\Nota;
use App\Models\ResultadoEvaluacion;
use App\Models\Usuario;
use App\Valores\ResultadoSemestral;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Evaluaciones por puntos y nota final por semestre.
 *
 * El docente configura de 2 a 5 parciales y un examen final por materia y semestre,
 * y carga los puntos de cada alumno. La nota del semestre se calcula acá (nunca en la
 * pantalla) y se guarda en notas con NotaService::registrar, en el período
 * "Semestre 1" o "Semestre 2", solo cuando el alumno tiene resultado en TODAS las
 * evaluaciones del semestre. Mientras falte alguna queda "Pendiente": sin nota guardada.
 *
 * Las reglas de negocio lanzan InvalidArgumentException con un mensaje listo para mostrar.
 */
final class EvaluacionService
{
    public const MIN_PARCIALES = 2;

    public const MAX_PARCIALES = 5;

    public const SEMESTRES = [1, 2];

    /** Rango del puntaje máximo que elige el docente para un parcial o el examen (enteros). */
    public const PUNTOS_MIN = 5;

    public const PUNTOS_MAX = 100;

    public function __construct(
        private readonly DocenciaService $docencia,
        private readonly NotaService $notas,
    ) {
    }

    /** Período con el que se guarda la nota final en la tabla notas. */
    public static function periodoDe(int $semestre): string
    {
        return Nota::PREFIJO_SEMESTRE . ' ' . $semestre;
    }

    /**
     * Evaluaciones de la materia en el semestre: parciales por orden y luego el examen.
     *
     * @return Collection<int, Evaluacion>
     */
    public function evaluacionesDe(int $materiaId, int $semestre): Collection
    {
        return Evaluacion::query()
            ->delSemestre($materiaId, $semestre)
            ->orderByRaw("tipo = 'examen'")
            ->orderBy('orden')
            ->get();
    }

    /**
     * Resultado calculado del alumno en el semestre; null si está "Pendiente"
     * (semestre sin configurar o con evaluaciones sin cargar).
     */
    public function resultadoDe(int $alumnoId, int $materiaId, int $semestre): ?ResultadoSemestral
    {
        $evaluaciones = $this->evaluacionesDe($materiaId, $semestre);

        $porEvaluacion = ResultadoEvaluacion::query()
            ->where('alumno_id', $alumnoId)
            ->whereIn('evaluacion_id', $evaluaciones->modelKeys())
            ->get()
            ->keyBy('evaluacion_id');

        return $this->calcular($evaluaciones, $porEvaluacion);
    }

    /**
     * Define los parciales y el examen del semestre (crea, edita y quita) y recalcula
     * las notas de los alumnos del curso.
     *
     * @param list<array{id?: int|string|null, nombre?: string|null, puntos_max: int|string}> $parciales
     *        Los que traen "id" se editan; los que no, se crean; los existentes que no aparecen se quitan.
     * @param array{nombre?: string|null, puntos_max: int|string} $examen
     * @param bool $confirmarBorrado debe ser true para quitar parciales que ya tienen resultados
     *
     * @return Collection<int, Evaluacion>
     */
    public function configurarSemestre(
        Usuario $profesor,
        int $materiaId,
        int $semestre,
        array $parciales,
        array $examen,
        bool $confirmarBorrado = false,
    ): Collection {
        $materia = $this->docencia->materiaAutorizada($profesor, $materiaId);
        $this->validarSemestre($semestre);

        $parciales = array_values($parciales);
        $cantidad = count($parciales);

        if ($cantidad < self::MIN_PARCIALES || $cantidad > self::MAX_PARCIALES) {
            throw new InvalidArgumentException(
                'Cada semestre debe tener de ' . self::MIN_PARCIALES . ' a ' . self::MAX_PARCIALES . " parciales. Recibido: {$cantidad}."
            );
        }

        $parciales = array_map(fn (array $parcial) => [
            'id' => ($parcial['id'] ?? null) === null || $parcial['id'] === '' ? null : (int) $parcial['id'],
            'nombre' => $this->nombreValido($parcial['nombre'] ?? null),
            'puntos_max' => $this->puntosMaxValidos($parcial['puntos_max'] ?? null),
        ], $parciales);

        $ids = array_filter(array_column($parciales, 'id'));

        if (count($ids) !== count(array_unique($ids))) {
            throw new InvalidArgumentException('Un parcial aparece repetido.');
        }

        $examen = [
            'nombre' => $this->nombreValido($examen['nombre'] ?? null) ?? TipoEvaluacion::Examen->etiqueta(),
            'puntos_max' => $this->puntosMaxValidos($examen['puntos_max'] ?? null),
        ];

        DB::transaction(function () use ($profesor, $materia, $semestre, $parciales, $ids, $examen, $confirmarBorrado): void {
            // Serializa las configuraciones concurrentes de la misma materia.
            Materia::query()->whereKey($materia->id)->lockForUpdate()->first();

            /** @var Collection<int, Evaluacion> $existentes */
            $existentes = Evaluacion::query()->delSemestre($materia->id, $semestre)->parciales()->get()->keyBy('id');

            foreach ($ids as $id) {
                if (! $existentes->has($id)) {
                    throw new InvalidArgumentException('Un parcial no pertenece a esta materia y semestre.');
                }
            }

            $quitados = $existentes->except($ids);

            if (! $confirmarBorrado && $quitados->contains(fn (Evaluacion $e) => $e->resultados()->exists())) {
                throw new InvalidArgumentException('Quitar un parcial que ya tiene resultados los borra: hace falta confirmarlo.');
            }

            foreach ($parciales as $parcial) {
                if ($parcial['id'] !== null) {
                    $this->validarMaximoNoBajaDeLoCargado($existentes->get($parcial['id']), $parcial['puntos_max']);
                }
            }

            $examenActual = Evaluacion::query()->delSemestre($materia->id, $semestre)->examenes()->first();

            if ($examenActual !== null) {
                $this->validarMaximoNoBajaDeLoCargado($examenActual, $examen['puntos_max']);
            }

            $quitados->each(fn (Evaluacion $e) => $e->delete());

            $ocupados = $existentes->except($quitados->modelKeys())->pluck('orden')->all();

            foreach ($parciales as $parcial) {
                if ($parcial['id'] !== null) {
                    $evaluacion = $existentes->get($parcial['id']);
                    $evaluacion->update([
                        'nombre' => $parcial['nombre'] ?? 'Parcial ' . $evaluacion->orden,
                        'puntos_max' => $parcial['puntos_max'],
                    ]);

                    continue;
                }

                $orden = $this->primerOrdenLibre($ocupados);
                $ocupados[] = $orden;

                Evaluacion::create([
                    'materia_id' => $materia->id,
                    'semestre' => $semestre,
                    'tipo' => TipoEvaluacion::Parcial,
                    'orden' => $orden,
                    'nombre' => $parcial['nombre'] ?? 'Parcial ' . $orden,
                    'puntos_max' => $parcial['puntos_max'],
                ]);
            }

            Evaluacion::updateOrCreate(
                [
                    'materia_id' => $materia->id,
                    'semestre' => $semestre,
                    'tipo' => TipoEvaluacion::Examen->value,
                    'orden' => 1,
                ],
                ['nombre' => $examen['nombre'], 'puntos_max' => $examen['puntos_max']]
            );

            $this->sincronizarSemestre($profesor, $materia, $semestre);
        });

        return $this->evaluacionesDe($materiaId, $semestre);
    }

    /**
     * Crea (sin $evaluacionId) o edita (con $evaluacionId) UN parcial, sin necesidad de tener
     * definido el resto del semestre. Hasta 5 parciales por materia y semestre.
     */
    public function guardarParcial(
        Usuario $profesor,
        int $materiaId,
        int $semestre,
        ?int $evaluacionId,
        ?string $nombre,
        int|string|null $puntosMax,
    ): Evaluacion {
        $materia = $this->docencia->materiaAutorizada($profesor, $materiaId);
        $this->validarSemestre($semestre);
        $puntosMax = $this->puntosMaxValidos($puntosMax);
        $nombre = $this->nombreValido($nombre);

        return DB::transaction(function () use ($profesor, $materia, $semestre, $evaluacionId, $nombre, $puntosMax): Evaluacion {
            $this->bloquearMateria($materia);

            if ($evaluacionId === null) {
                $ocupados = Evaluacion::query()->delSemestre($materia->id, $semestre)->parciales()->pluck('orden')->map(fn ($o) => (int) $o)->all();
                $orden = $this->primerOrdenLibre($ocupados);

                $evaluacion = Evaluacion::create([
                    'materia_id' => $materia->id,
                    'semestre' => $semestre,
                    'tipo' => TipoEvaluacion::Parcial,
                    'orden' => $orden,
                    'nombre' => $nombre ?? 'Parcial ' . $orden,
                    'puntos_max' => $puntosMax,
                ]);
            } else {
                $evaluacion = Evaluacion::query()->delSemestre($materia->id, $semestre)->parciales()->whereKey($evaluacionId)->first()
                    ?? throw new InvalidArgumentException('El parcial no pertenece a esta materia y semestre.');

                $this->validarMaximoNoBajaDeLoCargado($evaluacion, $puntosMax);
                $evaluacion->update(['nombre' => $nombre ?? 'Parcial ' . $evaluacion->orden, 'puntos_max' => $puntosMax]);
            }

            $this->sincronizarSemestre($profesor, $materia, $semestre);

            return $evaluacion->refresh();
        });
    }

    /** Crea o edita el examen final del semestre, sin necesidad de tener definidos los parciales. */
    public function guardarExamen(Usuario $profesor, int $materiaId, int $semestre, ?string $nombre, int|string|null $puntosMax): Evaluacion
    {
        $materia = $this->docencia->materiaAutorizada($profesor, $materiaId);
        $this->validarSemestre($semestre);
        $puntosMax = $this->puntosMaxValidos($puntosMax);
        $nombre = $this->nombreValido($nombre) ?? TipoEvaluacion::Examen->etiqueta();

        return DB::transaction(function () use ($profesor, $materia, $semestre, $nombre, $puntosMax): Evaluacion {
            $this->bloquearMateria($materia);

            $actual = Evaluacion::query()->delSemestre($materia->id, $semestre)->examenes()->first();

            if ($actual !== null) {
                $this->validarMaximoNoBajaDeLoCargado($actual, $puntosMax);
            }

            $evaluacion = Evaluacion::updateOrCreate(
                [
                    'materia_id' => $materia->id,
                    'semestre' => $semestre,
                    'tipo' => TipoEvaluacion::Examen->value,
                    'orden' => 1,
                ],
                ['nombre' => $nombre, 'puntos_max' => $puntosMax]
            );

            $this->sincronizarSemestre($profesor, $materia, $semestre);

            return $evaluacion->refresh();
        });
    }

    /**
     * Quita UN parcial o el examen final. Si ya tiene puntos cargados hace falta confirmarlo.
     * La nota del semestre se recalcula (queda "Pendiente" si ya no están todas las evaluaciones).
     */
    public function quitarEvaluacion(Usuario $profesor, int $evaluacionId, bool $confirmarBorrado = false): void
    {
        $evaluacion = Evaluacion::query()->find($evaluacionId)
            ?? throw new InvalidArgumentException('La evaluación no existe.');

        $materia = $this->docencia->materiaAutorizada($profesor, $evaluacion->materia_id);

        DB::transaction(function () use ($profesor, $materia, $evaluacion, $confirmarBorrado): void {
            $this->bloquearMateria($materia);

            if (! $confirmarBorrado && $evaluacion->resultados()->exists()) {
                throw new InvalidArgumentException("Quitar '{$evaluacion->nombre}' borra los puntos ya cargados: hace falta confirmarlo.");
            }

            $semestre = $evaluacion->semestre;
            $evaluacion->delete();

            $this->sincronizarSemestre($profesor, $materia, $semestre);
        });
    }

    /**
     * Guarda los puntos de varios alumnos de una vez (la grilla alumnos × evaluaciones).
     * Un valor vacío borra el resultado de esa celda (queda sin cargar).
     *
     * @param array<int|string, array<int|string, int|float|string|null>> $puntos [alumno_id => [evaluacion_id => puntos]]
     */
    public function registrarResultados(Usuario $profesor, int $materiaId, int $semestre, array $puntos): void
    {
        $materia = $this->docencia->materiaAutorizada($profesor, $materiaId);
        $this->validarSemestre($semestre);

        $evaluaciones = $this->evaluacionesDe($materiaId, $semestre)->keyBy('id');

        DB::transaction(function () use ($profesor, $materia, $semestre, $puntos, $evaluaciones): void {
            foreach ($puntos as $alumnoId => $porEvaluacion) {
                $this->docencia->alumnoDeMateria($profesor, (int) $alumnoId, $materia->id);

                foreach ($porEvaluacion as $evaluacionId => $valor) {
                    $evaluacion = $evaluaciones->get((int) $evaluacionId)
                        ?? throw new InvalidArgumentException('La evaluación no pertenece a esa materia y semestre.');

                    if ($valor === null || trim((string) $valor) === '') {
                        ResultadoEvaluacion::query()
                            ->where('evaluacion_id', $evaluacion->id)
                            ->where('alumno_id', (int) $alumnoId)
                            ->delete();

                        continue;
                    }

                    ResultadoEvaluacion::updateOrCreate(
                        ['evaluacion_id' => $evaluacion->id, 'alumno_id' => (int) $alumnoId],
                        ['puntos' => $this->puntosValidos($valor, $evaluacion), 'cargado_por' => $profesor->id]
                    );
                }
            }

            $this->sincronizarSemestre($profesor, $materia, $semestre, array_map('intval', array_keys($puntos)));
        });
    }

    /** Guarda (o corrige) los puntos de un alumno en una evaluación y recalcula su semestre. */
    public function registrarResultado(Usuario $profesor, int $evaluacionId, int $alumnoId, int|float|string $puntos): void
    {
        $evaluacion = Evaluacion::query()->findOrFail($evaluacionId);

        $this->registrarResultados($profesor, $evaluacion->materia_id, $evaluacion->semestre, [
            $alumnoId => [$evaluacionId => $puntos],
        ]);
    }

    /**
     * Datos de la pantalla "Cargar notas" del profesor: su materia (o la primera), el semestre,
     * las evaluaciones configuradas y una fila por alumno con sus puntos y su resultado.
     *
     * @return array<string, mixed>
     */
    public function pantallaDeCarga(Usuario $profesor, ?int $materiaId, int $semestre): array
    {
        $this->validarSemestre($semestre);

        $materias = $this->docencia->materiasConAlumnos($profesor);
        $materia = $materiaId === null
            ? $materias->first()
            : ($materias->firstWhere('id', $materiaId) ?? abort(403, 'Materia no asignada al profesor.'));

        $base = ['materias' => $materias, 'materia' => $materia, 'semestre' => $semestre];

        if ($materia === null) {
            return $base + ['parciales' => collect(), 'examen' => null, 'filas' => collect(), 'conPuntos' => []];
        }

        $evaluaciones = $this->evaluacionesDe($materia->id, $semestre);

        $resultados = ResultadoEvaluacion::query()
            ->whereIn('evaluacion_id', $evaluaciones->modelKeys())
            ->get()
            ->groupBy('alumno_id');

        $filas = $materia->curso->alumnos->map(function (Alumno $alumno) use ($evaluaciones, $resultados) {
            $porEvaluacion = ($resultados->get($alumno->id) ?? collect())->keyBy('evaluacion_id');

            return (object) [
                'alumno' => $alumno,
                'puntos' => $porEvaluacion->map(fn (ResultadoEvaluacion $r) => $r->puntos)->all(),
                'resultado' => $this->calcular($evaluaciones, $porEvaluacion),
            ];
        });

        return $base + [
            'parciales' => $evaluaciones->filter(fn (Evaluacion $e) => $e->esParcial())->values(),
            'examen' => $evaluaciones->first(fn (Evaluacion $e) => ! $e->esParcial()),
            'filas' => $filas,
            // Evaluaciones que ya tienen puntos de algún alumno (quitarlas pide confirmación).
            'conPuntos' => $resultados->flatMap(fn ($grupo) => $grupo->pluck('evaluacion_id'))->unique()->values()->all(),
        ];
    }

    /**
     * Los dos semestres del alumno para "Mis notas": por materia, cada parcial y el examen
     * con sus puntos y el resultado ya calculado (null = Pendiente). La pantalla no calcula nada.
     *
     * @param Collection<int, object{materia: Materia, profesor: ?string, asistencia: mixed}> $materiasResumen
     *
     * @return list<array<string, mixed>>
     */
    public function semestresDelAlumno(Alumno $alumno, Collection $materiasResumen): array
    {
        $evaluaciones = Evaluacion::query()
            ->whereIn('materia_id', $materiasResumen->map(fn ($item) => $item->materia->id)->all())
            ->orderByRaw("tipo = 'examen'")
            ->orderBy('orden')
            ->get()
            ->groupBy(fn (Evaluacion $e) => $e->materia_id . '-' . $e->semestre);

        $resultados = ResultadoEvaluacion::query()
            ->where('alumno_id', $alumno->id)
            ->whereIn('evaluacion_id', $evaluaciones->flatMap(fn ($grupo) => $grupo->modelKeys())->all())
            ->get()
            ->keyBy('evaluacion_id');

        $punto = fn (Evaluacion $e) => [
            'nombre' => $e->nombre,
            'max' => $e->puntos_max,
            'puntos' => $resultados->get($e->id)?->puntos,
        ];

        $nombres = [1 => 'Primer Semestre', 2 => 'Segundo Semestre'];

        return array_map(fn (int $semestre) => [
            'id' => 's' . $semestre,
            'nombre' => $nombres[$semestre],
            'materias' => $materiasResumen->map(function ($item) use ($evaluaciones, $resultados, $punto, $semestre) {
                $delSemestre = $evaluaciones->get($item->materia->id . '-' . $semestre, collect());

                return [
                    'nombre' => $item->materia->nombre,
                    'profesor' => $item->profesor,
                    'asistencia' => $item->asistencia,
                    'parciales' => $delSemestre->filter(fn (Evaluacion $e) => $e->esParcial())->map($punto)->values()->all(),
                    'examen' => ($examen = $delSemestre->first(fn (Evaluacion $e) => ! $e->esParcial())) ? $punto($examen) : null,
                    'resultado' => $this->calcular($delSemestre, $resultados),
                ];
            })->all(),
        ], self::SEMESTRES);
    }

    /**
     * Calcula la nota de cada alumno y la guarda (o la deja pendiente).
     *
     * @param list<int>|null $alumnoIds null = todos los alumnos del curso de la materia
     */
    private function sincronizarSemestre(Usuario $profesor, Materia $materia, int $semestre, ?array $alumnoIds = null): void
    {
        $alumnoIds ??= Alumno::query()->where('curso_id', $materia->curso_id)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $evaluaciones = $this->evaluacionesDe($materia->id, $semestre);

        $resultados = ResultadoEvaluacion::query()
            ->whereIn('evaluacion_id', $evaluaciones->modelKeys())
            ->whereIn('alumno_id', $alumnoIds)
            ->get()
            ->groupBy('alumno_id');

        foreach ($alumnoIds as $alumnoId) {
            $resultado = $this->calcular($evaluaciones, ($resultados->get($alumnoId) ?? collect())->keyBy('evaluacion_id'));

            if ($resultado === null) {
                Nota::query()
                    ->where('alumno_id', $alumnoId)
                    ->where('materia_id', $materia->id)
                    ->where('periodo', self::periodoDe($semestre))
                    ->delete();

                continue;
            }

            $this->notas->registrar($profesor, $materia->id, $alumnoId, self::periodoDe($semestre), $resultado->nota()->valor());
        }
    }

    /**
     * @param Collection<int, Evaluacion> $evaluaciones
     * @param Collection<int, ResultadoEvaluacion> $porEvaluacion resultados del alumno por evaluacion_id
     */
    private function calcular(Collection $evaluaciones, Collection $porEvaluacion): ?ResultadoSemestral
    {
        $parciales = $evaluaciones->filter(fn (Evaluacion $e) => $e->esParcial());
        $examen = $evaluaciones->first(fn (Evaluacion $e) => ! $e->esParcial());

        if ($parciales->count() < self::MIN_PARCIALES || $examen === null) {
            return null;
        }

        if ($evaluaciones->contains(fn (Evaluacion $e) => ! $porEvaluacion->has($e->id))) {
            return null;
        }

        return ResultadoSemestral::calcular(
            $parciales->map(fn (Evaluacion $e) => [$porEvaluacion->get($e->id)->puntos, $e->puntos_max])->values()->all(),
            $porEvaluacion->get($examen->id)->puntos,
            $examen->puntos_max,
        );
    }

    private function bloquearMateria(Materia $materia): void
    {
        // Serializa las configuraciones concurrentes de la misma materia.
        Materia::query()->whereKey($materia->id)->lockForUpdate()->first();
    }

    private function validarSemestre(int $semestre): void
    {
        if (! in_array($semestre, self::SEMESTRES, true)) {
            throw new InvalidArgumentException("El semestre debe ser 1 o 2. Recibido: {$semestre}.");
        }
    }

    private function puntosMaxValidos(mixed $valor): int
    {
        $texto = is_string($valor) ? trim($valor) : $valor;

        if (
            ! is_numeric($texto)
            || (float) $texto !== (float) (int) $texto
            || (int) $texto < self::PUNTOS_MIN
            || (int) $texto > self::PUNTOS_MAX
        ) {
            throw new InvalidArgumentException(
                'El puntaje máximo debe ser un número entero de ' . self::PUNTOS_MIN . ' a ' . self::PUNTOS_MAX . '.'
            );
        }

        return (int) $texto;
    }

    private function nombreValido(mixed $nombre): ?string
    {
        $nombre = trim((string) $nombre);

        if ($nombre === '') {
            return null;
        }

        if (mb_strlen($nombre) > 100) {
            throw new InvalidArgumentException('El nombre de la evaluación admite hasta 100 caracteres.');
        }

        return $nombre;
    }

    private function puntosValidos(int|float|string $valor, Evaluacion $evaluacion): float
    {
        $texto = is_string($valor) ? str_replace(',', '.', trim($valor)) : $valor;

        if (! is_numeric($texto)) {
            throw new InvalidArgumentException("Los puntos de '{$evaluacion->nombre}' no son numéricos.");
        }

        $puntos = (float) $texto;

        if (abs($puntos * 100 - round($puntos * 100)) > 1e-6) {
            throw new InvalidArgumentException("Los puntos de '{$evaluacion->nombre}' admiten hasta 2 decimales.");
        }

        if ($puntos < 0 || $puntos > $evaluacion->puntos_max) {
            throw new InvalidArgumentException("Los puntos de '{$evaluacion->nombre}' deben estar entre 0 y {$evaluacion->puntos_max}.");
        }

        return round($puntos, 2);
    }

    private function validarMaximoNoBajaDeLoCargado(Evaluacion $evaluacion, int $nuevoMaximo): void
    {
        $mayorCargado = $evaluacion->resultados()->max('puntos');

        if ($mayorCargado !== null && (float) $mayorCargado > $nuevoMaximo) {
            throw new InvalidArgumentException(
                "'{$evaluacion->nombre}' ya tiene un puntaje de {$mayorCargado}: el máximo no puede ser menor ({$nuevoMaximo})."
            );
        }
    }

    /** @param list<int> $ocupados */
    private function primerOrdenLibre(array $ocupados): int
    {
        for ($orden = 1; $orden <= self::MAX_PARCIALES; $orden++) {
            if (! in_array($orden, $ocupados, true)) {
                return $orden;
            }
        }

        throw new InvalidArgumentException('No puede haber más de ' . self::MAX_PARCIALES . ' parciales por semestre.');
    }
}

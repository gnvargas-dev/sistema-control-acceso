<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AsistenciaService;
use App\Services\DocenciaService;
use App\Services\EvaluacionService;
use App\Services\NotaService;
use App\Services\PanelProfesorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class ProfesorController extends Controller
{
    public function __construct(
        private readonly DocenciaService $docencia,
        private readonly PanelProfesorService $panel,
        private readonly NotaService $notas,
        private readonly AsistenciaService $asistencias,
        private readonly EvaluacionService $evaluaciones,
    ) {
    }

    public function inicio()
    {
        return view('profesor.inicio', $this->panel->inicio(Auth::user()));
    }

    public function cargarNotas(Request $request)
    {
        $semestre = in_array($request->integer('semestre'), EvaluacionService::SEMESTRES, true) ? $request->integer('semestre') : 1;
        $materiaId = $request->filled('materia_id') ? $request->integer('materia_id') : null;

        return view('profesor.cargar-notas', $this->evaluaciones->pantallaDeCarga(Auth::user(), $materiaId, $semestre));
    }

    /**
     * Evaluaciones de una materia en un semestre. Cada evaluación se maneja por separado
     * (accion = parcial | examen | quitar); sin accion se define el semestre completo de una vez.
     */
    public function guardarEvaluaciones(Request $request)
    {
        $min = EvaluacionService::PUNTOS_MIN;
        $max = EvaluacionService::PUNTOS_MAX;

        $accion = $request->input('accion');

        if (! in_array($accion, ['parcial', 'examen', 'quitar'], true)) {
            return $this->guardarSemestreCompleto($request);
        }

        $data = $request->validate([
            'materia_id' => ['required', 'integer'],
            'semestre' => ['required', 'integer', 'in:1,2'],
            'evaluacion_id' => [$accion === 'quitar' ? 'required' : 'nullable', 'integer'],
            'nombre' => ['nullable', 'string', 'max:100'],
            'puntos_max' => [$accion === 'quitar' ? 'nullable' : 'required', 'integer', "between:{$min},{$max}"],
            'confirmar_borrado' => ['nullable', 'boolean'],
        ]);

        $destino = ['materia_id' => (int) $data['materia_id'], 'semestre' => (int) $data['semestre']];
        $evaluacionId = isset($data['evaluacion_id']) ? (int) $data['evaluacion_id'] : null;

        try {
            match ($accion) {
                'parcial' => $this->evaluaciones->guardarParcial(Auth::user(), $destino['materia_id'], $destino['semestre'], $evaluacionId, $data['nombre'] ?? null, $data['puntos_max']),
                'examen' => $this->evaluaciones->guardarExamen(Auth::user(), $destino['materia_id'], $destino['semestre'], $data['nombre'] ?? null, $data['puntos_max']),
                'quitar' => $this->evaluaciones->quitarEvaluacion(Auth::user(), (int) $evaluacionId, $request->boolean('confirmar_borrado')),
            };
        } catch (InvalidArgumentException $e) {
            return redirect()->route('profesor.cargar-notas', $destino)->withErrors(['evaluaciones' => $e->getMessage()]);
        }

        $mensaje = ['parcial' => 'Parcial guardado.', 'examen' => 'Examen final guardado.', 'quitar' => 'Evaluación quitada.'][$accion];

        return redirect()->route('profesor.cargar-notas', $destino)->with('success', $mensaje . ' Las notas del semestre se recalcularon.');
    }

    /** Define todos los parciales y el examen del semestre de una vez (2 a 5 parciales). */
    private function guardarSemestreCompleto(Request $request)
    {
        $min = EvaluacionService::PUNTOS_MIN;
        $max = EvaluacionService::PUNTOS_MAX;

        $data = $request->validate([
            'materia_id' => ['required', 'integer'],
            'semestre' => ['required', 'integer', 'in:1,2'],
            'parciales' => ['required', 'array', 'max:' . EvaluacionService::MAX_PARCIALES],
            'parciales.*.id' => ['nullable', 'integer'],
            'parciales.*.nombre' => ['nullable', 'string', 'max:100'],
            'parciales.*.puntos_max' => ['required', 'integer', "between:{$min},{$max}"],
            'examen.nombre' => ['nullable', 'string', 'max:100'],
            'examen.puntos_max' => ['required', 'integer', "between:{$min},{$max}"],
            'confirmar_borrado' => ['nullable', 'boolean'],
        ]);

        $destino = ['materia_id' => (int) $data['materia_id'], 'semestre' => (int) $data['semestre']];

        try {
            $this->evaluaciones->configurarSemestre(
                Auth::user(),
                $destino['materia_id'],
                $destino['semestre'],
                $data['parciales'],
                $data['examen'],
                $request->boolean('confirmar_borrado'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->route('profesor.cargar-notas', $destino)->withInput()->withErrors(['evaluaciones' => $e->getMessage()]);
        }

        return redirect()->route('profesor.cargar-notas', $destino)->with('success', 'Evaluaciones guardadas. Las notas del semestre se recalcularon.');
    }

    /** Guarda los puntos de la grilla alumnos × evaluaciones; el servidor calcula la nota final. */
    public function guardarResultados(Request $request)
    {
        $data = $request->validate([
            'materia_id' => ['required', 'integer'],
            'semestre' => ['required', 'integer', 'in:1,2'],
            'puntos' => ['nullable', 'array'],
            'puntos.*' => ['array'],
            'puntos.*.*' => ['nullable', 'string', 'max:10'],
        ]);

        $destino = ['materia_id' => (int) $data['materia_id'], 'semestre' => (int) $data['semestre']];

        try {
            $this->evaluaciones->registrarResultados(Auth::user(), $destino['materia_id'], $destino['semestre'], $data['puntos'] ?? []);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('profesor.cargar-notas', $destino)->withInput()->withErrors(['puntos' => $e->getMessage()]);
        }

        return redirect()->route('profesor.cargar-notas', $destino)->with('success', 'Puntos guardados. Las notas finales se calcularon.');
    }

    public function guardarNota(Request $request)
    {
        $data = $request->validate([
            'materia_id' => ['required', 'integer'],
            'alumno_id' => ['required', 'integer'],
            'nota' => ['required', 'integer', 'between:1,5'],
            'periodo' => ['required', 'string', 'max:50'],
        ]);

        $this->notas->registrar(
            Auth::user(),
            (int) $data['materia_id'],
            (int) $data['alumno_id'],
            $data['periodo'],
            $data['nota'],
        );

        return back()->with('success', 'Nota registrada correctamente.');
    }

    public function cargarAsistencias(Request $request)
    {
        $materiaId = $request->filled('materia_id') ? $request->integer('materia_id') : null;

        return view('profesor.cargar-asistencias', $this->asistencias->pantallaDeCarga(
            Auth::user(),
            $materiaId,
            $request->input('fecha'),
        ));
    }

    public function guardarAsistencias(Request $request)
    {
        $data = $request->validate([
            'materia_id' => ['required', 'integer'],
            'fecha' => ['required', 'date'],
            'estados' => ['required', 'array'],
            'estados.*' => ['required', 'in:presente,ausente,tarde'],
            'observaciones' => ['nullable', 'array'],
            'observaciones.*' => ['nullable', 'string', 'max:255'],
        ]);

        $materia = $this->asistencias->guardarLote(
            Auth::user(),
            (int) $data['materia_id'],
            $data['fecha'],
            $data['estados'],
            $data['observaciones'] ?? null,
        );

        return redirect()
            ->route('profesor.cargar-asistencias', ['materia_id' => $materia->id, 'fecha' => $data['fecha']])
            ->with('success', 'Asistencias guardadas correctamente.');
    }

    public function historialAsistencias(Request $request, \App\Services\AsistenciaMetricasService $metricas)
    {
        $materias = $this->docencia->materiasDe(Auth::user());
        $materiaId = $request->filled('materia_id') ? $request->integer('materia_id') : null;
        $registros = $this->asistencias->historial($materias, $materiaId);
        $graficos = $metricas->deProfesor($materias, $materiaId);

        return view('profesor.historial-asistencias', compact('materias', 'materiaId', 'registros', 'graficos'));
    }

    public function misAlumnos()
    {
        $filas = $this->docencia->filasDeAlumnos(Auth::user());

        return view('profesor.mis-alumnos', compact('filas'));
    }
}

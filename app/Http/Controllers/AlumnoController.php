<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\EvaluacionService;
use App\Services\PanelAlumnoService;
use Illuminate\Support\Facades\Auth;

class AlumnoController extends Controller
{
    public function __construct(
        private readonly PanelAlumnoService $panel,
        private readonly EvaluacionService $evaluaciones,
    ) {
    }

    public function inicio()
    {
        return view('alumno.inicio', $this->panel->inicio($this->alumno()));
    }

    public function notas()
    {
        $datos = $this->panel->inicio($this->alumno());

        return view('alumno.notas', [
            'semestres' => $this->evaluaciones->semestresDelAlumno($datos['alumno'], $datos['materiasResumen']),
        ]);
    }

    public function historial()
    {
        $alumno = $this->alumno();
        $alumno->load('curso', 'usuario');

        $registros = $this->panel->registrosDeHistorial($alumno);

        return view('alumno.historial', [
            'alumno' => $alumno,
            'registros' => $registros,
            'porPeriodo' => $registros->groupBy('periodo'),
        ]);
    }

    public function asistencia(\App\Services\AsistenciaMetricasService $metricas)
    {
        $alumno = $this->alumno();

        return view('alumno.asistencia', $this->panel->asistencia($alumno) + [
            'graficos' => $metricas->deAlumno($alumno),
        ]);
    }

    private function alumno()
    {
        return $this->panel->alumnoDe(Auth::user());
    }
}

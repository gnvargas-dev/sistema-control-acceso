<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Services\AlumnoService;
use Illuminate\Http\Request;

class AlumnosController extends Controller
{
    public function __construct(private readonly AlumnoService $alumnos)
    {
    }

    public function index(Request $request)
    {
        return view('admin.alumnos', [
            'alumnos' => $this->alumnos->listado(
                $request->filled('curso_id') ? $request->input('curso_id') : null,
                $request->filled('estado') ? $request->input('estado') : null,
                $request->filled('q') ? $request->input('q') : null,
            ),
            'cursos' => $this->alumnos->cursos(),
            'estadisticas' => $this->alumnos->estadisticas(),
        ]);
    }

    public function actualizar(Request $request, Alumno $alumno)
    {
        $datos = $request->validate([
            'curso_id' => ['required', 'exists:cursos,id'],
            'legajo' => ['nullable', 'string', 'max:255'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'activo' => ['required', 'boolean'],
        ]);

        $this->alumnos->actualizar($alumno, $datos);

        return back()->with('success', 'Alumno actualizado correctamente.');
    }

    public function eliminar(Alumno $alumno)
    {
        $this->alumnos->eliminar($alumno);

        return back()->with('success', 'Alumno eliminado correctamente.');
    }
}

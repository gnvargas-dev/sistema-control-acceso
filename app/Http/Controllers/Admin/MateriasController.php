<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materia;
use App\Services\MateriaService;
use Illuminate\Http\Request;

class MateriasController extends Controller
{
    public function __construct(private readonly MateriaService $materias)
    {
    }

    public function index()
    {
        return view('admin.materias', $this->materias->datosDeIndice());
    }

    public function guardar(Request $request)
    {
        $this->guardarMateria($request, new Materia());

        return back()->with('success', 'Materia creada correctamente.');
    }

    public function actualizar(Request $request, Materia $materia)
    {
        $this->guardarMateria($request, $materia);

        return back()->with('success', 'Materia actualizada correctamente.');
    }

    public function eliminar(Materia $materia)
    {
        $this->materias->eliminar($materia);

        return back()->with('success', 'Materia eliminada correctamente.');
    }

    /** Valida el formulario y delega el guardado (materia y profesores) en el servicio. */
    private function guardarMateria(Request $request, Materia $materia): void
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'curso_id' => ['required', 'exists:cursos,id'],
            'profesor_ids' => ['nullable', 'array'],
            'profesor_ids.*' => ['nullable', 'exists:profesores,id'],
        ]);

        $this->materias->guardarDesdePanel($materia, $datos);
    }
}

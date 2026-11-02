<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Materia;
use App\Services\MateriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RegistroAlumnoController extends Controller
{
    public function __construct(private readonly MateriaService $materias)
    {
    }

    public function porMateria(Request $request, Materia $materia)
    {
        Gate::authorize('viewForMateria', [Alumno::class, $materia]);

        return response()->json($this->materias->alumnosActivos($materia));
    }
}

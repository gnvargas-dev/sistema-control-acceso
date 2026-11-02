<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Alumno;
use Illuminate\Http\Request;

class LegajoController extends Controller
{
    public function show(Alumno $alumno)
    {
        return response()->json(
            $alumno->load([
                'usuario',
                'curso',
                'notas.materia',
                'asistencias.materia',
                'historialAcademico',
            ])
        );
    }

    public function update(Request $request, Alumno $alumno)
    {
        $datos = $request->validate([
            'curso_id' => ['sometimes', 'integer', 'exists:cursos,id'],
            'legajo' => [
                'sometimes',
                'string',
                'max:50',
                'unique:alumnos,legajo,'.$alumno->id,
            ],
            'activo' => ['sometimes', 'boolean'],
        ]);

        $alumno->update($datos);

        return response()->json($alumno->fresh());
    }
}

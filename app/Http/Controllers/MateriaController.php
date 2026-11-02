<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreMateriaRequest;
use App\Models\Materia;
use App\Services\MateriaService;

class MateriaController extends Controller
{
    public function __construct(private readonly MateriaService $materias)
    {
    }

    public function index()
    {
        return response()->json($this->materias->activas());
    }

    public function store(StoreMateriaRequest $request)
    {
        return response()->json($this->materias->crear($request->validated()), 201);
    }

    public function show(Materia $materia)
    {
        return response()->json($this->materias->conDetalle($materia));
    }

    public function update(StoreMateriaRequest $request, Materia $materia)
    {
        return response()->json($this->materias->actualizar($materia, $request->validated()));
    }

    public function destroy(Materia $materia)
    {
        $this->materias->darDeBaja($materia);

        return response()->json([
            'message' => 'Materia dada de baja.',
        ]);
    }
}

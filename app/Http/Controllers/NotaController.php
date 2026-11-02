<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreNotaRequest;
use App\Models\Nota;
use App\Services\NotaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NotaController extends Controller
{
    public function __construct(private readonly NotaService $notas)
    {
    }

    public function index(Request $request)
    {
        $alumno = $request->user()->alumno;

        abort_unless($alumno, 403, 'El usuario no tiene perfil de alumno.');

        return response()->json(
            $this->notas->deAlumno($alumno, $request->input('periodo'))
        );
    }

    public function promedio(Request $request)
    {
        $alumno = $request->user()->alumno;

        abort_unless($alumno, 403);

        return response()->json([
            'alumno_id' => $alumno->id,
            'promedio' => $this->notas->promedioDe($alumno),
        ]);
    }

    public function store(StoreNotaRequest $request)
    {
        $datos = $request->validated();

        Gate::authorize('create', [Nota::class, $datos]);

        return response()->json(
            $this->notas->crear($datos, $request->user()->id),
            201
        );
    }

    public function update(StoreNotaRequest $request, Nota $nota)
    {
        Gate::authorize('update', $nota);

        return response()->json(
            $this->notas->actualizar($nota, $request->validated())
        );
    }
}

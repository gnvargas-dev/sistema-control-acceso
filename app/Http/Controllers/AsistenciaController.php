<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAsistenciaRequest;
use App\Models\Asistencia;
use App\Services\AsistenciaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AsistenciaController extends Controller
{
    public function __construct(private readonly AsistenciaService $asistencias)
    {
    }

    public function index(Request $request)
    {
        $alumno = $request->user()->alumno;

        abort_unless($alumno, 403);

        return response()->json($this->asistencias->deAlumno($alumno));
    }

    public function porcentaje(Request $request)
    {
        $alumno = $request->user()->alumno;

        abort_unless($alumno, 403);

        return response()->json($this->asistencias->porcentajeDe($alumno));
    }

    public function store(StoreAsistenciaRequest $request)
    {
        $datos = $request->validated();

        Gate::authorize('create', [Asistencia::class, $datos]);

        return response()->json(
            $this->asistencias->crear($datos, $request->user()->id),
            201
        );
    }

    public function update(StoreAsistenciaRequest $request, Asistencia $asistencia)
    {
        Gate::authorize('update', $asistencia);

        return response()->json(
            $this->asistencias->actualizar($asistencia, $request->validated())
        );
    }
}

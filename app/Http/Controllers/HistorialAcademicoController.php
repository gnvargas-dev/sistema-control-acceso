<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PanelAlumnoService;
use Illuminate\Http\Request;

class HistorialAcademicoController extends Controller
{
    public function __construct(private readonly PanelAlumnoService $panel)
    {
    }

    public function index(Request $request)
    {
        $alumno = $request->user()->alumno;

        abort_unless($alumno, 403);

        return response()->json($this->panel->historialPorPeriodo($alumno));
    }
}

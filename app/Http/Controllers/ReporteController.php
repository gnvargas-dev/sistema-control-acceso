<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ReporteService;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function __construct(private readonly ReporteService $reportes)
    {
    }

    public function notas(Request $request)
    {
        return response()->json(
            $this->reportes->notasPorMateria($request->input('periodo'))
        );
    }

    public function asistencias()
    {
        return response()->json($this->reportes->asistenciasPorMateria());
    }
}

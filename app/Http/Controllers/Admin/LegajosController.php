<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Services\LegajoService;
use Illuminate\Http\Request;

class LegajosController extends Controller
{
    public function __construct(private readonly LegajoService $legajos)
    {
    }

    public function index(Request $request)
    {
        return view('admin.legajos', $this->legajos->pantalla(
            $request->input('q'),
            $request->filled('curso_id') ? $request->input('curso_id') : null,
            $request->filled('estado') ? $request->input('estado') : null,
            (int) $request->input('page', 1),
            $request->url(),
            $request->query(),
        ));
    }

    public function documentos(Alumno $alumno)
    {
        return response()->json($this->legajos->documentosDe($alumno));
    }

    public function guardarDocumentos(Request $request, Alumno $alumno)
    {
        $datos = $request->validate([
            'documentos' => ['nullable', 'array'],
            'documentos.*' => ['string'],
        ]);

        $entregados = $this->legajos->guardarDocumentos($alumno, $datos['documentos'] ?? []);

        return response()->json([
            'message' => 'Legajo actualizado correctamente.',
            'entregados' => $entregados,
            'total' => $this->legajos->totalDocumentos(),
        ]);
    }
}

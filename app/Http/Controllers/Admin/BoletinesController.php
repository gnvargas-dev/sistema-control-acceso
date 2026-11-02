<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoletinGenerado;
use App\Services\BoletinService;
use Illuminate\Http\Request;

class BoletinesController extends Controller
{
    public function __construct(private readonly BoletinService $boletines)
    {
    }

    public function index(Request $request)
    {
        return view('admin.boletines', $this->boletines->pantalla(
            $request->filled('curso_id') ? $request->input('curso_id') : null,
            $request->filled('periodo') ? $request->input('periodo') : null,
            $request->filled('q') ? $request->input('q') : null,
        ));
    }

    public function generar(Request $request)
    {
        $datos = $request->validate([
            'alumno_id' => ['nullable', 'exists:alumnos,id'],
            'curso_id' => ['nullable', 'exists:cursos,id'],
            'periodo' => ['required', 'string', 'max:50'],
        ]);

        if (empty($datos['alumno_id']) && empty($datos['curso_id'])) {
            return back()
                ->withInput()
                ->withErrors(['alumno_id' => 'Seleccioná un alumno o un curso para generar el boletín.']);
        }

        $alumnos = $this->boletines->alumnosParaBoletin($datos['alumno_id'] ?? null, $datos['curso_id'] ?? null);

        if ($alumnos->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['alumno_id' => 'No se encontraron alumnos para los criterios seleccionados.']);
        }

        $cantidad = $this->boletines->generar($alumnos, $datos['periodo'], auth()->id());

        return back()->with(
            'success',
            $cantidad === 1
                ? 'Boletín generado correctamente.'
                : "Se generaron {$cantidad} boletines correctamente."
        );
    }

    public function descargar(BoletinGenerado $boletin)
    {
        abort_unless($this->boletines->existeArchivo($boletin), 404);

        return $this->boletines->descargar($boletin);
    }

    public function ver(BoletinGenerado $boletin)
    {
        abort_unless($this->boletines->existeArchivo($boletin), 404);

        return $this->boletines->ver($boletin);
    }
}

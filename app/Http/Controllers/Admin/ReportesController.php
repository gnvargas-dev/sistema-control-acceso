<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Documentos\GeneradorReporte;
use App\Http\Controllers\Controller;
use App\Services\ReporteService;
use Illuminate\Http\Request;

class ReportesController extends Controller
{
    public function __construct(
        private readonly ReporteService $reportes,
        private readonly GeneradorReporte $generador,
    ) {
    }

    public function index(Request $request)
    {
        return view('admin.reportes', $this->reportes->pantalla($this->filtros($request)));
    }

    /**
     * Genera el PDF de "Exportar reporte" para el tipo elegido:
     * 1 = Resumen ejecutivo, 2 = Comparativo por curso, 3 = Cuadro de honor, 4 = Alerta de riesgo.
     */
    public function exportar(Request $request)
    {
        $datos = $request->validate([
            'tipo' => ['required', 'integer', 'between:1,4'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
            'curso_id' => ['nullable', 'exists:cursos,id'],
        ]);

        $tipo = (int) $datos['tipo'];

        return $this->generador
            ->generar($this->reportes->contextoDePdf($tipo, $this->filtros($request)))
            ->download($this->reportes->nombreDeArchivo($tipo));
    }

    private function filtros(Request $request): array
    {
        return $this->reportes->filtros(
            $request->query('desde'),
            $request->query('hasta'),
            $request->query('curso_id'),
        );
    }
}

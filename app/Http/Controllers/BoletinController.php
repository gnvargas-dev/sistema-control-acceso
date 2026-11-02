<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Alumno;
use Barryvdh\DomPDF\Facade\Pdf;

class BoletinController extends Controller
{
    public function generar(Alumno $alumno)
    {
        $alumno->load([
            'usuario',
            'curso',
            'notas.materia',
            'asistencias.materia',
        ]);

        $pdf = Pdf::loadView('boletines.boletin', [
            'alumno' => $alumno,
        ]);

        return $pdf->download(
            'boletin-alumno-'.$alumno->id.'.pdf'
        );
    }
}

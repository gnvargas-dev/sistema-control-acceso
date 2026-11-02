<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EstadisticasService;

class PanelController extends Controller
{
    public function __construct(private readonly EstadisticasService $estadisticas)
    {
    }

    public function inicio()
    {
        return view('admin.inicio', $this->estadisticas->resumenInstitucional());
    }
}

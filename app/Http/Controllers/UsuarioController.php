<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsuarioRequest;
use App\Models\Usuario;
use App\Services\UsuarioService;

class UsuarioController extends Controller
{
    public function __construct(private readonly UsuarioService $usuarios)
    {
    }

    public function index()
    {
        return response()->json($this->usuarios->paginado());
    }

    public function store(StoreUsuarioRequest $request)
    {
        return response()->json(
            $this->usuarios->crearCuenta($request->validated()),
            201
        );
    }

    public function show(Usuario $usuario)
    {
        return response()->json(
            $usuario->load(['alumno', 'profesor'])
        );
    }

    public function update(StoreUsuarioRequest $request, Usuario $usuario)
    {
        return response()->json(
            $this->usuarios->actualizarCuenta($usuario, $request->validated())
        );
    }

    public function destroy(Usuario $usuario)
    {
        $this->usuarios->darDeBaja($usuario);

        return response()->json([
            'message' => 'Usuario dado de baja.',
        ]);
    }
}

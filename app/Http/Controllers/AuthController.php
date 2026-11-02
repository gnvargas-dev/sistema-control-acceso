<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\UsuarioService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly UsuarioService $usuarios)
    {
    }

    public function login(LoginRequest $request)
    {
        $usuario = $this->usuarios->autenticar(
            $request->string('cedula')->toString(),
            $request->string('password')->toString()
        );

        if (! $usuario) {
            return response()->json([
                'message' => 'Credenciales inválidas.',
            ], 401);
        }

        $token = $usuario->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'usuario' => [
                'id' => $usuario->id,
                'cedula' => $usuario->cedula,
                'nombre' => $usuario->nombre,
                'rol' => $usuario->rol,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesión cerrada.',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(
            $request->user()->load(['alumno', 'profesor'])
        );
    }
}

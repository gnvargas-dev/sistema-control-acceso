<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(
        Request $request,
        Closure $next,
        ...$roles
    ): Response {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        }

        if (!$usuario->activo) {
            return response()->json([
                'message' => 'Usuario inactivo.',
            ], 403);
        }

        if (!$usuario->es(...$roles)) {
            return response()->json([
                'message' => 'No tiene permisos para realizar esta acción.',
            ], 403);
        }

        return $next($request);
    }
}

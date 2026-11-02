<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Roles\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Inicio y cierre de sesión de la web (la API usa AuthController con tokens). */
class SesionController extends Controller
{
    public function formulario()
    {
        return view('auth.login');
    }

    public function iniciar(Request $request)
    {
        $request->validate([
            'cedula' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $cedula = trim((string) $request->input('cedula'));

        if (! Auth::attempt([
            'cedula' => $cedula,
            'password' => $request->input('password'),
        ])) {
            return back()
                ->withInput($request->only('cedula'))
                ->withErrors([
                    'auth' => 'La cédula o la contraseña son incorrectas.',
                ]);
        }

        $request->session()->regenerate();

        $rol = Rol::tryDesde(strtolower(trim((string) Auth::user()->rol)));

        if ($rol !== null) {
            return redirect()->route($rol->rutaInicio());
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return back()->withErrors([
            'auth' => 'Tu usuario no tiene un rol autorizado.',
        ]);
    }

    public function cerrar(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

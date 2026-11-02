<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Services\UsuarioService;
use Illuminate\Http\Request;

class UsuarioAdminController extends Controller
{
    public function __construct(private readonly UsuarioService $usuarios)
    {
    }

    public function index()
    {
        return view('admin.usuarios', $this->usuarios->datosDeIndice());
    }

    public function store(Request $request)
    {
        // Limpiamos espacios invisibles antes de validar, para que la
        // cédula que se guarda sea EXACTAMENTE la misma que la persona
        // va a tipear al iniciar sesión.
        $request->merge([
            'cedula' => trim((string) $request->input('cedula')),
            'email' => trim((string) $request->input('email')),
            'nombre' => trim((string) $request->input('nombre')),
        ]);

        $reglas = [
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:usuarios,email'],
            'cedula' => ['required', 'string', 'max:30', 'unique:usuarios,cedula'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'rol' => ['required', 'in:alumno,profesor,administrador'],
            'activo' => ['required', 'boolean'],
        ];

        // Cada rol aporta sus propias reglas (polimorfismo de Rol).
        $reglas += $this->usuarios->reglasExtraPara($request->input('rol'));

        $this->usuarios->registrar($request->validate($reglas));

        return redirect()->route('admin.usuarios')
            ->with('success', 'Usuario registrado correctamente.');
    }

    public function destroy(Usuario $usuario)
    {
        abort_if($usuario->id === auth()->id(), 422, 'No podés eliminar tu propia cuenta.');

        $this->usuarios->eliminar($usuario);

        return back()->with('success', 'Usuario eliminado correctamente.');
    }
}

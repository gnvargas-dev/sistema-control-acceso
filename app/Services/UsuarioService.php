<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Curso;
use App\Models\Materia;
use App\Models\Usuario;
use App\Roles\Rol;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class UsuarioService
{
    /** Intentos al registrar si dos altas simultáneas eligen el mismo legajo. */
    private const INTENTOS_DE_ALTA = 3;

    /** @return array<string, mixed> variables de la vista admin.usuarios */
    public function datosDeIndice(): array
    {
        return [
            'usuarios' => Usuario::query()->orderBy('nombre')->orderBy('id')->paginate(15),
            'cursos' => Curso::query()->orderBy('anio')->orderBy('nombre')->orderBy('id')->get(),
            'materias' => Materia::query()->with('curso')->orderBy('nombre')->orderBy('id')->get(),
        ];
    }

    /**
     * Reglas extra de validación según el rol elegido en el formulario.
     * Un rol desconocido no suma reglas: lo rechaza la regla "in" del formulario.
     */
    public function reglasExtraPara(mixed $rol): array
    {
        return is_string($rol) ? (Rol::tryDesde($rol)?->reglasDeRegistro() ?? []) : [];
    }

    /**
     * Crea el usuario y, según su rol, su perfil (alumno o profesor).
     *
     * @param  array<string, mixed>  $datos  datos ya validados
     */
    public function registrar(array $datos): void
    {
        $rol = Rol::desde($datos['rol']);
        $materiaIds = $this->materiasSeleccionadas($datos);

        for ($intento = 1; ; $intento++) {
            try {
                DB::transaction(function () use ($datos, $rol, $materiaIds) {
                    $usuarioId = DB::table('usuarios')->insertGetId([
                        'nombre' => $datos['nombre'],
                        'email' => $datos['email'],
                        'cedula' => $datos['cedula'],
                        'password' => Hash::make($datos['password']),
                        'rol' => $datos['rol'],
                        'activo' => $datos['activo'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $rol->crearPerfil($usuarioId, $datos, $materiaIds);
                });

                return;
            } catch (UniqueConstraintViolationException $e) {
                // Otra alta tomó el mismo legajo entre la comprobación y el guardado: la transacción
                // se deshizo entera, así que se repite con un legajo nuevo. Cualquier otro duplicado falla igual que antes.
                if ($intento >= self::INTENTOS_DE_ALTA || ! str_contains($e->getMessage(), 'legajo')) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Cada fila de "materia" del formulario puede venir de dos formas:
     * - una materia ya existente (materia_ids[i] = su ID)
     * - una materia nueva (materia_ids[i] = '__nueva__' y materia_nuevas[i] = el nombre)
     * Se normalizan a una lista de IDs, creando las nuevas (sin curso todavía).
     *
     * @return Collection<int, int>
     */
    private function materiasSeleccionadas(array $datos): Collection
    {
        $materiaIdsRaw = $datos['materia_ids'] ?? [];
        $materiaNuevasRaw = $datos['materia_nuevas'] ?? [];
        $materiaIds = collect();

        foreach ($materiaIdsRaw as $indice => $valor) {
            if ($valor === '__nueva__') {
                $nombreNuevo = trim((string) ($materiaNuevasRaw[$indice] ?? ''));

                if ($nombreNuevo === '') {
                    continue;
                }

                $materia = Materia::firstOrCreate(
                    ['nombre' => $nombreNuevo, 'curso_id' => null],
                    ['activa' => true]
                );

                $materiaIds->push($materia->id);
            } elseif (is_numeric($valor)) {
                $materiaIds->push((int) $valor);
            }
        }

        return $materiaIds->filter()->unique()->values();
    }

    /** Borra al usuario junto con su perfil de profesor (y sus materias) o de alumno. */
    public function eliminar(Usuario $usuario): void
    {
        DB::transaction(function () use ($usuario) {
            $profesor = DB::table('profesores')
                ->where('usuario_id', $usuario->id)
                ->first();

            if ($profesor) {
                DB::table('profesor_materia')
                    ->where('profesor_id', $profesor->id)
                    ->delete();

                DB::table('profesores')
                    ->where('id', $profesor->id)
                    ->delete();
            }

            DB::table('alumnos')->where('usuario_id', $usuario->id)->delete();
            DB::table('usuarios')->where('id', $usuario->id)->delete();
        });
    }

    /** Usuario activo con esa cédula y contraseña, o null si las credenciales no son válidas. */
    public function autenticar(string $cedula, string $password): ?Usuario
    {
        $usuario = Usuario::where('cedula', $cedula)
            ->where('activo', true)
            ->first();

        if (! $usuario || ! Hash::check($password, $usuario->password)) {
            return null;
        }

        return $usuario;
    }

    /** Listado paginado de la API (20 por página), los más nuevos primero. */
    public function paginado(): LengthAwarePaginator
    {
        return Usuario::query()
            ->select(['id', 'cedula', 'nombre', 'rol', 'activo', 'created_at'])
            ->latest()
            ->latest('id')
            ->paginate(20);
    }

    /** Alta de una cuenta desde la API (guarda la contraseña ya cifrada). */
    public function crearCuenta(array $datos): Usuario
    {
        return Usuario::create([
            ...$datos,
            'password' => Hash::make($datos['password']),
        ]);
    }

    /** Modificación desde la API; sin contraseña nueva se conserva la actual. */
    public function actualizarCuenta(Usuario $usuario, array $datos): Usuario
    {
        if (! empty($datos['password'])) {
            $datos['password'] = Hash::make($datos['password']);
        } else {
            unset($datos['password']);
        }

        $usuario->update($datos);

        return $usuario->fresh();
    }

    /** Baja lógica (no se borra el registro) y cierre de todas sus sesiones de API. */
    public function darDeBaja(Usuario $usuario): void
    {
        $usuario->update(['activo' => false]);

        $usuario->tokens()->delete();
    }
}

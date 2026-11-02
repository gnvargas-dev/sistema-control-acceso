<?php

declare(strict_types=1);

namespace App\Roles;

use App\Enums\RolUsuario;
use App\Models\Alumno;
use App\Models\Materia;
use App\Models\Persona;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

final class RolProfesor extends Rol
{
    public function clave(): RolUsuario
    {
        return RolUsuario::Profesor;
    }

    public function rutaInicio(): string
    {
        return 'profesor.inicio';
    }

    public function menu(): array
    {
        return [
            new ItemMenu('profesor.inicio', 'Inicio', 'home'),
            new ItemMenu('profesor.cargar-notas', 'Cargar notas', 'files'),
            new ItemMenu('profesor.cargar-asistencias', 'Cargar asistencias', 'reports'),
            new ItemMenu('profesor.historial-asistencias', 'Historial de asistencias', 'history'),
            new ItemMenu('profesor.mis-alumnos', 'Mis alumnos', 'students'),
        ];
    }

    public function perfilDe(Usuario $usuario): ?Persona
    {
        return $usuario->profesor;
    }

    public function veListadoDeAlumnos(): bool
    {
        return true;
    }

    public function veAlumnosDeMateria(Usuario $usuario, Materia $materia): bool
    {
        return $this->dictaLaMateria($usuario, $materia->id);
    }

    public function veAlumno(Usuario $usuario, Alumno $alumno): bool
    {
        return (bool) $usuario->profesor
            ?->materias()
            ->where('curso_id', $alumno->curso_id)
            ->exists();
    }

    public function cargaDatosDeMateria(Usuario $usuario, int|string|null $materiaId): bool
    {
        return $this->dictaLaMateria($usuario, $materiaId);
    }

    public function esTitularDelRegistro(Usuario $usuario, int|string|null $alumnoId): bool
    {
        return false;
    }

    private function dictaLaMateria(Usuario $usuario, int|string|null $materiaId): bool
    {
        return (bool) $usuario->profesor
            ?->materias()
            ->whereKey($materiaId)
            ->exists();
    }

    public function reglasDeRegistro(): array
    {
        return [
            'materia_ids' => ['nullable', 'array'],
            'materia_ids.*' => ['nullable', 'string'],
            'materia_nuevas' => ['nullable', 'array'],
            'materia_nuevas.*' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function crearPerfil(int $usuarioId, array $datos, Collection $materiaIds): void
    {
        $profesorId = DB::table('profesores')->insertGetId([
            'usuario_id' => $usuarioId,
            'activo' => $datos['activo'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($materiaIds->isNotEmpty()) {
            DB::table('profesor_materia')->insert(
                $materiaIds->map(fn ($materiaId) => [
                    'profesor_id' => $profesorId,
                    'materia_id' => $materiaId,
                ])->all()
            );
        }
    }
}

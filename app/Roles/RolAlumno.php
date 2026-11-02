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

final class RolAlumno extends Rol
{
    public function clave(): RolUsuario
    {
        return RolUsuario::Alumno;
    }

    public function rutaInicio(): string
    {
        return 'alumno.inicio';
    }

    public function menu(): array
    {
        return [
            new ItemMenu('alumno.inicio', 'Inicio', 'home'),
            new ItemMenu('alumno.notas', 'Mis notas', 'files'),
            new ItemMenu('alumno.asistencia', 'Mi asistencia', 'reports'),
            new ItemMenu('alumno.historial', 'Historial académico', 'subjects'),
        ];
    }

    public function perfilDe(Usuario $usuario): ?Persona
    {
        return $usuario->alumno;
    }

    public function veListadoDeAlumnos(): bool
    {
        return false;
    }

    public function veAlumnosDeMateria(Usuario $usuario, Materia $materia): bool
    {
        return false;
    }

    public function veAlumno(Usuario $usuario, Alumno $alumno): bool
    {
        return false;
    }

    public function cargaDatosDeMateria(Usuario $usuario, int|string|null $materiaId): bool
    {
        return false;
    }

    public function esTitularDelRegistro(Usuario $usuario, int|string|null $alumnoId): bool
    {
        return $usuario->alumno?->id === $alumnoId;
    }

    public function reglasDeRegistro(): array
    {
        return ['curso_id' => ['required', 'exists:cursos,id']];
    }

    public function crearPerfil(int $usuarioId, array $datos, Collection $materiaIds): void
    {
        DB::table('alumnos')->insert([
            'usuario_id' => $usuarioId,
            'curso_id' => $datos['curso_id'],
            'legajo' => Alumno::nuevoLegajo(),
            'activo' => $datos['activo'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

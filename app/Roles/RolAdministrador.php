<?php

declare(strict_types=1);

namespace App\Roles;

use App\Enums\RolUsuario;
use App\Models\Alumno;
use App\Models\Materia;
use App\Models\Persona;
use App\Models\Usuario;
use Illuminate\Support\Collection;

final class RolAdministrador extends Rol
{
    public function clave(): RolUsuario
    {
        return RolUsuario::Administrador;
    }

    public function rutaInicio(): string
    {
        return 'admin.inicio';
    }

    public function menu(): array
    {
        return [
            new ItemMenu('admin.inicio', 'Inicio', 'home', 'Panel de control'),
            new ItemMenu('admin.usuarios', 'Usuarios', 'users', 'Gestionar usuarios del sistema'),
            new ItemMenu('admin.boletines', 'Boletines', 'files', 'Generar boletines en PDF'),
            new ItemMenu('admin.reportes', 'Reportes', 'reports', 'Reportes y estadísticas'),
            new ItemMenu('admin.materias', 'Materias', 'subjects', 'Gestionar materias'),
            new ItemMenu('admin.legajos', 'Legajos', 'folder', 'Gestión de legajos'),
            new ItemMenu('admin.alumnos', 'Alumnos', 'students', 'Registro de todos los alumnos'),
        ];
    }

    public function perfilDe(Usuario $usuario): ?Persona
    {
        return null;
    }

    public function veListadoDeAlumnos(): bool
    {
        return true;
    }

    public function veAlumnosDeMateria(Usuario $usuario, Materia $materia): bool
    {
        return true;
    }

    public function veAlumno(Usuario $usuario, Alumno $alumno): bool
    {
        return true;
    }

    public function cargaDatosDeMateria(Usuario $usuario, int|string|null $materiaId): bool
    {
        return false;
    }

    public function esTitularDelRegistro(Usuario $usuario, int|string|null $alumnoId): bool
    {
        return false;
    }

    public function reglasDeRegistro(): array
    {
        return [];
    }

    public function crearPerfil(int $usuarioId, array $datos, Collection $materiaIds): void
    {
        // El administrador no tiene perfil propio.
    }
}

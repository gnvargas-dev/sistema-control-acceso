<?php

declare(strict_types=1);

namespace App\Roles;

use App\Enums\RolUsuario;
use App\Models\Alumno;
use App\Models\Materia;
use App\Models\Persona;
use App\Models\Usuario;
use Illuminate\Support\Collection;

/**
 * Rol de un usuario. Cada hija (administrador, profesor, alumno) responde a su
 * manera: es el polimorfismo que reemplaza los `if ($usuario->rol === ...)`.
 */
abstract class Rol
{
    abstract public function clave(): RolUsuario;

    /** Nombre de la ruta a la que va el usuario al iniciar sesión. */
    abstract public function rutaInicio(): string;

    /** @return list<ItemMenu> */
    abstract public function menu(): array;

    /** Perfil (Alumno o Profesor) del usuario según su rol; null si el rol no tiene perfil. */
    abstract public function perfilDe(Usuario $usuario): ?Persona;

    /** ¿Puede consultar el listado de alumnos? */
    abstract public function veListadoDeAlumnos(): bool;

    /** ¿Puede ver los alumnos de una materia? */
    abstract public function veAlumnosDeMateria(Usuario $usuario, Materia $materia): bool;

    /** ¿Puede ver a un alumno en particular? */
    abstract public function veAlumno(Usuario $usuario, Alumno $alumno): bool;

    /** ¿Puede cargar o modificar notas y asistencias de esa materia? */
    abstract public function cargaDatosDeMateria(Usuario $usuario, int|string|null $materiaId): bool;

    /** ¿El registro (nota, asistencia) de ese alumno pertenece al usuario? */
    abstract public function esTitularDelRegistro(Usuario $usuario, int|string|null $alumnoId): bool;

    /**
     * Reglas de validación que se suman al registrar un usuario con este rol
     * (por ejemplo, el curso del alumno).
     *
     * @return array<string, list<string>>
     */
    abstract public function reglasDeRegistro(): array;

    /**
     * Crea el perfil propio del rol al registrar un usuario (se ejecuta dentro
     * de la transacción del registro). El administrador no tiene perfil.
     *
     * @param  array<string, mixed>  $datos        datos ya validados del formulario
     * @param  Collection<int, int>  $materiaIds   materias a asignar (solo las usa el profesor)
     */
    abstract public function crearPerfil(int $usuarioId, array $datos, Collection $materiaIds): void;

    public function nombre(): string
    {
        return $this->clave()->etiqueta();
    }

    public function esUnoDe(string ...$claves): bool
    {
        return in_array($this->clave()->value, $claves, true);
    }

    /**
     * Simulación de sobrecarga: PHP no la tiene, así que un único método
     * acepta el enum o su texto (union type). La comparación es exacta,
     * igual que la de `usuarios.rol` en la base de datos.
     *
     * @throws \ValueError si el rol no existe
     */
    public static function desde(RolUsuario|string $rol): self
    {
        $clave = $rol instanceof RolUsuario ? $rol : RolUsuario::from($rol);

        return self::instanciar($clave);
    }

    /** Igual que desde(), pero devuelve null si el rol no existe. */
    public static function tryDesde(RolUsuario|string $rol): ?self
    {
        $clave = $rol instanceof RolUsuario ? $rol : RolUsuario::tryFrom($rol);

        return $clave === null ? null : self::instanciar($clave);
    }

    private static function instanciar(RolUsuario $clave): self
    {
        return match ($clave) {
            RolUsuario::Administrador => new RolAdministrador(),
            RolUsuario::Profesor => new RolProfesor(),
            RolUsuario::Alumno => new RolAlumno(),
        };
    }
}

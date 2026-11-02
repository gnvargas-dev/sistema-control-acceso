<?php

namespace Tests\Unit;

use App\Enums\RolUsuario;
use App\Roles\ItemMenu;
use App\Roles\Rol;
use App\Roles\RolAdministrador;
use App\Roles\RolAlumno;
use App\Roles\RolProfesor;
use PHPUnit\Framework\TestCase;
use ValueError;

class RolTest extends TestCase
{
    public function test_desde_acepta_el_enum_o_el_texto(): void
    {
        $this->assertInstanceOf(RolAlumno::class, Rol::desde('alumno'));
        $this->assertInstanceOf(RolProfesor::class, Rol::desde(RolUsuario::Profesor));
        $this->assertInstanceOf(RolAdministrador::class, Rol::desde('administrador'));
    }

    public function test_desde_es_exacto_y_rechaza_roles_inexistentes(): void
    {
        foreach (['Alumno', ' alumno', 'admin', ''] as $texto) {
            try {
                Rol::desde($texto);
                $this->fail("Debía rechazar '{$texto}'");
            } catch (ValueError $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_tryDesde_devuelve_null_si_el_rol_no_existe(): void
    {
        $this->assertNull(Rol::tryDesde('invitado'));
        $this->assertNull(Rol::tryDesde(''));
        $this->assertInstanceOf(RolAlumno::class, Rol::tryDesde('alumno'));
    }

    public function test_cada_rol_responde_distinto_a_los_mismos_metodos(): void
    {
        $esperado = [
            'administrador' => ['Administrador', 'admin.inicio'],
            'profesor' => ['Profesor', 'profesor.inicio'],
            'alumno' => ['Alumno', 'alumno.inicio'],
        ];

        foreach ($esperado as $clave => [$nombre, $ruta]) {
            $rol = Rol::desde($clave);

            $this->assertSame($clave, $rol->clave()->value);
            $this->assertSame($nombre, $rol->nombre());
            $this->assertSame($ruta, $rol->rutaInicio());
        }
    }

    public function test_esUnoDe_compara_de_forma_exacta(): void
    {
        $profesor = Rol::desde('profesor');

        $this->assertTrue($profesor->esUnoDe('profesor'));
        $this->assertTrue($profesor->esUnoDe('profesor', 'administrador'));
        $this->assertFalse($profesor->esUnoDe('alumno', 'administrador'));
        $this->assertFalse($profesor->esUnoDe('Profesor'));
        $this->assertFalse($profesor->esUnoDe());
    }

    public function test_solo_el_administrador_y_el_profesor_ven_el_listado_de_alumnos(): void
    {
        $this->assertTrue(Rol::desde('administrador')->veListadoDeAlumnos());
        $this->assertTrue(Rol::desde('profesor')->veListadoDeAlumnos());
        $this->assertFalse(Rol::desde('alumno')->veListadoDeAlumnos());
    }

    /**
     * El menú de cada rol es idéntico (rutas, textos, íconos y orden) al que
     * tenían escrito los tres layouts antes de usar la jerarquía de roles.
     */
    public function test_el_menu_de_cada_rol_es_el_mismo_que_tenian_los_layouts(): void
    {
        $original = [
            'administrador' => [
                ['admin.inicio', 'Inicio', 'home', 'Panel de control'],
                ['admin.usuarios', 'Usuarios', 'users', 'Gestionar usuarios del sistema'],
                ['admin.boletines', 'Boletines', 'files', 'Generar boletines en PDF'],
                ['admin.reportes', 'Reportes', 'reports', 'Reportes y estadísticas'],
                ['admin.materias', 'Materias', 'subjects', 'Gestionar materias'],
                ['admin.legajos', 'Legajos', 'folder', 'Gestión de legajos'],
                ['admin.alumnos', 'Alumnos', 'students', 'Registro de todos los alumnos'],
            ],
            'profesor' => [
                ['profesor.inicio', 'Inicio', 'home', null],
                ['profesor.cargar-notas', 'Cargar notas', 'files', null],
                ['profesor.cargar-asistencias', 'Cargar asistencias', 'reports', null],
                ['profesor.historial-asistencias', 'Historial de asistencias', 'history', null],
                ['profesor.mis-alumnos', 'Mis alumnos', 'students', null],
            ],
            'alumno' => [
                ['alumno.inicio', 'Inicio', 'home', null],
                ['alumno.notas', 'Mis notas', 'files', null],
                ['alumno.asistencia', 'Mi asistencia', 'reports', null],
                ['alumno.historial', 'Historial académico', 'subjects', null],
            ],
        ];

        foreach ($original as $clave => $items) {
            $actual = array_map(
                fn (ItemMenu $item) => [$item->ruta, $item->titulo, $item->icono, $item->subtitulo],
                Rol::desde($clave)->menu()
            );

            $this->assertSame($items, $actual, "El menú de {$clave} cambió.");
        }
    }

    public function test_item_de_menu_es_inmutable(): void
    {
        $item = new ItemMenu('alumno.inicio', 'Inicio', 'home');

        $this->expectException(\Error::class);

        $item->titulo = 'Otro';
    }
}

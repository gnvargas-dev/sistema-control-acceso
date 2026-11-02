<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Profesor;
use App\Models\Usuario;
use Tests\TestCase;

/**
 * Renderiza cada layout con un usuario de ese rol (sin base de datos) y comprueba
 * que el menú sale con los mismos enlaces, textos y orden, y que el pie y el
 * encabezado usan identificador() y descripcion() del perfil.
 */
class MenuDeLayoutsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function render(string $layout, Usuario $usuario): string
    {
        $this->actingAs($usuario);

        return view($layout)->render();
    }

    public function test_menu_del_alumno_y_datos_del_perfil(): void
    {
        $alumno = (new Alumno(['legajo' => '2026-1187']))
            ->setRelation('curso', new Curso(['anio' => 3, 'nombre' => 'A']));
        $usuario = (new Usuario(['nombre' => 'Emilce Espinola', 'rol' => 'alumno']))
            ->setRelation('alumno', $alumno);

        $html = $this->render('layouts.alumno', $usuario);

        $this->assertStringContainsString('class="se-menu"', $html);
        $this->assertSeeInOrder(
            [
                route('alumno.inicio'), 'Inicio',
                route('alumno.notas'), 'Mis notas',
                route('alumno.asistencia'), 'Mi asistencia',
                route('alumno.historial'), 'Historial académico',
            ],
            $html
        );
        $this->assertSeeInOrder(['Alumno', '· 3° A · Legajo 2026-1187'], $html);
        $this->assertStringContainsString('Legajo: 2026-1187', $html);
    }

    public function test_menu_del_profesor_y_datos_del_perfil(): void
    {
        $profesor = (new Profesor())->setRelation('materias', collect([new Materia(), new Materia()]));
        $usuario = (new Usuario(['nombre' => 'Dario Borba', 'rol' => 'profesor']))
            ->setRelation('profesor', $profesor);

        $html = $this->render('layouts.profesor', $usuario);

        $this->assertSeeInOrder(
            [
                route('profesor.inicio'), 'Inicio',
                route('profesor.cargar-notas'), 'Cargar notas',
                route('profesor.cargar-asistencias'), 'Cargar asistencias',
                route('profesor.historial-asistencias'), 'Historial de asistencias',
                route('profesor.mis-alumnos'), 'Mis alumnos',
            ],
            $html
        );
        $this->assertSeeInOrder(['se-perfil-detalle', 'Profesor', '· 2 materias asignadas'], $html);
        $this->assertStringContainsString('Prof. Dario Borba', $html);
    }

    public function test_menu_del_administrador(): void
    {
        $usuario = new Usuario(['nombre' => 'Admin Sistema', 'rol' => 'administrador']);

        $html = $this->render('layouts.dashboard', $usuario);

        $this->assertSeeInOrder(
            [
                route('admin.inicio'), 'Inicio', 'Panel de control',
                route('admin.usuarios'), 'Usuarios', 'Gestionar usuarios del sistema',
                route('admin.boletines'), 'Boletines', 'Generar boletines en PDF',
                route('admin.reportes'), 'Reportes', 'Reportes y estadísticas',
                route('admin.materias'), 'Materias', 'Gestionar materias',
                route('admin.legajos'), 'Legajos', 'Gestión de legajos',
                route('admin.alumnos'), 'Alumnos', 'Registro de todos los alumnos',
            ],
            $html
        );
        $this->assertStringContainsString('Administrador', $html);
    }

    private function assertSeeInOrder(array $textos, string $html): void
    {
        $posicion = 0;

        foreach ($textos as $texto) {
            $encontrado = strpos($html, e($texto), $posicion);
            $this->assertNotFalse($encontrado, "No se encontró (o está fuera de orden): {$texto}");
            $posicion = $encontrado + strlen(e($texto));
        }
    }
}

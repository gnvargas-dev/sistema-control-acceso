<?php

namespace Tests\Unit;

use App\Roles\Rol;
use PHPUnit\Framework\TestCase;

/** Polimorfismo: cada rol aporta sus propias reglas al registrar un usuario. */
class RolRegistroTest extends TestCase
{
    public function test_el_administrador_no_suma_reglas(): void
    {
        $this->assertSame([], Rol::desde('administrador')->reglasDeRegistro());
    }

    public function test_el_alumno_exige_curso(): void
    {
        $this->assertSame(
            ['curso_id' => ['required', 'exists:cursos,id']],
            Rol::desde('alumno')->reglasDeRegistro()
        );
    }

    public function test_el_profesor_admite_materias_existentes_y_nuevas(): void
    {
        $reglas = Rol::desde('profesor')->reglasDeRegistro();

        $this->assertSame(
            ['materia_ids', 'materia_ids.*', 'materia_nuevas', 'materia_nuevas.*'],
            array_keys($reglas)
        );
        $this->assertContains('max:120', $reglas['materia_nuevas.*']);
    }

    public function test_cada_rol_responde_distinto_a_la_misma_pregunta(): void
    {
        $reglas = array_map(
            fn (string $rol) => array_keys(Rol::desde($rol)->reglasDeRegistro()),
            ['administrador', 'alumno', 'profesor']
        );

        $this->assertCount(3, array_unique(array_map('json_encode', $reglas)));
    }
}

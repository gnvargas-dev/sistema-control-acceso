<?php

namespace Tests\Feature;

use Tests\Feature\Caracterizacion\PantallaTestCase;

/** API GET /api/materias/{materia}/alumnos: la policy ahora se resuelve y responde según el usuario. */
class RegistroAlumnoApiTest extends PantallaTestCase
{
    public function test_el_profesor_de_la_materia_ve_los_alumnos_activos_de_su_curso(): void
    {
        $respuesta = $this->comoUsuario(self::PROFESOR)->getJson('/api/materias/1/alumnos');

        $respuesta->assertOk();
        $this->assertSame([1, 2], collect($respuesta->json())->pluck('id')->sort()->values()->all());
    }

    public function test_un_profesor_de_otra_materia_recibe_403(): void
    {
        $this->comoUsuario(3)->getJson('/api/materias/1/alumnos')->assertForbidden();
    }

    public function test_un_alumno_recibe_403(): void
    {
        $this->comoUsuario(4)->getJson('/api/materias/1/alumnos')->assertForbidden();
    }
}

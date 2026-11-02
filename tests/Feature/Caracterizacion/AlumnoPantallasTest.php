<?php

namespace Tests\Feature\Caracterizacion;

use App\Models\Usuario;

/**
 * Fija lo que muestran hoy las pantallas del alumno.
 */
class AlumnoPantallasTest extends PantallaTestCase
{
    public function test_inicio(): void
    {
        $this->verificarPantalla('alumno_inicio', $this->comoUsuario(self::ALUMNO)->get(route('alumno.inicio')));
    }

    public function test_inicio_de_otro_alumno_con_notas_bajas(): void
    {
        $this->verificarPantalla('alumno_inicio_ricardo', $this->comoUsuario(5)->get(route('alumno.inicio')));
    }

    public function test_notas(): void
    {
        $this->verificarPantalla('alumno_notas', $this->comoUsuario(self::ALUMNO)->get(route('alumno.notas')));
    }

    public function test_notas_de_un_periodo(): void
    {
        $this->verificarPantalla(
            'alumno_notas_1er_bimestre',
            $this->comoUsuario(self::ALUMNO)->get(route('alumno.notas', ['periodo' => '1er Bimestre']))
        );
    }

    public function test_notas_con_un_periodo_invalido_muestra_todos(): void
    {
        $this->verificarPantalla(
            'alumno_notas',
            $this->comoUsuario(self::ALUMNO)->get(route('alumno.notas', ['periodo' => 'cualquiera']))
        );
    }

    public function test_asistencia(): void
    {
        $this->verificarPantalla('alumno_asistencia', $this->comoUsuario(self::ALUMNO)->get(route('alumno.asistencia')));
    }

    public function test_asistencia_de_otro_alumno(): void
    {
        $this->verificarPantalla('alumno_asistencia_ricardo', $this->comoUsuario(5)->get(route('alumno.asistencia')));
    }

    public function test_historial(): void
    {
        $this->verificarPantalla('alumno_historial', $this->comoUsuario(self::ALUMNO)->get(route('alumno.historial')));
    }

    public function test_un_usuario_alumno_sin_perfil_recibe_403(): void
    {
        $sinPerfil = Usuario::factory()->alumno()->create();

        foreach (['alumno.inicio', 'alumno.notas', 'alumno.asistencia', 'alumno.historial'] as $ruta) {
            $this->actingAs($sinPerfil)->get(route($ruta))->assertForbidden();
        }
    }

    public function test_el_profesor_no_entra_a_las_pantallas_del_alumno(): void
    {
        $this->comoUsuario(self::PROFESOR)->get(route('alumno.inicio'))->assertForbidden();
    }
}

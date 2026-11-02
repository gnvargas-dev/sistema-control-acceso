<?php

namespace Tests\Feature\Caracterizacion;

use App\Models\Asistencia;
use App\Models\Nota;
use App\Models\Usuario;

/**
 * Fija lo que muestran y hacen hoy las pantallas del profesor.
 */
class ProfesorPantallasTest extends PantallaTestCase
{
    public function test_inicio(): void
    {
        $this->verificarPantalla('profesor_inicio', $this->comoUsuario(self::PROFESOR)->get(route('profesor.inicio')));
    }

    public function test_inicio_de_la_otra_profesora(): void
    {
        $this->verificarPantalla('profesor_inicio_laura', $this->comoUsuario(self::PROFESORA)->get(route('profesor.inicio')));
    }

    public function test_cargar_notas(): void
    {

        $this->verificarPantalla('profesor_cargar_notas', $this->comoUsuario(self::PROFESOR)->get(route('profesor.cargar-notas')));
    }

    public function test_cargar_asistencias_con_la_primera_materia_y_la_fecha_de_hoy(): void
    {
        $this->verificarPantalla('profesor_cargar_asistencias', $this->comoUsuario(self::PROFESOR)->get(route('profesor.cargar-asistencias')));
    }

    public function test_cargar_asistencias_de_una_materia_y_fecha_con_registros(): void
    {
        $this->verificarPantalla(
            'profesor_cargar_asistencias_fisica',
            $this->comoUsuario(self::PROFESOR)->get(route('profesor.cargar-asistencias', ['materia_id' => 2, 'fecha' => '2026-10-06']))
        );
    }

    public function test_cargar_asistencias_con_materia_ajena_da_403(): void
    {
        $this->comoUsuario(self::PROFESOR)
            ->get(route('profesor.cargar-asistencias', ['materia_id' => 3]))
            ->assertForbidden();
    }

    public function test_historial_de_asistencias(): void
    {
        $this->verificarPantalla('profesor_historial_asistencias', $this->comoUsuario(self::PROFESOR)->get(route('profesor.historial-asistencias')));
    }

    public function test_historial_de_asistencias_de_una_materia(): void
    {
        $this->verificarPantalla(
            'profesor_historial_asistencias_matematica',
            $this->comoUsuario(self::PROFESOR)->get(route('profesor.historial-asistencias', ['materia_id' => 1]))
        );
    }

    public function test_mis_alumnos(): void
    {
        $this->verificarPantalla('profesor_mis_alumnos', $this->comoUsuario(self::PROFESOR)->get(route('profesor.mis-alumnos')));
    }

    public function test_un_usuario_profesor_sin_perfil_recibe_403(): void
    {
        $sinPerfil = Usuario::factory()->profesor()->create();

        foreach (['profesor.inicio', 'profesor.cargar-notas', 'profesor.cargar-asistencias', 'profesor.historial-asistencias', 'profesor.mis-alumnos'] as $ruta) {
            $this->actingAs($sinPerfil)->get(route($ruta))->assertForbidden();
        }
    }

    // ---- guardar nota -------------------------------------------------

    private function datosNota(array $cambios = []): array
    {
        return array_merge(['materia_id' => 1, 'alumno_id' => 1, 'nota' => '4', 'periodo' => '3er Bimestre'], $cambios);
    }

    public function test_guardar_una_nota_nueva(): void
    {
        $this->comoUsuario(self::PROFESOR)
            ->from('/profesor/cargar-notas')
            ->post(route('profesor.guardar-nota'), $this->datosNota())
            ->assertRedirect('/profesor/cargar-notas')
            ->assertSessionHas('success', 'Nota registrada correctamente.');

        $nota = Nota::where(['alumno_id' => 1, 'materia_id' => 1, 'periodo' => '3er Bimestre'])->firstOrFail();
        $this->assertSame('4.00', $nota->valor);
        $this->assertSame(self::PROFESOR, $nota->cargado_por);
        $this->assertSame(9, Nota::count());
    }

    public function test_guardar_una_nota_existente_la_actualiza(): void
    {
        $this->comoUsuario(self::PROFESOR)->post(route('profesor.guardar-nota'), $this->datosNota([
            'periodo' => '1er Bimestre', 'nota' => '3',
        ]))->assertSessionHas('success');

        $this->assertSame(8, Nota::count());
        $this->assertSame('3.00', Nota::findOrFail(1)->valor);
    }

    public function test_guardar_nota_valida_los_datos(): void
    {
        $this->comoUsuario(self::PROFESOR)->from('/profesor/cargar-notas')->post(route('profesor.guardar-nota'), [])
            ->assertSessionHasErrors(['materia_id', 'alumno_id', 'nota', 'periodo']);

        foreach (['11', '6', '0', '-1', '2.5', 'abc'] as $nota) {
            $this->comoUsuario(self::PROFESOR)->post(route('profesor.guardar-nota'), $this->datosNota(['nota' => $nota]))
                ->assertSessionHasErrors('nota');
        }

        $this->assertSame(8, Nota::count());
    }

    public function test_guardar_nota_en_materia_ajena_da_403(): void
    {
        $this->comoUsuario(self::PROFESOR)->post(route('profesor.guardar-nota'), $this->datosNota(['materia_id' => 3, 'alumno_id' => 3]))
            ->assertForbidden();
    }

    public function test_guardar_nota_de_un_alumno_de_otro_curso_da_403(): void
    {
        $this->comoUsuario(self::PROFESOR)->post(route('profesor.guardar-nota'), $this->datosNota(['alumno_id' => 3]))
            ->assertForbidden();
    }

    public function test_guardar_nota_de_un_alumno_inexistente_da_404(): void
    {
        $this->comoUsuario(self::PROFESOR)->post(route('profesor.guardar-nota'), $this->datosNota(['alumno_id' => 999]))
            ->assertNotFound();
    }

    // ---- guardar asistencias -----------------------------------------

    private function datosAsistencia(array $cambios = []): array
    {
        return array_merge([
            'materia_id' => 1,
            'fecha' => '2026-10-13',
            'estados' => [1 => 'presente', 2 => 'tarde', 3 => 'ausente'],
            'observaciones' => [2 => 'Llegó 10 minutos tarde', 3 => 'No es de este curso'],
        ], $cambios);
    }

    public function test_guardar_asistencias_nuevas_ignora_alumnos_de_otro_curso(): void
    {
        $this->comoUsuario(self::PROFESOR)
            ->post(route('profesor.guardar-asistencias'), $this->datosAsistencia())
            ->assertRedirect(route('profesor.cargar-asistencias', ['materia_id' => 1, 'fecha' => '2026-10-13']))
            ->assertSessionHas('success', 'Asistencias guardadas correctamente.');

        $this->assertSame(18, Asistencia::count());
        $this->assertSame('presente', Asistencia::where(['alumno_id' => 1, 'materia_id' => 1, 'fecha' => '2026-10-13'])->firstOrFail()->estado);

        $tarde = Asistencia::where(['alumno_id' => 2, 'materia_id' => 1, 'fecha' => '2026-10-13'])->firstOrFail();
        $this->assertSame('tarde', $tarde->estado);
        $this->assertSame('Llegó 10 minutos tarde', $tarde->observacion);
        $this->assertSame(self::PROFESOR, $tarde->cargado_por);
        $this->assertFalse(Asistencia::where('alumno_id', 3)->where('fecha', '2026-10-13')->exists());
    }

    public function test_guardar_asistencias_de_un_dia_ya_cargado_las_actualiza(): void
    {
        $this->comoUsuario(self::PROFESOR)->post(route('profesor.guardar-asistencias'), $this->datosAsistencia([
            'fecha' => '2026-10-12',
            'estados' => [1 => 'ausente', 2 => 'ausente'],
            'observaciones' => null,
        ]))->assertSessionHas('success');

        $this->assertSame(16, Asistencia::count());
        $this->assertSame('ausente', Asistencia::findOrFail(9)->estado);
        $this->assertNull(Asistencia::findOrFail(9)->observacion);
    }

    public function test_guardar_asistencias_valida_los_datos(): void
    {
        $this->comoUsuario(self::PROFESOR)->from('/x')->post(route('profesor.guardar-asistencias'), [])
            ->assertSessionHasErrors(['materia_id', 'fecha', 'estados']);

        $this->comoUsuario(self::PROFESOR)->post(route('profesor.guardar-asistencias'), $this->datosAsistencia([
            'estados' => [1 => 'justificado'],
        ]))->assertSessionHasErrors('estados.1');

        $this->assertSame(16, Asistencia::count());
    }

    public function test_guardar_asistencias_de_una_materia_ajena_da_403(): void
    {
        $this->comoUsuario(self::PROFESOR)->post(route('profesor.guardar-asistencias'), $this->datosAsistencia(['materia_id' => 3]))
            ->assertForbidden();
    }
}

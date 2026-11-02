<?php

namespace Tests\Feature\Caracterizacion;

use Illuminate\Support\Facades\DB;

/**
 * Etapa 8B: las pantallas de asistencia reciben las métricas y dibujan los tres gráficos.
 * Usa el escenario fijo (hoy = 12/10/2026): el alumno 1 tiene 7 registros y el profesor 2,
 * 14 en sus materias 1 y 2 (8 presentes, 2 tardanzas, 3 ausentes y 1 justificado).
 */
class AsistenciaGraficosTest extends PantallaTestCase
{
    public function test_el_alumno_recibe_las_metricas_y_ve_los_tres_graficos(): void
    {
        $respuesta = $this->comoUsuario(self::ALUMNO)->get(route('alumno.asistencia'));

        $respuesta->assertOk()
            ->assertViewHas('graficos', function (array $g) {
                return $g['hayDatos'] === true
                    && $g['minimo'] === 70
                    && $g['anillo']['total'] === 7
                    && $g['anillo']['presentes'] === 5
                    && $g['anillo']['tardanzas'] === 1
                    && $g['anillo']['ausentes'] === 1
                    && $g['anillo']['porcentaje'] === 85.71
                    && count($g['semanas']) === 8
                    && count($g['dias']) === 5;
            })
            ->assertSee('se-ga-anillo', false)
            ->assertSee('se-ga-curva', false)
            ->assertSee('se-ga-barra', false)
            ->assertSee('<strong>70%</strong>', false);
    }

    public function test_el_alumno_conserva_las_variables_de_siempre(): void
    {
        $this->comoUsuario(self::ALUMNO)->get(route('alumno.asistencia'))
            ->assertViewHasAll(['alumno', 'materiasAsistencia', 'resumenGeneral', 'detalleInasistencias', 'diasConClase', 'totalMaterias']);
    }

    public function test_el_profesor_recibe_las_metricas_de_todas_sus_materias(): void
    {
        $this->comoUsuario(self::PROFESOR)->get(route('profesor.historial-asistencias'))
            ->assertOk()
            ->assertViewHas('graficos', function (array $g) {
                return $g['anillo']['total'] === 14
                    && $g['anillo']['presentes'] === 8
                    && $g['anillo']['tardanzas'] === 2
                    && $g['anillo']['ausentes'] === 3
                    && $g['anillo']['justificados'] === 1;
            })
            ->assertSee('se-ga-anillo', false);
    }

    public function test_el_profesor_filtra_los_graficos_por_materia(): void
    {
        $this->comoUsuario(self::PROFESOR)->get(route('profesor.historial-asistencias', ['materia_id' => 2]))
            ->assertOk()
            ->assertViewHas('graficos', fn (array $g) => $g['anillo']['total'] === 4);
    }

    public function test_el_profesor_no_ve_metricas_de_una_materia_ajena(): void
    {
        $this->comoUsuario(self::PROFESOR)->get(route('profesor.historial-asistencias', ['materia_id' => 3]))
            ->assertOk()
            ->assertViewHas('graficos', fn (array $g) => $g['hayDatos'] === false)
            ->assertSee('Todavía no hay asistencias registradas para mostrar gráficos.');
    }

    public function test_el_enlace_ver_editar_del_historial_es_un_enlace_real(): void
    {
        $this->comoUsuario(self::PROFESOR)->get(route('profesor.historial-asistencias', ['materia_id' => 1]))
            ->assertSee('<a', false)
            // Blade escapa el "&" de la URL como "&amp;"
            ->assertSee(e(route('profesor.cargar-asistencias', ['materia_id' => 1, 'fecha' => '2026-10-12'])), false);
    }

    public function test_mirar_los_graficos_no_escribe_en_la_base(): void
    {
        $antes = DB::table('asistencias')->count();

        $this->comoUsuario(self::ALUMNO)->get(route('alumno.asistencia'))->assertOk();
        $this->comoUsuario(self::PROFESOR)->get(route('profesor.historial-asistencias'))->assertOk();

        $this->assertSame($antes, DB::table('asistencias')->count());
    }
}

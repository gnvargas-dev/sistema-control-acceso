<?php

namespace Tests\Feature;

use App\Models\Evaluacion;
use App\Models\Nota;
use App\Models\Usuario;
use App\Services\EvaluacionService;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Caracterizacion\PantallaTestCase;

/**
 * Pantallas y rutas de las evaluaciones por puntos: "Cargar notas" del profesor
 * (parciales, examen final y grilla de puntos) y "Mis notas" del alumno.
 */
class EvaluacionPantallasTest extends PantallaTestCase
{
    private function evaluacionesValidas(array $cambios = []): array
    {
        return array_replace_recursive([
            'materia_id' => 1,
            'semestre' => 1,
            'parciales' => [
                ['id' => '', 'nombre' => 'Parcial 1', 'puntos_max' => 50],
                ['id' => '', 'nombre' => 'Parcial 2', 'puntos_max' => 30],
            ],
            'examen' => ['nombre' => 'Examen final', 'puntos_max' => 40],
        ], $cambios);
    }

    /** @return list<int> ids: parcial 1, parcial 2, examen */
    private function configurarComoProfesor(): array
    {
        $this->comoUsuario(self::PROFESOR)->post(route('profesor.guardar-evaluaciones'), $this->evaluacionesValidas())->assertSessionHasNoErrors();

        return app(EvaluacionService::class)->evaluacionesDe(1, 1)->modelKeys();
    }

    private function destino(): string
    {
        return route('profesor.cargar-notas', ['materia_id' => 1, 'semestre' => 1]);
    }

    public function test_la_pantalla_del_profesor_ya_no_tiene_bimestres_ni_calculadora(): void
    {
        $respuesta = $this->comoUsuario(self::PROFESOR)->get(route('profesor.cargar-notas'));

        $respuesta->assertOk()
            ->assertSee('Agregar parcial')
            ->assertSee('Agregar examen final')
            ->assertDontSee('Bimestre')
            ->assertDontSee('Calculadora')
            ->assertDontSee('data-se-calc', false);
    }

    public function test_guardar_evaluaciones_las_crea_y_vuelve_a_la_pantalla(): void
    {
        $this->comoUsuario(self::PROFESOR)
            ->post(route('profesor.guardar-evaluaciones'), $this->evaluacionesValidas())
            ->assertRedirect($this->destino())
            ->assertSessionHas('success');

        $this->assertSame([50, 30, 40], Evaluacion::query()->where('materia_id', 1)->where('semestre', 1)->orderByRaw("tipo = 'examen'")->orderBy('orden')->pluck('puntos_max')->all());

        $this->comoUsuario(self::PROFESOR)->get($this->destino())->assertOk()->assertSee('Puntos de los alumnos')->assertSee('Emilce Espinola');
    }

    public function test_guardar_evaluaciones_valida_el_rango_y_la_cantidad_de_parciales(): void
    {
        $this->comoUsuario(self::PROFESOR);

        foreach ([4, 101, 0, -3] as $max) {
            $this->post(route('profesor.guardar-evaluaciones'), $this->evaluacionesValidas(['parciales' => [1 => ['puntos_max' => $max]]]))
                ->assertSessionHasErrors('parciales.1.puntos_max');
        }

        $this->post(route('profesor.guardar-evaluaciones'), $this->evaluacionesValidas(['examen' => ['puntos_max' => 150]]))
            ->assertSessionHasErrors('examen.puntos_max');

        $solo = $this->evaluacionesValidas();
        $solo['parciales'] = [$solo['parciales'][0]];
        $this->post(route('profesor.guardar-evaluaciones'), $solo)->assertSessionHasErrors('evaluaciones');

        $seis = $this->evaluacionesValidas();
        $seis['parciales'] = array_fill(0, 6, ['id' => '', 'nombre' => 'P', 'puntos_max' => 20]);
        $this->post(route('profesor.guardar-evaluaciones'), $seis)->assertSessionHasErrors('parciales');

        $this->assertSame(0, Evaluacion::query()->count());
    }

    public function test_el_puntaje_maximo_acepta_los_bordes_5_y_100(): void
    {
        $this->comoUsuario(self::PROFESOR)
            ->post(route('profesor.guardar-evaluaciones'), $this->evaluacionesValidas([
                'parciales' => [['puntos_max' => 5], ['puntos_max' => 100]],
                'examen' => ['puntos_max' => 100],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(3, Evaluacion::query()->count());
    }

    public function test_un_profesor_no_configura_ni_carga_materias_ajenas(): void
    {
        $this->comoUsuario(self::PROFESORA)
            ->post(route('profesor.guardar-evaluaciones'), $this->evaluacionesValidas())
            ->assertForbidden();

        $this->comoUsuario(self::PROFESORA)
            ->post(route('profesor.guardar-resultados'), ['materia_id' => 1, 'semestre' => 1, 'puntos' => []])
            ->assertForbidden();

        $this->comoUsuario(self::PROFESORA)->get(route('profesor.cargar-notas', ['materia_id' => 1]))->assertForbidden();
    }

    public function test_guardar_puntos_calcula_la_nota_y_el_alumno_la_ve(): void
    {
        [$p1, $p2, $examen] = $this->configurarComoProfesor();

        $this->comoUsuario(self::PROFESOR)
            ->post(route('profesor.guardar-resultados'), [
                'materia_id' => 1,
                'semestre' => 1,
                'puntos' => [1 => [$p1 => '45', $p2 => '25,5', $examen => '36']],
            ])
            ->assertRedirect($this->destino())
            ->assertSessionHasNoErrors();

        // Parciales 70,5/80 (88,13% → 52,88) + examen 36/40 (90% → 36) = 88,88 → 89 → nota 4
        $nota = Nota::query()->where('alumno_id', 1)->where('materia_id', 1)->where('periodo', 'Semestre 1')->firstOrFail();
        $this->assertSame(4.0, (float) $nota->valor);

        $this->comoUsuario(self::ALUMNO)->get(route('alumno.notas'))
            ->assertOk()
            ->assertSee('Aprobado')
            ->assertSee('Pendiente') // Física y el segundo semestre siguen sin evaluaciones
            ->assertDontSee('Calculadora');

        $this->comoUsuario(self::PROFESOR)->get($this->destino())->assertOk()->assertSee('89/100');
    }

    public function test_guardar_puntos_invalidos_no_guarda_nada_y_avisa(): void
    {
        [$p1, $p2, $examen] = $this->configurarComoProfesor();

        $this->comoUsuario(self::PROFESOR)
            ->post(route('profesor.guardar-resultados'), ['materia_id' => 1, 'semestre' => 1, 'puntos' => [1 => [$p1 => '51', $p2 => '10']]])
            ->assertRedirect($this->destino())
            ->assertSessionHasErrors('puntos');

        $this->assertSame(0, DB::table('resultados_evaluacion')->count());
    }

    public function test_un_puntaje_vacio_deja_la_nota_pendiente_en_pantalla(): void
    {
        [$p1, $p2, $examen] = $this->configurarComoProfesor();

        $this->post(route('profesor.guardar-resultados'), ['materia_id' => 1, 'semestre' => 1, 'puntos' => [1 => [$p1 => '40', $p2 => '25', $examen => '']]]);

        $this->assertSame(0, Nota::query()->where('alumno_id', 1)->where('periodo', 'Semestre 1')->count());
        $this->comoUsuario(self::PROFESOR)->get($this->destino())->assertSee('Pendiente');
    }

    public function test_el_alumno_sin_evaluaciones_ve_todo_pendiente_y_sin_bimestres(): void
    {
        $this->comoUsuario(self::ALUMNO)->get(route('alumno.notas'))
            ->assertOk()
            ->assertSee('Pendiente')
            ->assertSee('Primer Semestre')
            ->assertSee('Segundo Semestre')
            ->assertDontSee('Bimestre');
    }

    private function accion(array $datos)
    {
        return $this->comoUsuario(self::PROFESOR)->post(route('profesor.guardar-evaluaciones'), $datos + ['materia_id' => 1, 'semestre' => 1]);
    }

    public function test_se_puede_cargar_un_parcial_solo_y_cargar_sus_puntos(): void
    {
        $this->accion(['accion' => 'parcial', 'nombre' => 'Primer parcial', 'puntos_max' => 40])
            ->assertRedirect($this->destino())
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();

        $p1 = app(EvaluacionService::class)->evaluacionesDe(1, 1);
        $this->assertCount(1, $p1);
        $this->assertSame('Primer parcial', $p1->first()->nombre);

        // Con una sola evaluación ya aparece la grilla de puntos
        $this->comoUsuario(self::PROFESOR)->get($this->destino())->assertOk()->assertSee('Primer parcial')->assertSee('Emilce Espinola')->assertSee('Pendiente');

        $this->post(route('profesor.guardar-resultados'), ['materia_id' => 1, 'semestre' => 1, 'puntos' => [1 => [$p1->first()->id => '35']]])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, Nota::query()->where('alumno_id', 1)->where('periodo', 'Semestre 1')->count());

        // El alumno ya ve ese parcial cargado (35 de 40) y su nota sigue pendiente
        $this->comoUsuario(self::ALUMNO)->get(route('alumno.notas'))
            ->assertOk()
            ->assertSee('Primer parcial', false)
            ->assertSee('de 40')
            ->assertSee('Pendiente');
    }

    public function test_los_parciales_y_el_examen_se_cargan_en_cualquier_orden_y_por_separado(): void
    {
        $this->accion(['accion' => 'examen', 'puntos_max' => 50])->assertSessionHasNoErrors();
        $this->accion(['accion' => 'parcial', 'puntos_max' => 30])->assertSessionHasNoErrors();
        $this->accion(['accion' => 'parcial', 'puntos_max' => 20])->assertSessionHasNoErrors();

        $ids = app(EvaluacionService::class)->evaluacionesDe(1, 1)->modelKeys();
        $this->assertCount(3, $ids);

        [$p1, $p2, $examen] = $ids;

        // Un alumno a la vez y una evaluación a la vez
        $this->post(route('profesor.guardar-resultados'), ['materia_id' => 1, 'semestre' => 1, 'puntos' => [1 => [$p1 => '30']]]);
        $this->post(route('profesor.guardar-resultados'), ['materia_id' => 1, 'semestre' => 1, 'puntos' => [1 => [$p2 => '20']]]);
        $this->assertSame(0, Nota::query()->where('alumno_id', 1)->where('periodo', 'Semestre 1')->count());

        $this->post(route('profesor.guardar-resultados'), ['materia_id' => 1, 'semestre' => 1, 'puntos' => [1 => [$examen => '50']]]);

        $nota = Nota::query()->where('alumno_id', 1)->where('materia_id', 1)->where('periodo', 'Semestre 1')->firstOrFail();
        $this->assertSame(5.0, (float) $nota->valor);

        $this->comoUsuario(self::ALUMNO)->get(route('alumno.notas'))->assertOk()->assertSee('Aprobado');
    }

    public function test_editar_un_parcial_y_quitarlo_con_y_sin_puntos(): void
    {
        $this->accion(['accion' => 'parcial', 'nombre' => 'A', 'puntos_max' => 50]);
        $this->accion(['accion' => 'parcial', 'nombre' => 'B', 'puntos_max' => 50]);
        [$a, $b] = app(EvaluacionService::class)->evaluacionesDe(1, 1)->modelKeys();

        $this->accion(['accion' => 'parcial', 'evaluacion_id' => $a, 'nombre' => 'A editado', 'puntos_max' => 60])->assertSessionHasNoErrors();
        $this->assertSame('A editado', Evaluacion::findOrFail($a)->nombre);
        $this->assertSame(60, Evaluacion::findOrFail($a)->puntos_max);

        $this->post(route('profesor.guardar-resultados'), ['materia_id' => 1, 'semestre' => 1, 'puntos' => [1 => [$a => '40']]]);

        $this->accion(['accion' => 'quitar', 'evaluacion_id' => $a])->assertSessionHasErrors('evaluaciones');
        $this->assertNotNull(Evaluacion::find($a));

        $this->accion(['accion' => 'quitar', 'evaluacion_id' => $a, 'confirmar_borrado' => 1])->assertSessionHasNoErrors();
        $this->assertNull(Evaluacion::find($a));

        $this->accion(['accion' => 'quitar', 'evaluacion_id' => $b])->assertSessionHasNoErrors(); // sin puntos: no pide confirmación
        $this->assertSame(0, Evaluacion::query()->count());
    }

    public function test_no_se_puede_pasar_de_5_parciales_ni_salir_del_rango(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->accion(['accion' => 'parcial', 'puntos_max' => 20])->assertSessionHasNoErrors();
        }

        $this->accion(['accion' => 'parcial', 'puntos_max' => 20])->assertSessionHasErrors('evaluaciones');
        $this->assertSame(5, Evaluacion::query()->count());

        $this->accion(['accion' => 'parcial', 'puntos_max' => 4])->assertSessionHasErrors('puntos_max');
        $this->accion(['accion' => 'examen', 'puntos_max' => 101])->assertSessionHasErrors('puntos_max');
        $this->assertSame(5, Evaluacion::query()->count());
    }

    public function test_un_profesor_ajeno_no_maneja_evaluaciones_individuales(): void
    {
        $this->comoUsuario(self::PROFESORA)
            ->post(route('profesor.guardar-evaluaciones'), ['accion' => 'parcial', 'materia_id' => 1, 'semestre' => 1, 'puntos_max' => 20])
            ->assertForbidden();

        $this->accion(['accion' => 'parcial', 'puntos_max' => 20]);
        $id = Evaluacion::query()->value('id');

        $this->comoUsuario(self::PROFESORA)
            ->post(route('profesor.guardar-evaluaciones'), ['accion' => 'quitar', 'evaluacion_id' => $id, 'materia_id' => 1, 'semestre' => 1])
            ->assertForbidden();

        $this->assertNotNull(Evaluacion::find($id));
    }

    public function test_la_vista_del_alumno_ya_no_muestra_total_sobre_100(): void
    {
        $this->configurarComoProfesor();

        $this->comoUsuario(self::ALUMNO)->get(route('alumno.notas'))
            ->assertOk()
            ->assertDontSee('Total sobre 100')
            ->assertDontSee('/100');
    }

    public function test_guardar_nota_antiguo_sigue_funcionando(): void
    {
        $this->comoUsuario(self::PROFESOR)
            ->post(route('profesor.guardar-nota'), ['materia_id' => 1, 'alumno_id' => 1, 'nota' => 3, 'periodo' => '3er Bimestre'])
            ->assertRedirect();

        $this->assertSame(1, Nota::query()->where('alumno_id', 1)->where('periodo', '3er Bimestre')->count());
    }
}

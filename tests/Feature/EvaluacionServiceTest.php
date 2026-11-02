<?php

namespace Tests\Feature;

use App\Enums\TipoEvaluacion;
use App\Models\Evaluacion;
use App\Models\Nota;
use App\Models\ResultadoEvaluacion;
use App\Models\Usuario;
use App\Services\EvaluacionService;
use App\Valores\ResultadoSemestral;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Feature\Caracterizacion\PantallaTestCase;

/**
 * Evaluaciones por puntos: configuración (2 a 5 parciales + examen), carga de resultados
 * y nota final del semestre guardada en notas. Escenario: profesor 2 da la materia 1
 * (curso 1, alumnos 1 y 2).
 */
class EvaluacionServiceTest extends PantallaTestCase
{
    private const MATERIA = 1;

    private function servicio(): EvaluacionService
    {
        return app(EvaluacionService::class);
    }

    private function profesor(): Usuario
    {
        return Usuario::findOrFail(self::PROFESOR);
    }

    /** @param list<int> $maximos */
    private function configurar(int $semestre = 1, array $maximos = [50, 50], int $examen = 50, bool $confirmar = false, array $conIds = []): void
    {
        $parciales = [];

        foreach ($maximos as $i => $max) {
            $parciales[] = ['id' => $conIds[$i] ?? null, 'nombre' => 'Parcial ' . ($i + 1), 'puntos_max' => $max];
        }

        $this->servicio()->configurarSemestre($this->profesor(), self::MATERIA, $semestre, $parciales, ['puntos_max' => $examen], $confirmar);
    }

    /** @return list<int> ids de las evaluaciones en el orden parcial 1..n, examen */
    private function ids(int $semestre = 1): array
    {
        return $this->servicio()->evaluacionesDe(self::MATERIA, $semestre)->modelKeys();
    }

    private function cargar(int $alumno, array $puntosPorEvaluacion): void
    {
        $this->servicio()->registrarResultados($this->profesor(), self::MATERIA, 1, [$alumno => $puntosPorEvaluacion]);
    }

    private function notaSemestre(int $alumno, int $semestre = 1): ?float
    {
        $valor = Nota::query()
            ->where('alumno_id', $alumno)
            ->where('materia_id', self::MATERIA)
            ->where('periodo', 'Semestre ' . $semestre)
            ->value('valor');

        return $valor === null ? null : (float) $valor;
    }

    public function test_configurar_crea_los_parciales_y_el_examen(): void
    {
        $this->configurar(1, [20, 30, 40]);

        $evaluaciones = $this->servicio()->evaluacionesDe(self::MATERIA, 1);

        $this->assertCount(4, $evaluaciones);
        $this->assertSame([1, 2, 3, 1], $evaluaciones->pluck('orden')->all());
        $this->assertSame([20, 30, 40, 50], $evaluaciones->pluck('puntos_max')->all());
        $this->assertSame(TipoEvaluacion::Examen, $evaluaciones->last()->tipo);
        $this->assertSame('Parcial 1', $evaluaciones->first()->nombre);
        $this->assertSame('Examen final', $evaluaciones->last()->nombre);
    }

    public function test_cada_semestre_tiene_de_2_a_5_parciales(): void
    {
        foreach ([[], [50], [50, 50, 50, 50, 50, 50]] as $maximos) {
            try {
                $this->configurar(1, $maximos);
                $this->fail('Debía rechazar ' . count($maximos) . ' parciales');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->configurar(1, [50, 50]);
        $this->configurar(2, [20, 20, 20, 20, 20]);

        $this->assertCount(3, $this->servicio()->evaluacionesDe(self::MATERIA, 1));
        $this->assertCount(6, $this->servicio()->evaluacionesDe(self::MATERIA, 2));
    }

    public function test_el_puntaje_maximo_es_libre_entero_de_5_a_100(): void
    {
        foreach ([4, 101, 0, -5, '', 'abc', 30.5, '7.5'] as $max) {
            try {
                $this->configurar(1, [50, $max]);
                $this->fail('Debía rechazar el máximo ' . json_encode($max));
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }

        try {
            $this->configurar(1, [50, 50], 4);
            $this->fail('Debía rechazar un examen de 4 puntos');
        } catch (InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }

        $this->configurar(1, [5, 100, 37, 73, 61], 100);
        $this->configurar(2, [5, 100], 5);

        $this->assertSame([5, 100, 37, 73, 61, 100], $this->servicio()->evaluacionesDe(self::MATERIA, 1)->pluck('puntos_max')->all());
        $this->assertSame([5, 100, 5], $this->servicio()->evaluacionesDe(self::MATERIA, 2)->pluck('puntos_max')->all());
    }

    public function test_el_semestre_debe_ser_1_o_2(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->configurar(3);
    }

    public function test_solo_el_profesor_de_la_materia_puede_configurar_y_cargar(): void
    {
        $otra = Usuario::findOrFail(self::PROFESORA);

        try {
            $this->servicio()->configurarSemestre($otra, self::MATERIA, 1, [
                ['puntos_max' => 50], ['puntos_max' => 50],
            ], ['puntos_max' => 50]);
            $this->fail('Debía rechazar a un profesor ajeno a la materia');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->configurar();
        [$p1, $p2, $examen] = $this->ids();

        $this->expectException(HttpException::class);
        $this->servicio()->registrarResultados($otra, self::MATERIA, 1, [1 => [$p1 => 40]]);
    }

    public function test_la_nota_queda_pendiente_hasta_cargar_todas_las_evaluaciones(): void
    {
        $this->configurar();
        [$p1, $p2, $examen] = $this->ids();

        $this->cargar(1, [$p1 => 45, $p2 => 40]);
        $this->assertNull($this->notaSemestre(1));
        $this->assertNull($this->servicio()->resultadoDe(1, self::MATERIA, 1));

        $this->cargar(1, [$examen => 40]);

        // 85/100 → 51 + 40/50 → 32 = 83 → nota 3
        $this->assertSame(3.0, $this->notaSemestre(1));

        $resultado = $this->servicio()->resultadoDe(1, self::MATERIA, 1);
        $this->assertInstanceOf(ResultadoSemestral::class, $resultado);
        $this->assertSame(83, $resultado->total());
    }

    public function test_la_nota_se_guarda_en_notas_con_el_periodo_del_semestre(): void
    {
        $this->configurar(2);
        [$p1, $p2, $examen] = $this->ids(2);

        $this->servicio()->registrarResultados($this->profesor(), self::MATERIA, 2, [1 => [$p1 => 50, $p2 => 50, $examen => 50]]);

        $nota = Nota::query()->where('alumno_id', 1)->where('materia_id', self::MATERIA)->where('periodo', 'Semestre 2')->firstOrFail();
        $this->assertSame(5.0, (float) $nota->valor);
        $this->assertEquals(self::PROFESOR, $nota->cargado_por);
        $this->assertNull($this->notaSemestre(1, 1));
    }

    public function test_el_candado_deja_la_nota_en_1(): void
    {
        $this->configurar();
        [$p1, $p2, $examen] = $this->ids();

        // Parciales 100/100 (60) + examen 25/50 (20) = 80, pero el examen no llega al 70%
        $this->cargar(2, [$p1 => 50, $p2 => 50, $examen => 25]);

        $this->assertSame(1.0, $this->notaSemestre(2));
        $this->assertSame(80, $this->servicio()->resultadoDe(2, self::MATERIA, 1)->total());
    }

    public function test_corregir_un_puntaje_recalcula_la_nota(): void
    {
        $this->configurar();
        [$p1, $p2, $examen] = $this->ids();
        $this->cargar(1, [$p1 => 45, $p2 => 40, $examen => 40]);
        $this->assertSame(3.0, $this->notaSemestre(1));

        $this->servicio()->registrarResultado($this->profesor(), $p2, 1, 50);

        // 95/100 → 57 + 32 = 89 → nota 4
        $this->assertSame(4.0, $this->notaSemestre(1));
        $this->assertSame(1, ResultadoEvaluacion::query()->where('evaluacion_id', $p2)->where('alumno_id', 1)->count());
    }

    public function test_vaciar_una_celda_deja_la_nota_pendiente(): void
    {
        $this->configurar();
        [$p1, $p2, $examen] = $this->ids();
        $this->cargar(1, [$p1 => 45, $p2 => 40, $examen => 40]);

        $this->cargar(1, [$p2 => '']);

        $this->assertNull($this->notaSemestre(1));
        $this->assertSame(0, ResultadoEvaluacion::query()->where('evaluacion_id', $p2)->count());
    }

    public function test_agregar_un_parcial_deja_pendiente_la_nota_ya_guardada(): void
    {
        $this->configurar();
        [$p1, $p2, $examen] = $this->ids();
        $this->cargar(1, [$p1 => 45, $p2 => 40, $examen => 40]);
        $this->assertSame(3.0, $this->notaSemestre(1));

        $this->configurar(1, [50, 50, 50], 50, false, [$p1, $p2]);

        $this->assertNull($this->notaSemestre(1));

        [, , $p3] = $this->servicio()->evaluacionesDe(self::MATERIA, 1)->modelKeys();
        $this->cargar(1, [$p3 => 50]);

        // 135/150 = 90% → 54 + 32 = 86 → nota 4
        $this->assertSame(4.0, $this->notaSemestre(1));
    }

    public function test_quitar_un_parcial_recalcula_con_los_que_quedan(): void
    {
        $this->configurar(1, [50, 50, 50]);
        [$p1, $p2, $p3, $examen] = $this->ids();
        $this->cargar(1, [$p1 => 45, $p2 => 40, $p3 => 50, $examen => 40]);
        $this->assertSame(4.0, $this->notaSemestre(1)); // 90% → 54 + 32 = 86

        $this->configurar(1, [50, 50], 50, true, [$p1, $p2]);

        $this->assertNull(Evaluacion::find($p3));
        $this->assertSame(3.0, $this->notaSemestre(1)); // 85/100 → 51 + 32 = 83
    }

    public function test_quitar_un_parcial_con_resultados_pide_confirmacion(): void
    {
        $this->configurar(1, [50, 50, 50]);
        [$p1, $p2, $p3] = $this->ids();
        $this->cargar(1, [$p3 => 30]);

        try {
            $this->configurar(1, [50, 50], 50, false, [$p1, $p2]);
            $this->fail('Debía pedir confirmación');
        } catch (InvalidArgumentException) {
            $this->assertNotNull(Evaluacion::find($p3));
            $this->assertSame(1, ResultadoEvaluacion::query()->where('evaluacion_id', $p3)->count());
        }
    }

    public function test_no_se_puede_dejar_un_semestre_con_menos_de_2_parciales_ni_borrando_parciales(): void
    {
        $this->configurar(1, [50, 50, 50]);
        [$p1] = $this->ids();

        try {
            $this->configurar(1, [50], 50, true, [$p1]);
            $this->fail('Debía rechazar quedar con un solo parcial');
        } catch (InvalidArgumentException) {
            $this->assertCount(4, $this->servicio()->evaluacionesDe(self::MATERIA, 1));
        }
    }

    public function test_un_parcial_nuevo_ocupa_el_primer_lugar_libre(): void
    {
        $this->configurar(1, [50, 50, 50]);
        [$p1, , $p3] = $this->ids();

        $this->configurar(1, [50, 50, 50], 50, true, [$p1, $p3]); // quita el 2.º y agrega uno nuevo

        $this->assertSame([1, 2, 3, 1], $this->servicio()->evaluacionesDe(self::MATERIA, 1)->pluck('orden')->all());
    }

    public function test_los_puntos_deben_estar_entre_0_y_el_maximo(): void
    {
        $this->configurar(1, [20, 30]);
        [$p1, $p2, $examen] = $this->ids();

        foreach ([[$p1, 21], [$p1, -1], [$p2, 31], [$examen, 51], [$p1, 'abc'], [$p1, 10.005]] as [$evaluacion, $puntos]) {
            try {
                $this->cargar(1, [$evaluacion => $puntos]);
                $this->fail("Debía rechazar {$puntos}");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->cargar(1, [$p1 => 20, $p2 => '12,5']);
        $this->assertSame('12.50', ResultadoEvaluacion::query()->where('evaluacion_id', $p2)->where('alumno_id', 1)->value('puntos'));
    }

    public function test_no_se_puede_cargar_una_evaluacion_de_otra_materia_ni_un_alumno_de_otro_curso(): void
    {
        $this->configurar();
        [$p1] = $this->ids();

        try {
            $this->cargar(3, [$p1 => 10]); // alumno 3 cursa 2° B
            $this->fail('Debía rechazar al alumno de otro curso');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->cargar(1, [999999 => 10]);
    }

    public function test_no_se_puede_bajar_el_maximo_por_debajo_de_un_puntaje_cargado(): void
    {
        $this->configurar(1, [50, 50]);
        [$p1, $p2] = $this->ids();
        $this->cargar(1, [$p1 => 35]);

        try {
            $this->configurar(1, [30, 50], 50, false, [$p1, $p2]);
            $this->fail('Debía rechazar bajar el máximo a 30 con 35 cargado');
        } catch (InvalidArgumentException) {
            $this->assertSame(50, Evaluacion::findOrFail($p1)->puntos_max);
        }

        $this->configurar(1, [40, 50], 50, false, [$p1, $p2]); // 40 >= 35: permitido
        $this->assertSame(40, Evaluacion::findOrFail($p1)->puntos_max);
    }

    public function test_cambiar_un_maximo_recalcula_las_notas(): void
    {
        $this->configurar(1, [50, 50]);
        [$p1, $p2, $examen] = $this->ids();
        $this->cargar(1, [$p1 => 40, $p2 => 40, $examen => 40]); // 80% / 80% → 48 + 32 = 80 → nota 3
        $this->assertSame(3.0, $this->notaSemestre(1));

        $this->configurar(1, [50, 50], 40, false, [$p1, $p2]); // examen 40/40 = 100% → 48 + 40 = 88 → 4

        $this->assertSame(4.0, $this->notaSemestre(1));
    }

    public function test_las_notas_viejas_por_bimestre_no_se_tocan(): void
    {
        $antes = Nota::query()->where('periodo', 'like', '%Bimestre')->orderBy('id')->get(['id', 'valor', 'periodo'])->toArray();

        $this->configurar();
        [$p1, $p2, $examen] = $this->ids();
        $this->cargar(1, [$p1 => 50, $p2 => 50, $examen => 50]);
        $this->configurar(1, [50, 50, 50], 50, false, [$p1, $p2]);

        $despues = Nota::query()->where('periodo', 'like', '%Bimestre')->orderBy('id')->get(['id', 'valor', 'periodo'])->toArray();
        $this->assertSame($antes, $despues);
    }

    public function test_los_semestres_y_las_materias_son_independientes(): void
    {
        $this->configurar(1);
        $this->configurar(2, [20, 20]);

        $this->assertCount(3, $this->servicio()->evaluacionesDe(self::MATERIA, 1));
        $this->assertCount(3, $this->servicio()->evaluacionesDe(self::MATERIA, 2));
        $this->assertCount(0, $this->servicio()->evaluacionesDe(2, 1));
    }

    public function test_la_base_rechaza_un_sexto_parcial_y_valores_invalidos(): void
    {
        $this->configurar(1, [50, 50, 50, 50, 50]);

        $fila = ['materia_id' => self::MATERIA, 'semestre' => 1, 'tipo' => 'parcial', 'orden' => 6, 'nombre' => 'Sexto', 'puntos_max' => 50];

        foreach ([$fila, [...$fila, 'orden' => 1], [...$fila, 'orden' => 3, 'semestre' => 2, 'puntos_max' => 4], [...$fila, 'orden' => 3, 'semestre' => 2, 'puntos_max' => 101], [...$fila, 'orden' => 3, 'semestre' => 3]] as $invalida) {
            try {
                DB::table('evaluaciones')->insert($invalida);
                $this->fail('La base debía rechazar: ' . json_encode($invalida));
            } catch (QueryException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_el_promedio_usa_solo_semestres_si_el_alumno_ya_los_tiene(): void
    {
        // Alumno 1 (solo Bimestres): promedian todas sus notas (4, 5 y 5)
        $this->assertCount(3, Nota::query()->where('alumno_id', 1)->paraPromedio(1)->get());

        $this->configurar();
        [$p1, $p2, $examen] = $this->ids();
        $this->cargar(1, [$p1 => 50, $p2 => 50, $examen => 50]);

        $notas = Nota::query()->where('alumno_id', 1)->paraPromedio(1)->get();

        $this->assertCount(1, $notas);
        $this->assertSame('Semestre 1', $notas->first()->periodo);

        // Alumno 2 sigue sin semestres: nada cambia
        $this->assertCount(3, Nota::query()->where('alumno_id', 2)->paraPromedio(2)->get());
    }

    public function test_se_puede_crear_un_parcial_solo_y_despues_los_demas(): void
    {
        $p1 = $this->servicio()->guardarParcial($this->profesor(), self::MATERIA, 1, null, 'Primero', 40);
        $this->assertSame(1, $p1->orden);
        $this->assertSame('Primero', $p1->nombre);
        $this->assertCount(1, $this->servicio()->evaluacionesDe(self::MATERIA, 1));

        $this->cargar(1, [$p1->id => 30]);
        $this->assertNull($this->notaSemestre(1));
        $this->assertNull($this->servicio()->resultadoDe(1, self::MATERIA, 1));

        $p2 = $this->servicio()->guardarParcial($this->profesor(), self::MATERIA, 1, null, null, 60);
        $this->assertSame('Parcial 2', $p2->nombre);

        $examen = $this->servicio()->guardarExamen($this->profesor(), self::MATERIA, 1, null, 50);
        $this->assertSame('Examen final', $examen->nombre);

        // Faltan puntos del 2.º parcial y del examen: sigue pendiente
        $this->cargar(1, [$p2->id => 60]);
        $this->assertNull($this->notaSemestre(1));

        $this->cargar(1, [$examen->id => 50]);

        // 90/100 → 54 + 40 = 94 → nota 5
        $this->assertSame(5.0, $this->notaSemestre(1));
    }

    public function test_el_examen_se_puede_cargar_antes_que_los_parciales(): void
    {
        $examen = $this->servicio()->guardarExamen($this->profesor(), self::MATERIA, 1, 'Final', 30);
        $this->cargar(2, [$examen->id => 30]);

        $this->assertNull($this->notaSemestre(2));

        $a = $this->servicio()->guardarParcial($this->profesor(), self::MATERIA, 1, null, null, 20);
        $b = $this->servicio()->guardarParcial($this->profesor(), self::MATERIA, 1, null, null, 20);
        $this->cargar(2, [$a->id => 20, $b->id => 20]);

        $this->assertSame(5.0, $this->notaSemestre(2));
    }

    public function test_editar_un_parcial_individual_recalcula_y_respeta_lo_cargado(): void
    {
        $this->configurar();
        [$p1, $p2, $examen] = $this->ids();
        $this->cargar(1, [$p1 => 40, $p2 => 40, $examen => 40]);
        $this->assertSame(3.0, $this->notaSemestre(1));

        $this->servicio()->guardarParcial($this->profesor(), self::MATERIA, 1, $p2, 'Segundo', 40);

        // Parciales 80/90 = 88,9% → 53,33 + examen 32 = 85,33 → 85 → 3 (no cambió el tramo, pero se recalculó)
        $this->assertSame('Segundo', Evaluacion::findOrFail($p2)->nombre);
        $this->assertSame(3.0, $this->notaSemestre(1));

        $this->expectException(InvalidArgumentException::class);
        $this->servicio()->guardarParcial($this->profesor(), self::MATERIA, 1, $p1, null, 30); // 40 cargado > 30
    }

    public function test_agregar_un_parcial_individual_deja_pendiente_la_nota_guardada(): void
    {
        $this->configurar();
        [$p1, $p2, $examen] = $this->ids();
        $this->cargar(1, [$p1 => 45, $p2 => 40, $examen => 40]);
        $this->assertSame(3.0, $this->notaSemestre(1));

        $nuevo = $this->servicio()->guardarParcial($this->profesor(), self::MATERIA, 1, null, null, 50);
        $this->assertNull($this->notaSemestre(1));

        $this->cargar(1, [$nuevo->id => 50]);
        $this->assertSame(4.0, $this->notaSemestre(1));
    }

    public function test_no_hay_mas_de_5_parciales_por_la_via_individual(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->servicio()->guardarParcial($this->profesor(), self::MATERIA, 1, null, null, 20);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->servicio()->guardarParcial($this->profesor(), self::MATERIA, 1, null, null, 20);
    }

    public function test_quitar_una_evaluacion_pide_confirmacion_si_tiene_puntos_y_recalcula(): void
    {
        $this->configurar(1, [50, 50, 50]);
        [$p1, $p2, $p3, $examen] = $this->ids();
        $this->cargar(1, [$p1 => 45, $p2 => 40, $p3 => 50, $examen => 40]);
        $this->assertSame(4.0, $this->notaSemestre(1));

        try {
            $this->servicio()->quitarEvaluacion($this->profesor(), $p3);
            $this->fail('Debía pedir confirmación');
        } catch (InvalidArgumentException) {
            $this->assertNotNull(Evaluacion::find($p3));
        }

        $this->servicio()->quitarEvaluacion($this->profesor(), $p3, true);

        $this->assertNull(Evaluacion::find($p3));
        $this->assertSame(3.0, $this->notaSemestre(1));

        // Quitar el examen (con puntos) deja la nota pendiente
        $this->servicio()->quitarEvaluacion($this->profesor(), $examen, true);
        $this->assertNull($this->notaSemestre(1));
    }

    public function test_quitar_una_evaluacion_sin_puntos_no_pide_confirmacion(): void
    {
        $parcial = $this->servicio()->guardarParcial($this->profesor(), self::MATERIA, 1, null, null, 20);

        $this->servicio()->quitarEvaluacion($this->profesor(), $parcial->id);

        $this->assertNull(Evaluacion::find($parcial->id));
    }

    public function test_las_operaciones_individuales_validan_permisos_semestre_y_pertenencia(): void
    {
        $otra = Usuario::findOrFail(self::PROFESORA);

        try {
            $this->servicio()->guardarParcial($otra, self::MATERIA, 1, null, null, 20);
            $this->fail('Debía rechazar a un profesor ajeno');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $parcial = $this->servicio()->guardarParcial($this->profesor(), self::MATERIA, 1, null, null, 20);

        try {
            $this->servicio()->quitarEvaluacion($otra, $parcial->id);
            $this->fail('Debía rechazar quitar una evaluación ajena');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // El parcial es del semestre 1: no se edita desde el 2
        try {
            $this->servicio()->guardarParcial($this->profesor(), self::MATERIA, 2, $parcial->id, null, 30);
            $this->fail('Debía rechazar un parcial de otro semestre');
        } catch (InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->servicio()->guardarExamen($this->profesor(), self::MATERIA, 3, null, 20);
    }

    public function test_cambiar_el_maximo_del_examen_no_baja_de_lo_cargado(): void
    {
        $examen = $this->servicio()->guardarExamen($this->profesor(), self::MATERIA, 1, null, 50);
        $this->servicio()->guardarParcial($this->profesor(), self::MATERIA, 1, null, null, 20);
        $this->cargar(1, [$examen->id => 45]);

        $this->expectException(InvalidArgumentException::class);
        $this->servicio()->guardarExamen($this->profesor(), self::MATERIA, 1, null, 40);
    }

    public function test_la_pantalla_de_carga_informa_que_evaluaciones_tienen_puntos(): void
    {
        $a = $this->servicio()->guardarParcial($this->profesor(), self::MATERIA, 1, null, null, 20);
        $b = $this->servicio()->guardarParcial($this->profesor(), self::MATERIA, 1, null, null, 20);
        $this->cargar(1, [$a->id => 10]);

        $datos = $this->servicio()->pantallaDeCarga($this->profesor(), self::MATERIA, 1);

        $this->assertSame([$a->id], $datos['conPuntos']);
        $this->assertCount(2, $datos['parciales']);
        $this->assertNull($datos['examen']);
    }
}

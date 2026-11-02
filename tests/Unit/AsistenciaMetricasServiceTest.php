<?php

namespace Tests\Unit;

use App\Reglas\PresentesYTardanzas;
use App\Reglas\SoloPresentes;
use App\Services\AsistenciaMetricasService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ValueError;

/**
 * El cálculo de las métricas es puro (no usa la base de datos), así que se prueba sin Laravel.
 * Los datos son las asistencias del alumno 1 del escenario de pruebas; "hoy" es el lunes 12/10/2026.
 */
class AsistenciaMetricasServiceTest extends TestCase
{
    private function servicio(): AsistenciaMetricasService
    {
        return new AsistenciaMetricasService();
    }

    private function hoy(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-12 10:00:00');
    }

    /** Siete registros: 5 presentes, 1 tarde y 1 ausente (una fila por fecha y estado, como en la consulta). */
    private function filasDelAlumno(): array
    {
        return [
            ['fecha' => '2026-09-15', 'estado' => 'presente', 'cantidad' => 1], // martes
            ['fecha' => '2026-10-05', 'estado' => 'presente', 'cantidad' => 1], // lunes
            ['fecha' => '2026-10-06', 'estado' => 'presente', 'cantidad' => 1], // martes
            ['fecha' => '2026-10-07', 'estado' => 'tarde', 'cantidad' => 1],    // miércoles
            ['fecha' => '2026-10-08', 'estado' => 'ausente', 'cantidad' => 1],  // jueves
            ['fecha' => '2026-10-09', 'estado' => 'presente', 'cantidad' => 1], // viernes
            ['fecha' => '2026-10-12', 'estado' => 'presente', 'cantidad' => 1], // lunes
        ];
    }

    private function calcular(array $filas, $regla = null, ?DateTimeImmutable $hoy = null): array
    {
        return $this->servicio()->calcular($filas, $regla ?? new PresentesYTardanzas(), $hoy ?? $this->hoy(), 'regla de prueba');
    }

    public function test_el_minimo_de_asistencia_es_70_y_no_80(): void
    {
        $this->assertSame(70, AsistenciaMetricasService::MINIMO);
        $this->assertSame(70, $this->calcular([])['minimo']);
    }

    public function test_el_anillo_cuenta_cada_estado_y_el_porcentaje_usa_la_regla(): void
    {
        $anillo = $this->calcular($this->filasDelAlumno())['anillo'];

        $this->assertSame(7, $anillo['total']);
        $this->assertSame(5, $anillo['presentes']);
        $this->assertSame(1, $anillo['tardanzas']);
        $this->assertSame(1, $anillo['ausentes']);
        $this->assertSame(0, $anillo['justificados']);
        $this->assertSame(85.71, $anillo['porcentaje']); // 6 de 7: presentes y tardanzas
    }

    public function test_con_solo_presentes_las_tardanzas_no_suman(): void
    {
        $resultado = $this->calcular($this->filasDelAlumno(), new SoloPresentes());

        $this->assertSame(71.43, $resultado['anillo']['porcentaje']); // 5 de 7
        $this->assertSame(60.0, $resultado['semanas'][6]['porcentaje']); // semana del 5/10: 3 de 5
        $this->assertSame(0.0, $resultado['dias'][2]['porcentaje']);     // el miércoles fue tarde
    }

    public function test_los_justificados_cuentan_en_el_total_pero_no_como_asistidos(): void
    {
        $filas = [
            ['fecha' => '2026-10-09', 'estado' => 'justificado', 'cantidad' => 1],
            ['fecha' => '2026-10-12', 'estado' => 'presente', 'cantidad' => 1],
        ];

        $anillo = $this->calcular($filas)['anillo'];

        $this->assertSame(2, $anillo['total']);
        $this->assertSame(1, $anillo['justificados']);
        $this->assertSame(50.0, $anillo['porcentaje']);
    }

    public function test_son_ocho_semanas_de_la_mas_antigua_a_la_actual(): void
    {
        $semanas = $this->calcular($this->filasDelAlumno())['semanas'];

        $this->assertCount(8, $semanas);
        $this->assertSame('2026-08-24', $semanas[0]['inicio']);
        $this->assertSame('24/08', $semanas[0]['etiqueta']);
        $this->assertSame('2026-10-12', $semanas[7]['inicio']);
        $this->assertSame('12/10', $semanas[7]['etiqueta']);
    }

    public function test_cada_semana_calcula_su_porcentaje_y_las_vacias_quedan_en_null(): void
    {
        $semanas = $this->calcular($this->filasDelAlumno())['semanas'];

        $this->assertSame(100.0, $semanas[3]['porcentaje']); // 14/09: presente
        $this->assertSame(80.0, $semanas[6]['porcentaje']);  // 5/10: 4 de 5
        $this->assertSame(5, $semanas[6]['total']);
        $this->assertSame(100.0, $semanas[7]['porcentaje']); // 12/10
        $this->assertNull($semanas[0]['porcentaje']);
        $this->assertNull($semanas[4]['porcentaje']);
        $this->assertSame(0, $semanas[4]['total']);
    }

    public function test_las_barras_van_de_lunes_a_viernes(): void
    {
        $dias = $this->calcular($this->filasDelAlumno())['dias'];

        $this->assertSame(['Lun', 'Mar', 'Mié', 'Jue', 'Vie'], array_column($dias, 'dia'));
        $this->assertSame([100.0, 100.0, 100.0, 0.0, 100.0], array_column($dias, 'porcentaje'));
        $this->assertSame([2, 2, 1, 1, 1], array_column($dias, 'total'));
    }

    public function test_el_fin_de_semana_cuenta_en_el_anillo_pero_no_en_las_barras(): void
    {
        $filas = [...$this->filasDelAlumno(), ['fecha' => '2026-10-10', 'estado' => 'presente', 'cantidad' => 1]]; // sábado

        $resultado = $this->calcular($filas);

        $this->assertSame(8, $resultado['anillo']['total']);
        $this->assertSame(7, array_sum(array_column($resultado['dias'], 'total')));
    }

    public function test_un_registro_anterior_a_las_ocho_semanas_solo_cuenta_en_el_anillo(): void
    {
        $filas = [['fecha' => '2026-08-17', 'estado' => 'presente', 'cantidad' => 3]]; // lunes, hace 8 semanas

        $resultado = $this->calcular($filas);

        $this->assertSame(3, $resultado['anillo']['total']);
        $this->assertSame([null], array_unique(array_column($resultado['semanas'], 'porcentaje')));
    }

    public function test_la_semana_actual_empieza_el_lunes_aunque_hoy_sea_domingo(): void
    {
        $domingo = new DateTimeImmutable('2026-10-18 23:30:00');

        $semanas = $this->calcular([['fecha' => '2026-10-13', 'estado' => 'presente', 'cantidad' => 2]], null, $domingo)['semanas'];

        $this->assertSame('2026-10-12', $semanas[7]['inicio']);
        $this->assertSame(100.0, $semanas[7]['porcentaje']);
    }

    public function test_acepta_fechas_como_texto_con_hora_o_como_objeto(): void
    {
        $filas = [
            ['fecha' => '2026-10-12 00:00:00', 'estado' => 'presente', 'cantidad' => '2'],
            ['fecha' => new DateTimeImmutable('2026-10-12'), 'estado' => 'ausente', 'cantidad' => 2],
        ];

        $semana = $this->calcular($filas)['semanas'][7];

        $this->assertSame(4, $semana['total']);
        $this->assertSame(50.0, $semana['porcentaje']);
    }

    public function test_sin_registros_no_hay_datos_ni_porcentajes(): void
    {
        $resultado = $this->calcular([]);

        $this->assertFalse($resultado['hayDatos']);
        $this->assertSame(0, $resultado['anillo']['total']);
        $this->assertSame(0, $resultado['anillo']['porcentaje']);
        $this->assertSame([null], array_unique(array_column($resultado['semanas'], 'porcentaje')));
        $this->assertSame([null], array_unique(array_column($resultado['dias'], 'porcentaje')));
    }

    public function test_un_estado_invalido_falla_como_en_el_resto_del_sistema(): void
    {
        $this->expectException(ValueError::class);

        $this->calcular([['fecha' => '2026-10-12', 'estado' => 'inventado', 'cantidad' => 1]]);
    }

    public function test_devuelve_la_descripcion_de_la_regla_que_recibe(): void
    {
        $this->assertSame('regla de prueba', $this->calcular([])['regla']);
    }
}

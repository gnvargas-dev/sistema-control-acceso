<?php

namespace Tests\Unit;

use App\Valores\ResultadoSemestral;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ResultadoSemestralTest extends TestCase
{
    /** Dos parciales de 50 (100 puntos) y un examen de 50: parciales aportan 0,6 por punto; examen, 0,8. */
    private function resultado(float $parciales, float $examen): ResultadoSemestral
    {
        return ResultadoSemestral::calcular(
            [[min(50, $parciales), 50], [max(0, $parciales - 50), 50]],
            $examen,
            50
        );
    }

    public static function bordes(): array
    {
        return [
            // parciales (de 100), examen (de 50), total esperado, nota esperada
            'candado del examen: total 69 → 1' => [100, 11.25, 69, 1],
            'mínimo exacto 42 + 28 → 2' => [70, 35, 70, 2],
            'tope del 2 (77)' => [75, 40, 77, 2],
            'inicio del 3 (78)' => [80, 37.5, 78, 3],
            'tope del 3 (85)' => [85, 42.5, 85, 3],
            'inicio del 4 (86)' => [88, 41.5, 86, 4],
            'tope del 4 (93)' => [93, 46.5, 93, 4],
            'inicio del 5 (94)' => [94, 47, 94, 5],
            'máximo (100)' => [100, 50, 100, 5],
        ];
    }

    #[DataProvider('bordes')]
    public function test_conversion_del_total_a_la_escala(float $parciales, float $examen, int $total, int $nota): void
    {
        $resultado = $this->resultado($parciales, $examen);

        $this->assertSame($total, $resultado->total());
        $this->assertSame((float) $nota, $resultado->nota()->valor());
    }

    public function test_el_total_se_redondea_al_entero_mas_cercano_y_la_mitad_sube(): void
    {
        // 45,3 + 32,2 = 77,5 → 78 → nota 3 (no queda un hueco entre 77 y 78)
        $resultado = $this->resultado(75.5, 40.25);

        $this->assertSame(77.5, $resultado->totalExacto());
        $this->assertSame(78, $resultado->total());
        $this->assertSame(3.0, $resultado->nota()->valor());
    }

    public function test_candado_de_parciales_con_porcentaje_exacto(): void
    {
        $this->assertTrue($this->resultado(70, 50)->parcialesSuperan());
        $this->assertSame(4.0, $this->resultado(80, 50)->nota()->valor());

        $debajo = $this->resultado(69.99, 50);
        $this->assertFalse($debajo->parcialesSuperan());
        $this->assertFalse($debajo->candadoSuperado());
        $this->assertSame(1.0, $debajo->nota()->valor());
        $this->assertFalse($debajo->aprueba());
    }

    public function test_candado_del_examen_con_porcentaje_exacto(): void
    {
        $this->assertTrue($this->resultado(100, 35)->examenSupera());

        $debajo = $this->resultado(100, 34.99);
        $this->assertFalse($debajo->examenSupera());
        $this->assertSame(1.0, $debajo->nota()->valor());
    }

    public function test_un_bloque_flojo_no_se_compensa_con_el_otro(): void
    {
        // Total 80 (60 + 20) pero el examen no llega al 70% → 1
        $resultado = $this->resultado(100, 25);

        $this->assertSame(80, $resultado->total());
        $this->assertSame(1.0, $resultado->nota()->valor());
    }

    public function test_un_mal_parcial_se_remonta_con_los_siguientes(): void
    {
        // 10 de 50 y luego 50 y 50: 110 de 150 = 73,33% → supera el candado
        $resultado = ResultadoSemestral::calcular([[10, 50], [50, 50], [50, 50]], 50, 50);

        $this->assertSame(73.33, $resultado->porcentajeParciales());
        $this->assertTrue($resultado->parcialesSuperan());
        $this->assertSame(84, $resultado->total());
        $this->assertSame(3.0, $resultado->nota()->valor());
    }

    public function test_los_parciales_se_suman_en_puntos_y_no_se_promedian_los_porcentajes(): void
    {
        // 10/20 (50%) y 40/50 (80%): el promedio de porcentajes daría 65%, pero 50 de 70 = 71,43% supera el 70%
        $resultado = ResultadoSemestral::calcular([[10, 20], [40, 50]], 50, 50);

        $this->assertSame(50.0, $resultado->puntosParciales());
        $this->assertSame(70, $resultado->maximoParciales());
        $this->assertSame(71.43, $resultado->porcentajeParciales());
        $this->assertTrue($resultado->parcialesSuperan());
    }

    public function test_cinco_parciales_con_puntajes_distintos(): void
    {
        $resultado = ResultadoSemestral::calcular([[15, 20], [25, 30], [35, 40], [45, 50], [20, 20]], 45, 50);

        $this->assertSame(140.0, $resultado->puntosParciales());
        $this->assertSame(160, $resultado->maximoParciales());
        $this->assertSame(87.5, $resultado->porcentajeParciales());
        $this->assertSame(52.5, $resultado->aporteParciales());
        $this->assertSame(36.0, $resultado->aporteExamen());
        $this->assertSame(88.5, $resultado->totalExacto());
        $this->assertSame(89, $resultado->total());
        $this->assertSame(4.0, $resultado->nota()->valor());
    }

    public function test_aportes_y_porcentajes_para_mostrar(): void
    {
        $resultado = $this->resultado(85, 40);

        $this->assertSame(85.0, $resultado->puntosParciales());
        $this->assertSame(85.0, $resultado->porcentajeParciales());
        $this->assertSame(51.0, $resultado->aporteParciales());
        $this->assertSame(40.0, $resultado->puntosExamen());
        $this->assertSame(80.0, $resultado->porcentajeExamen());
        $this->assertSame(32.0, $resultado->aporteExamen());
        $this->assertSame(83.0, $resultado->totalExacto());
        $this->assertSame(83, $resultado->total());
        $this->assertSame(3.0, $resultado->nota()->valor());
    }

    public function test_acepta_puntos_como_texto(): void
    {
        $resultado = ResultadoSemestral::calcular([['45.50', 50], ['40', 50]], '40.25', 50);

        $this->assertSame(85.5, $resultado->puntosParciales());
        $this->assertSame(40.25, $resultado->puntosExamen());
    }

    public function test_rechaza_datos_invalidos(): void
    {
        $invalidos = [
            'sin parciales' => fn () => ResultadoSemestral::calcular([], 30, 50),
            'puntos sobre el máximo' => fn () => ResultadoSemestral::calcular([[51, 50]], 30, 50),
            'puntos negativos' => fn () => ResultadoSemestral::calcular([[-1, 50]], 30, 50),
            'más de 2 decimales' => fn () => ResultadoSemestral::calcular([[10.005, 50]], 30, 50),
            'no numérico' => fn () => ResultadoSemestral::calcular([['abc', 50]], 30, 50),
            'máximo cero' => fn () => ResultadoSemestral::calcular([[0, 0]], 30, 50),
            'examen sobre el máximo' => fn () => ResultadoSemestral::calcular([[10, 50]], 51, 50),
        ];

        foreach ($invalidos as $caso => $llamada) {
            try {
                $llamada();
                $this->fail("Debía rechazar: {$caso}");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_se_serializa_a_json(): void
    {
        $json = $this->resultado(80, 37.5)->jsonSerialize();

        $this->assertSame(78, $json['total']);
        $this->assertSame(3.0, $json['nota']);
        $this->assertTrue($json['candado_superado']);
    }
}

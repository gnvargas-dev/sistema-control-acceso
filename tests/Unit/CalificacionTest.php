<?php

namespace Tests\Unit;

use App\Valores\Calificacion;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CalificacionTest extends TestCase
{
    public function test_acepta_enteros_y_texto_numerico_entero(): void
    {
        $this->assertSame(4.0, Calificacion::de(4)->valor());
        $this->assertSame(4.0, Calificacion::de(4.0)->valor());
        $this->assertSame(3.0, Calificacion::de('3')->valor());
        $this->assertSame(2.0, Calificacion::de(' 2 ')->valor());
    }

    public function test_acepta_los_limites_del_rango(): void
    {
        $this->assertSame(1.0, Calificacion::de(1)->valor());
        $this->assertSame(5.0, Calificacion::de(5)->valor());
    }

    public function test_rechaza_valores_fuera_de_rango(): void
    {
        foreach ([0, 6, 10, -1, 0.99, 5.01] as $valor) {
            try {
                Calificacion::de($valor);
                $this->fail("Debía rechazar {$valor}");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_rechaza_notas_con_decimales(): void
    {
        foreach ([2.5, '3.5', 4.01, '1.99'] as $valor) {
            try {
                Calificacion::de($valor);
                $this->fail("Debía rechazar {$valor}");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_rechaza_texto_no_numerico(): void
    {
        foreach (['abc', '', '4 puntos'] as $texto) {
            try {
                Calificacion::de($texto);
                $this->fail("Debía rechazar '{$texto}'");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_de_devuelve_la_misma_instancia_si_ya_es_calificacion(): void
    {
        $nota = Calificacion::de(4);

        $this->assertSame($nota, Calificacion::de($nota));
    }

    public function test_aprueba_desde_dos(): void
    {
        $this->assertFalse(Calificacion::de(1)->aprueba());
        $this->assertTrue(Calificacion::de(2)->aprueba());
        $this->assertTrue(Calificacion::de(5)->aprueba());
        $this->assertSame(2.0, Calificacion::APROBACION);
    }

    public function test_promedio_de_varias_calificaciones_puede_tener_decimales(): void
    {
        $promedio = Calificacion::promedioDe(Calificacion::de(4), Calificacion::de(5), Calificacion::de(4));

        $this->assertSame(4.33, $promedio->valor());
        $this->assertNull(Calificacion::promedioDe());
        $this->assertFalse(Calificacion::promedioDe(Calificacion::de(1), Calificacion::de(2))->aprueba());
    }

    public function test_igualdad_texto_y_json(): void
    {
        $this->assertTrue(Calificacion::de(3)->igualA(Calificacion::de('3')));
        $this->assertFalse(Calificacion::de(3)->igualA(Calificacion::de(4)));
        $this->assertSame('3.00', Calificacion::de(3)->texto());
        $this->assertSame('3', Calificacion::de(3)->texto(0));
        $this->assertSame('5.00', (string) Calificacion::de(5));
        $this->assertEquals(4, json_decode(json_encode(Calificacion::de(4))));
    }
}

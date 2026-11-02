<?php

namespace Tests\Unit;

use App\Enums\EstadoAsistencia;
use App\Reglas\PresentesTardanzasYJustificados;
use App\Reglas\PresentesYTardanzas;
use App\Reglas\SoloPresentes;
use App\Valores\PorcentajeAsistencia;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PorcentajeAsistenciaTest extends TestCase
{
    /** Cinco registros: 2 presentes, 1 tarde, 1 justificado, 1 ausente. */
    private function registros(): array
    {
        return [
            EstadoAsistencia::Presente,
            'presente',
            EstadoAsistencia::Tarde,
            'justificado',
            EstadoAsistencia::Ausente,
        ];
    }

    public function test_cada_regla_cuenta_distinto_sobre_los_mismos_registros(): void
    {
        $this->assertSame(40.0, PorcentajeAsistencia::deEstados($this->registros(), new SoloPresentes())->redondeado());
        $this->assertSame(60.0, PorcentajeAsistencia::deEstados($this->registros(), new PresentesYTardanzas())->redondeado());
        $this->assertSame(80.0, PorcentajeAsistencia::deEstados($this->registros(), new PresentesTardanzasYJustificados())->redondeado());
    }

    public function test_el_redondeo_replica_el_del_sistema(): void
    {
        $tercio = PorcentajeAsistencia::deConteos(1, 3);

        $this->assertSame(33.3, $tercio->redondeado(1));
        $this->assertSame(33.33, $tercio->redondeado(2));
        $this->assertSame(round((2 / 3) * 100, 1), PorcentajeAsistencia::deConteos(2, 3)->redondeado(1));
    }

    public function test_sin_registros_devuelve_null_o_cero_entero(): void
    {
        $vacio = PorcentajeAsistencia::sinRegistros();

        $this->assertTrue($vacio->esVacio());
        $this->assertNull($vacio->redondeado());
        $this->assertSame(0, $vacio->redondeadoOCero());
        $this->assertNull($vacio->jsonSerialize());
    }

    public function test_deEstados_sin_elementos_equivale_a_sin_registros(): void
    {
        $this->assertTrue(PorcentajeAsistencia::deEstados([], new SoloPresentes())->esVacio());
    }

    public function test_deConteos_valida_el_rango(): void
    {
        foreach ([[5, 3], [-1, 3], [1, -3]] as [$contadas, $total]) {
            try {
                PorcentajeAsistencia::deConteos($contadas, $total);
                $this->fail("Debía rechazar {$contadas} de {$total}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('inválido', $e->getMessage());
            }
        }
    }

    public function test_acceso_a_conteos_y_json(): void
    {
        $porcentaje = PorcentajeAsistencia::deConteos(3, 4);

        $this->assertSame(3, $porcentaje->contadas());
        $this->assertSame(4, $porcentaje->total());
        $this->assertSame(75.0, $porcentaje->jsonSerialize());
        $this->assertSame(100.0, PorcentajeAsistencia::deConteos(4, 4)->redondeado());
        $this->assertSame(0.0, PorcentajeAsistencia::deConteos(0, 4)->redondeado());
    }
}

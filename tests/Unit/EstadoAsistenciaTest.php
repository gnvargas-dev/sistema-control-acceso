<?php

namespace Tests\Unit;

use App\Enums\EstadoAsistencia;
use PHPUnit\Framework\TestCase;
use ValueError;

class EstadoAsistenciaTest extends TestCase
{
    public function test_tiene_los_cuatro_estados_de_la_base(): void
    {
        $this->assertSame(
            ['presente', 'ausente', 'tarde', 'justificado'],
            array_map(fn (EstadoAsistencia $e) => $e->value, EstadoAsistencia::cases())
        );
    }

    public function test_desde_acepta_enum_o_texto(): void
    {
        $this->assertSame(EstadoAsistencia::Tarde, EstadoAsistencia::desde('tarde'));
        $this->assertSame(EstadoAsistencia::Tarde, EstadoAsistencia::desde(' TARDE '));
        $this->assertSame(EstadoAsistencia::Ausente, EstadoAsistencia::desde(EstadoAsistencia::Ausente));
    }

    public function test_desde_rechaza_un_estado_inexistente(): void
    {
        $this->expectException(ValueError::class);

        EstadoAsistencia::desde('faltó');
    }

    public function test_etiquetas(): void
    {
        $this->assertSame('Tardanza', EstadoAsistencia::Tarde->etiqueta());
        $this->assertSame('Justificado', EstadoAsistencia::Justificado->etiqueta());
    }
}

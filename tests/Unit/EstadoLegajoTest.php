<?php

namespace Tests\Unit;

use App\Enums\EstadoLegajo;
use PHPUnit\Framework\TestCase;

class EstadoLegajoTest extends TestCase
{
    public function test_sin_documentos_incompleto_y_completo(): void
    {
        $this->assertSame(EstadoLegajo::SinDocumentos, EstadoLegajo::desdeConteo(0, 10));
        $this->assertSame(EstadoLegajo::Incompleto, EstadoLegajo::desdeConteo(3, 10));
        $this->assertSame(EstadoLegajo::Completo, EstadoLegajo::desdeConteo(10, 10));
    }

    public function test_entregar_mas_de_lo_pedido_cuenta_como_completo(): void
    {
        $this->assertSame(EstadoLegajo::Completo, EstadoLegajo::desdeConteo(12, 10));
    }

    public function test_los_valores_son_los_que_usan_las_vistas_y_el_filtro(): void
    {
        $this->assertSame('sin_documentos', EstadoLegajo::SinDocumentos->value);
        $this->assertSame('incompleto', EstadoLegajo::Incompleto->value);
        $this->assertSame('completo', EstadoLegajo::Completo->value);
    }
}

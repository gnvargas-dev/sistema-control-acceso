<?php

namespace Tests\Feature;

use App\Enums\EstadoAsistencia;
use App\Models\Asistencia;
use App\Models\Nota;
use App\Valores\Calificacion;
use InvalidArgumentException;
use Tests\TestCase;
use ValueError;

/**
 * No usa la base de datos: comprueba el encapsulamiento de Nota y Asistencia.
 */
class ModelosConObjetosDeValorTest extends TestCase
{
    public function test_nota_acepta_distintos_tipos_al_asignar_la_calificacion(): void
    {
        $nota = new Nota();

        $nota->asignarCalificacion('4');
        $this->assertSame(4.0, $nota->calificacion()->valor());

        $nota->asignarCalificacion(5);
        $this->assertSame(5.0, $nota->calificacion()->valor());

        $nota->asignarCalificacion(Calificacion::de(3));
        $this->assertSame('3.00', $nota->valor);
    }

    public function test_nota_rechaza_calificaciones_invalidas(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Nota())->asignarCalificacion(6);
    }

    public function test_nota_rechaza_calificaciones_con_decimales(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Nota())->asignarCalificacion(2.5);
    }

    public function test_nota_sin_valor_no_tiene_calificacion(): void
    {
        $this->assertNull((new Nota())->calificacion());
    }

    public function test_asistencia_marca_con_enum_o_texto(): void
    {
        $asistencia = new Asistencia();

        $asistencia->marcar('TARDE');
        $this->assertSame('tarde', $asistencia->estado);
        $this->assertSame(EstadoAsistencia::Tarde, $asistencia->estadoAsistencia());

        $asistencia->marcar(EstadoAsistencia::Justificado);
        $this->assertSame('justificado', $asistencia->estado);
    }

    public function test_asistencia_rechaza_un_estado_inexistente(): void
    {
        $this->expectException(ValueError::class);

        (new Asistencia())->marcar('faltó');
    }

    public function test_asistencia_sin_estado_no_tiene_enum(): void
    {
        $this->assertNull((new Asistencia())->estadoAsistencia());
    }
}

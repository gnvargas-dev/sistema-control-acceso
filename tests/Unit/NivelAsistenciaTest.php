<?php

namespace Tests\Unit;

use App\Enums\NivelAsistencia;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NivelAsistenciaTest extends TestCase
{
    /** @return array<string, array{float, NivelAsistencia}> */
    public static function limites(): array
    {
        return [
            '100' => [100.0, NivelAsistencia::Excelente],
            '90 justo' => [90.0, NivelAsistencia::Excelente],
            '89.99' => [89.99, NivelAsistencia::Bueno],
            '79 justo' => [79.0, NivelAsistencia::Bueno],
            '78.99' => [78.99, NivelAsistencia::Regular],
            '70 justo' => [70.0, NivelAsistencia::Regular],
            '69.99' => [69.99, NivelAsistencia::Malo],
            '0' => [0.0, NivelAsistencia::Malo],
        ];
    }

    #[DataProvider('limites')]
    public function test_clasifica_segun_el_porcentaje(float $porcentaje, NivelAsistencia $esperado): void
    {
        $this->assertSame($esperado, NivelAsistencia::desdePorcentaje($porcentaje));
    }

    public function test_el_arreglo_tiene_el_formato_de_las_vistas(): void
    {
        $this->assertSame(
            ['label' => 'Excelente', 'clase' => 'excelente'],
            NivelAsistencia::Excelente->comoArreglo()
        );
        $this->assertSame(['label' => 'Malo', 'clase' => 'malo'], NivelAsistencia::Malo->comoArreglo());
    }
}

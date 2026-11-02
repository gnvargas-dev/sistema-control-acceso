<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Alumno;
use RuntimeException;
use Tests\TestCase;

/** No usa la base de datos: comprueba la elección del número de legajo. */
final class LegajoLibreTest extends TestCase
{
    public function test_elige_el_unico_numero_libre(): void
    {
        $usados = array_values(array_diff(range(1, 9999), [7]));

        $this->assertSame('2026-0007', Alumno::legajoLibre(2026, $usados));
    }

    public function test_nunca_devuelve_un_numero_usado(): void
    {
        $usados = range(1, 9990);

        for ($i = 0; $i < 50; $i++) {
            $numero = (int) substr(Alumno::legajoLibre(2026, $usados), 5);

            $this->assertGreaterThan(9990, $numero);
            $this->assertLessThanOrEqual(9999, $numero);
        }
    }

    public function test_formato_anio_guion_cuatro_digitos(): void
    {
        $this->assertMatchesRegularExpression('/^2026-\d{4}$/', Alumno::legajoLibre(2026, []));
    }

    public function test_sin_numeros_libres_falla_en_lugar_de_quedarse_buscando(): void
    {
        $this->expectException(RuntimeException::class);

        Alumno::legajoLibre(2026, range(1, 9999));
    }
}

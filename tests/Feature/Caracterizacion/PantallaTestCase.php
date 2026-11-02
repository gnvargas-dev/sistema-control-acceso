<?php

namespace Tests\Feature\Caracterizacion;

use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\RequiereMysql;
use Tests\Support\EscenarioEscolar;
use Tests\TestCase;

/**
 * Base de las pruebas de caracterización: fija el escenario, la fecha y el disco,
 * y compara lo que muestra cada pantalla con una captura guardada en
 * tests/fixtures/pantallas. Las capturas salen del comportamiento ANTERIOR al
 * refactor; si cambian, el refactor alteró lo que ve el usuario.
 *
 * Para regenerarlas (solo si el cambio de pantalla es intencional):
 *   ACTUALIZAR_SNAPSHOTS=1 php artisan test --configuration=phpunit.mysql.xml --filter=Caracterizacion
 */
abstract class PantallaTestCase extends TestCase
{
    use RequiereMysql;

    public const ADMIN = 1;
    public const PROFESOR = 2;
    public const PROFESORA = 3;
    public const ALUMNO = 4;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-12 10:00:00'));
        Storage::fake('local');
        $this->withoutVite();

        EscenarioEscolar::crear();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    protected function comoUsuario(int $id): static
    {
        return $this->actingAs(Usuario::findOrFail($id));
    }

    /** Compara la respuesta con la captura guardada (la crea si se pide actualizar). */
    protected function verificarPantalla(string $nombre, TestResponse $respuesta, int $estado = 200): void
    {
        $respuesta->assertStatus($estado);

        $this->compararConCaptura($nombre . '.html', $this->normalizarHtml($respuesta->getContent()));
    }

    protected function compararConCaptura(string $archivo, string $actual): void
    {
        $ruta = base_path('tests/fixtures/pantallas/' . $archivo);

        if (! is_file($ruta)) {
            if (! env('ACTUALIZAR_SNAPSHOTS')) {
                $this->fail("Falta la captura {$archivo}. Generarla con ACTUALIZAR_SNAPSHOTS=1.");
            }

            @mkdir(dirname($ruta), 0777, true);
            file_put_contents($ruta, $actual);
            $this->addToAssertionCount(1);

            return;
        }

        if (env('ACTUALIZAR_SNAPSHOTS')) {
            file_put_contents($ruta, $actual);
        }

        $esperado = file_get_contents($ruta);

        if ($esperado !== $actual) {
            $this->fail($this->describirDiferencia($archivo, $esperado, $actual));
        }

        $this->addToAssertionCount(1);
    }

    private function normalizarHtml(string $html): string
    {
        $html = str_replace("\r\n", "\n", $html);
        $html = str_replace(rtrim((string) config('app.url'), '/'), 'http://localhost', $html);
        $html = preg_replace('/(name="_token" value=")[^"]*/', '$1TOKEN', $html);
        $html = preg_replace('/(<meta name="csrf-token" content=")[^"]*/', '$1TOKEN', $html);
        $html = preg_replace('/(id="legajo-csrf-token" value=")[^"]*/', '$1TOKEN', $html);
        $html = preg_replace('/(tema\.css\?v=)\d+/', '${1}VERSION', $html);
        $html = preg_replace('/[ \t]+$/m', '', $html);

        return rtrim($html) . "\n";
    }

    private function describirDiferencia(string $archivo, string $esperado, string $actual): string
    {
        $a = explode("\n", $esperado);
        $b = explode("\n", $actual);

        foreach ($b as $i => $linea) {
            if (($a[$i] ?? null) !== $linea) {
                return "La pantalla {$archivo} cambió en la línea " . ($i + 1)
                    . ".\n  Esperado: " . trim($a[$i] ?? '(no hay línea)')
                    . "\n  Actual:   " . trim($linea);
            }
        }

        return "La pantalla {$archivo} cambió: ahora tiene menos líneas (" . count($b) . ' contra ' . count($a) . ').';
    }
}

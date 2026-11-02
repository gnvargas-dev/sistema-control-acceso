<?php

namespace Tests\Feature;

use Illuminate\Routing\Route as RutaLaravel;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Compara las rutas actuales con la línea base de la Etapa 0.
 * Solo mira método, URI, nombre y middleware; la acción (controlador) puede cambiar.
 */
class LineaBaseRutasTest extends TestCase
{
    /** Rutas que agrega el framework y no forman parte de la línea base. */
    private const RUTAS_DEL_FRAMEWORK = ['up', 'sanctum/csrf-cookie', 'storage/{path}'];

    public function test_las_rutas_coinciden_con_la_linea_base(): void
    {
        $esperadas = json_decode(
            file_get_contents(base_path('tests/fixtures/baseline_rutas.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $actuales = collect(Route::getRoutes()->getRoutes())
            ->reject(fn (RutaLaravel $ruta) => in_array($ruta->uri(), self::RUTAS_DEL_FRAMEWORK, true))
            ->map(fn (RutaLaravel $ruta) => [
                'metodo' => implode('|', $ruta->methods()),
                'uri' => $ruta->uri(),
                'nombre' => (string) $ruta->getName(),
                'middleware' => array_values(array_filter(
                    $ruta->gatherMiddleware(),
                    fn ($middleware) => is_string($middleware)
                )),
            ])
            ->all();

        $this->assertSame(
            $this->ordenar($esperadas),
            $this->ordenar($actuales),
            'Las rutas cambiaron respecto de la línea base (método, URI, nombre o middleware).'
        );
    }

    /** @param  array<int, array<string, mixed>>  $rutas */
    private function ordenar(array $rutas): array
    {
        usort($rutas, fn ($a, $b) => [$a['uri'], $a['metodo']] <=> [$b['uri'], $b['metodo']]);

        return array_values($rutas);
    }
}

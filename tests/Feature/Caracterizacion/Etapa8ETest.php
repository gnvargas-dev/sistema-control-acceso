<?php

namespace Tests\Feature\Caracterizacion;

use App\Services\AsistenciaMetricasService;
use App\Valores\Calificacion;
use Illuminate\Testing\TestResponse;

/**
 * Etapa 8E: Cargar asistencias con el estilo unificado se-*, limpieza de estilos
 * incrustados en Cargar notas y Panel de control, y textos alineados con la regla real:
 * escala MEC 1 a 5 (aprueba con 2) y asistencia mínima del 70 %.
 */
class Etapa8ETest extends PantallaTestCase
{
    private function contenido(TestResponse $respuesta): string
    {
        $html = $respuesta->getContent();
        $inicio = strpos($html, '<main class="se-contenido">');
        $fin = strpos($html, '</main>', $inicio);

        $this->assertNotFalse($inicio);
        $this->assertNotFalse($fin);

        return substr($html, $inicio, $fin - $inicio);
    }

    private function asistencias(array $parametros = []): TestResponse
    {
        return $this->comoUsuario(self::PROFESOR)->get(route('profesor.cargar-asistencias', $parametros))->assertOk();
    }

    public function test_el_minimo_de_asistencia_y_la_nota_de_aprobacion_son_los_del_sistema(): void
    {
        $this->assertSame(70, AsistenciaMetricasService::MINIMO);
        $this->assertSame(2.0, (float) Calificacion::APROBACION);
    }

    public function test_cargar_asistencias_no_tiene_estilos_incrustados(): void
    {
        $respuesta = $this->asistencias(['materia_id' => 2, 'fecha' => '2026-10-06']);

        $this->assertStringNotContainsString('<style', $respuesta->getContent());
        $this->assertStringNotContainsString('style="', $this->contenido($respuesta));
        $this->assertStringNotContainsString('style.display', $respuesta->getContent());
    }

    public function test_cargar_asistencias_conserva_controles_nombres_y_rutas(): void
    {
        $respuesta = $this->asistencias(['materia_id' => 2, 'fecha' => '2026-10-06']);
        $contenido = $this->contenido($respuesta);

        $this->assertStringContainsString('method="GET" action="' . e(route('profesor.cargar-asistencias')) . '"', $contenido);
        $this->assertStringContainsString('id="asis-form" method="POST" action="' . e(route('profesor.guardar-asistencias')) . '"', $contenido);
        $this->assertStringContainsString('name="_token"', $contenido);
        foreach (['asis-materia', 'asis-fecha', 'asis-error', 'asis-contador-presente', 'asis-contador-ausente', 'asis-contador-tarde', 'asis-porcentaje'] as $id) {
            $this->assertStringContainsString('id="' . $id . '"', $contenido, $id);
        }
        foreach (['materia_id', 'fecha'] as $campo) {
            $this->assertStringContainsString('name="' . $campo . '"', $contenido, $campo);
        }
        $this->assertMatchesRegularExpression('/name="estados\[\d+\]"/', $contenido);
        $this->assertMatchesRegularExpression('/name="observaciones\[\d+\]"/', $contenido);
        foreach (['presente', 'ausente', 'tarde'] as $estado) {
            $this->assertStringContainsString('data-estado="' . $estado . '"', $contenido, $estado);
        }
        $this->assertMatchesRegularExpression('/id="asis-error"[^>]*\shidden/', $contenido);
        $this->assertMatchesRegularExpression('/id="asis-obs-fila-\d+" hidden/', $contenido);
    }

    public function test_guardar_asistencias_sigue_funcionando_con_el_formulario_nuevo(): void
    {
        $respuesta = $this->asistencias(['materia_id' => 2, 'fecha' => '2026-10-06']);
        preg_match_all('/name="estados\[(\d+)\]"/', $respuesta->getContent(), $coincidencias);
        $this->assertNotEmpty($coincidencias[1]);

        $estados = [];
        foreach ($coincidencias[1] as $alumnoId) {
            $estados[$alumnoId] = 'presente';
        }

        $this->comoUsuario(self::PROFESOR)
            ->post(route('profesor.guardar-asistencias'), ['materia_id' => 2, 'fecha' => '2026-10-06', 'estados' => $estados])
            ->assertRedirect()
            ->assertSessionHas('success', 'Asistencias guardadas correctamente.');
    }

    public function test_cargar_notas_y_panel_de_control_no_tienen_estilos_incrustados(): void
    {
        $notas = $this->comoUsuario(self::PROFESOR)->get(route('profesor.cargar-notas'))->assertOk();
        $this->assertStringNotContainsString('<style', $notas->getContent());
        $this->assertStringNotContainsString('style="', $this->contenido($notas));

        $inicio = $this->comoUsuario(self::ADMIN)->get(route('admin.inicio'))->assertOk();
        $this->assertStringNotContainsString('<style', $inicio->getContent());
        $this->assertStringNotContainsString('style="', $this->contenido($inicio));
    }

    public function test_los_textos_usan_el_minimo_de_70_y_la_escala_del_mec(): void
    {
        $paginas = [
            $this->comoUsuario(self::ADMIN)->get(route('admin.inicio')),
            $this->comoUsuario(self::ADMIN)->get(route('admin.reportes')),
            $this->comoUsuario(self::ALUMNO)->get(route('alumno.inicio')),
            $this->comoUsuario(self::ALUMNO)->get(route('alumno.notas')),
        ];

        foreach ($paginas as $respuesta) {
            $respuesta->assertOk();
            $html = $respuesta->getContent();
            foreach (['mínimo exigido: 80%', 'Asistencia mínima: 80%', 'mínimo esperado es 80%', 'Mínimo esperado: 80%', 'asistencia &lt; 80%', 'Promedio &lt; 6.0'] as $viejo) {
                $this->assertStringNotContainsString($viejo, $html, $viejo);
            }
        }

        $this->assertStringContainsString('mínimo exigido: 70%', $paginas[0]->getContent());
        $this->assertStringContainsString('Promedio &lt; 2 o asistencia &lt; 70%', $paginas[1]->getContent());
        $this->assertStringContainsString('Asistencia mínima: 70%', $paginas[3]->getContent());
    }
}

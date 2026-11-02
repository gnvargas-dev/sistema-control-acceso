<?php

namespace Tests\Feature\Caracterizacion;

use Illuminate\Testing\TestResponse;

/**
 * Etapa 8D: Legajos, Boletines y Reportes usan el estilo unificado se-*, sin estilos
 * incrustados, con <dialog> nativo y con los gráficos de Reportes dibujados en SVG
 * (antes eran <canvas> de Chart.js, una librería que nunca se cargaba). Los atributos
 * de control y las rutas siguen siendo los de siempre; los PDF no se tocan.
 */
class AdminLegajosBoletinesReportesTest extends PantallaTestCase
{
    private function pantalla(string $ruta, array $parametros = []): TestResponse
    {
        return $this->comoUsuario(self::ADMIN)->get(route($ruta, $parametros))->assertOk();
    }

    /** Solo lo que dibuja la vista (el menú lateral del layout lleva su propio estilo de animación). */
    private function contenido(TestResponse $respuesta): string
    {
        $html = $respuesta->getContent();
        $inicio = strpos($html, '<main class="se-contenido">');
        $fin = strpos($html, '</main>', $inicio);

        $this->assertNotFalse($inicio);
        $this->assertNotFalse($fin);

        return substr($html, $inicio, $fin - $inicio);
    }

    public function test_las_tres_pantallas_no_tienen_estilos_incrustados_ni_modales_caseros(): void
    {
        foreach (['admin.legajos', 'admin.boletines', 'admin.reportes'] as $ruta) {
            $respuesta = $this->pantalla($ruta);

            $this->assertStringNotContainsString('<style', $respuesta->getContent(), $ruta);
            $this->assertStringNotContainsString('style="', $this->contenido($respuesta), $ruta);
            $this->assertStringNotContainsString("classList.add('activo')", $respuesta->getContent(), $ruta);
            $this->assertStringNotContainsString('<canvas', $respuesta->getContent(), $ruta);
            $this->assertStringNotContainsString('new Chart(', $respuesta->getContent(), $ruta);
        }
    }

    public function test_legajos_conserva_sus_controles_y_usa_un_dialog_nativo(): void
    {
        $respuesta = $this->pantalla('admin.legajos');
        $contenido = $this->contenido($respuesta);

        $this->assertStringContainsString('<dialog id="legajo-modal-fondo" class="se-modal se-modal--form"', $contenido);
        $this->assertStringContainsString('method="GET" action="' . e(route('admin.legajos')) . '"', $contenido);
        foreach (['q', 'curso_id', 'estado'] as $campo) {
            $this->assertStringContainsString('name="' . $campo . '"', $contenido, $campo);
        }
        foreach (['legajo-csrf-token', 'legajo-alumno-select', 'legajo-selector-alumno', 'legajo-info-alumno', 'legajo-info-nombre', 'legajo-info-progreso', 'legajo-contenido', 'legajo-guardar-btn', 'legajo-modal-mensaje'] as $id) {
            $this->assertStringContainsString('id="' . $id . '"', $contenido, $id);
        }
        $this->assertStringContainsString(route('admin.legajos.documentos', ['alumno' => 0]), $respuesta->getContent());
        $this->assertStringContainsString("method: 'PUT'", $respuesta->getContent());
        $this->assertStringContainsString("'X-CSRF-TOKEN'", $respuesta->getContent());
    }

    public function test_legajos_guarda_con_el_mismo_spinner_que_los_formularios(): void
    {
        $respuesta = $this->pantalla('admin.legajos');

        $this->assertStringContainsString("window.seSpinner.mostrar('Guardando…')", $respuesta->getContent());
        $this->assertStringContainsString('window.seSpinner.ocultar()', $respuesta->getContent());

        $js = file_get_contents(public_path('js/tema.js'));
        $this->assertStringContainsString('window.seSpinner = { mostrar: mostrarSpinner, ocultar: ocultarSpinner };', $js);
        $this->assertStringContainsString('TIEMPO_SPINNER = 4000', $js);
    }

    public function test_legajos_muestra_el_estado_de_cada_legajo(): void
    {
        $contenido = $this->contenido($this->pantalla('admin.legajos'));

        $this->assertMatchesRegularExpression('/se-badge--(aprobado|riesgo|reprobado)/', $contenido);
        $this->assertStringContainsString('documentos', $contenido);
    }

    public function test_boletines_conserva_el_formulario_de_generacion_en_un_dialog_nativo(): void
    {
        $contenido = $this->contenido($this->pantalla('admin.boletines'));

        $this->assertStringContainsString('<dialog id="boletines-modal-fondo" class="se-modal se-modal--form"', $contenido);
        $this->assertStringContainsString('method="POST" action="' . e(route('admin.boletines.pdf')) . '"', $contenido);
        foreach (['periodo', 'alumno_id', 'curso_id'] as $campo) {
            $this->assertStringContainsString('name="' . $campo . '"', $contenido, $campo);
        }
        foreach (['boletin-periodo', 'boletin-alumno', 'boletin-curso', 'filtro-curso', 'filtro-periodo', 'filtro-buscar'] as $id) {
            $this->assertStringContainsString('id="' . $id . '"', $contenido, $id);
        }
        $this->assertStringContainsString('name="_token"', $contenido);
    }

    public function test_boletines_conserva_los_enlaces_del_historial(): void
    {
        $contenido = $this->contenido($this->pantalla('admin.boletines'));

        $this->assertStringContainsString('href="' . e(route('admin.boletines.descargar', 1)) . '"', $contenido);
        $this->assertStringContainsString('href="' . e(route('admin.boletines.ver', 1)) . '"', $contenido);
        $this->assertStringContainsString('target="_blank"', $contenido);
        $this->assertStringContainsString('boletin-1.pdf', $contenido);
    }

    public function test_reportes_dibuja_los_graficos_en_svg_nativo(): void
    {
        $contenido = $this->contenido($this->pantalla('admin.reportes'));

        $this->assertSame(2, substr_count($contenido, 'class="se-ga-svg se-ga-anillo"'));
        $this->assertStringContainsString('se-rg-barras', $contenido);
        $this->assertStringContainsString('se-rg-barra"', $contenido);
        $this->assertStringContainsString('<title id="se-rg-asis-t">Asistencia general</title>', $contenido);
        $this->assertStringContainsString('<title id="se-rg-dist-t">Distribución de promedios</title>', $contenido);
        $this->assertStringContainsString('<title id="se-rg-cur-t">Promedio por curso</title>', $contenido);
    }

    public function test_reportes_conserva_filtros_y_exportaciones(): void
    {
        $respuesta = $this->pantalla('admin.reportes', ['desde' => '2026-09-01', 'hasta' => '2026-10-12', 'curso_id' => 1]);
        $contenido = $this->contenido($respuesta);

        $this->assertStringContainsString('method="GET"', $contenido);
        $this->assertStringContainsString('action="' . e(route('admin.reportes')) . '"', $contenido);
        foreach (['desde', 'hasta', 'curso_id'] as $campo) {
            $this->assertStringContainsString('name="' . $campo . '"', $contenido, $campo);
        }
        foreach ([1, 2, 3, 4] as $tipo) {
            $this->assertStringContainsString('tipo=' . $tipo, $contenido, "tipo $tipo");
        }
        $this->assertSame(4, substr_count($contenido, 'class="se-ad-menu-item"'));
        $this->assertStringContainsString('id="exportToggle"', $contenido);
        $this->assertStringContainsString('id="exportMenu"', $contenido);
        $this->assertStringContainsString('curso_id=1', $contenido);
    }

    public function test_reportes_no_cambia_los_datos_que_recibe_la_vista(): void
    {
        $this->pantalla('admin.reportes')->assertViewHasAll([
            'filtros', 'cursosFiltro', 'resumen', 'distribucion', 'promedioPorCursoChart', 'resumenPorCurso',
        ]);
    }
}

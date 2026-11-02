<?php

namespace Tests\Feature\Caracterizacion;

/**
 * Fija lo que muestran hoy las pantallas del administrador.
 */
class AdminPantallasTest extends PantallaTestCase
{
    private function pantalla(string $ruta, array $parametros = [])
    {
        return $this->comoUsuario(self::ADMIN)->get(route($ruta, $parametros));
    }

    public function test_inicio(): void
    {
        $this->verificarPantalla('admin_inicio', $this->pantalla('admin.inicio'));
    }

    public function test_usuarios(): void
    {
        $this->verificarPantalla('admin_usuarios', $this->pantalla('admin.usuarios'));
    }

    public function test_boletines(): void
    {
        $this->verificarPantalla('admin_boletines', $this->pantalla('admin.boletines'));
    }

    public function test_boletines_filtrados(): void
    {
        $this->verificarPantalla(
            'admin_boletines_filtrados',
            $this->pantalla('admin.boletines', ['curso_id' => 1, 'periodo' => '1er Bimestre', 'q' => 'Emilce'])
        );
    }

    public function test_reportes_del_mes(): void
    {
        $this->verificarPantalla('admin_reportes', $this->pantalla('admin.reportes'));
    }

    public function test_reportes_con_rango_de_fechas_y_curso(): void
    {
        $this->verificarPantalla(
            'admin_reportes_curso',
            $this->pantalla('admin.reportes', ['desde' => '2026-09-01', 'hasta' => '2026-10-12', 'curso_id' => 1])
        );
    }

    public function test_materias(): void
    {
        $this->verificarPantalla('admin_materias', $this->pantalla('admin.materias'));
    }

    public function test_legajos(): void
    {
        $this->verificarPantalla('admin_legajos', $this->pantalla('admin.legajos'));
    }

    public function test_legajos_filtrados(): void
    {
        $this->verificarPantalla('admin_legajos_filtrados', $this->pantalla('admin.legajos', ['q' => 'Emilce', 'curso_id' => 1]));
    }

    public function test_documentos_del_legajo_de_un_alumno(): void
    {
        $respuesta = $this->pantalla('admin.legajos.documentos', ['alumno' => 1])->assertOk();

        $this->compararConCaptura(
            'admin_legajo_documentos.json',
            json_encode($respuesta->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
        );
    }

    public function test_alumnos(): void
    {
        $this->verificarPantalla('admin_alumnos', $this->pantalla('admin.alumnos'));
    }

    public function test_alumnos_buscados(): void
    {
        $this->verificarPantalla('admin_alumnos_busqueda', $this->pantalla('admin.alumnos', ['q' => 'Ricardo']));
    }

    public function test_el_profesor_no_entra_a_las_pantallas_del_administrador(): void
    {
        foreach (['admin.inicio', 'admin.usuarios', 'admin.boletines', 'admin.reportes', 'admin.materias', 'admin.legajos', 'admin.alumnos'] as $ruta) {
            $this->comoUsuario(self::PROFESOR)->get(route($ruta))->assertForbidden();
        }
    }
}

<?php

namespace Tests\Feature\Caracterizacion;

use Illuminate\Testing\TestResponse;

/**
 * Etapa 8C: Usuarios, Alumnos y Materias usan el estilo unificado se-*, sin estilos
 * incrustados y con el <dialog> nativo en lugar de los modales caseros. Los atributos
 * de control (action, method, name, id, @csrf, @method) siguen siendo los de siempre.
 */
class AdminDialogosTest extends PantallaTestCase
{
    private function pantalla(string $ruta): TestResponse
    {
        return $this->comoUsuario(self::ADMIN)->get(route($ruta))->assertOk();
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
        foreach (['admin.usuarios', 'admin.alumnos', 'admin.materias'] as $ruta) {
            $respuesta = $this->pantalla($ruta);
            $contenido = $this->contenido($respuesta);

            $this->assertStringNotContainsString('<style', $respuesta->getContent(), $ruta);
            $this->assertStringNotContainsString('style="', $contenido, $ruta);
            $this->assertStringNotContainsString('confirmar-modal', $respuesta->getContent(), $ruta);
            $this->assertStringNotContainsString('confirmar-eliminar-fondo', $respuesta->getContent(), $ruta);
            $this->assertStringNotContainsString('classList.add(\'activo\')', $respuesta->getContent(), $ruta);
        }
    }

    public function test_usuarios_elimina_con_un_formulario_que_confirma_en_el_modal_nativo(): void
    {
        $contenido = $this->contenido($this->pantalla('admin.usuarios'));

        $this->assertStringContainsString('action="' . e(route('admin.usuarios.eliminar', 2)) . '"', $contenido);
        $this->assertStringContainsString('name="_method" value="DELETE"', $contenido);
        $this->assertStringContainsString('data-se-confirmar="', $contenido);
        $this->assertStringContainsString('data-se-confirmar-titulo="Eliminar usuario"', $contenido);
        $this->assertStringContainsString('Esta acción no se puede deshacer.', $contenido);
    }

    public function test_usuarios_no_ofrece_eliminar_la_propia_cuenta(): void
    {
        $contenido = $this->contenido($this->pantalla('admin.usuarios'));

        $this->assertStringNotContainsString('action="' . e(route('admin.usuarios.eliminar', self::ADMIN)) . '"', $contenido);
    }

    public function test_usuarios_conserva_el_formulario_de_alta_y_sus_campos(): void
    {
        $contenido = $this->contenido($this->pantalla('admin.usuarios'));

        $this->assertStringContainsString('action="' . e(route('admin.usuarios.guardar')) . '"', $contenido);
        $this->assertStringContainsString('id="nuevo-usuario"', $contenido);
        $this->assertMatchesRegularExpression('/id="nuevo-usuario"[^>]*\shidden/', $contenido); // sin errores: plegado
        foreach (['nombre', 'email', 'cedula', 'rol', 'curso_id', 'activo', 'password', 'password_confirmation'] as $campo) {
            $this->assertStringContainsString('name="' . $campo . '"', $contenido, $campo);
        }
        foreach (['rol', 'campo-curso', 'curso-placeholder', 'campo-materias-profesor', 'materias-profesor-filas', 'password', 'password_confirmation'] as $id) {
            $this->assertStringContainsString('id="' . $id . '"', $contenido, $id);
        }
    }

    public function test_usuarios_abre_el_formulario_cuando_hay_errores_de_validacion(): void
    {
        // Un alta inválida vuelve a la lista con los errores y lo escrito (sigue el redirect como un navegador).
        $contenido = $this->contenido(
            $this->comoUsuario(self::ADMIN)->followingRedirects()
                ->from(route('admin.usuarios'))
                ->post(route('admin.usuarios.guardar'), [])
                ->assertOk()
        );

        $this->assertDoesNotMatchRegularExpression('/id="nuevo-usuario"[^>]*\shidden/', $contenido);
        $this->assertStringContainsString('se-aviso--error', $contenido);
    }

    public function test_alumnos_usa_dialogs_nativos_y_conserva_sus_controles(): void
    {
        $contenido = $this->contenido($this->pantalla('admin.alumnos'));

        $this->assertStringContainsString('<dialog id="alumnos-modal-ver" class="se-modal se-modal--form"', $contenido);
        $this->assertStringContainsString('<dialog id="alumnos-modal-editar" class="se-modal se-modal--form"', $contenido);
        $this->assertStringContainsString('id="form-editar-alumno"', $contenido);
        $this->assertStringContainsString('name="_method" value="PUT"', $contenido);
        foreach (['curso_id', 'legajo', 'fecha_nacimiento', 'telefono', 'activo'] as $campo) {
            $this->assertStringContainsString('name="' . $campo . '"', $contenido, $campo);
        }
        $this->assertStringContainsString('js-ver-alumno', $contenido);
        $this->assertStringContainsString('js-editar-alumno', $contenido);
    }

    public function test_alumnos_elimina_con_confirmacion_en_el_modal_nativo(): void
    {
        $contenido = $this->contenido($this->pantalla('admin.alumnos'));

        $this->assertStringContainsString('action="' . e(route('admin.alumnos.eliminar', 1)) . '"', $contenido);
        $this->assertStringContainsString('data-se-confirmar="¿Eliminar a este alumno? Se perderá su historial académico, notas y asistencias."', $contenido);
        $this->assertStringContainsString('data-se-confirmar-titulo="Eliminar alumno"', $contenido);
        $this->assertStringNotContainsString('onsubmit=', $contenido);
    }

    public function test_alumnos_conserva_los_filtros_por_get(): void
    {
        $contenido = $this->contenido($this->pantalla('admin.alumnos'));

        $this->assertStringContainsString('method="GET" action="' . e(route('admin.alumnos')) . '"', $contenido);
        foreach (['q', 'curso_id', 'estado'] as $campo) {
            $this->assertStringContainsString('name="' . $campo . '"', $contenido, $campo);
        }
    }

    public function test_materias_usa_un_dialog_nativo_para_crear_y_editar(): void
    {
        $contenido = $this->contenido($this->pantalla('admin.materias'));

        $this->assertStringContainsString('<dialog id="materias-modal-fondo" class="se-modal se-modal--form"', $contenido);
        $this->assertStringContainsString('id="materia-form" method="POST" action="' . e(route('admin.materias.guardar')) . '"', $contenido);
        $this->assertStringContainsString('name="_method" id="materia-method" value=""', $contenido);
        foreach (['nombre', 'curso_id', 'profesor_ids[]'] as $campo) {
            $this->assertStringContainsString('name="' . $campo . '"', $contenido, $campo);
        }
        foreach (['materia-nombre', 'materia-curso', 'materia-profesores', 'materia-submit-btn', 'materias-busqueda', 'materias-filtro-curso'] as $id) {
            $this->assertStringContainsString('id="' . $id . '"', $contenido, $id);
        }
        $this->assertStringContainsString('abrirModalEditarMateria(', $contenido);
        $this->assertStringContainsString(route('admin.materias.actualizar', ['materia' => 0]), $this->pantalla('admin.materias')->getContent());
    }

    public function test_materias_elimina_con_un_formulario_que_confirma_en_el_modal_nativo(): void
    {
        $contenido = $this->contenido($this->pantalla('admin.materias'));

        $this->assertStringContainsString('action="' . e(route('admin.materias.eliminar', 1)) . '"', $contenido);
        $this->assertStringContainsString('name="_method" value="DELETE"', $contenido);
        $this->assertStringContainsString('data-se-confirmar-titulo="Eliminar materia"', $contenido);
        $this->assertStringNotContainsString('onclick="confirmarEliminarMateria', $contenido);
    }

    public function test_el_modal_nativo_de_formularios_existe_en_el_js_compartido(): void
    {
        $js = file_get_contents(public_path('js/tema.js'));

        $this->assertStringContainsString('window.seDialogo', $js);
        $this->assertStringContainsString('data-se-abrir', $js);
        $this->assertStringContainsString('data-se-cerrar', $js);
    }
}

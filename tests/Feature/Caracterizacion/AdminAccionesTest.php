<?php

namespace Tests\Feature\Caracterizacion;

use App\Models\Alumno;
use App\Models\BoletinGenerado;
use App\Models\DocumentoAlumno;
use App\Models\Materia;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

/**
 * Fija lo que hacen hoy las acciones del administrador (usuarios, materias, alumnos,
 * legajos, boletines y reportes).
 */
class AdminAccionesTest extends PantallaTestCase
{
    private function admin(): static
    {
        return $this->comoUsuario(self::ADMIN);
    }

    // ---- usuarios -----------------------------------------------------

    private function datosUsuario(array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'Nuevo Usuario',
            'email' => 'nuevo@escuela.test',
            'cedula' => '4000001',
            'password' => 'clave-segura',
            'password_confirmation' => 'clave-segura',
            'rol' => 'administrador',
            'activo' => 1,
        ], $cambios);
    }

    public function test_registrar_un_alumno_le_crea_su_perfil_con_legajo(): void
    {
        $this->admin()->post(route('admin.usuarios.guardar'), $this->datosUsuario(['rol' => 'alumno', 'curso_id' => 2]))
            ->assertRedirect(route('admin.usuarios'))
            ->assertSessionHas('success', 'Usuario registrado correctamente.');

        $usuario = Usuario::where('cedula', '4000001')->firstOrFail();
        $this->assertSame('alumno', $usuario->rol);
        $this->assertSame('Nuevo Usuario', $usuario->nombre);
        $this->assertTrue($usuario->activo);
        $this->assertTrue(password_verify('clave-segura', $usuario->password));

        $alumno = Alumno::where('usuario_id', $usuario->id)->firstOrFail();
        $this->assertSame(2, $alumno->curso_id);
        $this->assertMatchesRegularExpression('/^2026-\d{4}$/', $alumno->legajo);
        $this->assertTrue($alumno->activo);
    }

    public function test_registrar_un_profesor_con_materias_existentes_y_nuevas(): void
    {
        $this->admin()->post(route('admin.usuarios.guardar'), $this->datosUsuario([
            'rol' => 'profesor',
            'materia_ids' => ['1', '__nueva__', '__nueva__', 'x'],
            'materia_nuevas' => [1 => 'Química', 2 => '   '],
        ]))->assertRedirect(route('admin.usuarios'));

        $usuario = Usuario::where('cedula', '4000001')->firstOrFail();
        $profesorId = DB::table('profesores')->where('usuario_id', $usuario->id)->value('id');
        $nueva = Materia::where('nombre', 'Química')->firstOrFail();

        $this->assertNull($nueva->curso_id);
        $this->assertTrue((bool) $nueva->activa);
        $this->assertEqualsCanonicalizing(
            [1, $nueva->id],
            DB::table('profesor_materia')->where('profesor_id', $profesorId)->pluck('materia_id')->map(fn ($id) => (int) $id)->all()
        );
        $this->assertSame(1, Materia::where('nombre', 'Química')->count());
    }

    public function test_registrar_un_administrador_no_crea_perfil(): void
    {
        $this->admin()->post(route('admin.usuarios.guardar'), $this->datosUsuario())->assertSessionHas('success');

        $usuario = Usuario::where('cedula', '4000001')->firstOrFail();
        $this->assertSame('administrador', $usuario->rol);
        $this->assertFalse(DB::table('alumnos')->where('usuario_id', $usuario->id)->exists());
        $this->assertFalse(DB::table('profesores')->where('usuario_id', $usuario->id)->exists());
    }

    public function test_la_cedula_se_guarda_sin_espacios(): void
    {
        $this->admin()->post(route('admin.usuarios.guardar'), $this->datosUsuario(['cedula' => '  4000009  ', 'nombre' => '  Con Espacios  ']));

        $this->assertDatabaseHas('usuarios', ['cedula' => '4000009', 'nombre' => 'Con Espacios']);
    }

    public function test_registrar_usuario_valida_los_datos(): void
    {
        $this->admin()->from('/x')->post(route('admin.usuarios.guardar'), [])
            ->assertSessionHasErrors(['nombre', 'email', 'cedula', 'password', 'rol', 'activo']);

        $this->admin()->post(route('admin.usuarios.guardar'), $this->datosUsuario(['cedula' => '3000001', 'email' => 'emilce@escuela.test']))
            ->assertSessionHasErrors(['cedula', 'email']);

        $this->admin()->post(route('admin.usuarios.guardar'), $this->datosUsuario(['password' => 'corta', 'password_confirmation' => 'corta']))
            ->assertSessionHasErrors('password');

        $this->admin()->post(route('admin.usuarios.guardar'), $this->datosUsuario(['password_confirmation' => 'otra-clave']))
            ->assertSessionHasErrors('password');

        $this->admin()->post(route('admin.usuarios.guardar'), $this->datosUsuario(['rol' => 'director']))
            ->assertSessionHasErrors('rol');

        $this->admin()->post(route('admin.usuarios.guardar'), $this->datosUsuario(['rol' => 'alumno']))
            ->assertSessionHasErrors('curso_id');

        $this->assertSame(7, Usuario::count());
    }

    public function test_eliminar_un_profesor_borra_su_perfil_y_sus_materias_asignadas(): void
    {
        // RESTRICT en la base real: asistencias/notas referencian al usuario; se limpian antes.
        DB::table('asistencias')->where('cargado_por', 2)->delete();
        DB::table('notas')->where('cargado_por', 2)->delete();

        $this->admin()->from('/admin/usuarios')->delete(route('admin.usuarios.eliminar', 2))
            ->assertRedirect('/admin/usuarios')
            ->assertSessionHas('success', 'Usuario eliminado correctamente.');

        $this->assertDatabaseMissing('usuarios', ['id' => 2]);
        $this->assertDatabaseMissing('profesores', ['usuario_id' => 2]);
        $this->assertSame(0, DB::table('profesor_materia')->where('profesor_id', 1)->count());
        $this->assertSame(1, DB::table('profesor_materia')->where('profesor_id', 2)->count());
    }

    public function test_eliminar_un_alumno_borra_su_perfil(): void
    {
        $this->admin()->delete(route('admin.usuarios.eliminar', 7))->assertSessionHas('success');

        $this->assertDatabaseMissing('usuarios', ['id' => 7]);
        $this->assertDatabaseMissing('alumnos', ['usuario_id' => 7]);
    }

    public function test_no_se_puede_eliminar_la_propia_cuenta(): void
    {
        $this->admin()->delete(route('admin.usuarios.eliminar', self::ADMIN))->assertStatus(422);

        $this->assertDatabaseHas('usuarios', ['id' => self::ADMIN]);
    }

    // ---- materias -----------------------------------------------------

    public function test_crear_una_materia_con_profesores(): void
    {
        $this->admin()->from('/admin/materias')
            ->post(route('admin.materias.guardar'), ['nombre' => 'Química', 'curso_id' => 2, 'profesor_ids' => ['1', '', '2', '1']])
            ->assertRedirect('/admin/materias')
            ->assertSessionHas('success', 'Materia creada correctamente.');

        $materia = Materia::where('nombre', 'Química')->firstOrFail();
        $this->assertSame(2, $materia->curso_id);
        $this->assertEqualsCanonicalizing([1, 2], $materia->profesores()->pluck('profesores.id')->all());
    }

    public function test_actualizar_una_materia_reemplaza_sus_profesores(): void
    {
        $this->admin()
            ->put(route('admin.materias.actualizar', 1), ['nombre' => 'Matemática II', 'curso_id' => 2, 'profesor_ids' => ['2']])
            ->assertSessionHas('success', 'Materia actualizada correctamente.');

        $materia = Materia::findOrFail(1);
        $this->assertSame('Matemática II', $materia->nombre);
        $this->assertSame(2, $materia->curso_id);
        $this->assertSame([2], $materia->profesores()->pluck('profesores.id')->all());
    }

    public function test_actualizar_una_materia_sin_profesores_los_quita(): void
    {
        $this->admin()->put(route('admin.materias.actualizar', 1), ['nombre' => 'Matemática', 'curso_id' => 1]);

        $this->assertSame([], Materia::findOrFail(1)->profesores()->pluck('profesores.id')->all());
    }

    public function test_materia_valida_los_datos(): void
    {
        $this->admin()->from('/x')->post(route('admin.materias.guardar'), [])->assertSessionHasErrors(['nombre', 'curso_id']);
        $this->admin()->post(route('admin.materias.guardar'), ['nombre' => 'X', 'curso_id' => 99])->assertSessionHasErrors('curso_id');
        $this->admin()->post(route('admin.materias.guardar'), ['nombre' => 'X', 'curso_id' => 1, 'profesor_ids' => [99]])->assertSessionHasErrors('profesor_ids.0');

        $this->assertSame(3, Materia::count());
    }

    public function test_eliminar_una_materia(): void
    {
        // RESTRICT en la base real: asistencias/notas de la materia se limpian antes.
        DB::table('asistencias')->where('materia_id', 3)->delete();
        DB::table('notas')->where('materia_id', 3)->delete();

        $this->admin()->delete(route('admin.materias.eliminar', 3))->assertSessionHas('success', 'Materia eliminada correctamente.');

        $this->assertDatabaseMissing('materias', ['id' => 3]);
    }

    // ---- alumnos ------------------------------------------------------

    public function test_actualizar_un_alumno(): void
    {
        $this->admin()->put(route('admin.alumnos.actualizar', 1), [
            'curso_id' => 2, 'legajo' => '2026-9999', 'fecha_nacimiento' => '2011-01-02', 'telefono' => '0982 333 444', 'activo' => 0,
        ])->assertSessionHas('success', 'Alumno actualizado correctamente.');

        $alumno = Alumno::findOrFail(1);
        $this->assertSame(2, $alumno->curso_id);
        $this->assertSame('2026-9999', $alumno->legajo);
        $this->assertSame('2011-01-02', $alumno->fecha_nacimiento->toDateString());
        $this->assertSame('0982 333 444', $alumno->telefono);
        $this->assertFalse($alumno->activo);
    }

    public function test_actualizar_un_alumno_valida_los_datos(): void
    {
        $this->admin()->from('/x')->put(route('admin.alumnos.actualizar', 1), [])->assertSessionHasErrors(['curso_id', 'activo']);
        $this->admin()->put(route('admin.alumnos.actualizar', 1), ['curso_id' => 1, 'activo' => 1, 'fecha_nacimiento' => 'no-es-fecha'])
            ->assertSessionHasErrors('fecha_nacimiento');
    }

    public function test_eliminar_un_alumno(): void
    {
        $this->admin()->delete(route('admin.alumnos.eliminar', 4))->assertSessionHas('success', 'Alumno eliminado correctamente.');

        $this->assertDatabaseMissing('alumnos', ['id' => 4]);
    }

    // ---- legajos ------------------------------------------------------

    public function test_guardar_documentos_del_legajo(): void
    {
        $this->admin()->putJson(route('admin.legajos.documentos.guardar', 1), [
            'documentos' => ['cedula_dni', 'cedula_dni', 'certificado_medico', 'no_existe'],
        ])->assertOk()->assertJson(['message' => 'Legajo actualizado correctamente.', 'entregados' => 3]);

        $this->assertEqualsCanonicalizing(
            ['cedula_dni', 'certificado_medico'],
            DocumentoAlumno::where('alumno_id', 1)->pluck('documento')->all()
        );
        $this->assertSame('salud', DocumentoAlumno::where(['alumno_id' => 1, 'documento' => 'certificado_medico'])->value('categoria'));
        $this->assertSame(1, DocumentoAlumno::where('alumno_id', 2)->count());
    }

    public function test_guardar_el_legajo_sin_documentos_lo_vacia(): void
    {
        $this->admin()->putJson(route('admin.legajos.documentos.guardar', 1), [])
            ->assertOk()->assertJson(['entregados' => 0]);

        $this->assertSame(0, DocumentoAlumno::where('alumno_id', 1)->count());
    }

    // ---- boletines ----------------------------------------------------

    private function datosPdf(): array
    {
        $capturados = [];
        View::composer('admin.boletin-pdf', function ($vista) use (&$capturados) {
            $capturados[] = $vista->getData();
        });

        return [&$capturados];
    }

    public function test_generar_el_boletin_de_un_alumno(): void
    {
        $this->admin()->from('/admin/boletines')
            ->post(route('admin.boletines.pdf'), ['alumno_id' => 1, 'periodo' => '1er Bimestre'])
            ->assertRedirect('/admin/boletines')
            ->assertSessionHas('success', 'Boletín generado correctamente.');

        $archivos = Storage::disk('local')->allFiles('boletines');
        $this->assertCount(1, $archivos);
        $this->assertMatchesRegularExpression('#^boletines/Boletin_emilceespinola_3a_\d{8}_\d{6}_[A-Za-z0-9]{6}\.pdf$#', $archivos[0]);
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($archivos[0]));

        $boletin = BoletinGenerado::where('id', '>', 1)->firstOrFail();
        $this->assertSame([1, 1, '1er Bimestre', $archivos[0], self::ADMIN, 0], [
            $boletin->alumno_id, $boletin->curso_id, $boletin->periodo, $boletin->archivo, $boletin->generado_por, $boletin->descargas,
        ]);
    }

    public function test_generar_los_boletines_de_un_curso(): void
    {
        $this->admin()->post(route('admin.boletines.pdf'), ['curso_id' => 1, 'periodo' => '2do Bimestre'])
            ->assertSessionHas('success', 'Se generaron 2 boletines correctamente.');

        $this->assertCount(2, Storage::disk('local')->allFiles('boletines'));
        $this->assertSame(3, BoletinGenerado::count());
    }

    public function test_generar_boletin_sin_alumno_ni_curso_da_error(): void
    {
        $this->admin()->from('/admin/boletines')->post(route('admin.boletines.pdf'), ['periodo' => '1er Bimestre'])
            ->assertRedirect('/admin/boletines')
            ->assertSessionHasErrors(['alumno_id' => 'Seleccioná un alumno o un curso para generar el boletín.']);

        $this->assertSame(1, BoletinGenerado::count());
    }

    public function test_generar_boletin_sin_alumnos_que_coincidan_da_error(): void
    {
        $this->admin()->post(route('admin.boletines.pdf'), ['alumno_id' => 1, 'curso_id' => 2, 'periodo' => '1er Bimestre'])
            ->assertSessionHasErrors(['alumno_id' => 'No se encontraron alumnos para los criterios seleccionados.']);
    }

    public function test_generar_boletin_valida_los_datos(): void
    {
        $this->admin()->from('/x')->post(route('admin.boletines.pdf'), ['alumno_id' => 99])->assertSessionHasErrors(['alumno_id', 'periodo']);
    }

    public function test_descargar_un_boletin_cuenta_la_descarga(): void
    {
        Storage::disk('local')->put('boletines/boletin-1.pdf', '%PDF-prueba');

        $this->admin()->get(route('admin.boletines.descargar', 1))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=Boletin_emilce_espinola.pdf');

        $this->assertSame(3, BoletinGenerado::findOrFail(1)->descargas);
    }

    public function test_descargar_un_boletin_inexistente_da_404(): void
    {
        $this->admin()->get(route('admin.boletines.descargar', 1))->assertNotFound();
        $this->assertSame(2, BoletinGenerado::findOrFail(1)->descargas);
    }

    public function test_ver_un_boletin(): void
    {
        Storage::disk('local')->put('boletines/boletin-1.pdf', '%PDF-prueba');

        $this->admin()->get(route('admin.boletines.ver', 1))->assertOk();
        $this->admin()->get(route('admin.boletines.ver', 99))->assertNotFound();
    }

    public function test_ver_un_boletin_sin_archivo_da_404(): void
    {
        $this->admin()->get(route('admin.boletines.ver', 1))->assertNotFound();
    }

    // ---- reportes -----------------------------------------------------

    /** @return array<string, mixed> los datos con los que se arma el PDF del reporte */
    private function datosDelReporte(array $parametros)
    {
        $capturados = [];
        View::composer('admin.reporte-pdf', function ($vista) use (&$capturados) {
            $capturados[] = $vista->getData();
        });

        $respuesta = $this->admin()->get(route('admin.reportes.exportar', $parametros))->assertOk();

        $this->assertSame('application/pdf', $respuesta->headers->get('content-type'));
        $this->assertCount(1, $capturados);

        return [$respuesta, $capturados[0]];
    }

    private function aJson(mixed $valor): mixed
    {
        return match (true) {
            $valor instanceof \DateTimeInterface => $valor->format('Y-m-d H:i:s'),
            $valor instanceof \Illuminate\Support\Collection => $this->aJson($valor->all()),
            $valor instanceof \Illuminate\Database\Eloquent\Model => $this->aJson($valor->attributesToArray()),
            $valor instanceof \Illuminate\Contracts\Pagination\Paginator => $this->aJson($valor->items()),
            is_object($valor) => $this->aJson(get_object_vars($valor)),
            is_array($valor) => array_map(fn ($v) => $this->aJson($v), $valor),
            default => $valor,
        };
    }

    public function test_exportar_los_cuatro_tipos_de_reporte(): void
    {
        $nombres = [
            1 => 'reporte-resumen-ejecutivo-institucional',
            2 => 'reporte-reporte-comparativo-por-curso',
            3 => 'reporte-cuadro-de-honor-y-alumnos-destacados',
            4 => 'reporte-alerta-de-riesgo-academico-y-ausentismo',
        ];

        foreach ($nombres as $tipo => $nombre) {
            [$respuesta, $datos] = $this->datosDelReporte(['tipo' => $tipo, 'desde' => '2026-09-01', 'hasta' => '2026-10-12']);

            $this->assertSame(
                'attachment; filename=' . $nombre . '-2026-10-12.pdf',
                $respuesta->headers->get('content-disposition')
            );

            $this->compararConCaptura(
                "reporte_tipo_{$tipo}.json",
                json_encode($this->aJson($datos), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
            );
        }
    }

    public function test_exportar_un_reporte_filtrado_por_curso(): void
    {
        [, $datos] = $this->datosDelReporte(['tipo' => 1, 'desde' => '2026-09-01', 'hasta' => '2026-10-12', 'curso_id' => 1]);

        $this->compararConCaptura(
            'reporte_tipo_1_curso_1.json',
            json_encode($this->aJson($datos), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
        );
    }

    public function test_exportar_reporte_valida_los_datos(): void
    {
        $this->admin()->from('/x')->get(route('admin.reportes.exportar'))->assertSessionHasErrors('tipo');
        $this->admin()->get(route('admin.reportes.exportar', ['tipo' => 5]))->assertSessionHasErrors('tipo');
        $this->admin()->get(route('admin.reportes.exportar', ['tipo' => 1, 'curso_id' => 99]))->assertSessionHasErrors('curso_id');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Services\EstadisticasService;
use App\Services\LegajoService;
use App\Services\NotaService;
use App\Services\UsuarioService;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Caracterizacion\PantallaTestCase;

class ServiciosAdministradorTest extends PantallaTestCase
{
    private function datos(string $rol, array $extra = []): array
    {
        return array_merge([
            'nombre' => 'Persona Nueva',
            'email' => $rol . '@escuela.test',
            'cedula' => '9' . random_int(100000, 999999),
            'password' => 'clave-segura',
            'rol' => $rol,
            'activo' => true,
        ], $extra);
    }

    public function test_registrar_un_administrador_no_crea_perfil(): void
    {
        $antes = [DB::table('alumnos')->count(), DB::table('profesores')->count()];

        app(UsuarioService::class)->registrar($this->datos('administrador'));

        $this->assertSame($antes, [DB::table('alumnos')->count(), DB::table('profesores')->count()]);
    }

    public function test_registrar_un_alumno_crea_su_perfil_con_legajo(): void
    {
        app(UsuarioService::class)->registrar($this->datos('alumno', ['curso_id' => 2]));

        $alumno = Alumno::query()->latest('id')->firstOrFail();
        $this->assertSame(2, $alumno->curso_id);
        $this->assertMatchesRegularExpression('/^2026-\d{4}$/', $alumno->legajo);
    }

    public function test_registrar_un_profesor_le_asigna_las_materias(): void
    {
        app(UsuarioService::class)->registrar($this->datos('profesor', ['materia_ids' => ['1', '3']]));

        $profesorId = DB::table('profesores')->max('id');
        $this->assertEqualsCanonicalizing(
            [1, 3],
            DB::table('profesor_materia')->where('profesor_id', $profesorId)->pluck('materia_id')->all()
        );
    }

    public function test_un_nuevo_legajo_nunca_repite_uno_existente(): void
    {
        $legajo = Alumno::nuevoLegajo();

        $this->assertMatchesRegularExpression('/^2026-\d{4}$/', $legajo);
        $this->assertFalse(Alumno::where('legajo', $legajo)->exists());
    }

    public function test_cargar_una_nota_dos_veces_deja_un_solo_registro(): void
    {
        $profesor = \App\Models\Usuario::findOrFail(self::PROFESOR);
        $servicio = app(NotaService::class);

        $servicio->registrar($profesor, 1, 1, 'Prueba', '4');
        $servicio->registrar($profesor, 1, 1, 'Prueba', 5);

        $filas = DB::table('notas')->where(['alumno_id' => 1, 'materia_id' => 1, 'periodo' => 'Prueba'])->get();
        $this->assertCount(1, $filas);
        $this->assertSame('5.00', $filas->first()->valor);
    }

    public function test_cargar_una_nota_fuera_de_rango_se_rechaza(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(NotaService::class)->registrar(\App\Models\Usuario::findOrFail(self::PROFESOR), 1, 1, 'Prueba', 6);
    }

    public function test_el_legajo_cuenta_los_documentos_del_catalogo(): void
    {
        $servicio = app(LegajoService::class);
        $alumno = Alumno::findOrFail(1);

        $entregados = $servicio->guardarDocumentos($alumno, ['cedula_dni', 'cedula_dni', 'no_existe']);

        // Se cuenta lo recibido (sin repetidos), aunque un código no esté en el catálogo.
        $this->assertSame(2, $entregados);
        $this->assertSame(1, $alumno->documentos()->count());
    }

    public function test_las_estadisticas_son_decimales_incluso_sin_datos(): void
    {
        DB::table('boletines_generados')->delete();
        DB::table('notas')->delete();
        DB::table('asistencias')->delete();

        $servicio = app(EstadisticasService::class);

        $this->assertSame(0.0, $servicio->promedioGeneral());
        $this->assertSame(0.0, $servicio->asistenciaGeneral());
    }
}

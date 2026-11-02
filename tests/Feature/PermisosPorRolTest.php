<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Nota;
use App\Models\Profesor;
use App\Models\Usuario;
use Illuminate\Support\Facades\Gate;
use Tests\Concerns\RequiereMysql;
use Tests\TestCase;

/**
 * Las policies ahora delegan en el rol; esta matriz fija las respuestas que daban antes.
 * Necesita MySQL: php artisan test --configuration=phpunit.mysql.xml
 */
class PermisosPorRolTest extends TestCase
{
    use RequiereMysql;

    private Curso $curso;
    private Curso $otroCurso;
    private Materia $materia;
    private Materia $ajena;
    private Usuario $admin;
    private Usuario $profesor;
    private Usuario $profesorSinMaterias;
    private Usuario $alumno;
    private Usuario $otroAlumno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->curso = Curso::factory()->create();
        $this->otroCurso = Curso::factory()->create();
        $this->materia = Materia::factory()->create(['curso_id' => $this->curso->id]);
        $this->ajena = Materia::factory()->create(['curso_id' => $this->otroCurso->id]);

        $this->admin = Usuario::factory()->administrador()->create();

        $perfilProfesor = Profesor::factory()->create();
        $perfilProfesor->materias()->attach($this->materia->id);
        $this->profesor = $perfilProfesor->usuario;
        $this->profesorSinMaterias = Profesor::factory()->create()->usuario;

        $this->alumno = Alumno::factory()->create(['curso_id' => $this->curso->id])->usuario;
        $this->otroAlumno = Alumno::factory()->create(['curso_id' => $this->otroCurso->id])->usuario;
    }

    private function puede(Usuario $usuario, string $accion, mixed $argumento): bool
    {
        return Gate::forUser($usuario)->allows($accion, $argumento);
    }

    public function test_solo_el_profesor_de_la_materia_crea_notas(): void
    {
        $datos = [Nota::class, ['materia_id' => $this->materia->id]];

        $this->assertTrue($this->puede($this->profesor, 'create', $datos));
        $this->assertFalse($this->puede($this->profesorSinMaterias, 'create', $datos));
        $this->assertFalse($this->puede($this->admin, 'create', $datos));
        $this->assertFalse($this->puede($this->alumno, 'create', $datos));
        $this->assertFalse($this->puede($this->profesor, 'create', [Nota::class, ['materia_id' => $this->ajena->id]]));
    }

    public function test_solo_el_profesor_de_la_materia_modifica_notas(): void
    {
        $nota = new Nota(['alumno_id' => $this->alumno->alumno->id, 'materia_id' => $this->materia->id]);
        $notaAjena = new Nota(['alumno_id' => $this->alumno->alumno->id, 'materia_id' => $this->ajena->id]);

        $this->assertTrue($this->puede($this->profesor, 'update', $nota));
        $this->assertFalse($this->puede($this->profesor, 'update', $notaAjena));
        $this->assertFalse($this->puede($this->admin, 'update', $nota));
        $this->assertFalse($this->puede($this->alumno, 'update', $nota));
    }

    public function test_solo_el_alumno_titular_ve_su_nota(): void
    {
        $nota = new Nota(['alumno_id' => $this->alumno->alumno->id, 'materia_id' => $this->materia->id]);

        $this->assertTrue($this->puede($this->alumno, 'view', $nota));
        $this->assertFalse($this->puede($this->otroAlumno, 'view', $nota));
        $this->assertFalse($this->puede($this->profesor, 'view', $nota));
        $this->assertFalse($this->puede($this->admin, 'view', $nota));
    }

    public function test_asistencias_siguen_las_mismas_reglas(): void
    {
        $propia = new Asistencia(['alumno_id' => $this->alumno->alumno->id, 'materia_id' => $this->materia->id]);
        $ajena = new Asistencia(['alumno_id' => $this->alumno->alumno->id, 'materia_id' => $this->ajena->id]);
        $datos = [Asistencia::class, ['materia_id' => $this->materia->id]];

        $this->assertTrue($this->puede($this->profesor, 'create', $datos));
        $this->assertFalse($this->puede($this->admin, 'create', $datos));
        $this->assertTrue($this->puede($this->profesor, 'update', $propia));
        $this->assertFalse($this->puede($this->profesor, 'update', $ajena));
        $this->assertFalse($this->puede($this->alumno, 'update', $propia));
        $this->assertTrue($this->puede($this->alumno, 'view', $propia));
        $this->assertFalse($this->puede($this->otroAlumno, 'view', $propia));
    }

    public function test_listado_de_alumnos_solo_para_profesor_y_administrador(): void
    {
        $this->assertTrue($this->puede($this->profesor, 'viewAny', Alumno::class));
        $this->assertTrue($this->puede($this->admin, 'viewAny', Alumno::class));
        $this->assertFalse($this->puede($this->alumno, 'viewAny', Alumno::class));
    }

    public function test_alumnos_de_una_materia(): void
    {
        $this->assertTrue($this->puede($this->admin, 'viewForMateria', [Alumno::class, $this->ajena]));
        $this->assertTrue($this->puede($this->profesor, 'viewForMateria', [Alumno::class, $this->materia]));
        $this->assertFalse($this->puede($this->profesor, 'viewForMateria', [Alumno::class, $this->ajena]));
        $this->assertFalse($this->puede($this->alumno, 'viewForMateria', [Alumno::class, $this->materia]));
    }

    public function test_ver_un_alumno_segun_el_curso_del_profesor(): void
    {
        $delCurso = $this->alumno->alumno;
        $deOtroCurso = $this->otroAlumno->alumno;

        $this->assertTrue($this->puede($this->admin, 'view', $deOtroCurso));
        $this->assertTrue($this->puede($this->profesor, 'view', $delCurso));
        $this->assertFalse($this->puede($this->profesor, 'view', $deOtroCurso));
        $this->assertFalse($this->puede($this->alumno, 'view', $delCurso));
    }

    public function test_es_acepta_varios_roles_y_compara_exacto(): void
    {
        $this->assertTrue($this->profesor->es('profesor'));
        $this->assertTrue($this->profesor->es('administrador', 'profesor'));
        $this->assertFalse($this->profesor->es('alumno'));
        $this->assertFalse($this->profesor->es('Profesor'));
    }

    public function test_perfil_del_usuario_segun_su_rol(): void
    {
        $this->assertNull($this->admin->perfil());
        $this->assertInstanceOf(Profesor::class, $this->profesor->perfil());
        $this->assertInstanceOf(Alumno::class, $this->alumno->perfil());
    }
}

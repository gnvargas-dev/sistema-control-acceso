<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Persona;
use App\Models\Profesor;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ReflectionClass;
use Tests\TestCase;

/**
 * No usa la base de datos: solo comprueba la estructura y el comportamiento de las clases.
 */
class PersonaTest extends TestCase
{
    private function usuario(string $cedula = '4512883', string $nombre = 'Emilce Espinola'): Usuario
    {
        return new Usuario(['cedula' => $cedula, 'nombre' => $nombre]);
    }

    public function test_persona_es_abstracta_y_la_heredan_alumno_y_profesor(): void
    {
        $this->assertTrue((new ReflectionClass(Persona::class))->isAbstract());
        $this->assertInstanceOf(Persona::class, new Alumno());
        $this->assertInstanceOf(Persona::class, new Profesor());
    }

    public function test_conservan_sus_tablas(): void
    {
        $this->assertSame('alumnos', (new Alumno())->getTable());
        $this->assertSame('profesores', (new Profesor())->getTable());
    }

    public function test_conservan_los_atributos_asignables_de_antes(): void
    {
        $this->assertEqualsCanonicalizing(
            ['usuario_id', 'curso_id', 'legajo', 'activo', 'fecha_nacimiento', 'telefono', 'foto'],
            (new Alumno())->getFillable()
        );
        $this->assertEqualsCanonicalizing(['usuario_id', 'activo'], (new Profesor())->getFillable());
    }

    public function test_conservan_los_casts_de_antes(): void
    {
        $alumno = (new Alumno())->getCasts();
        $profesor = (new Profesor())->getCasts();

        $this->assertSame('boolean', $alumno['activo']);
        $this->assertSame('date', $alumno['fecha_nacimiento']);
        $this->assertSame('boolean', $profesor['activo']);
        $this->assertArrayNotHasKey('fecha_nacimiento', $profesor);
    }

    public function test_la_asignacion_masiva_funciona_como_antes(): void
    {
        $alumno = new Alumno([
            'usuario_id' => 7,
            'curso_id' => 2,
            'legajo' => '2026-1187',
            'activo' => false,
            'no_asignable' => 'x',
        ]);

        $this->assertSame(7, $alumno->usuario_id);
        $this->assertSame('2026-1187', $alumno->legajo);
        $this->assertFalse($alumno->activo);
        $this->assertNull($alumno->no_asignable);

        $profesor = new Profesor(['usuario_id' => 9, 'activo' => true, 'legajo' => 'no aplica']);

        $this->assertSame(9, $profesor->usuario_id);
        $this->assertTrue($profesor->activo);
        $this->assertNull($profesor->legajo);
    }

    public function test_la_relacion_con_usuario_es_comun(): void
    {
        foreach ([new Alumno(), new Profesor()] as $persona) {
            $relacion = $persona->usuario();

            $this->assertInstanceOf(BelongsTo::class, $relacion);
            $this->assertSame('usuario_id', $relacion->getForeignKeyName());
            $this->assertInstanceOf(Usuario::class, $relacion->getRelated());
        }
    }

    public function test_nombre_cedula_y_estado_activo_vienen_de_la_clase_base(): void
    {
        $alumno = (new Alumno(['activo' => true]))->setRelation('usuario', $this->usuario());
        $profesor = (new Profesor(['activo' => false]))->setRelation('usuario', $this->usuario('1234567', 'Dario Borba'));

        $this->assertSame('Emilce Espinola', $alumno->nombre());
        $this->assertSame('4512883', $alumno->cedula());
        $this->assertTrue($alumno->estaActivo());
        $this->assertSame('Dario Borba', $profesor->nombre());
        $this->assertFalse($profesor->estaActivo());
    }

    public function test_polimorfismo_cada_hija_implementa_distinto_los_metodos_abstractos(): void
    {
        $alumno = (new Alumno(['legajo' => '2026-1187']))
            ->setRelation('usuario', $this->usuario())
            ->setRelation('curso', new Curso(['anio' => 3, 'nombre' => 'A']));

        $profesor = (new Profesor())
            ->setRelation('usuario', $this->usuario('1234567', 'Dario Borba'))
            ->setRelation('materias', collect([new Materia(), new Materia()]));

        $identificadores = [];
        $descripciones = [];

        foreach ([$alumno, $profesor] as $persona) {
            $this->assertInstanceOf(Persona::class, $persona);
            $identificadores[] = $persona->identificador();
            $descripciones[] = $persona->descripcion();
        }

        $this->assertSame(['2026-1187', '1234567'], $identificadores);
        $this->assertSame(['3° A · Legajo 2026-1187', '2 materias asignadas'], $descripciones);
    }

    public function test_descripcion_del_alumno_sin_curso_o_sin_legajo(): void
    {
        $sinCurso = (new Alumno(['legajo' => 'L-1']))->setRelation('curso', null);
        $sinLegajo = (new Alumno())->setRelation('curso', new Curso(['anio' => 1, 'nombre' => 'B']));

        $this->assertSame('Sin curso · Legajo L-1', $sinCurso->descripcion());
        $this->assertSame('1° B', $sinLegajo->descripcion());
    }

    public function test_descripcion_del_profesor_en_singular_y_cero(): void
    {
        $uno = (new Profesor())->setRelation('materias', collect([new Materia()]));
        $ninguna = (new Profesor())->setRelation('materias', collect());

        $this->assertSame('1 materia asignada', $uno->descripcion());
        $this->assertSame('0 materias asignadas', $ninguna->descripcion());
    }
}

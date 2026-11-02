<?php

namespace Tests\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Datos fijos (con IDs explícitos) para las pruebas de caracterización:
 * así las pantallas muestran siempre lo mismo y se pueden comparar.
 *
 * Usuarios: 1 admin · 2 y 3 profesores · 4 a 7 alumnos.
 * Cursos: 1 = 3° A (alumnos 1 y 2) · 2 = 2° B (alumnos 3 y 4).
 * Materias: 1 Matemática y 2 Física (curso 1, profesor 1) · 3 Historia (curso 2, profesor 2).
 */
class EscenarioEscolar
{
    private const CREADO = '2026-10-01 08:00:00';

    /** Hora propia por registro (CREADO + N minutos): así ningún orden por fecha depende del motor. */
    private static function marca(int $minutos): array
    {
        $hora = Carbon::parse(self::CREADO)->addMinutes($minutos)->toDateTimeString();

        return ['created_at' => $hora, 'updated_at' => $hora];
    }

    public static function crear(): void
    {
        $clave = Hash::make('password');
        $t = ['created_at' => self::CREADO, 'updated_at' => self::CREADO];

        DB::table('cursos')->insert([
            ['id' => 1, 'nombre' => 'A', 'anio' => 3] + $t,
            ['id' => 2, 'nombre' => 'B', 'anio' => 2] + $t,
        ]);

        DB::table('usuarios')->insert([
            ['id' => 1, 'cedula' => '1000001', 'email' => 'ana@escuela.test', 'password' => $clave, 'nombre' => 'Ana Administradora', 'rol' => 'administrador', 'activo' => 1] + $t,
            ['id' => 2, 'cedula' => '2000001', 'email' => 'dario@escuela.test', 'password' => $clave, 'nombre' => 'Dario Borba', 'rol' => 'profesor', 'activo' => 1] + $t,
            ['id' => 3, 'cedula' => '2000002', 'email' => null, 'password' => $clave, 'nombre' => 'Laura Gómez', 'rol' => 'profesor', 'activo' => 1] + $t,
            ['id' => 4, 'cedula' => '3000001', 'email' => 'emilce@escuela.test', 'password' => $clave, 'nombre' => 'Emilce Espinola', 'rol' => 'alumno', 'activo' => 1] + $t,
            ['id' => 5, 'cedula' => '3000002', 'email' => null, 'password' => $clave, 'nombre' => 'Ricardo Ortiz', 'rol' => 'alumno', 'activo' => 1] + $t,
            ['id' => 6, 'cedula' => '3000003', 'email' => null, 'password' => $clave, 'nombre' => 'Armando Velazco', 'rol' => 'alumno', 'activo' => 1] + $t,
            ['id' => 7, 'cedula' => '3000004', 'email' => null, 'password' => $clave, 'nombre' => 'Bautista Gavilán', 'rol' => 'alumno', 'activo' => 0] + $t,
        ]);

        DB::table('alumnos')->insert([
            ['id' => 1, 'usuario_id' => 4, 'curso_id' => 1, 'legajo' => '2026-1001', 'activo' => 1, 'fecha_nacimiento' => '2010-03-05', 'telefono' => '0981 111 222', 'foto' => null] + $t,
            ['id' => 2, 'usuario_id' => 5, 'curso_id' => 1, 'legajo' => '2026-1002', 'activo' => 1, 'fecha_nacimiento' => null, 'telefono' => null, 'foto' => null] + $t,
            ['id' => 3, 'usuario_id' => 6, 'curso_id' => 2, 'legajo' => '2026-1003', 'activo' => 1, 'fecha_nacimiento' => null, 'telefono' => null, 'foto' => null] + $t,
            ['id' => 4, 'usuario_id' => 7, 'curso_id' => 2, 'legajo' => '2026-1004', 'activo' => 0, 'fecha_nacimiento' => null, 'telefono' => null, 'foto' => null] + $t,
        ]);

        DB::table('profesores')->insert([
            ['id' => 1, 'usuario_id' => 2, 'activo' => 1] + $t,
            ['id' => 2, 'usuario_id' => 3, 'activo' => 1] + $t,
        ]);

        DB::table('materias')->insert([
            ['id' => 1, 'nombre' => 'Matemática', 'codigo' => 'MAT101', 'descripcion' => 'Álgebra y geometría', 'curso_id' => 1, 'activa' => 1] + $t,
            ['id' => 2, 'nombre' => 'Física', 'codigo' => 'FIS102', 'descripcion' => null, 'curso_id' => 1, 'activa' => 1] + $t,
            ['id' => 3, 'nombre' => 'Historia', 'codigo' => 'HIS103', 'descripcion' => null, 'curso_id' => 2, 'activa' => 1] + $t,
        ]);

        DB::table('profesor_materia')->insert([
            ['profesor_id' => 1, 'materia_id' => 1],
            ['profesor_id' => 1, 'materia_id' => 2],
            ['profesor_id' => 2, 'materia_id' => 3],
        ]);

        $notas = [
            // id, alumno, materia, valor, periodo, cargado_por
            [1, 1, 1, 4.00, '1er Bimestre', 2],
            [2, 1, 1, 5.00, '2do Bimestre', 2],
            [3, 1, 2, 5.00, '1er Bimestre', 2],
            [4, 2, 1, 1.00, '1er Bimestre', 2],
            [5, 2, 1, 2.00, '2do Bimestre', 2],
            [6, 2, 2, 1.00, '1er Bimestre', 2],
            [7, 3, 3, 4.00, '1er Bimestre', 3],
            [8, 4, 3, 1.00, '1er Bimestre', 3],
        ];
        DB::table('notas')->insert(array_map(fn ($n) => [
            'id' => $n[0], 'alumno_id' => $n[1], 'materia_id' => $n[2], 'valor' => $n[3],
            'periodo' => $n[4], 'cargado_por' => $n[5], 'observacion' => null,
        ] + self::marca($n[0]), $notas));

        $asistencias = [
            // id, alumno, materia, fecha, estado, observación
            [1, 1, 1, '2026-09-15', 'presente', null],
            [2, 2, 1, '2026-09-15', 'tarde', null],
            [3, 1, 1, '2026-10-05', 'presente', null],
            [4, 2, 1, '2026-10-05', 'presente', null],
            [5, 1, 1, '2026-10-07', 'tarde', null],
            [6, 2, 1, '2026-10-07', 'ausente', null],
            [7, 1, 1, '2026-10-09', 'presente', null],
            [8, 2, 1, '2026-10-09', 'justificado', 'Certificado médico'],
            [9, 1, 1, '2026-10-12', 'presente', null],
            [10, 2, 1, '2026-10-12', 'presente', null],
            [11, 1, 2, '2026-10-06', 'presente', null],
            [12, 2, 2, '2026-10-06', 'ausente', null],
            [13, 1, 2, '2026-10-08', 'ausente', 'Sin aviso'],
            [14, 2, 2, '2026-10-08', 'presente', null],
            [15, 3, 3, '2026-10-07', 'presente', null],
            [16, 4, 3, '2026-10-07', 'ausente', null],
        ];
        DB::table('asistencias')->insert(array_map(fn ($a) => [
            'id' => $a[0], 'alumno_id' => $a[1], 'materia_id' => $a[2], 'fecha' => $a[3],
            'estado' => $a[4], 'observacion' => $a[5], 'cargado_por' => $a[2] === 3 ? 3 : 2,
        ] + self::marca(10 + $a[0]), $asistencias));

        DB::table('historial_academico')->insert([
            ['id' => 1, 'alumno_id' => 1, 'detalle' => 'Promedio del 1er Bimestre: 4.50', 'periodo' => '1er Bimestre', 'created_at' => '2026-09-30 12:00:00', 'updated_at' => '2026-09-30 12:00:00'],
            ['id' => 2, 'alumno_id' => 1, 'detalle' => 'Aprobó Matemática', 'periodo' => '2do Bimestre', 'created_at' => '2026-10-02 12:00:00', 'updated_at' => '2026-10-02 12:00:00'],
            ['id' => 3, 'alumno_id' => 2, 'detalle' => 'Promedio del 1er Bimestre: 1.00', 'periodo' => '1er Bimestre', 'created_at' => '2026-09-30 12:00:00', 'updated_at' => '2026-09-30 12:00:00'],
        ]);

        DB::table('documentos_alumno')->insert([
            ['id' => 1, 'alumno_id' => 1, 'categoria' => 'personal', 'documento' => 'cedula_dni', 'fecha_entrega' => '2026-03-01'] + $t,
            ['id' => 2, 'alumno_id' => 1, 'categoria' => 'personal', 'documento' => 'partida_nacimiento', 'fecha_entrega' => '2026-03-01'] + $t,
            ['id' => 3, 'alumno_id' => 1, 'categoria' => 'salud', 'documento' => 'certificado_medico', 'fecha_entrega' => '2026-03-05'] + $t,
            ['id' => 4, 'alumno_id' => 2, 'categoria' => 'personal', 'documento' => 'cedula_dni', 'fecha_entrega' => '2026-03-02'] + $t,
        ]);

        DB::table('boletines_generados')->insert([
            ['id' => 1, 'alumno_id' => 1, 'curso_id' => 1, 'periodo' => '1er Bimestre', 'archivo' => 'boletines/boletin-1.pdf', 'generado_por' => 1, 'descargas' => 2] + $t,
        ]);
    }
}

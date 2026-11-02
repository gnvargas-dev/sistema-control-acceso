<?php

declare(strict_types=1);

namespace Tests\Feature\Caracterizacion;

use App\Models\Materia;
use App\Models\Usuario;

/**
 * Respuestas de la API que los controladores delegan en los servicios (notas, asistencias,
 * historial, reportes, usuarios, materias y login): fijan la forma de cada respuesta.
 */
class ApiControladoresTest extends PantallaTestCase
{
    public function test_login_devuelve_token_y_usuario_con_credenciales_validas(): void
    {
        $this->postJson('/api/auth/login', ['cedula' => '3000001', 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['token', 'usuario' => ['id', 'cedula', 'nombre', 'rol']])
            ->assertJsonPath('usuario.cedula', '3000001');
    }

    public function test_login_rechaza_contrasena_incorrecta_y_usuarios_inactivos(): void
    {
        $this->postJson('/api/auth/login', ['cedula' => '3000001', 'password' => 'incorrecta1'])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Credenciales inválidas.');

        $this->postJson('/api/auth/login', ['cedula' => '3000004', 'password' => 'password'])
            ->assertStatus(401);
    }

    public function test_el_alumno_consulta_sus_notas_asistencias_e_historial(): void
    {
        $this->comoUsuario(self::ALUMNO);

        $this->getJson('/api/notas-propias')->assertOk()->assertJsonIsArray();
        $this->getJson('/api/promedio-propio')->assertOk()->assertJsonStructure(['alumno_id', 'promedio']);
        $this->getJson('/api/asistencias-propias')->assertOk()->assertJsonIsArray();
        $this->getJson('/api/asistencias-propias/porcentaje')->assertOk()->assertJsonStructure(['total', 'presentes', 'porcentaje']);
        $this->getJson('/api/historial-propio')->assertOk()->assertJsonIsArray();
    }

    public function test_un_profesor_no_puede_usar_las_rutas_del_alumno(): void
    {
        $this->comoUsuario(self::PROFESOR)->getJson('/api/notas-propias')->assertForbidden();
    }

    public function test_el_administrador_consulta_reportes_materias_y_usuarios(): void
    {
        $this->comoUsuario(self::ADMIN);

        $notas = $this->getJson('/api/reportes/notas')->assertOk()->assertJsonIsArray();
        $asistencias = $this->getJson('/api/reportes/asistencias')->assertOk()->assertJsonIsArray();

        if ($notas->json() !== []) {
            $notas->assertJsonStructure([['nombre', 'cantidad', 'promedio']]);
        }
        if ($asistencias->json() !== []) {
            $asistencias->assertJsonStructure([['nombre', 'total', 'presentes', 'ausentes']]);
        }

        $this->getJson('/api/materias')->assertOk()->assertJsonIsArray();
        $this->getJson('/api/usuarios')->assertOk()->assertJsonPath('per_page', 20)->assertJsonStructure(['data']);
    }

    public function test_alta_y_baja_de_un_usuario_por_la_api(): void
    {
        $this->comoUsuario(self::ADMIN);

        $id = $this->postJson('/api/usuarios', [
            'cedula' => '9000001',
            'password' => 'clave1234',
            'nombre' => 'Usuario de prueba',
            'rol' => 'administrador',
        ])->assertCreated()->json('id');

        $this->assertNotSame('clave1234', Usuario::findOrFail($id)->getRawOriginal('password'));

        $this->deleteJson("/api/usuarios/{$id}")->assertOk()->assertJsonPath('message', 'Usuario dado de baja.');
        $this->assertFalse((bool) Usuario::findOrFail($id)->activo);
    }

    public function test_alta_y_baja_logica_de_una_materia_por_la_api(): void
    {
        $this->comoUsuario(self::ADMIN);

        $id = $this->postJson('/api/materias', ['nombre' => 'Taller de prueba', 'curso_id' => 1])
            ->assertCreated()
            ->assertJsonStructure(['id', 'nombre', 'curso'])
            ->json('id');

        $this->getJson("/api/materias/{$id}")->assertOk()->assertJsonStructure(['curso', 'profesores']);

        $this->deleteJson("/api/materias/{$id}")->assertOk()->assertJsonPath('message', 'Materia dada de baja.');
        $this->assertFalse((bool) Materia::findOrFail($id)->activa);
    }
}

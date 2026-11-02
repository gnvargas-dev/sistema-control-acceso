<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Usa make(): no escribe en la base de datos.
 */
class UsuarioFactoryTest extends TestCase
{
    public function test_crea_un_usuario_valido_por_defecto(): void
    {
        $usuario = Usuario::factory()->make();

        $this->assertInstanceOf(Usuario::class, $usuario);
        $this->assertMatchesRegularExpression('/^\d{7}$/', $usuario->cedula);
        $this->assertSame('alumno', $usuario->rol);
        $this->assertTrue($usuario->activo);
        $this->assertTrue(Hash::check('password', $usuario->password));
    }

    public function test_estados_de_rol_y_actividad(): void
    {
        $this->assertSame('administrador', Usuario::factory()->administrador()->make()->rol);
        $this->assertSame('profesor', Usuario::factory()->profesor()->make()->rol);
        $this->assertFalse(Usuario::factory()->inactivo()->make()->activo);
    }

    public function test_las_cedulas_generadas_no_se_repiten(): void
    {
        $cedulas = Usuario::factory()->count(5)->make()->pluck('cedula');

        $this->assertCount(5, $cedulas->unique());
    }
}

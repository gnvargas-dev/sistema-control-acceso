<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Tests\Concerns\RequiereMysql;
use Tests\TestCase;

/**
 * Un caso por cada comportamiento del login actual (routes/web.php).
 * Necesita MySQL: php artisan test --configuration=phpunit.mysql.xml
 */
class LoginTest extends TestCase
{
    use RequiereMysql;

    protected function tearDown(): void
    {
        Usuario::flushEventListeners();

        parent::tearDown();
    }

    private function entrar(string $cedula, string $password = 'password')
    {
        return $this->from('/login')->post('/login', ['cedula' => $cedula, 'password' => $password]);
    }

    public function test_muestra_el_formulario_de_login(): void
    {
        $this->get('/login')->assertOk()->assertViewIs('auth.login');
    }

    public function test_la_cedula_y_la_contrasena_son_obligatorias(): void
    {
        $this->from('/login')->post('/login', [])->assertSessionHasErrors(['cedula', 'password']);
        $this->assertGuest();
    }

    public function test_credenciales_incorrectas_vuelven_al_login_con_el_mensaje(): void
    {
        $usuario = Usuario::factory()->create();

        $this->entrar($usuario->cedula, 'otra-clave')
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['auth' => 'La cédula o la contraseña son incorrectas.'])
            ->assertSessionHasInput('cedula', $usuario->cedula)
            ->assertSessionMissing('_old_input.password');

        $this->assertGuest();
    }

    public function test_una_cedula_inexistente_da_el_mismo_mensaje(): void
    {
        $this->entrar('0000000')
            ->assertSessionHasErrors(['auth' => 'La cédula o la contraseña son incorrectas.']);

        $this->assertGuest();
    }

    public function test_la_contrasena_distingue_mayusculas_y_minusculas(): void
    {
        $usuario = Usuario::factory()->create();

        $this->entrar($usuario->cedula, 'PASSWORD')->assertSessionHasErrors('auth');
        $this->assertGuest();
    }

    public function test_el_alumno_va_a_su_panel(): void
    {
        $usuario = Usuario::factory()->alumno()->create();

        $this->entrar($usuario->cedula)->assertRedirect(route('alumno.inicio'));
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_el_profesor_va_a_su_panel(): void
    {
        $usuario = Usuario::factory()->profesor()->create();

        $this->entrar($usuario->cedula)->assertRedirect(route('profesor.inicio'));
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_el_administrador_va_a_su_panel(): void
    {
        $usuario = Usuario::factory()->administrador()->create();

        $this->entrar($usuario->cedula)->assertRedirect(route('admin.inicio'));
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_los_espacios_alrededor_de_la_cedula_se_recortan(): void
    {
        $usuario = Usuario::factory()->alumno()->create();

        $this->entrar('  ' . $usuario->cedula . '  ')->assertRedirect(route('alumno.inicio'));
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_un_usuario_inactivo_entra_igual_comportamiento_actual(): void
    {
        $usuario = Usuario::factory()->alumno()->inactivo()->create();

        $this->entrar($usuario->cedula)->assertRedirect(route('alumno.inicio'));
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_un_rol_no_autorizado_cierra_la_sesion_con_el_mensaje(): void
    {
        $usuario = Usuario::factory()->create();
        Usuario::retrieved(function (Usuario $recuperado) {
            $recuperado->rol = 'invitado';
        });

        $this->entrar($usuario->cedula)
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['auth' => 'Tu usuario no tiene un rol autorizado.']);

        $this->assertGuest();
    }

    public function test_el_rol_se_normaliza_antes_de_elegir_el_panel(): void
    {
        $usuario = Usuario::factory()->create();
        Usuario::retrieved(function (Usuario $recuperado) {
            $recuperado->rol = '  Profesor ';
        });

        $this->entrar($usuario->cedula)->assertRedirect(route('profesor.inicio'));
    }

    public function test_cerrar_sesion_vuelve_al_login(): void
    {
        $usuario = Usuario::factory()->create();

        $this->actingAs($usuario)->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_cerrar_sesion_sin_estar_autenticado_redirige_al_login(): void
    {
        $this->post('/logout')->assertRedirect(route('login'));
    }
}

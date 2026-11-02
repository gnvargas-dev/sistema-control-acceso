<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** La raíz del sitio redirige al login (Route::redirect('/', '/login')). */
    public function test_la_raiz_redirige_al_login(): void
    {
        $response = $this->get('/');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
}

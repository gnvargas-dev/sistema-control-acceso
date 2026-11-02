<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Usuario::updateOrCreate(
            ['cedula' => 'admin'],
            [
                'nombre' => 'Administrador Principal',
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', Str::random(20))),
                'rol' => 'administrador',
                'activo' => true,
            ]
        );

        Usuario::updateOrCreate(
            ['cedula' => '20000001'],
            [
                'nombre' => 'Profesor Demo',
                'password' => Hash::make(env('SEED_PROFESOR_PASSWORD', Str::random(20))),
                'rol' => 'profesor',
                'activo' => true,
            ]
        );

        Usuario::updateOrCreate(
            ['cedula' => '30000001'],
            [
                'nombre' => 'Alumno Demo',
                'password' => Hash::make(env('SEED_ALUMNO_PASSWORD', Str::random(20))),
                'rol' => 'alumno',
                'activo' => true,
            ]
        );
    }
}

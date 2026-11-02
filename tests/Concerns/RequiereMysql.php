<?php

namespace Tests\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Para pruebas que necesitan la base de datos. Las migraciones del proyecto usan
 * ALTER TABLE ... MODIFY (solo MySQL), así que con SQLite la prueba se omite en
 * lugar de fallar. Ejecutar con: php artisan test --configuration=phpunit.mysql.xml
 */
trait RequiereMysql
{
    use RefreshDatabase {
        refreshDatabase as protected refrescarBaseDeDatos;
    }

    public function refreshDatabase(): void
    {
        $conexion = config('database.default');

        if (config("database.connections.{$conexion}.driver") !== 'mysql') {
            $this->markTestSkipped(
                'Requiere MySQL: ejecutar con --configuration=phpunit.mysql.xml (base sistema_escolar_test).'
            );
        }

        $this->refrescarBaseDeDatos();
    }
}

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\LegajoController;
use App\Http\Controllers\BoletinController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\RegistroAlumnoController;
use App\Http\Controllers\HistorialAcademicoController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | Alumno
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:alumno')->group(function () {
        Route::get('/notas-propias', [NotaController::class, 'index']);
        Route::get('/promedio-propio', [NotaController::class, 'promedio']);

        Route::get(
            '/asistencias-propias',
            [AsistenciaController::class, 'index']
        );

        Route::get(
            '/asistencias-propias/porcentaje',
            [AsistenciaController::class, 'porcentaje']
        );

        Route::get(
            '/historial-propio',
            [HistorialAcademicoController::class, 'index']
        );
    });

    /*
    |--------------------------------------------------------------------------
    | Profesor
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:profesor')->group(function () {
        Route::post('/notas', [NotaController::class, 'store']);
        Route::put('/notas/{nota}', [NotaController::class, 'update']);

        Route::post('/asistencias', [
            AsistenciaController::class,
            'store',
        ]);

        Route::put('/asistencias/{asistencia}', [
            AsistenciaController::class,
            'update',
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | Registro de alumnos
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:profesor,administrador')->group(function () {
        Route::get(
            '/materias/{materia}/alumnos',
            [RegistroAlumnoController::class, 'porMateria']
        );
    });

    /*
    |--------------------------------------------------------------------------
    | Administrador
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:administrador')->group(function () {
        Route::apiResource('usuarios', UsuarioController::class);

        Route::apiResource('materias', MateriaController::class);

        Route::get(
            '/boletines/{alumno}/pdf',
            [BoletinController::class, 'generar']
        );

        Route::get(
            '/reportes/notas',
            [ReporteController::class, 'notas']
        );

        Route::get(
            '/reportes/asistencias',
            [ReporteController::class, 'asistencias']
        );

        Route::get(
            '/legajos/{alumno}',
            [LegajoController::class, 'show']
        );

        Route::put(
            '/legajos/{alumno}',
            [LegajoController::class, 'update']
        );
    });
});

<?php

use App\Http\Controllers\Admin\AlumnosController;
use App\Http\Controllers\Admin\BoletinesController;
use App\Http\Controllers\Admin\LegajosController;
use App\Http\Controllers\Admin\MateriasController;
use App\Http\Controllers\Admin\PanelController;
use App\Http\Controllers\Admin\ReportesController;
use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\ProfesorController;
use App\Http\Controllers\SesionController;
use App\Http\Controllers\UsuarioAdminController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [SesionController::class, 'formulario'])->name('login');
    Route::post('/login', [SesionController::class, 'iniciar'])->name('login.store');
});

Route::post('/logout', [SesionController::class, 'cerrar'])->middleware('auth')->name('logout');

Route::prefix('alumno')
    ->name('alumno.')
    ->middleware(['auth', 'role:alumno'])
    ->group(function () {
        Route::get('/inicio', [AlumnoController::class, 'inicio'])
            ->name('inicio');

        Route::get('/notas', [AlumnoController::class, 'notas'])
            ->name('notas');

        Route::get('/asistencia', [AlumnoController::class, 'asistencia'])
            ->name('asistencia');

        Route::get('/historial', [AlumnoController::class, 'historial'])
            ->name('historial');
    });

Route::prefix('profesor')
    ->name('profesor.')
    ->middleware(['auth', 'role:profesor'])
    ->group(function () {
        Route::get('/inicio', [ProfesorController::class, 'inicio'])
            ->name('inicio');

        Route::get('/cargar-notas', [ProfesorController::class, 'cargarNotas'])
            ->name('cargar-notas');

        Route::post('/cargar-notas', [ProfesorController::class, 'guardarNota'])
            ->name('guardar-nota');

        Route::post('/cargar-notas/evaluaciones', [ProfesorController::class, 'guardarEvaluaciones'])
            ->name('guardar-evaluaciones');

        Route::post('/cargar-notas/resultados', [ProfesorController::class, 'guardarResultados'])
            ->name('guardar-resultados');

        Route::get('/cargar-asistencias', [ProfesorController::class, 'cargarAsistencias'])
            ->name('cargar-asistencias');

        Route::post('/cargar-asistencias', [ProfesorController::class, 'guardarAsistencias'])
            ->name('guardar-asistencias');

        Route::get('/historial-asistencias', [ProfesorController::class, 'historialAsistencias'])
            ->name('historial-asistencias');

        Route::get('/mis-alumnos', [ProfesorController::class, 'misAlumnos'])
            ->name('mis-alumnos');
    });

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:administrador'])
    ->group(function () {
        Route::get('/inicio', [PanelController::class, 'inicio'])
            ->name('inicio');

        Route::get('/usuarios', [UsuarioAdminController::class, 'index'])
            ->name('usuarios');

        Route::post('/usuarios', [UsuarioAdminController::class, 'store'])
            ->name('usuarios.guardar');

        Route::delete('/usuarios/{usuario}', [UsuarioAdminController::class, 'destroy'])
            ->name('usuarios.eliminar');

        Route::get('/boletines', [BoletinesController::class, 'index'])
            ->name('boletines');

        Route::post('/boletines/pdf', [BoletinesController::class, 'generar'])
            ->name('boletines.pdf');

        Route::get('/boletines/{boletin}/descargar', [BoletinesController::class, 'descargar'])
            ->name('boletines.descargar');

        Route::get('/boletines/{boletin}/ver', [BoletinesController::class, 'ver'])
            ->name('boletines.ver');

        Route::get('/reportes', [ReportesController::class, 'index'])
            ->name('reportes');

        Route::get('/reportes/exportar', [ReportesController::class, 'exportar'])
            ->name('reportes.exportar');

        Route::get('/materias', [MateriasController::class, 'index'])
            ->name('materias');

        Route::post('/materias', [MateriasController::class, 'guardar'])
            ->name('materias.guardar');

        Route::put('/materias/{materia}', [MateriasController::class, 'actualizar'])
            ->name('materias.actualizar');

        Route::delete('/materias/{materia}', [MateriasController::class, 'eliminar'])
            ->name('materias.eliminar');

        Route::get('/legajos', [LegajosController::class, 'index'])
            ->name('legajos');

        Route::get('/legajos/{alumno}/documentos', [LegajosController::class, 'documentos'])
            ->name('legajos.documentos');

        Route::put('/legajos/{alumno}/documentos', [LegajosController::class, 'guardarDocumentos'])
            ->name('legajos.documentos.guardar');

        Route::get('/alumnos', [AlumnosController::class, 'index'])
            ->name('alumnos');

        Route::put('/alumnos/{alumno}', [AlumnosController::class, 'actualizar'])
            ->name('alumnos.actualizar');

        Route::delete('/alumnos/{alumno}', [AlumnosController::class, 'eliminar'])
            ->name('alumnos.eliminar');
    });
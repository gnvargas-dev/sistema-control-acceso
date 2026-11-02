@extends('layouts.dashboard')

@section('content')
@php
    $iconos = [
        'alumnos' => '<path d="M2.5 9 12 4.5 21.5 9 12 13.5 2.5 9Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M6.5 11v4.2c0 1.1 2.5 2.3 5.5 2.3s5.5-1.2 5.5-2.3V11M21.5 9v6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'profes' => '<path d="M16 19v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 17.5V19M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM17.5 19v-1.5a3.5 3.5 0 0 0-2.3-3.29M14.5 5.1a3 3 0 0 1 0 5.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'promedio' => '<path d="m12 4 2.4 5 5.4.7-4 3.8 1 5.4L12 16.2 7.2 18.9l1-5.4-4-3.8 5.4-.7L12 4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
        'asistencia' => '<circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/><path d="m8.5 12.3 2.4 2.4 4.6-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'usuario' => '<path d="M15 19v-1.5a3.5 3.5 0 0 0-3.5-3.5h-4A3.5 3.5 0 0 0 4 17.5V19M9.5 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM18 8v6M21 11h-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'boletin' => '<path d="M7 3.5h7.5L19 8v12a.5.5 0 0 1-.5.5h-11A.5.5 0 0 1 7 20V4a.5.5 0 0 1 0-.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M14 3.5V8h4.5M9.5 12.5h5M9.5 15.5h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'reporte' => '<path d="M4 20V10M10 20V4M16 20v-7M4 20h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'flecha' => '<path d="M5 12h14m-5-5 5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    ];

    $cifras = [
        ['c' => 'azul', 'icono' => 'alumnos', 'titulo' => 'Total alumnos', 'valor' => number_format($totalAlumnos), 'pie' => 'Todos los cursos'],
        ['c' => 'verde', 'icono' => 'profes', 'titulo' => 'Total profesores', 'valor' => number_format($totalProfesores), 'pie' => 'Personal docente'],
        ['c' => 'violeta', 'icono' => 'promedio', 'titulo' => 'Promedio general', 'valor' => number_format($promedioGeneral, 2), 'pie' => 'Promedio del colegio'],
        ['c' => 'naranja', 'icono' => 'asistencia', 'titulo' => '% asistencia general', 'valor' => number_format($asistenciaGeneral, 1) . '%', 'pie' => 'Asistencia del colegio (mínimo exigido: ' . \App\Services\AsistenciaMetricasService::MINIMO . '%)'],
    ];

    $accesos = [
        ['c' => 'azul', 'icono' => 'usuario', 'titulo' => 'Nuevo usuario', 'texto' => 'Crear un nuevo usuario y asignar rol en el sistema.', 'boton' => 'Crear usuario', 'ruta' => 'admin.usuarios', 'clase' => ''],
        ['c' => 'verde', 'icono' => 'boletin', 'titulo' => 'Generar boletín', 'texto' => 'Generar y descargar boletines de alumnos o cursos.', 'boton' => 'Generar boletín', 'ruta' => 'admin.boletines', 'clase' => 'se-btn--exito'],
        ['c' => 'violeta', 'icono' => 'reporte', 'titulo' => 'Ver reportes', 'texto' => 'Ver estadísticas y reportes generales del sistema.', 'boton' => 'Ir a reportes', 'ruta' => 'admin.reportes', 'clase' => 'se-btn--violeta'],
    ];
@endphp

<div class="se-encabezado">
    <h1>Panel de control</h1>
    <p>Resumen general del sistema escolar</p>
</div>

<section class="se-stats">
    @foreach($cifras as $cifra)
        <article class="se-stat se-c-{{ $cifra['c'] }}">
            <span class="se-stat-icono">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">{!! $iconos[$cifra['icono']] !!}</svg>
            </span>
            <div class="se-stat-cuerpo">
                <span class="se-stat-titulo">{{ $cifra['titulo'] }}</span>
                <span class="se-stat-valor" data-se-contar>{{ $cifra['valor'] }}</span>
                <span class="se-stat-pie">{{ $cifra['pie'] }}</span>
            </div>
        </article>
    @endforeach
</section>

<h2 class="se-panel-cab se-ad-titulo-seccion">Accesos rápidos</h2>
<p class="se-ad-subtitulo-seccion">Accedé rápidamente a las funciones más utilizadas</p>

<section class="se-accesos">
    @foreach($accesos as $acc)
        <article class="se-acceso se-elevar se-c-{{ $acc['c'] }}">
            <span class="se-accion-icono">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">{!! $iconos[$acc['icono']] !!}</svg>
            </span>
            <h3>{{ $acc['titulo'] }}</h3>
            <p>{{ $acc['texto'] }}</p>
            <a class="se-btn {{ $acc['clase'] }}" href="{{ route($acc['ruta']) }}">
                {{ $acc['boton'] }}
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">{!! $iconos['flecha'] !!}</svg>
            </a>
        </article>
    @endforeach
</section>
@endsection

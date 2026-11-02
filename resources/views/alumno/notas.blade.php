@extends('layouts.alumno')

@section('title', 'Mis notas')

@section('content')
<header class="se-saludo">
    <div>
        <h1>Mis notas</h1>
        <p>Calificaciones por semestre: puntos de cada parcial y del examen final, total sobre 100, nota final y asistencia.</p>
    </div>
    <span class="se-badge se-badge--claro se-badge--sin-punto">Asistencia mínima: {{ \App\Services\AsistenciaMetricasService::MINIMO }}%</span>
</header>

@include('alumno.partials.notas-semestres', ['semestres' => $semestres])
@endsection

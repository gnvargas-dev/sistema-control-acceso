@extends('layouts.profesor')

@section('title', 'Mis alumnos')

@section('content')
<header class="se-saludo">
    <div>
        <h1>Mis alumnos</h1>
        <p>Alumnos de las materias que tenés asignadas.</p>
    </div>
    <span class="se-badge se-badge--claro se-badge--sin-punto">{{ $filas->count() }} {{ $filas->count() === 1 ? 'alumno' : 'alumnos' }}</span>
</header>

<section class="se-panel se-elevar se-c-azul">
    <div class="se-tabla-wrap" tabindex="0" role="region" aria-label="Alumnos asignados">
        <table class="se-tabla">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Legajo</th>
                    <th>Curso</th>
                    <th>Materia</th>
                </tr>
            </thead>
            <tbody>
                @forelse($filas as $fila)
                    <tr>
                        <td><span class="se-tabla-titulo">{{ $fila->alumno->usuario->nombre ?? 'Alumno #' . $fila->alumno->id }}</span></td>
                        <td>{{ $fila->alumno->legajo }}</td>
                        <td><span class="se-badge se-badge--info se-badge--sin-punto">{{ $fila->materia->curso->nombre }}</span></td>
                        <td>{{ $fila->materia->nombre }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="se-vacio">No hay alumnos asignados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

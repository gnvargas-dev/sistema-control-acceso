@extends('layouts.profesor')

@section('title', 'Historial de asistencias')

@section('content')
<header class="se-saludo">
    <div>
        <h1>Historial de asistencias</h1>
        <p>Mirá cómo viene la asistencia de tus materias y abrí cualquier día para verlo o editarlo.</p>
    </div>
    <span class="se-badge se-badge--claro se-badge--sin-punto">{{ $registros->count() }} {{ $registros->count() === 1 ? 'clase registrada' : 'clases registradas' }}</span>
</header>

<section class="se-panel se-elevar se-c-azul">
    <form method="GET" action="{{ route('profesor.historial-asistencias') }}" class="se-filtros">
        <div class="se-filtro">
            <label for="hist-materia">Materia</label>
            <select id="hist-materia" name="materia_id" onchange="this.form.submit()">
                <option value="">Todas mis materias</option>
                @foreach($materias as $item)
                    <option value="{{ $item->id }}" @selected($materiaId === $item->id)>
                        {{ $item->nombre }}
                        @if($item->curso)
                            ({{ $item->curso->anio ?? '' }}{{ isset($item->curso->anio) ? '° ' : '' }}{{ $item->curso->nombre }})
                        @endif
                    </option>
                @endforeach
            </select>
        </div>
    </form>
</section>

@include('partials.graficos-asistencia', ['graficos' => $graficos])

<section class="se-panel se-elevar se-c-azul">
    <div class="se-panel-cab">
        <h2>Clases registradas</h2>
    </div>

    <div class="se-tabla-wrap" tabindex="0" role="region" aria-label="Historial de asistencias">
        <table class="se-tabla">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Materia</th>
                    <th>Curso</th>
                    <th>Resultados</th>
                    <th><span class="se-ga-oculto">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse($registros as $registro)
                    <tr>
                        <td><span class="se-tabla-titulo">{{ $registro->fecha->format('d/m/Y') }}</span></td>
                        <td>{{ $registro->materia->nombre ?? 'Materia' }}</td>
                        <td>
                            @if($registro->materia?->curso)
                                <span class="se-badge se-badge--info se-badge--sin-punto">
                                    {{ $registro->materia->curso->anio ?? '' }}{{ isset($registro->materia->curso->anio) ? '° ' : '' }}{{ $registro->materia->curso->nombre }}
                                </span>
                            @else
                                &mdash;
                            @endif
                        </td>
                        <td>
                            <div class="se-ga-conteos">
                                <span class="se-ga-conteo se-c-verde" title="Presentes"><i class="se-ga-dot"></i>{{ $registro->presentes }}<span class="se-ga-oculto"> presentes</span></span>
                                <span class="se-ga-conteo se-c-rojo" title="Ausentes"><i class="se-ga-dot"></i>{{ $registro->ausentes }}<span class="se-ga-oculto"> ausentes</span></span>
                                <span class="se-ga-conteo se-c-naranja" title="Tardanzas"><i class="se-ga-dot"></i>{{ $registro->tardanzas }}<span class="se-ga-oculto"> tardanzas</span></span>
                            </div>
                            @if($registro->total > 0)
                                <div class="se-ga-apilada" aria-hidden="true">
                                    <i class="se-c-verde" style="--w: {{ round($registro->presentes / $registro->total * 100, 2) }}%"></i>
                                    <i class="se-c-naranja" style="--w: {{ round($registro->tardanzas / $registro->total * 100, 2) }}%"></i>
                                    <i class="se-c-rojo" style="--w: {{ round($registro->ausentes / $registro->total * 100, 2) }}%"></i>
                                </div>
                            @endif
                        </td>
                        <td>
                            <a
                                href="{{ route('profesor.cargar-asistencias', ['materia_id' => $registro->materia?->id, 'fecha' => $registro->fecha->toDateString()]) }}"
                                class="se-btn se-btn--mini se-btn--suave"
                            >
                                Ver / editar
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="se-vacio">Todavía no cargaste asistencias.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

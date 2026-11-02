@extends('layouts.alumno')

@section('title', 'Mi asistencia')

@section('content')
@php
    $minimo = (int) $graficos['minimo'];
    $porcentajeGeneral = (float) $resumenGeneral['porcentaje'];
    $colorGeneral = $resumenGeneral['total'] === 0 ? 'se-c-azul' : ($porcentajeGeneral >= $minimo ? 'se-c-verde' : 'se-c-rojo');

    /* Nivel de cada materia (NivelAsistencia) -> componentes del tema */
    $insigniaNivel = [
        'excelente' => 'se-badge--aprobado',
        'bueno' => 'se-badge--info',
        'regular' => 'se-badge--pendiente',
        'malo' => 'se-badge--reprobado',
    ];
    $colorNivel = [
        'excelente' => 'se-c-verde',
        'bueno' => 'se-c-azul',
        'regular' => 'se-c-naranja',
        'malo' => 'se-c-rojo',
    ];

    $diasSemana = ['D', 'L', 'M', 'X', 'J', 'V', 'S'];
    $cantidadInasistencias = $detalleInasistencias->count();
@endphp

<header class="se-saludo">
    <div>
        <h1>Mi asistencia</h1>
        <p>Consultá tu asistencia por materia y período académico.</p>
    </div>
    @if($resumenGeneral['total'] > 0)
        <span class="se-badge se-badge--claro">{{ $resumenGeneral['estado']['label'] }}</span>
    @endif
</header>

<section class="se-stats" aria-label="Resumen de asistencia">
    <article class="se-stat {{ $colorGeneral }}">
        <span class="se-stat-icono">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M8 6h8a2 2 0 0 1 2 2v11l-2-1.3L14 19l-2-1.3L10 19l-2-1.3L6 19V8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                <path d="m9.5 12 1.8 1.8L14.5 10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>
        <div class="se-stat-cuerpo">
            <span class="se-stat-titulo">Asistencia general</span>
            <span class="se-stat-valor" data-se-contar>{{ $resumenGeneral['porcentaje'] }}%</span>
            <span class="se-stat-pie">{{ $resumenGeneral['asistidas'] }} de {{ $resumenGeneral['total'] }} clases</span>
        </div>
    </article>

    <article class="se-stat se-c-azul">
        <span class="se-stat-icono">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M7 3.5h7.5L19 8v12a.5.5 0 0 1-.5.5h-11A.5.5 0 0 1 7 20V4a.5.5 0 0 1 0-.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                <path d="M14 3.5V8h4.5M9.5 12.5h5M9.5 15.5h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
        </span>
        <div class="se-stat-cuerpo">
            <span class="se-stat-titulo">Clases asistidas</span>
            <span class="se-stat-valor" data-se-contar>{{ $resumenGeneral['asistidas'] }}</span>
            <span class="se-stat-pie">de {{ $resumenGeneral['total'] }} clases</span>
        </div>
    </article>

    <article class="se-stat se-c-rojo">
        <span class="se-stat-icono">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M7 3.5h7.5L19 8v12a.5.5 0 0 1-.5.5h-11A.5.5 0 0 1 7 20V4a.5.5 0 0 1 0-.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                <path d="m10 12.5 4 4m0-4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
        </span>
        <div class="se-stat-cuerpo">
            <span class="se-stat-titulo">Inasistencias</span>
            <span class="se-stat-valor" data-se-contar>{{ $resumenGeneral['inasistencias'] }}</span>
            <span class="se-stat-pie">clases</span>
        </div>
    </article>

    <article class="se-stat se-c-violeta">
        <span class="se-stat-icono">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M4.5 5.2A2.2 2.2 0 0 1 6.7 3h10.8v16.5H6.7A2.2 2.2 0 0 0 4.5 21.7V5.2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                <path d="M4.5 18.2A2.2 2.2 0 0 1 6.7 16h10.8" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
            </svg>
        </span>
        <div class="se-stat-cuerpo">
            <span class="se-stat-titulo">Materias</span>
            <span class="se-stat-valor" data-se-contar>{{ $totalMaterias }}</span>
            <span class="se-stat-pie">materias cursadas</span>
        </div>
    </article>
</section>

@include('partials.graficos-asistencia', ['graficos' => $graficos])

    <section class="se-panel se-elevar se-c-azul">
        <div class="se-panel-cab">
            <h2>Asistencia por materia</h2>
        </div>

        <div class="se-tabla-wrap" tabindex="0" role="region" aria-label="Asistencia por materia">
            <table class="se-tabla">
                <thead>
                    <tr>
                        <th>Materia</th>
                        <th>Clases</th>
                        <th>Asistidas</th>
                        <th>Inasistencias</th>
                        <th>Porcentaje</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materiasAsistencia as $item)
                        <tr>
                            <td><span class="se-tabla-titulo">{{ $item->materia->nombre }}</span></td>
                            <td>{{ $item->clases }}</td>
                            <td>{{ $item->asistidas }}</td>
                            <td>{{ $item->inasistencias }}</td>
                            <td>
                                @if($item->porcentaje !== null)
                                    <span class="se-progreso {{ $colorNivel[$item->estado['clase']] ?? 'se-c-azul' }}">
                                        <span class="se-progreso-barra"><i style="--w: {{ max(0, min(100, (float) $item->porcentaje)) }}%"></i></span>
                                        <span class="se-progreso-num">{{ number_format($item->porcentaje, 2) }}%</span>
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($item->estado)
                                    <span class="se-badge {{ $insigniaNivel[$item->estado['clase']] ?? 'se-badge--neutro' }}">{{ $item->estado['label'] }}</span>
                                @else
                                    <span class="se-badge se-badge--neutro">Sin datos</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="se-vacio">No hay materias asignadas a tu curso todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="se-ga-pie">El porcentaje de asistencia mínimo requerido para aprobar es <strong>{{ $minimo }}%</strong>.</p>
    </section>

<div class="se-columnas">
    <div>
        <section class="se-panel se-elevar se-c-verde">
            <div class="se-panel-cab">
                <h2>Asistencia por fecha</h2>
            </div>

            @if($diasConClase->isNotEmpty())
                <div class="se-dias">
                    @foreach($diasConClase as $dia)
                        <div class="se-dia {{ $dia->estado === 'asistio' ? 'se-c-verde' : 'se-c-rojo' }}" title="{{ $dia->fecha->format('d/m/Y') }}">
                            <span class="se-dia-semana">{{ $diasSemana[(int) $dia->fecha->format('w')] }}</span>
                            <span class="se-dia-numero">{{ $dia->fecha->format('d') }}</span>
                            <span class="se-dia-icono" role="img" aria-label="{{ $dia->estado === 'asistio' ? 'Asististe' : 'Inasistencia' }}">
                                @if($dia->estado === 'asistio')
                                    <svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m3 8.5 3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                @else
                                    <svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m4 4 8 8m0-8-8 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="se-dias-leyenda">
                    <span class="se-c-verde"><i class="se-ga-dot"></i> Asististe</span>
                    <span class="se-c-rojo"><i class="se-ga-dot"></i> Inasistencia</span>
                </div>
            @else
                <p class="se-vacio">Todavía no hay clases registradas.</p>
            @endif
        </section>
    </div>

    <div>
        <section class="se-panel se-elevar se-c-rojo">
            <div class="se-panel-cab">
                <h2>Detalle de inasistencias</h2>
                @if($cantidadInasistencias > 0)
                    <span class="se-badge se-badge--reprobado se-badge--sin-punto">{{ $cantidadInasistencias }} {{ $cantidadInasistencias === 1 ? 'clase' : 'clases' }}</span>
                @endif
            </div>

            @if($cantidadInasistencias === 0)
                <p class="se-vacio">No tenés inasistencias registradas.</p>
            @else
                <ul class="se-lista">
                    @foreach($detalleInasistencias->take(5) as $inasistencia)
                        <li class="se-lista-item">
                            <div>
                                <strong>{{ $inasistencia->materia->nombre ?? 'Materia' }}</strong>
                                <p class="se-lista-detalle">{{ $inasistencia->fecha->format('d/m/Y') }}</p>
                            </div>
                            <span class="se-badge se-badge--reprobado">Inasistencia</span>
                        </li>
                    @endforeach
                </ul>

                @if($cantidadInasistencias > 5)
                    <ul class="se-lista" id="masis-inasistencias-extra" style="display:none">
                        @foreach($detalleInasistencias->slice(5) as $inasistencia)
                            <li class="se-lista-item">
                                <div>
                                    <strong>{{ $inasistencia->materia->nombre ?? 'Materia' }}</strong>
                                    <p class="se-lista-detalle">{{ $inasistencia->fecha->format('d/m/Y') }}</p>
                                </div>
                                <span class="se-badge se-badge--reprobado">Inasistencia</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="se-mas">
                        <button type="button" class="se-btn se-btn--suave se-btn--mini" onclick="mostrarTodasInasistencias(this)">
                            Ver todas mis inasistencias
                        </button>
                    </div>
                @endif
            @endif
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function mostrarTodasInasistencias(boton) {
        const extra = document.getElementById('masis-inasistencias-extra');
        extra.style.display = 'block';
        boton.style.display = 'none';
    }
</script>
@endpush

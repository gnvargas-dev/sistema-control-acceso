@extends('layouts.alumno')

@section('content')
@php
    $aprob = \App\Valores\Calificacion::APROBACION;
    $minAsistencia = \App\Services\AsistenciaMetricasService::MINIMO; // % mínimo de asistencia para no mostrar alerta

    $resumen = collect($materiasResumen);
    $asis = max(0, min(100, (float) $asistenciaGeneral));
    $conAsistencia = $resumen->whereNotNull('asistencia')->count() > 0;

    $enRiesgo = $resumen->filter(fn ($i) => $i->promedio !== null && $i->promedio < $aprob);
    $sinNotas = $resumen->filter(fn ($i) => $i->promedio === null);
    $bajaAsistencia = $resumen->filter(fn ($i) => $i->asistencia !== null && $i->asistencia < $minAsistencia);
    $nombres = fn ($col) => $col->map(fn ($i) => $i->materia->nombre)->implode(', ');

    $colorAsistencia = ! $conAsistencia ? 'se-c-azul' : ($asis >= $minAsistencia ? 'se-c-verde' : 'se-c-rojo');

    $alertas = [];
    if ($conAsistencia && $asis < $minAsistencia) {
        $alertas[] = ['rojo', 'Asistencia por debajo del mínimo', 'Tu asistencia general es ' . $asistenciaGeneral . '% y el mínimo esperado es ' . $minAsistencia . '%.', 'm12 3 9.5 17h-19L12 3ZM12 10v3.5M12 16.5v.01'];
    }
    if ($bajaAsistencia->count() > 0) {
        $alertas[] = ['naranja', 'Asistencia baja en materias', $nombres($bajaAsistencia), 'M12 7v5l3 2M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z'];
    }
    if ($enRiesgo->count() > 0) {
        $alertas[] = ['rojo', 'Materias en riesgo', 'Promedio por debajo de ' . (int) $aprob . ' en: ' . $nombres($enRiesgo), 'm12 3 9.5 17h-19L12 3ZM12 10v3.5M12 16.5v.01'];
    }
    if ($sinNotas->count() > 0) {
        $alertas[] = ['azul', 'Notas pendientes', 'Todavía no tenés notas en ' . $sinNotas->count() . ' ' . ($sinNotas->count() === 1 ? 'materia' : 'materias') . '.', 'M12 8v4.5M12 16v.01M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z'];
    }
    if (count($alertas) === 0) {
        $alertas[] = ['verde', 'Todo en orden', 'No tenés alertas por ahora. ¡Seguí así!', 'M5 12.5l4.5 4.5L19 7'];
    }

    $itemNotas = collect(auth()->user()->rolObjeto()->menu())
        ->first(fn ($i) => str_contains(strtolower($i->ruta), 'nota'));
@endphp

<div class="se-alumno-inicio">
    <header class="se-saludo">
        <div>
            <h1>¡Hola, {{ $alumno->usuario->nombre ?? 'Alumno' }}!</h1>
            <p>
                @if($alumno->curso)
                    {{ $alumno->curso->anio ?? '' }}{{ isset($alumno->curso->anio) ? '° ' : '' }}{{ $alumno->curso->nombre }} ·
                @endif
                Legajo: {{ $alumno->legajo }}
            </p>
        </div>
        <span class="se-badge se-badge--claro">{{ $alumno->activo ? 'Regular' : 'Inactivo' }}</span>
    </header>

    <section class="se-stats" aria-label="Resumen">
        <article class="se-stat se-c-azul">
            <span class="se-stat-icono">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 20V10M10 20V4M16 20v-7M4 20h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <div class="se-stat-cuerpo">
                <span class="se-stat-titulo">Promedio general</span>
                <span class="se-stat-valor" data-se-contar>{{ $promedioGeneral > 0 ? number_format($promedioGeneral, 2) : '—' }}</span>
                <span class="se-stat-pie">Sobre 5 puntos</span>
            </div>
        </article>

        <article class="se-stat se-c-verde">
            <span class="se-stat-icono">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M8 6h8a2 2 0 0 1 2 2v11l-2-1.3L14 19l-2-1.3L10 19l-2-1.3L6 19V8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                    <path d="m9.5 12 1.8 1.8L14.5 10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <div class="se-stat-cuerpo">
                <span class="se-stat-titulo">Asistencia general</span>
                <span class="se-stat-valor" data-se-contar>{{ $asistenciaGeneral }}%</span>
                <span class="se-stat-pie">De las clases registradas</span>
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
                <span class="se-stat-titulo">Materias aprobadas</span>
                <span class="se-stat-valor">{{ $materiasAprobadas }}/{{ $totalMaterias }}</span>
                <span class="se-stat-pie">Promedio igual o mayor a {{ (int) $aprob }}</span>
            </div>
        </article>

        <article class="se-stat se-c-naranja">
            <span class="se-stat-icono">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 21s-7.5-4.6-9.7-9.6C.7 7.4 3 4 6.6 4c2 0 3.5 1 5.4 3.1C13.9 5 15.4 4 17.4 4 21 4 23.3 7.4 21.7 11.4 19.5 16.4 12 21 12 21Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                </svg>
            </span>
            <div class="se-stat-cuerpo">
                <span class="se-stat-titulo">Estado académico</span>
                <span class="se-stat-valor se-stat-valor--sm">{{ $alumno->activo ? 'Regular' : 'Inactivo' }}</span>
                <span class="se-stat-pie">Estado actual en el sistema</span>
            </div>
        </article>
    </section>

    <div class="se-columnas">
        <div>
            <section class="se-panel">
                <div class="se-panel-cab">
                    <h2>Alertas</h2>
                    <span class="se-badge {{ $alertas[0][0] === 'verde' ? 'se-badge--aprobado' : 'se-badge--pendiente' }}">
                        {{ $alertas[0][0] === 'verde' ? 'Sin novedades' : count($alertas) . ' ' . (count($alertas) === 1 ? 'aviso' : 'avisos') }}
                    </span>
                </div>

                <div class="se-alertas">
                    @foreach($alertas as $alerta)
                        <div class="se-alerta se-c-{{ $alerta[0] }}" style="--i: {{ $loop->index }}">
                            <span class="se-alerta-icono">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="{{ $alerta[3] }}" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <div>
                                <strong>{{ $alerta[1] }}</strong>
                                <span>{{ $alerta[2] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="se-panel">
                <div class="se-panel-cab">
                    <h2>Mis materias</h2>
                    @if($itemNotas)
                        <a href="{{ route($itemNotas->ruta) }}" class="se-btn se-btn--mini se-btn--suave">Ver mis notas &rarr;</a>
                    @endif
                </div>

                <div class="se-tabla-wrap" tabindex="0" role="region" aria-label="Mis materias">
                    <table class="se-tabla">
                        <thead>
                            <tr>
                                <th>Materia</th>
                                <th>Asistencia</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($materiasResumen as $item)
                                <tr>
                                    <td>
                                        <span class="se-tabla-titulo">{{ $item->materia->nombre }}</span>
                                        <span class="se-tabla-sub">{{ $item->profesor ?? 'Sin profesor asignado' }}</span>
                                    </td>
                                    <td>
                                        @if($item->asistencia !== null)
                                            <div class="se-progreso {{ $item->asistencia >= $minAsistencia ? 'se-c-verde' : 'se-c-naranja' }}">
                                                <span class="se-progreso-barra"><i style="--w: {{ max(0, min(100, (float) $item->asistencia)) }}%"></i></span>
                                                <span class="se-progreso-num">{{ $item->asistencia }}%</span>
                                            </div>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->promedio === null)
                                            <span class="se-badge se-badge--neutro">Sin notas</span>
                                        @elseif($item->promedio >= $aprob)
                                            <span class="se-badge se-badge--aprobado">Aprobando</span>
                                        @else
                                            <span class="se-badge se-badge--reprobado">En riesgo</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="se-vacio">No hay materias asignadas a tu curso todavía.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="se-panel">
                <div class="se-panel-cab">
                    <h2>Historial académico reciente</h2>
                    <span class="se-badge se-badge--info se-badge--sin-punto">Actividad reciente</span>
                </div>

                @forelse($historial as $registro)
                    @if($loop->first)<ul class="se-hitos">@endif
                        <li class="se-hito">
                            <div class="se-hito-cab">
                                <span class="se-badge se-badge--info se-badge--sin-punto">{{ $registro->periodo }}</span>
                                <span class="se-tiempo">{{ $registro->created_at?->format('d/m/Y') }}</span>
                            </div>
                            <p>{{ $registro->detalle }}</p>
                        </li>
                    @if($loop->last)</ul>@endif
                @empty
                    <p class="se-vacio">Todavía no hay registros en tu historial académico.</p>
                @endforelse
            </section>
        </div>

        <div>
            <section class="se-panel">
                <div class="se-panel-cab">
                    <h2>Asistencia actual</h2>
                </div>

                <div class="se-anillo-caja {{ $colorAsistencia }}">
                    <div class="se-anillo" role="img" aria-label="Asistencia general {{ $asistenciaGeneral }}%">
                        <svg viewBox="0 0 100 100" aria-hidden="true">
                            <circle class="se-anillo-fondo" cx="50" cy="50" r="42" />
                            <circle class="se-anillo-valor" cx="50" cy="50" r="42" style="--p: {{ $asis / 100 }}" />
                        </svg>
                        <span class="se-anillo-texto" aria-hidden="true">{{ $asistenciaGeneral }}%</span>
                    </div>
                    <div class="se-anillo-info">
                        <strong>{{ ! $conAsistencia ? 'Sin clases registradas' : ($asis >= $minAsistencia ? 'Vas muy bien' : 'Necesitás mejorar') }}</strong>
                        Mínimo esperado: {{ $minAsistencia }}%.
                    </div>
                </div>
            </section>

            <section class="se-panel">
                <div class="se-panel-cab">
                    <h2>Resumen académico</h2>
                </div>

                <ul class="se-lista">
                    <li class="se-lista-item"><span>Promedio general</span><strong>{{ $promedioGeneral > 0 ? number_format($promedioGeneral, 2) : '—' }}</strong></li>
                    <li class="se-lista-item"><span>Materias aprobadas</span><strong>{{ $materiasAprobadas }} / {{ $totalMaterias }}</strong></li>
                    <li class="se-lista-item"><span>Materias en curso</span><strong>{{ $totalMaterias }}</strong></li>
                    <li class="se-lista-item"><span>Asistencia general</span><strong>{{ $asistenciaGeneral }}%</strong></li>
                </ul>
            </section>

            <section class="se-panel">
                <div class="se-panel-cab">
                    <h2>Períodos con actividad</h2>
                </div>

                <div class="se-panel-cuerpo">
                    @forelse($periodos as $periodo)
                        <span class="se-badge se-badge--info se-badge--sin-punto">{{ $periodo }}</span>
                    @empty
                        <p class="se-vacio">Todavía no tenés notas cargadas en ningún período.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>
@endsection

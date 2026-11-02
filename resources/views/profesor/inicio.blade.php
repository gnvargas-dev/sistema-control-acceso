@extends('layouts.profesor')

@section('content')
<div class="se-profesor-inicio">
    @if(session('success'))
        <div class="se-aviso se-aviso--exito" role="status">
            {{ session('success') }}
        </div>
    @endif

    <section class="se-stats" aria-label="Resumen">
        <article class="se-stat se-c-azul">
            <span class="se-stat-icono">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4.5 5.2A2.2 2.2 0 0 1 6.7 3h10.8v16.5H6.7A2.2 2.2 0 0 0 4.5 21.7V5.2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                    <path d="M4.5 18.2A2.2 2.2 0 0 1 6.7 16h10.8" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                </svg>
            </span>
            <div class="se-stat-cuerpo">
                <span class="se-stat-titulo">Mis materias asignadas</span>
                <span class="se-stat-valor" data-se-contar>{{ $materias->count() }}</span>
                <span class="se-stat-pie">Materias activas</span>
            </div>
        </article>

        <article class="se-stat se-c-verde">
            <span class="se-stat-icono">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M3 8.5 12 4l9 4.5L12 13 3 8.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                    <path d="M6 10.2V15c0 1.6 2.7 3.5 6 3.5s6-1.9 6-3.5v-4.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg>
            </span>
            <div class="se-stat-cuerpo">
                <span class="se-stat-titulo">Cursos a cargo</span>
                <span class="se-stat-valor" data-se-contar>{{ $cursosACargo }}</span>
                <span class="se-stat-pie">Cursos diferentes</span>
            </div>
        </article>

        <article class="se-stat se-c-violeta">
            <span class="se-stat-icono">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M16 19v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 17.5V19M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM17.5 19v-1.5a3.5 3.5 0 0 0-2.3-3.29M14.5 5.1a3 3 0 0 1 0 5.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <div class="se-stat-cuerpo">
                <span class="se-stat-titulo">Alumnos</span>
                <span class="se-stat-valor" data-se-contar>{{ $alumnosTotal }}</span>
                <span class="se-stat-pie">Total en mis materias</span>
            </div>
        </article>

        <article class="se-stat se-c-naranja">
            <span class="se-stat-icono">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M7 3.5h7.5L19 8v12a.5.5 0 0 1-.5.5h-11A.5.5 0 0 1 7 20V4a.5.5 0 0 1 0-.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                    <path d="M14 3.5V8h4.5M9.5 12.5h5M9.5 15.5h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg>
            </span>
            <div class="se-stat-cuerpo">
                <span class="se-stat-titulo">Pendientes por cargar</span>
                <span class="se-stat-valor" data-se-contar>{{ $totalPendientes }}</span>
                <span class="se-stat-pie">Notas / Asistencias de hoy</span>
            </div>
        </article>
    </section>

    <div class="se-columnas">
        <div>
            <section class="se-panel">
                <div class="se-panel-cab">
                    <h2>Mis materias asignadas</h2>
                    <a href="{{ route('profesor.mis-alumnos') }}" class="se-btn se-btn--mini se-btn--suave">Ver todas</a>
                </div>

                <div class="se-tabla-wrap" tabindex="0" role="region" aria-label="Mis materias asignadas">
                    <table class="se-tabla">
                        <thead>
                            <tr>
                                <th>Materia</th>
                                <th>Curso</th>
                                <th class="se-centro">Alumnos</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($materias as $materia)
                                <tr>
                                    <td><span class="se-tabla-titulo">{{ $materia->nombre }}</span></td>
                                    <td>
                                        <span class="se-badge se-badge--info se-badge--sin-punto">
                                            @if($materia->curso)
                                                {{ $materia->curso->anio ?? '' }}{{ isset($materia->curso->anio) ? '° ' : '' }}{{ $materia->curso->nombre }}
                                            @else
                                                Sin curso
                                            @endif
                                        </span>
                                    </td>
                                    <td class="se-centro">{{ $alumnosPorMateria[$materia->id]->count() }}</td>
                                    <td>
                                        <a href="{{ route('profesor.cargar-asistencias', ['materia_id' => $materia->id]) }}" class="se-btn se-btn--mini">
                                            Gestionar &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="se-vacio">Todavía no tenés materias asignadas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="se-panel">
                <div class="se-panel-cab">
                    <h2>Actividad reciente</h2>
                </div>

                @forelse($actividadReciente as $actividad)
                    @if($loop->first)<ul class="se-lista">@endif
                        <li class="se-lista-item">
                            <span>{{ $actividad->texto }}</span>
                            <span class="se-tiempo">{{ $actividad->fecha?->diffForHumans() }}</span>
                        </li>
                    @if($loop->last)</ul>@endif
                @empty
                    <p class="se-vacio">Todavía no registraste notas ni asistencias.</p>
                @endforelse
            </section>
        </div>

        <div>
            <section class="se-panel">
                <div class="se-panel-cab">
                    <h2>Acciones rápidas</h2>
                </div>

                <div class="se-acciones">
                    <a href="{{ route('profesor.cargar-notas') }}" class="se-accion se-c-azul">
                        <span class="se-accion-icono">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="m4 16.5-.8 3.8 3.8-.8L18.5 8a2.8 2.8 0 0 0-4-4L3 16.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <span>
                            <span class="se-accion-titulo">Cargar notas</span>
                            <span class="se-accion-sub">Registrar calificaciones</span>
                        </span>
                    </a>

                    <a href="{{ route('profesor.cargar-asistencias') }}" class="se-accion se-c-verde">
                        <span class="se-accion-icono">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 20V10M10 20V4M16 20v-7M4 20h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <span>
                            <span class="se-accion-titulo">Cargar asistencias</span>
                            <span class="se-accion-sub">Registrar asistencia</span>
                        </span>
                    </a>

                    <a href="{{ route('profesor.mis-alumnos') }}" class="se-accion se-c-violeta">
                        <span class="se-accion-icono">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M16 19v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 17.5V19M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <span>
                            <span class="se-accion-titulo">Ver alumnos</span>
                            <span class="se-accion-sub">Ver lista de alumnos</span>
                        </span>
                    </a>
                </div>
            </section>

            <section class="se-panel">
                <div class="se-panel-cab">
                    <h2>Pendientes por materia</h2>
                </div>

                @forelse($pendientesPorMateria as $pendiente)
                    @if($loop->first)<ul class="se-lista">@endif
                        <li class="se-lista-item">
                            <div>
                                <strong>
                                    {{ $pendiente->materia->nombre }}
                                    @if(isset($pendiente->materia->curso))
                                        — {{ $pendiente->materia->curso->anio ?? '' }}{{ isset($pendiente->materia->curso->anio) ? '° ' : '' }}{{ $pendiente->materia->curso->nombre }}
                                    @endif
                                </strong>
                                <p class="se-lista-detalle">
                                    @if($pendiente->sin_notas > 0)
                                        {{ $pendiente->sin_notas }} sin notas cargadas
                                    @endif
                                    @if($pendiente->sin_asistencia_hoy > 0)
                                        <span class="se-badge se-badge--pendiente">{{ $pendiente->sin_asistencia_hoy }} sin asistencia de hoy</span>
                                    @endif
                                    @if($pendiente->sin_notas === 0 && $pendiente->sin_asistencia_hoy === 0)
                                        <span class="se-badge se-badge--aprobado">Todo al día</span>
                                    @endif
                                </p>
                            </div>
                        </li>
                    @if($loop->last)</ul>@endif
                @empty
                    <p class="se-vacio">No hay materias asignadas.</p>
                @endforelse
            </section>
        </div>
    </div>
</div>
@endsection

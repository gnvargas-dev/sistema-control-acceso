@extends('layouts.dashboard')

@section('title', 'Alumnos')

@section('content')
<header class="se-saludo se-ad-cab">
    <div>
        <h1>Alumnos</h1>
        <p>Gestiona la información de los alumnos del sistema escolar</p>
    </div>

    <a class="se-btn" href="{{ route('admin.usuarios', ['rol' => 'alumno']) }}#nuevo-usuario">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" /></svg>
        Nuevo alumno
    </a>
</header>

@if(session('success'))
    <div class="se-aviso se-aviso--exito" role="status">{{ session('success') }}</div>
@endif

<section class="se-stats" aria-label="Resumen de alumnos">
        <article class="se-stat se-c-azul">
            <span class="se-stat-icono">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M16 19v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 17.5V19M20 19v-1.5a3.5 3.5 0 0 0-2.5-3.36M14.5 4.6a3.5 3.5 0 0 1 0 6.8M9.5 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <div class="se-stat-cuerpo">
                <span class="se-stat-titulo">Total alumnos</span>
                <span class="se-stat-valor">{{ $estadisticas['total'] }}</span>
                <span class="se-stat-pie">Todos los cursos</span>
            </div>
        </article>
        <article class="se-stat se-c-verde">
            <span class="se-stat-icono">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 3 2 8l10 5 10-5-10-5ZM6 12v5c0 1.1 2.7 3 6 3s6-1.9 6-3v-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <div class="se-stat-cuerpo">
                <span class="se-stat-titulo">Alumnos activos</span>
                <span class="se-stat-valor">{{ $estadisticas['activos'] }}</span>
                <span class="se-stat-pie">{{ $estadisticas['total'] > 0 ? round(($estadisticas['activos'] / $estadisticas['total']) * 100, 1) : 0 }}% del total</span>
            </div>
        </article>
        <article class="se-stat se-c-rojo">
            <span class="se-stat-icono">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 4l16 16M9.5 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM16 19v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 17.5V19" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <div class="se-stat-cuerpo">
                <span class="se-stat-titulo">Alumnos inactivos</span>
                <span class="se-stat-valor">{{ $estadisticas['inactivos'] }}</span>
                <span class="se-stat-pie">{{ $estadisticas['total'] > 0 ? round(($estadisticas['inactivos'] / $estadisticas['total']) * 100, 1) : 0 }}% del total</span>
            </div>
        </article>
        <article class="se-stat se-c-violeta">
            <span class="se-stat-icono">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                </svg>
            </span>
            <div class="se-stat-cuerpo">
                <span class="se-stat-titulo">Nuevos este mes</span>
                <span class="se-stat-valor">{{ $estadisticas['nuevos_este_mes'] }}</span>
                <span class="se-stat-pie">{{ $estadisticas['total'] > 0 ? round(($estadisticas['nuevos_este_mes'] / $estadisticas['total']) * 100, 1) : 0 }}% del total</span>
            </div>
        </article>
</section>

<section class="se-panel">
    <form method="GET" action="{{ route('admin.alumnos') }}" class="se-ad-barra">
        <div class="se-ad-buscar">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="10.8" cy="10.8" r="6.3" stroke="currentColor" stroke-width="1.8" />
                <path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>

            <input
                class="se-ad-input"
                type="search"
                name="q"
                value="{{ request('q') }}"
                placeholder="Buscar alumno..."
                autocomplete="off"
                aria-label="Buscar alumno"
            >
        </div>

        <select class="se-ad-input se-ad-select" name="curso_id" aria-label="Filtrar por curso" onchange="this.form.submit()">
            <option value="">Todos los cursos</option>

            @foreach($cursos as $curso)
                <option value="{{ $curso->id }}" @selected((string) request('curso_id') === (string) $curso->id)>
                    {{ $curso->anio ?? '' }}{{ isset($curso->anio) ? '° ' : '' }}{{ $curso->nombre }}
                </option>
            @endforeach
        </select>

        <select class="se-ad-input se-ad-select" name="estado" aria-label="Filtrar por estado" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            <option value="activo" @selected(request('estado') === 'activo')>Activo</option>
            <option value="inactivo" @selected(request('estado') === 'inactivo')>Inactivo</option>
        </select>

        <button class="se-btn" type="submit">Buscar</button>

        @if(request()->anyFilled(['q', 'curso_id', 'estado']))
            <a class="se-btn se-btn--suave" href="{{ route('admin.alumnos') }}">Limpiar filtros</a>
        @endif
    </form>

    <div class="se-tabla-wrap" tabindex="0" role="region" aria-label="Listado de alumnos">
        <table class="se-tabla">
            <thead>
                <tr>
                    <th><span class="se-sr">Foto</span></th>
                    <th>Nombre completo</th>
                    <th>Curso</th>
                    <th>Fecha de nacimiento</th>
                    <th>DNI</th>
                    <th>Teléfono</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse($alumnos as $alumno)
                    <tr>
                        <td>
                            <span class="se-ad-avatar">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle cx="12" cy="8.5" r="3.3" stroke="currentColor" stroke-width="1.7" />
                                    <path d="M5 19.5c.8-3.2 3.6-5 7-5s6.2 1.8 7 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                                </svg>
                            </span>
                        </td>

                        <td><span class="se-tabla-titulo">{{ $alumno->usuario->nombre ?? 'Alumno sin nombre' }}</span></td>

                        <td>
                            <span class="se-badge se-badge--info se-badge--sin-punto">
                                @if($alumno->curso)
                                    {{ $alumno->curso->anio ?? '' }}{{ isset($alumno->curso->anio) ? '° ' : '' }}{{ $alumno->curso->nombre }}
                                @else
                                    Sin curso
                                @endif
                            </span>
                        </td>

                        <td>{{ $alumno->fecha_nacimiento?->format('d/m/Y') ?? '—' }}</td>

                        <td>{{ $alumno->usuario->cedula ?? '—' }}</td>

                        <td>{{ $alumno->telefono ?? '—' }}</td>

                        <td>
                            <span class="se-badge {{ $alumno->activo ? 'se-badge--aprobado' : 'se-badge--neutro' }}">
                                {{ $alumno->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>

                        <td>
                            @php
                                $alumnoVer = [
                                    'nombre' => $alumno->usuario->nombre ?? 'Alumno sin nombre',
                                    'curso' => $alumno->curso ? (($alumno->curso->anio ?? '') . '° ' . $alumno->curso->nombre) : 'Sin curso',
                                    'cedula' => $alumno->usuario->cedula ?? '—',
                                    'legajo' => $alumno->legajo ?? '—',
                                    'fecha_nacimiento' => optional($alumno->fecha_nacimiento)->format('d/m/Y') ?? '—',
                                    'telefono' => $alumno->telefono ?? '—',
                                    'estado' => $alumno->activo ? 'Activo' : 'Inactivo',
                                ];

                                $alumnoEditar = [
                                    'id' => $alumno->id,
                                    'nombre' => $alumno->usuario->nombre ?? 'Alumno sin nombre',
                                    'curso_id' => $alumno->curso_id,
                                    'legajo' => $alumno->legajo,
                                    'fecha_nacimiento' => optional($alumno->fecha_nacimiento)->format('Y-m-d'),
                                    'telefono' => $alumno->telefono,
                                    'activo' => $alumno->activo ? 1 : 0,
                                ];
                            @endphp

                            <div class="se-ad-acciones">
                                <button
                                    type="button"
                                    class="se-ad-icono js-ver-alumno"
                                    title="Ver alumno"
                                    aria-label="Ver alumno"
                                    data-alumno='{{ json_encode($alumnoVer) }}'
                                >
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                                        <circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.7" />
                                    </svg>
                                </button>

                                <button
                                    type="button"
                                    class="se-ad-icono js-editar-alumno"
                                    title="Editar alumno"
                                    aria-label="Editar alumno"
                                    data-alumno='{{ json_encode($alumnoEditar) }}'
                                >
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="m4 16.5-.8 3.8 3.8-.8L18.5 8a2.8 2.8 0 0 0-4-4L3 16.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                                        <path d="m13.5 5.5 5 5" stroke="currentColor" stroke-width="1.7" />
                                    </svg>
                                </button>

                                <form
                                    method="POST"
                                    action="{{ route('admin.alumnos.eliminar', $alumno) }}"
                                    data-se-confirmar="¿Eliminar a este alumno? Se perderá su historial académico, notas y asistencias."
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        class="se-ad-icono se-ad-icono--peligro"
                                        type="submit"
                                        title="Eliminar alumno"
                                        aria-label="Eliminar alumno"
                                        data-se-confirmar-titulo="Eliminar alumno"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M5 7h14M10 11v6M14 11v6M9 7V4.5h6V7M7 7l.8 13h8.4L17 7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="se-vacio">
                            No se encontraron alumnos con los filtros seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="se-ad-pie">
        <span>
            Mostrando {{ $alumnos->firstItem() ?? 0 }} a {{ $alumnos->lastItem() ?? 0 }} de {{ $alumnos->total() }} alumnos
        </span>

        {{ $alumnos->links() }}
    </div>
</section>

<dialog id="alumnos-modal-ver" class="se-modal se-modal--form" aria-labelledby="alumno-ver-nombre">
    <div class="se-modal-caja">
        <h2 id="alumno-ver-nombre" class="se-modal-titulo">Alumno</h2>
        <p class="se-modal-mensaje">Información registrada del alumno</p>

        <dl class="se-ad-detalle">
            <div><dt>Curso</dt><dd id="alumno-ver-curso"></dd></div>
            <div><dt>DNI</dt><dd id="alumno-ver-cedula"></dd></div>
            <div><dt>Legajo</dt><dd id="alumno-ver-legajo"></dd></div>
            <div><dt>Fecha de nacimiento</dt><dd id="alumno-ver-nacimiento"></dd></div>
            <div><dt>Teléfono</dt><dd id="alumno-ver-telefono"></dd></div>
            <div><dt>Estado</dt><dd id="alumno-ver-estado"></dd></div>
        </dl>

        <div class="se-modal-acciones">
            <button type="button" class="se-btn" data-se-cerrar>Cerrar</button>
        </div>
    </div>
</dialog>

<dialog id="alumnos-modal-editar" class="se-modal se-modal--form" aria-labelledby="alumno-editar-titulo">
    <div class="se-modal-caja">
        <h2 id="alumno-editar-titulo" class="se-modal-titulo">Editar alumno</h2>
        <p id="alumno-editar-nombre" class="se-modal-mensaje">Actualice los datos del alumno</p>

        <form method="POST" id="form-editar-alumno" class="se-ad-form">
            @csrf
            @method('PUT')

            <div class="se-ad-campo se-ad-campo--ancho">
                <label class="se-ad-label" for="editar-curso">Curso</label>
                <select id="editar-curso" class="se-ad-input" name="curso_id" required>
                    @foreach($cursos as $curso)
                        <option value="{{ $curso->id }}">
                            {{ $curso->anio ?? '' }}{{ isset($curso->anio) ? '° ' : '' }}{{ $curso->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="se-ad-campo">
                <label class="se-ad-label" for="editar-legajo">Legajo</label>
                <input id="editar-legajo" class="se-ad-input" type="text" name="legajo">
            </div>

            <div class="se-ad-campo">
                <label class="se-ad-label" for="editar-nacimiento">Fecha de nacimiento</label>
                <input id="editar-nacimiento" class="se-ad-input" type="date" name="fecha_nacimiento">
            </div>

            <div class="se-ad-campo">
                <label class="se-ad-label" for="editar-telefono">Teléfono</label>
                <input id="editar-telefono" class="se-ad-input" type="text" name="telefono">
            </div>

            <div class="se-ad-campo">
                <label class="se-ad-label" for="editar-estado">Estado</label>
                <select id="editar-estado" class="se-ad-input" name="activo" required>
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>

            <div class="se-ad-form-pie">
                <button type="button" class="se-btn se-btn--suave" data-se-cerrar>Cancelar</button>
                <button type="submit" class="se-btn">Guardar cambios</button>
            </div>
        </form>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
    function abrirModalVer(datos) {
        document.getElementById('alumno-ver-nombre').textContent = datos.nombre;
        document.getElementById('alumno-ver-curso').textContent = datos.curso;
        document.getElementById('alumno-ver-cedula').textContent = datos.cedula;
        document.getElementById('alumno-ver-legajo').textContent = datos.legajo;
        document.getElementById('alumno-ver-nacimiento').textContent = datos.fecha_nacimiento;
        document.getElementById('alumno-ver-telefono').textContent = datos.telefono;
        document.getElementById('alumno-ver-estado').textContent = datos.estado;
        window.seDialogo.abrir(document.getElementById('alumnos-modal-ver'));
    }

    function abrirModalEditar(datos) {
        const formulario = document.getElementById('form-editar-alumno');
        formulario.action = '{{ url("admin/alumnos") }}/' + datos.id;

        document.getElementById('alumno-editar-nombre').textContent = 'Actualice los datos de ' + datos.nombre;
        document.getElementById('editar-curso').value = datos.curso_id;
        document.getElementById('editar-legajo').value = datos.legajo ?? '';
        document.getElementById('editar-nacimiento').value = datos.fecha_nacimiento ?? '';
        document.getElementById('editar-telefono').value = datos.telefono ?? '';
        document.getElementById('editar-estado').value = datos.activo;

        window.seDialogo.abrir(document.getElementById('alumnos-modal-editar'));
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-ver-alumno').forEach(function (boton) {
            boton.addEventListener('click', function () {
                abrirModalVer(JSON.parse(boton.dataset.alumno));
            });
        });

        document.querySelectorAll('.js-editar-alumno').forEach(function (boton) {
            boton.addEventListener('click', function () {
                abrirModalEditar(JSON.parse(boton.dataset.alumno));
            });
        });
    });
</script>
@endpush

@extends('layouts.profesor')

@section('title', 'Cargar asistencias')

@section('content')
@php
    $paleta = [0, 1, 2, 3, 4, 5];
    $etiquetaCurso = fn ($curso) => ($curso->anio ?? '') . (isset($curso->anio) ? '° ' : '') . $curso->nombre;
@endphp

<header class="se-saludo">
    <div>
        <h1>Cargar asistencias</h1>
        <p>Marcá el estado de cada alumno en la fecha elegida y guardá la planilla.</p>
    </div>
    <span class="se-badge se-badge--claro se-badge--sin-punto">Asistencia mínima: {{ \App\Services\AsistenciaMetricasService::MINIMO }}%</span>
</header>

@if(session('success'))
    <div class="se-aviso se-aviso--exito" role="status">{{ session('success') }}</div>
@endif

<form method="GET" action="{{ route('profesor.cargar-asistencias') }}" class="se-ad-filtros se-ad-filtros--tarjeta">
    <div class="se-ad-filtro se-ad-filtro--ancho">
        <label class="se-ad-label" for="asis-materia">Materia</label>
        <select id="asis-materia" name="materia_id" class="se-ad-input" onchange="this.form.submit()">
            @forelse($materias as $item)
                <option value="{{ $item->id }}" @selected($materia?->id === $item->id)>
                    {{ $item->nombre }}
                    @if($item->curso)
                        ({{ $etiquetaCurso($item->curso) }})
                    @endif
                </option>
            @empty
                <option value="">No tenés materias asignadas</option>
            @endforelse
        </select>
    </div>

    <div class="se-ad-filtro">
        <label class="se-ad-label" for="asis-fecha">Fecha</label>
        <input id="asis-fecha" type="date" name="fecha" class="se-ad-input" value="{{ $fecha }}" onchange="this.form.submit()">
    </div>

    @if($materia && $materia->curso)
        <p class="se-as-info">
            Curso: <strong>{{ $etiquetaCurso($materia->curso) }}</strong>
            &middot; {{ $alumnos->count() }} alumno(s)
        </p>
    @endif
</form>

<div class="se-as-layout">
    <div class="se-as-principal">
        <div class="se-aviso se-as-aviso-info">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.7" />
                <path d="M12 11v5.5M12 8v.01" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
            </svg>
            Seleccioná el estado correspondiente para cada alumno.
        </div>

        <div id="asis-error" class="se-aviso se-aviso--error" role="alert" hidden>
            Faltan seleccionar algunos alumnos. Elegí un estado para cada uno antes de guardar.
        </div>

        <form id="asis-form" method="POST" action="{{ route('profesor.guardar-asistencias') }}">
            @csrf
            <input type="hidden" name="materia_id" value="{{ $materia?->id }}">
            <input type="hidden" name="fecha" value="{{ $fecha }}">

            <section class="se-panel se-c-azul">
                <div class="se-tabla-wrap">
                    <table class="se-tabla">
                        <thead>
                            <tr>
                                <th class="se-as-num">N&deg;</th>
                                <th>Alumno</th>
                                <th>Legajo</th>
                                <th>Estado</th>
                                <th class="se-as-col-obs">Observaci&oacute;n</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($alumnos as $indice => $alumno)
                                @php
                                    $nombreAlumno = $alumno->usuario->nombre ?? 'Alumno #' . $alumno->id;
                                    $iniciales = collect(explode(' ', $nombreAlumno))->filter()->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');
                                    $existente = $asistenciasExistentes->get($alumno->id);
                                    $estados = ['presente' => 'Presente', 'ausente' => 'Ausente', 'tarde' => 'Tardanza'];
                                @endphp
                                <tr class="asis-fila" data-alumno-id="{{ $alumno->id }}">
                                    <td>{{ $indice + 1 }}</td>
                                    <td>
                                        <div class="se-as-alumno">
                                            <span class="se-as-avatar se-as-avatar--{{ $indice % count($paleta) }}">{{ $iniciales }}</span>
                                            {{ $nombreAlumno }}
                                        </div>
                                    </td>
                                    <td>{{ $alumno->legajo }}</td>
                                    <td>
                                        <div class="se-as-estados">
                                            @foreach($estados as $clave => $etiqueta)
                                                <button type="button" class="asis-estado-btn se-as-estado se-as-estado--{{ $clave }} @if($existente?->estado === $clave) activo @endif" data-estado="{{ $clave }}">
                                                    <svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m3 8.5 3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    {{ $etiqueta }}
                                                </button>
                                            @endforeach
                                        </div>
                                        <input type="hidden" name="estados[{{ $alumno->id }}]" value="{{ $existente?->estado }}" class="asis-estado-input">
                                    </td>
                                    <td>
                                        <button type="button" class="se-btn se-btn--suave se-btn--mini" data-asis-obs="{{ $alumno->id }}" aria-expanded="false">
                                            Observaci&oacute;n
                                        </button>
                                    </td>
                                </tr>
                                <tr class="se-as-obs-fila" id="asis-obs-fila-{{ $alumno->id }}" hidden>
                                    <td colspan="5">
                                        <textarea name="observaciones[{{ $alumno->id }}]" placeholder="Observaci&oacute;n para {{ $nombreAlumno }} (opcional)">{{ $existente?->observacion }}</textarea>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="se-vacio">
                                        @if(! $materia)
                                            Seleccion&aacute; una materia para cargar asistencias.
                                        @else
                                            Este curso todav&iacute;a no tiene alumnos.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($alumnos->isNotEmpty())
                    <div class="se-as-acciones">
                        <a href="{{ route('profesor.inicio') }}" class="se-btn se-btn--suave">Cancelar</a>
                        <button type="submit" class="se-btn">Guardar asistencias</button>
                    </div>
                @endif
            </section>
        </form>
    </div>

    <aside class="se-panel se-c-verde se-as-resumen">
        <div class="se-panel-cab"><h2>Resumen del d&iacute;a</h2></div>

        <div class="se-as-resumen-lista">
            <div class="se-as-resumen-item">
                <span class="se-as-resumen-etiqueta"><span class="se-as-punto"></span> Presentes</span>
                <span class="se-as-resumen-numero" id="asis-contador-presente">{{ $resumen['presentes'] }}</span>
            </div>
            <div class="se-as-resumen-item">
                <span class="se-as-resumen-etiqueta"><span class="se-as-punto se-as-punto--ausente"></span> Ausentes</span>
                <span class="se-as-resumen-numero" id="asis-contador-ausente">{{ $resumen['ausentes'] }}</span>
            </div>
            <div class="se-as-resumen-item">
                <span class="se-as-resumen-etiqueta"><span class="se-as-punto se-as-punto--tarde"></span> Tardanzas</span>
                <span class="se-as-resumen-numero" id="asis-contador-tarde">{{ $resumen['tardanzas'] }}</span>
            </div>

            <div class="se-as-resumen-pie">
                Total alumnos: {{ $resumen['total'] }}<br>
                Porcentaje de presentes:
                <strong id="asis-porcentaje">{{ $resumen['porcentaje'] }}%</strong>
            </div>
        </div>
    </aside>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.asis-fila').forEach(function (fila) {
        const input = fila.querySelector('.asis-estado-input');

        fila.querySelectorAll('.asis-estado-btn').forEach(function (boton) {
            boton.addEventListener('click', function () {
                fila.querySelectorAll('.asis-estado-btn').forEach(function (b) {
                    b.classList.remove('activo');
                });
                boton.classList.add('activo');
                input.value = boton.dataset.estado;
                fila.classList.remove('se-as-fila--pendiente');
                actualizarResumen();
            });
        });
    });

    document.querySelectorAll('[data-asis-obs]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            const fila = document.getElementById('asis-obs-fila-' + boton.dataset.asisObs);
            fila.hidden = !fila.hidden;
            boton.setAttribute('aria-expanded', fila.hidden ? 'false' : 'true');
        });
    });

    function actualizarResumen() {
        let presentes = 0;
        let ausentes = 0;
        let tardanzas = 0;
        let total = 0;

        document.querySelectorAll('.asis-fila').forEach(function (fila) {
            total++;
            const estado = fila.querySelector('.asis-estado-input').value;
            if (estado === 'presente') presentes++;
            if (estado === 'ausente') ausentes++;
            if (estado === 'tarde') tardanzas++;
        });

        document.getElementById('asis-contador-presente').textContent = presentes;
        document.getElementById('asis-contador-ausente').textContent = ausentes;
        document.getElementById('asis-contador-tarde').textContent = tardanzas;

        const porcentaje = total > 0 ? ((presentes / total) * 100).toFixed(2) : 0;
        document.getElementById('asis-porcentaje').textContent = porcentaje + '%';
    }

    const asisForm = document.getElementById('asis-form');
    if (asisForm) {
        asisForm.addEventListener('submit', function (evento) {
            let faltantes = 0;

            document.querySelectorAll('.asis-fila').forEach(function (fila) {
                const estado = fila.querySelector('.asis-estado-input').value;
                if (!estado) {
                    faltantes++;
                    fila.classList.add('se-as-fila--pendiente');
                } else {
                    fila.classList.remove('se-as-fila--pendiente');
                }
            });

            const aviso = document.getElementById('asis-error');

            if (faltantes > 0) {
                evento.preventDefault();
                aviso.hidden = false;
                aviso.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                aviso.hidden = true;
            }
        });
    }

    actualizarResumen();
</script>
@endpush

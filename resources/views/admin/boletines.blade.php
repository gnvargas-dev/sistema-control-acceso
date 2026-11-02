@extends('layouts.dashboard')

@section('title', 'Boletines')

@section('content')
<header class="se-saludo se-ad-cab">
    <div>
        <h1>Boletines</h1>
        <p>Generar y gestionar boletines de los alumnos</p>
    </div>

    <button type="button" class="se-btn" onclick="abrirModalBoletin()">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9l-6-6Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
            <path d="M14 3v6h6M9 13h6M9 17h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
        </svg>
        Generar nuevo boletín
    </button>
</header>

@if(session('success'))
    <div class="se-aviso se-aviso--exito" role="status">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="se-aviso se-aviso--error" role="alert">
        @foreach($errors->all() as $mensaje)
            <div>{{ $mensaje }}</div>
        @endforeach
    </div>
@endif

<section class="se-stats" aria-label="Resumen de boletines">
        <article class="se-stat se-c-azul">
            <span class="se-stat-icono">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9l-6-6Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                    <path d="M14 3v6h6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                </svg>
            </span>
            <div class="se-stat-cuerpo">
                <span class="se-stat-titulo">Boletines generados</span>
                <span class="se-stat-valor">{{ $estadisticas['total'] }}</span>
                <span class="se-stat-pie">Total en el sistema</span>
            </div>
        </article>
        <article class="se-stat se-c-verde">
            <span class="se-stat-icono">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="3.5" y="4.5" width="17" height="16" rx="2" stroke="currentColor" stroke-width="1.7" />
                    <path d="M3.5 9h17M8 3v3M16 3v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                </svg>
            </span>
            <div class="se-stat-cuerpo">
                <span class="se-stat-titulo">Este trimestre</span>
                <span class="se-stat-valor">{{ $estadisticas['este_trimestre'] }}</span>
                <span class="se-stat-pie">Boletines generados</span>
            </div>
        </article>
        <article class="se-stat se-c-violeta">
            <span class="se-stat-icono">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M16 19v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 17.5V19M20 19v-1.5a3.5 3.5 0 0 0-2.5-3.36M14.5 4.6a3.5 3.5 0 0 1 0 6.8M9.5 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <div class="se-stat-cuerpo">
                <span class="se-stat-titulo">Alumnos evaluados</span>
                <span class="se-stat-valor">{{ $estadisticas['alumnos_evaluados'] }}</span>
                <span class="se-stat-pie">Con boletín generado</span>
            </div>
        </article>
        <article class="se-stat se-c-naranja">
            <span class="se-stat-icono">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <div class="se-stat-cuerpo">
                <span class="se-stat-titulo">Descargas</span>
                <span class="se-stat-valor">{{ $estadisticas['descargas'] }}</span>
                <span class="se-stat-pie">Total de descargas</span>
            </div>
        </article>
</section>

<section class="se-panel">
    <form method="GET" action="{{ route('admin.boletines') }}" class="se-ad-filtros">
        <div class="se-ad-filtro">
            <label class="se-ad-label" for="filtro-curso">Curso</label>
            <select id="filtro-curso" class="se-ad-input" name="curso_id" onchange="this.form.submit()">
                <option value="">Todos los cursos</option>

                @foreach($cursos as $curso)
                    <option value="{{ $curso->id }}" @selected((string) request('curso_id') === (string) $curso->id)>
                        {{ $curso->anio ?? '' }}{{ isset($curso->anio) ? '° ' : '' }}{{ $curso->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="se-ad-filtro">
            <label class="se-ad-label" for="filtro-periodo">Período</label>
            <select id="filtro-periodo" class="se-ad-input" name="periodo" onchange="this.form.submit()">
                <option value="">Todos los períodos</option>

                @foreach($periodos as $periodo)
                    <option value="{{ $periodo }}" @selected(request('periodo') === $periodo)>
                        {{ $periodo }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="se-ad-filtro se-ad-filtro--ancho">
            <label class="se-ad-label" for="filtro-buscar">Buscar alumno</label>
            <div class="se-ad-buscar">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="10.8" cy="10.8" r="6.3" stroke="currentColor" stroke-width="1.8" />
                    <path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg>
                <input id="filtro-buscar" class="se-ad-input" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o apellido...">
            </div>
        </div>

        <div class="se-ad-filtro-acciones">
            <button class="se-btn" type="submit">Buscar</button>

            @if(request()->anyFilled(['q', 'curso_id', 'periodo']))
                <a class="se-btn se-btn--suave" href="{{ route('admin.boletines') }}">Limpiar filtros</a>
            @endif
        </div>
    </form>

    <div class="se-tabla-wrap" tabindex="0" role="region" aria-label="Historial de boletines">
        <table class="se-tabla">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Alumno</th>
                    <th>Curso</th>
                    <th>Período</th>
                    <th>Fecha generación</th>
                    <th>Generado por</th>
                    <th>Archivo</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse($historial as $boletin)
                    <tr>
                        <td class="se-ad-id">{{ $boletin->id }}</td>

                        <td><span class="se-tabla-titulo">{{ $boletin->alumno->usuario->nombre ?? 'Alumno eliminado' }}</span></td>

                        <td>
                            <span class="se-badge se-badge--info se-badge--sin-punto">
                                @if($boletin->curso)
                                    {{ $boletin->curso->anio ?? '' }}{{ isset($boletin->curso->anio) ? '° ' : '' }}{{ $boletin->curso->nombre }}
                                @else
                                    Sin curso
                                @endif
                            </span>
                        </td>

                        <td>{{ $boletin->periodo }}</td>

                        <td>{{ $boletin->created_at->format('d/m/Y H:i') }}</td>

                        <td>{{ $boletin->generadoPor->nombre ?? '—' }}</td>

                        <td>
                            <a class="se-ad-archivo" href="{{ route('admin.boletines.ver', $boletin) }}" target="_blank">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9l-6-6Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                                    <path d="M14 3v6h6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                                </svg>
                                {{ basename($boletin->archivo) }}
                            </a>
                        </td>

                        <td>
                            <div class="se-ad-acciones">
                                <a class="se-ad-icono" href="{{ route('admin.boletines.descargar', $boletin) }}" title="Descargar" aria-label="Descargar boletín">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </a>

                                <a class="se-ad-icono" href="{{ route('admin.boletines.ver', $boletin) }}" target="_blank" title="Ver" aria-label="Ver boletín">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                                        <circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.7" />
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="se-vacio">
                            Todavía no se generaron boletines con los filtros seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="se-ad-pie">
        <span>
            Mostrando {{ $historial->firstItem() ?? 0 }} a {{ $historial->lastItem() ?? 0 }} de {{ $historial->total() }} boletines
        </span>

        {{ $historial->links() }}
    </div>
</section>

<dialog id="boletines-modal-fondo" class="se-modal se-modal--form" aria-labelledby="boletin-modal-titulo">
    <div class="se-modal-caja">
        <h2 id="boletin-modal-titulo" class="se-modal-titulo">Generar nuevo boletín</h2>
        <p class="se-modal-mensaje">Elegí un alumno puntual o un curso completo, y el período a generar.</p>

        <form method="POST" action="{{ route('admin.boletines.pdf') }}" class="se-ad-form se-ad-form--una">
            @csrf

            <div class="se-ad-campo">
                <label class="se-ad-label" for="boletin-periodo">Período</label>
                <input
                    id="boletin-periodo"
                    class="se-ad-input"
                    type="text"
                    name="periodo"
                    placeholder="Ej: 1er Trimestre {{ date('Y') }}"
                    value="{{ old('periodo') }}"
                    required
                >
            </div>

            <div class="se-ad-campo">
                <label class="se-ad-label" for="boletin-alumno">Alumno</label>
                <select id="boletin-alumno" class="se-ad-input" name="alumno_id">
                    <option value="">Seleccionar alumno...</option>

                    @foreach($alumnos as $alumno)
                        <option value="{{ $alumno->id }}">
                            {{ $alumno->usuario->nombre ?? 'Alumno sin nombre' }}
                            @if($alumno->curso)
                                — {{ $alumno->curso->anio ?? '' }}{{ isset($alumno->curso->anio) ? '° ' : '' }}{{ $alumno->curso->nombre }}
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="se-ad-o">o por curso completo</div>

            <div class="se-ad-campo">
                <label class="se-ad-label" for="boletin-curso">Curso</label>
                <select id="boletin-curso" class="se-ad-input" name="curso_id">
                    <option value="">Seleccionar curso...</option>

                    @foreach($cursos as $curso)
                        <option value="{{ $curso->id }}">
                            {{ $curso->anio ?? '' }}{{ isset($curso->anio) ? '° ' : '' }}{{ $curso->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="se-ad-form-pie">
                <button type="button" class="se-btn se-btn--suave" onclick="cerrarModalBoletin()">Cancelar</button>
                <button type="submit" class="se-btn">Generar</button>
            </div>
        </form>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
    function abrirModalBoletin() {
        window.seDialogo.abrir(document.getElementById('boletines-modal-fondo'));
    }

    function cerrarModalBoletin() {
        window.seDialogo.cerrar(document.getElementById('boletines-modal-fondo'));
    }

    document.addEventListener('DOMContentLoaded', function () {
        const selectAlumno = document.getElementById('boletin-alumno');
        const selectCurso = document.getElementById('boletin-curso');

        selectAlumno.addEventListener('change', function () {
            selectCurso.disabled = selectAlumno.value !== '';
            if (selectAlumno.value !== '') {
                selectCurso.value = '';
            }
        });

        selectCurso.addEventListener('change', function () {
            selectAlumno.disabled = selectCurso.value !== '';
            if (selectCurso.value !== '') {
                selectAlumno.value = '';
            }
        });

        @if($errors->any())
            abrirModalBoletin();
        @endif
    });
</script>
@endpush

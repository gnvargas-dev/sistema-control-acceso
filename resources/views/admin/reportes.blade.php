@extends('layouts.dashboard')

@section('title', 'Reportes y estadísticas')

@section('content')
<header class="se-saludo se-ad-cab">
    <div>
        <h1>Reportes y estadísticas</h1>
        <p>Indicadores académicos generales del colegio</p>
    </div>
</header>

<form
    method="GET"
    action="{{ route('admin.reportes') }}"
    class="se-ad-filtros se-ad-filtros--tarjeta"
>
    <div class="se-ad-filtro">
        <label class="se-ad-label" for="reporte-desde">Desde</label>

        <input
            id="reporte-desde"
            class="se-ad-input"
            type="date"
            name="desde"
            value="{{ $filtros['desde']->format('Y-m-d') }}"
        >
    </div>

    <div class="se-ad-filtro">
        <label class="se-ad-label" for="reporte-hasta">Hasta</label>

        <input
            id="reporte-hasta"
            class="se-ad-input"
            type="date"
            name="hasta"
            value="{{ $filtros['hasta']->format('Y-m-d') }}"
        >
    </div>

    <div class="se-ad-filtro">
        <label class="se-ad-label" for="reporte-curso">Curso</label>

        <select
            id="reporte-curso"
            class="se-ad-input"
            name="curso_id"
        >
            <option value="">Todos los cursos</option>

            @foreach($cursosFiltro as $curso)
                <option
                    value="{{ $curso->id }}"
                    @selected((string) $filtros['curso_id'] === (string) $curso->id)
                >
                    {{ $curso->anio }}° {{ $curso->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="se-ad-filtro-acciones">
        <button class="se-btn se-btn--suave" type="submit">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>

            Aplicar filtros
        </button>

        <div class="se-ad-menu export-wrap">
            <button
                class="se-btn"
                type="button"
                id="exportToggle"
                aria-expanded="false"
                aria-controls="exportMenu"
            >
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 4v11M8 11l4 4 4-4M5 19h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>

                Exportar reporte
            </button>

            <div
                id="exportMenu"
                class="se-ad-menu-lista"
                hidden
            >
                @php
                    $paramsBase = array_filter([
                        'desde' => $filtros['desde']->format('Y-m-d'),
                        'hasta' => $filtros['hasta']->format('Y-m-d'),
                        'curso_id' => $filtros['curso_id'],
                    ]);
                @endphp

                <a class="se-ad-menu-item" href="{{ route('admin.reportes.exportar', $paramsBase + ['tipo' => 1]) }}">
                    Resumen ejecutivo institucional
                    <small>Métricas generales y gráficos principales</small>
                </a>
                <a class="se-ad-menu-item" href="{{ route('admin.reportes.exportar', $paramsBase + ['tipo' => 2]) }}">
                    Comparativo por curso
                    <small>Matrícula, asistencia y promedios por curso</small>
                </a>
                <a class="se-ad-menu-item" href="{{ route('admin.reportes.exportar', $paramsBase + ['tipo' => 3]) }}">
                    Cuadro de honor
                    <small>Alumnos con los promedios más altos</small>
                </a>
                <a class="se-ad-menu-item" href="{{ route('admin.reportes.exportar', $paramsBase + ['tipo' => 4]) }}">
                    Alerta de riesgo académico
                    <small>Promedio &lt; {{ (int) \App\Valores\Calificacion::APROBACION }} o asistencia &lt; {{ \App\Services\AsistenciaMetricasService::MINIMO }}%</small>
                </a>
            </div>
        </div>
    </div>
</form>

<section class="se-stats" aria-label="Indicadores generales">
    <article class="se-stat se-c-azul">
        <span class="se-stat-icono" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M16 19v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 17.5V19M10 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM17 8a2.5 2.5 0 1 1 0 5M16 14.5a3.5 3.5 0 0 1 3 3.5v1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>
        <div class="se-stat-cuerpo">
            <span class="se-stat-titulo">Total alumnos</span>
            <span class="se-stat-valor">{{ number_format($resumen['total_alumnos']) }}</span>
            <span class="se-stat-pie">Todos los cursos
            </span>
        </div>
    </article>
    <article class="se-stat se-c-verde">
        <span class="se-stat-icono" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M4 19v-1.5A3.5 3.5 0 0 1 7.5 14h5a3.5 3.5 0 0 1 3.5 3.5V19M10 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM17 8a2.5 2.5 0 1 1 0 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>
        <div class="se-stat-cuerpo">
            <span class="se-stat-titulo">Total profesores</span>
            <span class="se-stat-valor">{{ number_format($resumen['total_profesores']) }}</span>
            <span class="se-stat-pie">Personal docente
            </span>
        </div>
    </article>
    <article class="se-stat se-c-violeta">
        <span class="se-stat-icono" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M4 18V7.5A2.5 2.5 0 0 1 6.5 5H20v14H6.5A2.5 2.5 0 0 0 4 21.5M4 18a2.5 2.5 0 0 1 2.5-2.5H20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>
        <div class="se-stat-cuerpo">
            <span class="se-stat-titulo">Promedio general</span>
            <span class="se-stat-valor">{{ number_format($resumen['promedio_general'], 2) }}</span>
            <span class="se-stat-pie">vs. período anterior
                @if($resumen['promedio_variacion'] !== null)
                    <span class="se-badge se-badge--sin-punto se-ad-delta {{ $resumen['promedio_variacion'] >= 0 ? 'se-badge--aprobado' : 'se-badge--reprobado' }}">
                        {{ $resumen['promedio_variacion'] >= 0 ? '+' : '-' }}{{ abs($resumen['promedio_variacion']) }}%
                    </span>
                @endif
            </span>
        </div>
    </article>
    <article class="se-stat se-c-naranja">
        <span class="se-stat-icono" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M4 19h16M6 16V9M10 16V5M14 16v-3M18 16V7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>
        <div class="se-stat-cuerpo">
            <span class="se-stat-titulo">% asistencia general</span>
            <span class="se-stat-valor">{{ number_format($resumen['asistencia_general'], 1) }}%</span>
            <span class="se-stat-pie">vs. período anterior
                @if($resumen['asistencia_variacion'] !== null)
                    <span class="se-badge se-badge--sin-punto se-ad-delta {{ $resumen['asistencia_variacion'] >= 0 ? 'se-badge--aprobado' : 'se-badge--reprobado' }}">
                        {{ $resumen['asistencia_variacion'] >= 0 ? '+' : '-' }}{{ abs($resumen['asistencia_variacion']) }}%
                    </span>
                @endif
            </span>
        </div>
    </article>
</section>

@php
    /* Coordenadas y longitudes de SVG: punto decimal, sin separador de miles */
    $coord = fn ($valor) => rtrim(rtrim(number_format((float) $valor, 2, '.', ''), '0'), '.');
    $decimal = fn ($valor) => rtrim(rtrim(number_format((float) $valor, 1, '.', ''), '0'), '.');

    /* Anillo: parte de cada segmento en puntos de 0 a 100 (pathLength="100") */
    $anillo = function (array $segmentos): array {
        $suma = array_sum(array_column($segmentos, 'valor'));
        $conDatos = count(array_filter($segmentos, fn ($s) => $s['valor'] > 0));
        $hueco = $conDatos > 1 ? 1.2 : 0.0;
        $acumulado = 0.0;

        foreach ($segmentos as $i => $s) {
            $largo = $suma > 0 ? $s['valor'] / $suma * 100 : 0.0;
            $segmentos[$i]['trazo'] = max($largo - $hueco, 0.0);
            $segmentos[$i]['desde'] = $acumulado + $hueco / 2;
            $acumulado += $largo;
        }

        return $segmentos;
    };

    /* 1. Asistencia general: asistencias contra ausencias (mismos datos que antes) */
    $asistenciaGeneral = max(0.0, min(100.0, (float) $resumen['asistencia_general']));
    $segAsistencia = $anillo([
        ['clase' => 'se-rg-1', 'etiqueta' => 'Asistencias', 'valor' => $asistenciaGeneral],
        ['clase' => 'se-rg-resto', 'etiqueta' => 'Ausencias', 'valor' => max(0.0, 100 - $resumen['asistencia_general'])],
    ]);

    /* 2. Distribución de promedios: una porción por rango */
    $rangos = collect($distribucion['rangos'])->values();
    $totalDistribucion = (int) $rangos->sum('cantidad');
    $segDistribucion = $anillo($rangos->map(fn ($r, $i) => [
        'clase' => 'se-rg-' . ($i + 1),
        'etiqueta' => $r['rango'],
        'valor' => (float) $r['cantidad'],
    ])->all());

    /* 3. Promedio por curso: barras de 0 a 5 (misma escala que antes) */
    $cursosEtiquetas = collect($promedioPorCursoChart)->pluck('curso')->values()->all();
    $cursosPromedios = collect($promedioPorCursoChart)->pluck('promedio')->values()->all();
    $dosDecimales = fn ($valor) => number_format((float) $valor, 2, '.', '');
    $bMax = 5;
    $bRanura = count($cursosPromedios) <= 6 ? 110 : 76; $bIzq = 40; $bDer = 12; $bArr = 28; $bAba = 40; $bAlto = 260;
    $bAncho = max(520, count($cursosPromedios) * $bRanura + $bIzq + $bDer);
    $bPlotAlto = $bAlto - $bArr - $bAba;
    $bY = fn ($valor) => $bArr + $bPlotAlto * (1 - min(max((float) $valor, 0), $bMax) / $bMax);
    $bRanuraReal = ($bAncho - $bIzq - $bDer) / max(count($cursosPromedios), 1);
@endphp

<div class="se-ga-fila">
    <section class="se-panel se-elevar se-c-verde">
        <div class="se-panel-cab">
            <h2>Asistencia general</h2>
        </div>

        <div class="se-ga-anillo-caja">
            <svg class="se-ga-svg se-ga-anillo" viewBox="0 0 120 120" role="img" aria-labelledby="se-rg-asis-t se-rg-asis-d">
                <title id="se-rg-asis-t">Asistencia general</title>
                <desc id="se-rg-asis-d">Asistencias: {{ $decimal($asistenciaGeneral) }}%. Ausencias: {{ $decimal(max(0, 100 - $asistenciaGeneral)) }}%.</desc>
                <circle class="se-ga-anillo-fondo" cx="60" cy="60" r="46" />
                <g transform="rotate(-90 60 60)">
                    @foreach($segAsistencia as $s)
                        @if($s['valor'] > 0)
                            <circle class="se-ga-seg {{ $s['clase'] }}" cx="60" cy="60" r="46" pathLength="100" stroke-dasharray="{{ $coord($s['trazo']) }} 100" stroke-dashoffset="-{{ $coord($s['desde']) }}">
                                <title>{{ $s['etiqueta'] }}: {{ $decimal($s['valor']) }}%</title>
                            </circle>
                        @endif
                    @endforeach
                </g>
                <text class="se-ga-centro" x="60" y="62">{{ number_format($resumen['asistencia_general'], 1) }}%</text>
                <text class="se-ga-centro-sub" x="60" y="75">de asistencia</text>
            </svg>

            <ul class="se-ga-leyenda">
                @foreach($segAsistencia as $s)
                    <li class="{{ $s['clase'] }}">
                        <i class="se-ga-dot"></i>
                        <span>{{ $s['etiqueta'] }}</span>
                        <b>{{ $decimal($s['valor']) }}%</b>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="se-panel se-elevar se-c-azul">
        <div class="se-panel-cab">
            <h2>Distribución de promedios</h2>
            <span class="se-badge se-badge--info se-badge--sin-punto">{{ $totalDistribucion }} {{ $totalDistribucion === 1 ? 'alumno' : 'alumnos' }}</span>
        </div>

        <div class="se-ga-anillo-caja">
            <svg class="se-ga-svg se-ga-anillo" viewBox="0 0 120 120" role="img" aria-labelledby="se-rg-dist-t se-rg-dist-d">
                <title id="se-rg-dist-t">Distribución de promedios</title>
                <desc id="se-rg-dist-d">{{ $rangos->map(fn ($r) => $r['rango'] . ': ' . $r['cantidad'] . ' (' . $r['porcentaje'] . '%).')->implode(' ') }}</desc>
                <circle class="se-ga-anillo-fondo" cx="60" cy="60" r="46" />
                <g transform="rotate(-90 60 60)">
                    @foreach($segDistribucion as $s)
                        @if($s['valor'] > 0)
                            <circle class="se-ga-seg {{ $s['clase'] }}" cx="60" cy="60" r="46" pathLength="100" stroke-dasharray="{{ $coord($s['trazo']) }} 100" stroke-dashoffset="-{{ $coord($s['desde']) }}">
                                <title>{{ $s['etiqueta'] }}: {{ (int) $s['valor'] }}</title>
                            </circle>
                        @endif
                    @endforeach
                </g>
                <text class="se-ga-centro" x="60" y="62">{{ $totalDistribucion }}</text>
                <text class="se-ga-centro-sub" x="60" y="75">{{ $totalDistribucion === 1 ? 'alumno' : 'alumnos' }}</text>
            </svg>

            <ul class="se-ga-leyenda">
                @foreach($rangos as $i => $rango)
                    <li class="se-rg-{{ $i + 1 }}">
                        <i class="se-ga-dot"></i>
                        <span>{{ $rango['rango'] }}</span>
                        <b>{{ $rango['cantidad'] }}</b>
                        <small>{{ $rango['porcentaje'] }}%</small>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
</div>

<section class="se-panel se-elevar se-c-azul se-rg-panel-ancho">
    <div class="se-panel-cab">
        <h2>Promedio por curso</h2>
    </div>

    <div class="se-ga-cuerpo">
        @if(count($cursosPromedios) === 0)
            <p class="se-vacio">No hay cursos para mostrar.</p>
        @else
            <div class="se-ga-scroll" tabindex="0" role="region" aria-label="Gráfico de promedio por curso">
                <svg class="se-ga-svg se-rg-barras" width="{{ $bAncho }}" height="{{ $bAlto }}" viewBox="0 0 {{ $bAncho }} {{ $bAlto }}" role="img" aria-labelledby="se-rg-cur-t se-rg-cur-d">
                    <title id="se-rg-cur-t">Promedio por curso</title>
                    <desc id="se-rg-cur-d">{{ collect($cursosEtiquetas)->map(fn ($etiqueta, $i) => $etiqueta . ': ' . $dosDecimales($cursosPromedios[$i]) . '.')->implode(' ') }}</desc>

                    @foreach(range(0, $bMax) as $g)
                        <line class="se-ga-rejilla {{ $g === 0 ? 'se-ga-rejilla--base' : '' }}" x1="{{ $bIzq }}" x2="{{ $bAncho - $bDer }}" y1="{{ $coord($bY($g)) }}" y2="{{ $coord($bY($g)) }}" />
                        <text class="se-ga-eje se-ga-eje--y" x="{{ $bIzq - 8 }}" y="{{ $coord($bY($g) + 4) }}">{{ $g }}</text>
                    @endforeach

                    @foreach($cursosPromedios as $i => $promedio)
                        @php
                            $centro = $bIzq + ($i + 0.5) * $bRanuraReal;
                            $alto = max($bPlotAlto * min(max((float) $promedio, 0), $bMax) / $bMax, 2);
                            $arriba = $bArr + $bPlotAlto - $alto;
                        @endphp
                        <rect class="se-rg-barra" x="{{ $coord($centro - 19) }}" y="{{ $coord($arriba) }}" width="38" height="{{ $coord($alto) }}" rx="7">
                            <title>{{ $cursosEtiquetas[$i] }}: {{ $dosDecimales($promedio) }}</title>
                        </rect>
                        <text class="se-rg-valor" x="{{ $coord($centro) }}" y="{{ $coord($arriba - 7) }}">{{ $dosDecimales($promedio) }}</text>
                        <text class="se-ga-eje se-ga-eje--x" x="{{ $coord($centro) }}" y="{{ $bAlto - 14 }}">{{ $cursosEtiquetas[$i] }}</text>
                    @endforeach
                </svg>
            </div>
        @endif
    </div>
</section>

<section class="se-panel">
    <div class="se-panel-cab">
        <h2>Resumen por curso</h2>
    </div>

    <div class="se-tabla-wrap" tabindex="0" role="region" aria-label="Resumen por curso">
        <table class="se-tabla">
            <thead>
                <tr>
                    <th>Curso</th>
                    <th>Alumnos</th>
                    <th>% Asistencia</th>
                    <th>Promedio general</th>
                    <th>Mejor promedio</th>
                    <th>Menor promedio</th>
                </tr>
            </thead>

            <tbody>
                @forelse($resumenPorCurso as $fila)
                    <tr>
                        <td>
                            <span class="se-tabla-titulo">
                                {{ $fila['curso']->anio }}°
                                {{ $fila['curso']->nombre }}
                            </span>
                        </td>

                        <td>
                            {{ $fila['matricula'] }}
                        </td>

                        <td>
                            <span class="se-badge se-badge--info se-badge--sin-punto">
                                {{ number_format($fila['asistencia'], 1) }}%
                            </span>
                        </td>

                        <td>
                            {{ number_format($fila['promedio'], 2) }}
                        </td>

                        <td>
                            @if($fila['mejor'])
                                {{ number_format($fila['mejor']['promedio'], 2) }}
                                —
                                {{ \Illuminate\Support\Str::limit($fila['mejor']['nombre'], 22) }}
                            @else
                                —
                            @endif
                        </td>

                        <td>
                            @if($fila['menor'])
                                {{ number_format($fila['menor']['promedio'], 2) }}
                                —
                                {{ \Illuminate\Support\Str::limit($fila['menor']['nombre'], 22) }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="se-vacio">
                            No hay cursos registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="se-ad-pie reportes-paginacion">
        <p class="pagination-info">
            Mostrando
            {{ $resumenPorCurso->firstItem() ?? 0 }}
            a
            {{ $resumenPorCurso->lastItem() ?? 0 }}
            de
            {{ $resumenPorCurso->total() }}
            cursos
        </p>

        {{
            $resumenPorCurso
                ->onEachSide(1)
                ->links('vendor.pagination.bootstrap-5')
        }}
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const exportWrap = document.querySelector('.export-wrap');
        const exportToggle = document.getElementById('exportToggle');
        const exportMenu = document.getElementById('exportMenu');

        if (!exportToggle || !exportMenu) {
            return;
        }

        function alternarMenu(abrir) {
            exportMenu.hidden = !abrir;
            exportToggle.setAttribute('aria-expanded', abrir ? 'true' : 'false');
        }

        exportToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            alternarMenu(exportMenu.hidden);
        });

        document.addEventListener('click', function (event) {
            if (exportWrap && !exportWrap.contains(event.target)) {
                alternarMenu(false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !exportMenu.hidden) {
                alternarMenu(false);
                exportToggle.focus();
            }
        });
    });
</script>
@endpush

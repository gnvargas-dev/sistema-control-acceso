<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page {
        margin: 130px 36px 70px 36px;
    }

    * { box-sizing: border-box; }

    body {
        margin: 0;
        font-family: DejaVu Sans, sans-serif;
        color: #10265b;
        font-size: 12px;
    }

    .header {
        position: fixed;
        top: -100px;
        left: 0;
        right: 0;
        height: 100px;
        border-bottom: 2px solid #095df0;
        padding-bottom: 12px;
    }

    .header-top {
        display: block;
        width: 100%;
        overflow: hidden;
    }

    .header-brand {
        float: left;
        font-size: 16px;
        font-weight: 700;
        color: #061b52;
    }

    .header-meta {
        float: right;
        text-align: right;
        font-size: 10px;
        color: #586889;
    }

    .header-title {
        margin-top: 14px;
        font-size: 20px;
        font-weight: 800;
        color: #061b52;
    }

    .header-subtitle {
        margin-top: 4px;
        font-size: 11px;
        color: #586889;
    }

    .footer {
        position: fixed;
        bottom: -50px;
        left: 0;
        right: 0;
        height: 40px;
        border-top: 1px solid #dfe6f2;
        padding-top: 8px;
        font-size: 9px;
        color: #8592ab;
    }

    .footer-left { float: left; }
    .footer-right { float: right; }

    .pagenum:after { content: counter(page); }
    .pagecount:after { content: counter(pages); }

    h2.section {
        font-size: 14px;
        font-weight: 800;
        color: #061b52;
        margin: 18px 0 10px;
        padding-bottom: 6px;
        border-bottom: 1px solid #e7ecf4;
    }

    .kpis { width: 100%; }
    .kpis td {
        width: 25%;
        padding: 10px 12px;
        border: 1px solid #dfe6f2;
        border-radius: 4px;
        vertical-align: top;
    }

    .kpi-label { font-size: 10px; color: #586889; font-weight: 700; text-transform: uppercase; }
    .kpi-value { font-size: 20px; font-weight: 800; color: #0659ee; margin-top: 4px; }
    .kpi-delta { font-size: 9px; margin-top: 4px; color: #168343; }
    .kpi-delta.down { color: #cd3434; }

    table.data {
        width: 100%;
        border-collapse: collapse;
        margin-top: 6px;
    }

    table.data th {
        background: #061b52;
        color: #fff;
        text-align: left;
        padding: 8px 9px;
        font-size: 10.5px;
    }

    table.data td {
        padding: 7px 9px;
        border-bottom: 1px solid #e7ecf4;
        font-size: 10.5px;
        word-break: break-word;
    }

    table.data tbody tr:nth-child(even) { background: #f6f8fc; }
    table.data { page-break-inside: auto; }
    table.data tr { page-break-inside: avoid; page-break-after: auto; }

    .pill {
        display: inline-block;
        padding: 2px 7px;
        border-radius: 99px;
        font-size: 9.5px;
        font-weight: 700;
    }

    .pill-ok { background: #e5f8eb; color: #168343; }
    .pill-warn { background: #feeaea; color: #cd3434; }

    .empty-note {
        padding: 14px;
        text-align: center;
        color: #8592ab;
        font-size: 11px;
    }

    .honor-rank {
        display: inline-block;
        width: 18px;
        font-weight: 800;
        color: #f39200;
    }
</style>
</head>
<body>

<div class="header">
    <div class="header-top">
        <span class="header-brand">🎓 Sistema Escolar</span>
        <span class="header-meta">
            Período: {{ $filtros['desde']->format('d/m/Y') }} — {{ $filtros['hasta']->format('d/m/Y') }}<br>
            Emitido el {{ $generadoEl->format('d/m/Y') }} a las {{ $generadoEl->format('H:i') }}
        </span>
    </div>
    <div class="header-title">{{ $titulo }}</div>
    <div class="header-subtitle">Confidencial — Uso exclusivo del personal autorizado de la institución</div>
</div>

<div class="footer">
    <span class="footer-left">Sistema Escolar · Documento de uso interno, confidencial</span>
    <span class="footer-right">Página <span class="pagenum"></span> de <span class="pagecount"></span></span>
</div>

{{-- ============ OPCIÓN 1: RESUMEN EJECUTIVO INSTITUCIONAL ============ --}}
@if($tipo === 1)
    <h2 class="section">Métricas principales</h2>
    <table class="kpis">
        <tr>
            <td>
                <div class="kpi-label">Total alumnos</div>
                <div class="kpi-value">{{ number_format($resumen['total_alumnos']) }}</div>
            </td>
            <td>
                <div class="kpi-label">Total profesores</div>
                <div class="kpi-value">{{ number_format($resumen['total_profesores']) }}</div>
            </td>
            <td>
                <div class="kpi-label">Promedio general</div>
                <div class="kpi-value">{{ number_format($resumen['promedio_general'], 2) }}</div>
                @if($resumen['promedio_variacion'] !== null)
                    <div class="kpi-delta {{ $resumen['promedio_variacion'] < 0 ? 'down' : '' }}">
                        {{ $resumen['promedio_variacion'] >= 0 ? '↑' : '↓' }} {{ abs($resumen['promedio_variacion']) }}% vs. período anterior
                    </div>
                @endif
            </td>
            <td>
                <div class="kpi-label">% asistencia general</div>
                <div class="kpi-value">{{ number_format($resumen['asistencia_general'], 1) }}%</div>
                @if($resumen['asistencia_variacion'] !== null)
                    <div class="kpi-delta {{ $resumen['asistencia_variacion'] < 0 ? 'down' : '' }}">
                        {{ $resumen['asistencia_variacion'] >= 0 ? '↑' : '↓' }} {{ abs($resumen['asistencia_variacion']) }}% vs. período anterior
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <h2 class="section">Distribución de promedios</h2>
    @if($distribucion['total'] === 0)
        <p class="empty-note">No hay notas cargadas en el período seleccionado.</p>
    @else
        <table class="data">
            <thead>
                <tr><th>Rango de promedio</th><th>Cantidad de alumnos</th><th>% del total</th></tr>
            </thead>
            <tbody>
                @foreach($distribucion['rangos'] as $rango)
                    <tr>
                        <td>{{ $rango['rango'] }}</td>
                        <td>{{ $rango['cantidad'] }}</td>
                        <td>{{ $rango['porcentaje'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endif

{{-- ============ OPCIÓN 2: COMPARATIVO POR CURSO ============ --}}
@if($tipo === 2)
    <h2 class="section">Resumen por curso</h2>

    @if($cursos->isEmpty())
        <p class="empty-note">No hay cursos registrados.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th>Curso</th>
                    <th>Matrícula</th>
                    <th>% Asistencia</th>
                    <th>Promedio general</th>
                    <th>Mayor promedio</th>
                    <th>Menor promedio</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cursos as $fila)
                    <tr>
                        <td>{{ $fila['curso']->anio }}° {{ $fila['curso']->nombre }}</td>
                        <td>{{ $fila['matricula'] }}</td>
                        <td>{{ number_format($fila['asistencia'], 1) }}%</td>
                        <td>{{ number_format($fila['promedio'], 2) }}</td>
                        <td>
                            @if($fila['mejor'])
                                {{ $fila['mejor']['nombre'] }} ({{ number_format($fila['mejor']['promedio'], 2) }})
                            @else
                                Sin datos
                            @endif
                        </td>
                        <td>
                            @if($fila['menor'])
                                {{ $fila['menor']['nombre'] }} ({{ number_format($fila['menor']['promedio'], 2) }})
                            @else
                                Sin datos
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endif

{{-- ============ OPCIÓN 3: CUADRO DE HONOR ============ --}}
@if($tipo === 3)
    <h2 class="section">Cuadro de honor institucional</h2>

    @if($cuadroHonor['institucional']->isEmpty())
        <p class="empty-note">No hay notas cargadas en el período seleccionado.</p>
    @else
        <table class="data">
            <thead>
                <tr><th style="width:30px">#</th><th>Alumno</th><th>Curso</th><th>Promedio</th></tr>
            </thead>
            <tbody>
                @foreach($cuadroHonor['institucional'] as $index => $alumno)
                    <tr>
                        <td><span class="honor-rank">{{ $index + 1 }}</span></td>
                        <td>{{ $alumno['nombre'] }}</td>
                        <td>{{ $alumno['curso'] }}</td>
                        <td>{{ number_format($alumno['promedio'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h2 class="section">Mejor promedio por curso</h2>
        <table class="data">
            <thead>
                <tr><th>Curso</th><th>Alumno destacado</th><th>Promedio</th></tr>
            </thead>
            <tbody>
                @foreach($cuadroHonor['por_curso'] as $curso => $alumno)
                    <tr>
                        <td>{{ $curso }}</td>
                        <td>{{ $alumno['nombre'] }}</td>
                        <td>{{ number_format($alumno['promedio'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endif

{{-- ============ OPCIÓN 4: ALERTA DE RIESGO ACADÉMICO Y AUSENTISMO ============ --}}
@if($tipo === 4)
    <h2 class="section">Alumnos en riesgo (promedio &lt; {{ (int) \App\Valores\Calificacion::APROBACION }} o asistencia &lt; {{ \App\Services\AsistenciaMetricasService::MINIMO }}%)</h2>

    @if($riesgo->isEmpty())
        <p class="empty-note">No se detectaron alumnos en riesgo académico ni de ausentismo en el período seleccionado.</p>
    @else
        <table class="data">
            <thead>
                <tr><th>Alumno</th><th>Curso</th><th>Promedio</th><th>% Asistencia</th><th>Motivo</th></tr>
            </thead>
            <tbody>
                @foreach($riesgo as $alumno)
                    <tr>
                        <td>{{ $alumno['nombre'] }}</td>
                        <td>{{ $alumno['curso'] }}</td>
                        <td>{{ $alumno['promedio'] !== null ? number_format($alumno['promedio'], 2) : 'Sin notas' }}</td>
                        <td>{{ $alumno['asistencia'] !== null ? number_format($alumno['asistencia'], 1) . '%' : 'Sin registros' }}</td>
                        <td><span class="pill pill-warn">{{ $alumno['motivo'] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endif

</body>
</html>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans; color: #172554; }
        h1 { font-size: 22px; margin-bottom: 4px; }
        .periodo { color: #64748b; margin: 0 0 18px; font-size: 13px; }
        .card { border: 1px solid #cbd5e1; margin: 18px 0; padding: 14px; border-radius: 6px; }
        .card strong { font-size: 15px; }
        .curso { color: #64748b; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border-bottom: 1px solid #ddd; padding: 7px; text-align: left; font-size: 13px; }
        .vacio { color: #94a3b8; font-size: 13px; padding: 10px 0; }
    </style>
</head>
<body>
    <h1>Boletín académico</h1>
    @if(!empty($periodo))
        <p class="periodo">Período: {{ $periodo }}</p>
    @endif

    @foreach($alumnos as $alumno)
        <div class="card">
            <strong>{{ $alumno->usuario->nombre ?? 'Alumno sin nombre' }}</strong>
            <div class="curso">
                {{ $alumno->curso->anio ?? '' }}{{ isset($alumno->curso->anio) ? '° ' : '' }}{{ $alumno->curso->nombre ?? 'Sin curso' }}
            </div>

            @if($alumno->notas->isEmpty())
                <p class="vacio">Sin notas cargadas.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th>Nota</th>
                            <th>Período</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alumno->notas as $nota)
                            <tr>
                                <td>{{ $nota->materia->nombre ?? '—' }}</td>
                                <td>{{ number_format((float) $nota->valor, 2) }}</td>
                                <td>{{ $nota->periodo }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach
</body>
</html>
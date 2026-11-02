<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boletín académico</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        h1 {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #333;
            padding: 6px;
        }

        th {
            background: #eeeeee;
        }
    </style>
</head>
<body>
    <h1>Boletín académico</h1>

    <p>
        <strong>Alumno:</strong>
        {{ $alumno->usuario->nombre }}
    </p>

    <p>
        <strong>Legajo:</strong>
        {{ $alumno->legajo }}
    </p>

    <p>
        <strong>Curso:</strong>
        {{ $alumno->curso->nombre }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Materia</th>
                <th>Período</th>
                <th>Nota</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($alumno->notas as $nota)
                <tr>
                    <td>{{ $nota->materia->nombre }}</td>
                    <td>{{ $nota->periodo }}</td>
                    <td>{{ $nota->valor }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

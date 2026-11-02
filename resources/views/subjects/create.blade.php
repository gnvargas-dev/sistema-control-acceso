<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Asignatura</title>
    <style>
        /* Estilos base idénticos a tu pantalla principal */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e2e8f0;
            margin: 0;
            padding: 0;
            color: #334155;
        }

        /* Barra de navegación superior idéntica */
        .navbar-custom {
            background-color: #1d4ed8;
            color: white;
            padding: 15px 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            font-weight: bold;
            font-size: 16px;
        }

        .container {
            max-width: 1000px; /* Misma anchura que tu tabla para mantener la estructura */
            margin: 40px auto;
            padding: 0 20px;
        }

        /* Encabezado */
        .header-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .header-title h2 {
            margin: 0;
            font-size: 22px;
            color: #1e293b;
        }

        .header-title p {
            margin: 4px 0 0 0;
            font-size: 13px;
            color: #64748b;
        }

        /* Tarjeta Blanca Principal (Mismo tamaño y formato que la de tu tabla) */
        .card-custom {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #cbd5e1;
            overflow: hidden;
        }

        .card-header {
            background-color: #f8fafc;
            padding: 15px 20px;
            border-bottom: 1px solid #e2e8f0;
            font-weight: bold;
            font-size: 14px;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Cuerpo del formulario interno */
        .card-body {
            padding: 30px;
        }

        /* Estructura de campos */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            font-size: 14px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            box-sizing: border-box;
            color: #334155;
            background-color: #fff;
        }

        .form-control:focus {
            outline: none;
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
        }

        /* Mensajes de error */
        .error-message {
            color: #dc2626;
            font-size: 12px;
            margin-top: 5px;
            font-weight: 500;
        }

        /* Botones de acción */
        .btn-container {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 25px;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
        }

        .btn-submit {
            background-color: #0284c7; /* Azul corporativo de tus botones */
            color: white;
            padding: 10px 24px;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-submit:hover {
            background-color: #0369a1;
        }

        .btn-cancel {
            background-color: #f1f5f9;
            color: #475569;
            padding: 10px 20px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }

        .btn-cancel:hover {
            background-color: #e2e8f0;
        }
    </style>
</head>
<body>

    <!-- Barra Superior Idéntica -->
    <div class="navbar-custom">
        Sistema de Control de Asignaturas
    </div>

    <div class="container">
        <!-- Bloque de Título Superior -->
        <div class="header-box">
            <div class="header-title">
                <h2>Control de Materias</h2>
                <p>Ingresa la información requerida para registrar una nueva asignatura</p>
            </div>
        </div>

        <!-- Tarjeta con la misma métrica que tu listado principal -->
        <div class="card-custom">
            <div class="card-header">
                Formulario de Registro
            </div>
            
            <div class="card-body">
                <form action="{{ route('subjects.store') }}" method="POST">
                    @csrf

                    <!-- Campo Código -->
                    <div class="form-group">
                        <label for="code">Código de la Materia</label>
                        <input type="text" name="code" id="code" class="form-control" placeholder="Ej: MAT-101" value="{{ old('code') }}">
                        @error('code')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Campo Nombre -->
                    <div class="form-group">
                        <label for="name">Nombre de la Asignatura</label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="Ej: Matemáticas I" value="{{ old('name') }}">
                        @error('name')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Campo Docente -->
                    <div class="form-group">
                        <label for="teacher_id">Docente Asignado</label>
                        <select name="teacher_id" id="teacher_id" class="form-control">
                            <option value="">-- Seleccione un docente --</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                    {{ $teacher->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('teacher_id')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Botonera inferior alineada a la derecha -->
                    <div class="btn-container">
                        <a href="{{ route('subjects.index') }}" class="btn-cancel">Cancelar</a>
                        <button type="submit" class="btn-submit">Guardar Registro</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
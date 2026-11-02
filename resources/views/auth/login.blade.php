<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b193c">
    <title>Acceso seguro</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="{{ asset('css/tema.css') }}?v={{ file_exists(public_path('css/tema.css')) ? filemtime(public_path('css/tema.css')) : 1 }}">
</head>
<body class="se-login">
    <main class="se-lg-pantalla">
        <div class="se-lg-tarjeta {{ $errors->any() ? 'se-lg-tiembla' : '' }}" data-se-lg-tarjeta>
            <div class="se-lg-cabecera">
                <div class="se-lg-fondo" aria-hidden="true">
                    <i class="se-lg-onda"></i><i class="se-lg-onda"></i><i class="se-lg-onda"></i>
                </div>

                <div class="se-lg-titulo">
                    <svg class="se-lg-cerradura {{ $errors->any() ? 'mal' : '' }}" data-se-lg-cerradura viewBox="0 0 120 120" aria-hidden="true">
                        <circle class="se-lg-pi" cx="60" cy="60" r="54"/>
                        <circle class="se-lg-pr" cx="60" cy="60" r="54" transform="rotate(-90 60 60)"/>
                        <path class="se-lg-arco" d="M44 56V44a16 16 0 0132 0v12"/>
                        <rect class="se-lg-cuerpo" x="36" y="54" width="48" height="36" rx="9"/>
                        <circle class="se-lg-ojo" cx="60" cy="68" r="4.5"/>
                        <path class="se-lg-ojo-linea" d="M60 72v8"/>
                    </svg>
                    <h1>Sistema de Control de Acceso Seguro</h1>
                    <p>Accedé con tus credenciales</p>
                </div>
            </div>

            <div class="se-lg-formulario">
                <p class="se-lg-bienvenida">¡Bienvenido! Ingresá tus datos para continuar.</p>

                <div class="se-lg-error {{ $errors->any() ? 'visible' : '' }}" role="alert" aria-live="assertive" data-se-lg-error>@if ($errors->any()){{ $errors->first() }}@endif</div>

                <form method="POST" action="{{ route('login.store') }}" data-se-lg-form novalidate>
                    @csrf

                    <div class="se-lg-campo">
                        <label for="cedula">Número de cédula</label>
                        <input
                            id="cedula"
                            name="cedula"
                            type="text"
                            inputmode="numeric"
                            value="{{ old('cedula') }}"
                            autocomplete="username"
                            placeholder="Ingresá tu número de cédula"
                            required
                            autofocus
                        >
                    </div>

                    <div class="se-lg-campo">
                        <label for="password">Contraseña</label>
                        <div class="se-lg-clave">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                placeholder="Ingresá tu contraseña"
                                required
                            >
                            <button type="button" class="se-lg-ojo-boton" data-se-lg-ver aria-label="Mostrar contraseña" aria-pressed="false">Mostrar</button>
                        </div>
                    </div>

                    <button type="submit" class="se-lg-enviar" data-se-lg-enviar>
                        <span class="se-lg-enviar-texto">Ingresar</span>
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script src="{{ asset('js/tema.js') }}?v={{ file_exists(public_path('js/tema.js')) ? filemtime(public_path('js/tema.js')) : 1 }}" defer></script>
</body>
</html>

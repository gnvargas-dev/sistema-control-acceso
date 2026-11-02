<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#061b52">
    <title>@yield('title', 'Sistema Escolar')</title>

    <script>
        try {
            if (!sessionStorage.getItem('se-menu-visto')) {
                document.documentElement.classList.add('se-primera');
                sessionStorage.setItem('se-menu-visto', '1');
            }
        } catch (e) {}
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="{{ asset('css/tema.css') }}?v={{ file_exists(public_path('css/tema.css')) ? filemtime(public_path('css/tema.css')) : 1 }}">

    @stack('styles')
</head>

@php
    $seVariante = $seVariante ?? 'admin';

    $usuario = auth()->user();
    $rol = $usuario->rolObjeto();

    $conPerfil = in_array($seVariante, ['profesor', 'alumno'], true);
    $perfil = $conPerfil ? $rol->perfilDe($usuario) : null;

    $nombrePorDefecto = ['admin' => 'Administrador', 'profesor' => 'Profesor', 'alumno' => 'Alumno'];
    $nombre = $usuario->nombre ?? ($nombrePorDefecto[$seVariante] ?? 'Usuario');
    $primerNombre = trim(explode(' ', $nombre)[0] ?? $nombre);

    $iniciales = collect(explode(' ', $nombre))
        ->filter()
        ->map(fn ($parte) => strtoupper(substr($parte, 0, 1)))
        ->take(2)
        ->implode('');

    $menuItems = $rol->menu();

    $etiquetaMenu = [
        'admin' => 'Navegación principal',
        'profesor' => 'Navegación del profesor',
        'alumno' => 'Navegación del alumno',
    ][$seVariante] ?? 'Navegación principal';

    $chipNombre = $seVariante === 'profesor' ? 'Prof. ' . $nombre : $nombre;
    $chipDetalle = ($seVariante === 'alumno' && $perfil)
        ? 'Legajo: ' . $perfil->identificador()
        : $rol->nombre();

    if ($seVariante === 'profesor') {
        $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $hoy = now();
        $fechaLegible = $dias[(int) $hoy->format('w')] . ', ' . $hoy->day . ' de ' . $meses[(int) $hoy->month] . ' de ' . $hoy->year;
        $trimestre = (int) ceil($hoy->month / 4) > 3 ? 3 : (int) ceil($hoy->month / 4);
    }

    $iconos = [
        'home' => '<path d="M3 11.2 12 4l9 7.2M5.5 9.8V20h13V9.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>',
        'users' => '<path d="M16 19v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 17.5V19M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM17.5 19v-1.5a3.5 3.5 0 0 0-2.3-3.29M14.5 5.1a3 3 0 0 1 0 5.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>',
        'files' => '<path d="M7 3.5h7.5L19 8v12a.5.5 0 0 1-.5.5h-11A.5.5 0 0 1 7 20V4a.5.5 0 0 1 0-.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" fill="none"/><path d="M14 3.5V8h4.5M9.5 12.5h5M9.5 15.5h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'reports' => '<path d="M4 20V10M10 20V4M16 20v-7M4 20h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>',
        'subjects' => '<path d="M4.5 5.2A2.2 2.2 0 0 1 6.7 3h10.8v16.5H6.7A2.2 2.2 0 0 0 4.5 21.7V5.2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" fill="none"/><path d="M4.5 18.2A2.2 2.2 0 0 1 6.7 16h10.8" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
        'folder' => '<path d="M3.5 6.5A1.5 1.5 0 0 1 5 5h4.2l1.8 2h8a1.5 1.5 0 0 1 1.5 1.5v9A1.5 1.5 0 0 1 19 19H5a1.5 1.5 0 0 1-1.5-1.5v-11Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" fill="none"/>',
        'students' => '<path d="M2.5 9 12 4.5 21.5 9 12 13.5 2.5 9Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" fill="none"/><path d="M6.5 11v4.2c0 1.1 2.5 2.3 5.5 2.3s5.5-1.2 5.5-2.3V11M21.5 9v6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>',
        'history' => '<path d="M3.5 12a8.5 8.5 0 1 1 2.7 6.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" fill="none"/><path d="M3.5 6.5V12h5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/><path d="M12 8v4.5l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>',
    ];
@endphp

<body class="se-rol-{{ $seVariante }}">
    <div class="se-overlay" data-se-overlay aria-hidden="true"></div>

    <aside class="se-sidebar" id="se-sidebar">
        <div class="se-marca">
            <svg class="se-marca-icono" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M3 8.5 12 4l9 4.5L12 13 3 8.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                <path d="M6 10.2V15c0 1.6 2.7 3.5 6 3.5s6-1.9 6-3.5v-4.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                <path d="M21 9v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
            <div>
                <div class="se-marca-titulo">Sistema Escolar</div>
                @if($seVariante === 'profesor')
                    <div class="se-marca-subtitulo">Panel del Profesor</div>
                @endif
            </div>
        </div>

        <nav class="se-menu" data-se-menu aria-label="{{ $etiquetaMenu }}">
            @foreach($menuItems as $item)
                @php $activo = request()->routeIs($item->ruta); @endphp
                <a href="{{ route($item->ruta) }}"
                   class="{{ $activo ? 'activo' : '' }}"
                   style="--i: {{ $loop->index }}"
                   @if($activo) aria-current="page" @endif>
                    <span class="se-menu-icono">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            {!! $iconos[$item->icono] ?? '' !!}
                        </svg>
                    </span>

                    <span>
                        <span class="se-menu-titulo">{{ $item->titulo }}</span>
                        @if(!empty($item->subtitulo))
                            <span class="se-menu-subtitulo">{{ $item->subtitulo }}</span>
                        @endif
                    </span>
                </a>
            @endforeach
        </nav>

        <div class="se-sidebar-pie">
            @if($conPerfil)
                <div class="se-perfil">
                    <span class="se-perfil-avatar">{{ $iniciales }}</span>
                    <div class="se-perfil-info">
                        <div class="se-perfil-nombre">{{ $nombre }}</div>
                        <div class="se-perfil-detalle">{{ $rol->nombre() }}@if($perfil) · {{ $perfil->descripcion() }}@endif</div>
                    </div>
                </div>
            @endif

            <form class="se-form-salir" method="POST" action="{{ route('logout') }}" data-se-salir>
                @csrf
                <button class="se-salir" type="submit">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M10 5H5.5A1.5 1.5 0 0 0 4 6.5v11A1.5 1.5 0 0 0 5.5 19H10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        <path d="m14 8 4 4-4 4M18 12H9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    <div class="se-main">
        <header class="se-topbar">
            <button class="se-toggle" type="button" data-se-toggle aria-controls="se-sidebar" aria-label="Abrir o cerrar menú" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg>
            </button>

            <div class="se-topbar-titulo">
                @if($seVariante === 'admin')
                    <h1>¡Bienvenido, {{ $nombre }}!</h1>
                    <p>{{ $rol->nombre() }}</p>
                @elseif($seVariante === 'profesor')
                    <h1>¡Bienvenido, Prof. {{ $primerNombre }}!</h1>
                    <p>Este es tu panel de control como profesor.</p>
                @endif
            </div>

            <div class="se-topbar-acciones">
                @if($seVariante === 'profesor')
                    <div class="se-fecha">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <rect x="3.5" y="5" width="17" height="15" rx="2" stroke="currentColor" stroke-width="1.7" />
                            <path d="M3.5 9.5h17M8 3v3.5M16 3v3.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                        </svg>
                        <span>
                            <div class="se-fecha-dia">{{ ucfirst($fechaLegible) }}</div>
                            <div class="se-fecha-periodo">Período: {{ $trimestre }}° Trimestre {{ $hoy->year }}</div>
                        </span>
                    </div>
                @endif

                @if($conPerfil)
                    <span class="se-campana" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M6 9a6 6 0 0 1 12 0c0 4 1.5 5.5 1.5 5.5h-15S6 13 6 9Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M10 18a2 2 0 0 0 4 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                        </svg>
                        <span class="se-campana-punto"></span>
                    </span>
                @endif

                <div class="se-usuario">
                    <button class="se-usuario-boton" type="button" data-se-user-toggle aria-haspopup="true" aria-expanded="false">
                        <span class="se-avatar">{{ $iniciales }}</span>

                        <span class="se-usuario-datos">
                            <span class="se-usuario-nombre">{{ $chipNombre }}</span>
                            <span class="se-usuario-rol">{{ $chipDetalle }}</span>
                        </span>

                        <svg class="se-chevron" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                            <path d="m4 6 4 4 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>

                    <div class="se-usuario-menu" data-se-user-menu>
                        <form method="POST" action="{{ route('logout') }}" data-se-salir>
                            @csrf
                            <button type="submit">Cerrar sesión</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="se-contenido">
            @yield('content')
        </main>
    </div>

    <script>
        (function () {
            var cuerpo = document.body;
            var toggle = document.querySelector('[data-se-toggle]');
            var overlay = document.querySelector('[data-se-overlay]');
            var userBtn = document.querySelector('[data-se-user-toggle]');
            var userMenu = document.querySelector('[data-se-user-menu]');
            var movil = window.matchMedia('(max-width: 900px)');

            function menu(abrir) {
                cuerpo.classList.toggle('se-menu-abierto', abrir);
                if (toggle) toggle.setAttribute('aria-expanded', abrir ? 'true' : 'false');
            }

            function usuario(abrir) {
                if (!userBtn || !userMenu) return;
                userMenu.classList.toggle('abierto', abrir);
                userBtn.setAttribute('aria-expanded', abrir ? 'true' : 'false');
            }

            if (toggle) {
                toggle.addEventListener('click', function () {
                    menu(!cuerpo.classList.contains('se-menu-abierto'));
                });
            }

            if (overlay) {
                overlay.addEventListener('click', function () { menu(false); });
            }

            if (userBtn && userMenu) {
                userBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    usuario(!userMenu.classList.contains('abierto'));
                });

                document.addEventListener('click', function (e) {
                    if (!userMenu.contains(e.target)) usuario(false);
                });
            }

            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;
                var estabaAbierto = cuerpo.classList.contains('se-menu-abierto');
                menu(false);
                usuario(false);
                if (estabaAbierto && toggle) toggle.focus();
            });

            var alCambiar = function (e) { if (!e.matches) menu(false); };
            if (movil.addEventListener) movil.addEventListener('change', alCambiar);
            else if (movil.addListener) movil.addListener(alCambiar);

            window.addEventListener('pageshow', function () { menu(false); usuario(false); });

            // Al cerrar sesión, la próxima entrada vuelve a mostrar la animación escalonada del menú.
            document.querySelectorAll('[data-se-salir]').forEach(function (f) {
                f.addEventListener('submit', function () {
                    try { sessionStorage.removeItem('se-menu-visto'); } catch (e) {}
                });
            });
        })();
    </script>

    <script src="{{ asset('js/tema.js') }}" defer></script>

    @stack('scripts')
</body>
</html>

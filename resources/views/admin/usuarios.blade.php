@extends('layouts.dashboard')

@section('title', 'Usuarios')

@section('content')
<header class="se-saludo se-ad-cab">
    <div>
        <h1>Usuarios</h1>
        <p>Gestionar usuarios del sistema</p>
    </div>
    <button class="se-btn" type="button" onclick="mostrarFormularioUsuario()">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" /></svg>
        Crear nuevo usuario
    </button>
</header>

@if(session('success'))
    <div class="se-aviso se-aviso--exito" role="status">{{ session('success') }}</div>
@endif

<section id="nuevo-usuario" class="se-panel se-ad-alta" @unless($errors->any()) hidden @endunless>
    <header class="se-panel-cab">
        <h2>Crear nuevo usuario</h2>
    </header>

    <div class="se-panel-cuerpo">
        @if($errors->any())
            <div class="se-aviso se-aviso--error" role="alert">
                <strong>Revisá los siguientes datos:</strong>
                <ul>
                    @foreach($errors->all() as $mensaje)
                        <li>{{ $mensaje }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.usuarios.guardar') }}" class="se-ad-form">
            @csrf

            <label class="se-ad-campo">
                <span class="se-ad-label">Nombre completo</span>
                <input class="se-ad-input" name="nombre" value="{{ old('nombre') }}" required>
                @error('nombre')<p class="se-ad-error">{{ $message }}</p>@enderror
            </label>

            <label class="se-ad-campo">
                <span class="se-ad-label">Correo electrónico</span>
                <input class="se-ad-input" type="email" name="email" value="{{ old('email') }}" required>
                @error('email')<p class="se-ad-error">{{ $message }}</p>@enderror
            </label>

            <label class="se-ad-campo">
                <span class="se-ad-label">Número de cédula</span>
                <input class="se-ad-input" name="cedula" value="{{ old('cedula') }}" required>
                @error('cedula')<p class="se-ad-error">{{ $message }}</p>@enderror
            </label>

            <div class="se-ad-campo">
                <label class="se-ad-label" for="rol">Rol</label>
                <select class="se-ad-input" id="rol" name="rol" required>
                    <option value="">Seleccionar rol</option>
                    <option value="alumno" @selected(old('rol') === 'alumno')>Alumno</option>
                    <option value="profesor" @selected(old('rol') === 'profesor')>Profesor</option>
                    <option value="administrador" @selected(old('rol') === 'administrador')>Administrador</option>
                </select>
                @error('rol')<p class="se-ad-error">{{ $message }}</p>@enderror
            </div>

            <div class="se-ad-campo">
                <label class="se-ad-label" for="campo-curso">Curso</label>
                <select class="se-ad-input" id="campo-curso" name="curso_id">
                    <option value="" id="curso-placeholder">Seleccionar curso</option>
                    @foreach($cursos as $curso)
                        <option value="{{ $curso->id }}" @selected((string) old('curso_id') === (string) $curso->id)>
                            {{ $curso->anio }}-{{ $curso->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('curso_id')<p class="se-ad-error">{{ $message }}</p>@enderror
            </div>

            <div id="campo-materias-profesor" class="se-ad-campo se-ad-campo--ancho" hidden>
                <span class="se-ad-label">Materia(s) que va a enseñar</span>
                <div id="materias-profesor-filas" class="se-ad-filas"></div>
                <button type="button" class="se-btn se-btn--suave se-btn--mini se-ad-agregar" onclick="agregarFilaMateria()">
                    + Agregar otra materia
                </button>
                @error('materia_ids')<p class="se-ad-error">{{ $message }}</p>@enderror
                @error('materia_ids.*')<p class="se-ad-error">{{ $message }}</p>@enderror
                @error('materia_nuevas.*')<p class="se-ad-error">{{ $message }}</p>@enderror
            </div>

            <label class="se-ad-campo">
                <span class="se-ad-label">Estado</span>
                <select class="se-ad-input" name="activo">
                    <option value="1" @selected(old('activo', '1') === '1')>Activo</option>
                    <option value="0" @selected(old('activo') === '0')>Inactivo</option>
                </select>
                @error('activo')<p class="se-ad-error">{{ $message }}</p>@enderror
            </label>

            <div class="se-ad-campo">
                <label class="se-ad-label" for="password">Contraseña</label>
                <div class="se-ad-clave">
                    <input class="se-ad-input" id="password" type="password" name="password" required>
                    <button type="button" class="se-ad-ojo" onclick="alternarPassword('password', this)" aria-label="Mostrar contraseña">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                            <circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                    </button>
                </div>
                @error('password')<p class="se-ad-error">{{ $message }}</p>@enderror
            </div>

            <div class="se-ad-campo">
                <label class="se-ad-label" for="password_confirmation">Confirmar contraseña</label>
                <div class="se-ad-clave">
                    <input class="se-ad-input" id="password_confirmation" type="password" name="password_confirmation" required>
                    <button type="button" class="se-ad-ojo" onclick="alternarPassword('password_confirmation', this)" aria-label="Mostrar contraseña">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                            <circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="se-ad-form-pie">
                <button class="se-btn se-btn--suave" type="button" onclick="ocultarFormularioUsuario()">Cancelar</button>
                <button class="se-btn" type="submit">Guardar usuario</button>
            </div>
        </form>
    </div>
</section>

<section class="se-panel">
    <div class="se-tabla-wrap" tabindex="0" role="region" aria-label="Listado de usuarios">
        <table class="se-tabla">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Correo electrónico</th>
                    <th>Cédula</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th><span class="se-sr">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse($usuarios as $usuario)
                    <tr>
                        <td class="se-ad-id">{{ $usuario->id }}</td>
                        <td><span class="se-tabla-titulo">{{ $usuario->nombre }}</span></td>
                        <td>{{ $usuario->email }}</td>
                        <td>{{ $usuario->cedula }}</td>
                        <td><span class="se-badge se-badge--info se-badge--sin-punto">{{ ucfirst($usuario->rol) }}</span></td>
                        <td>
                            <span class="se-badge {{ $usuario->activo ? 'se-badge--aprobado' : 'se-badge--neutro' }}">
                                {{ $usuario->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td>
                            @if($usuario->id !== auth()->id())
                                <form
                                    method="POST"
                                    action="{{ route('admin.usuarios.eliminar', $usuario) }}"
                                    data-se-confirmar="¿Seguro que querés eliminar a &quot;{{ $usuario->nombre }}&quot;? Esta acción no se puede deshacer."
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="se-btn se-btn--peligro se-btn--mini"
                                        data-se-confirmar-titulo="Eliminar usuario"
                                    >
                                        Eliminar
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="se-vacio">No hay usuarios registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="se-ad-pie">
        {{ $usuarios->links() }}
    </div>
</section>
@endsection

@push('scripts')
<script>
    const panelNuevoUsuario = document.getElementById('nuevo-usuario');
    const rol = document.getElementById('rol');
    const cursoSelect = document.getElementById('campo-curso');
    const cursoPlaceholder = document.getElementById('curso-placeholder');
    const campoMateriasProfesor = document.getElementById('campo-materias-profesor');
    const materiasProfesorFilas = document.getElementById('materias-profesor-filas');

    // Materias disponibles, para armar los <select> de las filas dinámicas.
    const materiasDisponibles = @json($materias->map(fn ($materia) => [
        'id' => $materia->id,
        'nombre' => $materia->nombre . (
            $materia->curso
                ? ' (' . ($materia->curso->anio ?? '') . '° ' . $materia->curso->nombre . ')'
                : ''
        ),
    ])->values());

    // Materias que ya estaban seleccionadas (si el formulario volvió con
    // errores de validación), para no perderlas al recargar la página.
    const materiaIdsPrevias = @json(old('materia_ids', []));
    const materiaNuevasPrevias = @json(old('materia_nuevas', []));

    function crearFilaMateria(materiaIdSeleccionada, nombreNuevoPrevio) {
        const fila = document.createElement('div');
        fila.className = 'se-ad-fila';

        const controles = document.createElement('div');
        controles.className = 'se-ad-fila-controles';

        const select = document.createElement('select');
        select.name = 'materia_ids[]';
        select.className = 'se-ad-input';
        select.setAttribute('aria-label', 'Materia que va a enseñar');

        const opcionVacia = document.createElement('option');
        opcionVacia.value = '';
        opcionVacia.textContent = 'Seleccionar materia';
        select.appendChild(opcionVacia);

        materiasDisponibles.forEach(function (materia) {
            const opcion = document.createElement('option');
            opcion.value = materia.id;
            opcion.textContent = materia.nombre;
            if (materiaIdSeleccionada && String(materiaIdSeleccionada) === String(materia.id)) {
                opcion.selected = true;
            }
            select.appendChild(opcion);
        });

        const opcionNueva = document.createElement('option');
        opcionNueva.value = '__nueva__';
        opcionNueva.textContent = '+ Escribir materia nueva';
        if (materiaIdSeleccionada === '__nueva__') {
            opcionNueva.selected = true;
        }
        select.appendChild(opcionNueva);

        const inputNueva = document.createElement('input');
        inputNueva.type = 'text';
        inputNueva.name = 'materia_nuevas[]';
        inputNueva.className = 'se-ad-input';
        inputNueva.placeholder = 'Nombre de la materia nueva (ej: Historia)';
        inputNueva.setAttribute('aria-label', 'Nombre de la materia nueva');
        inputNueva.value = nombreNuevoPrevio || '';
        inputNueva.hidden = materiaIdSeleccionada !== '__nueva__';

        select.addEventListener('change', function () {
            const esNueva = select.value === '__nueva__';
            inputNueva.hidden = !esNueva;
            if (esNueva) {
                inputNueva.focus();
            } else {
                inputNueva.value = '';
            }
        });

        const botonQuitar = document.createElement('button');
        botonQuitar.type = 'button';
        botonQuitar.className = 'se-ad-quitar';
        botonQuitar.title = 'Quitar materia';
        botonQuitar.setAttribute('aria-label', 'Quitar materia');
        botonQuitar.textContent = '×';
        botonQuitar.onclick = function () {
            // Siempre dejamos al menos una fila visible.
            if (materiasProfesorFilas.children.length > 1) {
                fila.remove();
            } else {
                select.value = '';
                inputNueva.value = '';
                inputNueva.hidden = true;
            }
        };

        controles.appendChild(select);
        controles.appendChild(botonQuitar);

        fila.appendChild(controles);
        fila.appendChild(inputNueva);
        materiasProfesorFilas.appendChild(fila);
    }

    function agregarFilaMateria(materiaIdSeleccionada, nombreNuevoPrevio) {
        crearFilaMateria(materiaIdSeleccionada || '', nombreNuevoPrevio || '');
    }

    function actualizarCamposAlumno() {
        const esAlumno = rol.value === 'alumno';
        const esProfesor = rol.value === 'profesor';

        cursoSelect.disabled = !esAlumno;

        if (esAlumno) {
            cursoPlaceholder.textContent = 'Seleccionar curso';
        } else {
            cursoSelect.value = '';
            cursoPlaceholder.textContent = 'No aplica para este rol';
        }

        campoMateriasProfesor.hidden = !esProfesor;

        if (esProfesor && materiasProfesorFilas.children.length === 0) {
            if (materiaIdsPrevias.length > 0) {
                materiaIdsPrevias.forEach(function (materiaId, indice) {
                    agregarFilaMateria(materiaId, materiaNuevasPrevias[indice]);
                });
            } else {
                agregarFilaMateria();
            }
        }
    }

    function mostrarFormularioUsuario() {
        panelNuevoUsuario.hidden = false;
        panelNuevoUsuario.scrollIntoView({ behavior: 'smooth' });
    }

    function ocultarFormularioUsuario() {
        panelNuevoUsuario.hidden = true;
    }

    function alternarPassword(campoId, boton) {
        const campo = document.getElementById(campoId);
        const mostrar = campo.type === 'password';

        campo.type = mostrar ? 'text' : 'password';
        boton.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');

        boton.innerHTML = mostrar
            ? '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3.5 3.5l17 17M10.6 5.7A10.7 10.7 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a15.6 15.6 0 0 1-3.1 3.9M7.4 7.3C4.8 8.9 2.5 12 2.5 12s3.5 6.5 9.5 6.5a9.7 9.7 0 0 0 3.4-.6M9.9 10a2.6 2.6 0 0 0 3.6 3.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>'
            : '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" /><circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.8" /></svg>';
    }

    rol.addEventListener('change', actualizarCamposAlumno);
    actualizarCamposAlumno();

    // Si se llega desde "Nuevo alumno" (en la página de Alumnos), se
    // preselecciona el rol y se abre el formulario automáticamente.
    const parametros = new URLSearchParams(window.location.search);
    if (parametros.get('rol') === 'alumno' && !rol.value) {
        rol.value = 'alumno';
        actualizarCamposAlumno();
        mostrarFormularioUsuario();
    }

    @if($errors->any())
        panelNuevoUsuario.scrollIntoView({ behavior: 'smooth' });
    @endif
</script>
@endpush

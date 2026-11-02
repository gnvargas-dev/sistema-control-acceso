@extends('layouts.dashboard')

@section('title', 'Legajos')

@section('content')
<header class="se-saludo se-ad-cab">
    <div>
        <h1>Legajos</h1>
        <p>Gestione la documentación y legajos de los alumnos</p>
    </div>

    <button type="button" class="se-btn" onclick="abrirModalNuevoLegajo()">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" /></svg>
        Nuevo legajo
    </button>
</header>

@if(session('success'))
    <div class="se-aviso se-aviso--exito" role="status">{{ session('success') }}</div>
@endif

<section class="se-stats" aria-label="Resumen de legajos">
        <article class="se-stat se-c-azul">
            <span class="se-stat-icono">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                </svg>
            </span>
            <div class="se-stat-cuerpo">
                <span class="se-stat-titulo">Total legajos</span>
                <span class="se-stat-valor">{{ $estadisticas['total'] }}</span>
                <span class="se-stat-pie">Todos los cursos</span>
            </div>
        </article>
        <article class="se-stat se-c-verde">
            <span class="se-stat-icono">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M8 6h8a2 2 0 0 1 2 2v11l-2-1.3L14 19l-2-1.3L10 19l-2-1.3L6 19V8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                    <path d="m9.5 12 1.8 1.8L14.5 10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <div class="se-stat-cuerpo">
                <span class="se-stat-titulo">Legajos completos</span>
                <span class="se-stat-valor">{{ $estadisticas['completos'] }}</span>
                <span class="se-stat-pie">{{ $estadisticas['completos_pct'] }}% del total</span>
            </div>
        </article>
        <article class="se-stat se-c-naranja">
            <span class="se-stat-icono">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M8 6h8a2 2 0 0 1 2 2v11l-2-1.3L14 19l-2-1.3L10 19l-2-1.3L6 19V8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                    <path d="M12 9.5v3.2l2 1.3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <div class="se-stat-cuerpo">
                <span class="se-stat-titulo">Legajos incompletos</span>
                <span class="se-stat-valor">{{ $estadisticas['incompletos'] }}</span>
                <span class="se-stat-pie">{{ $estadisticas['incompletos_pct'] }}% del total</span>
            </div>
        </article>
        <article class="se-stat se-c-rojo">
            <span class="se-stat-icono">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M8 6h8a2 2 0 0 1 2 2v11l-2-1.3L14 19l-2-1.3L10 19l-2-1.3L6 19V8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                    <path d="M10 11.5 14 15m0-3.5-4 3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg>
            </span>
            <div class="se-stat-cuerpo">
                <span class="se-stat-titulo">Sin documentos</span>
                <span class="se-stat-valor">{{ $estadisticas['sin_documentos'] }}</span>
                <span class="se-stat-pie">{{ $estadisticas['sin_documentos_pct'] }}% del total</span>
            </div>
        </article>
</section>

<section class="se-panel">
    <header class="se-panel-cab">
        <h2>Lista de legajos</h2>
    </header>

    <form method="GET" action="{{ route('admin.legajos') }}" class="se-ad-filtros">
        <div class="se-ad-filtro se-ad-filtro--ancho">
            <label class="se-ad-label" for="legajos-busqueda-input">Buscar alumno</label>
            <div class="se-ad-buscar">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="10.8" cy="10.8" r="6.3" stroke="currentColor" stroke-width="1.8" />
                    <path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg>
                <input
                    id="legajos-busqueda-input"
                    class="se-ad-input"
                    type="search"
                    name="q"
                    value="{{ $busqueda }}"
                    placeholder="Buscar por nombre o apellido..."
                    autocomplete="off"
                >
            </div>
        </div>

        <div class="se-ad-filtro">
            <label class="se-ad-label" for="legajos-filtro-curso">Curso</label>
            <select id="legajos-filtro-curso" class="se-ad-input" name="curso_id">
                <option value="">Todos los cursos</option>
                @foreach($cursos as $curso)
                    <option value="{{ $curso->id }}" @selected((string) request('curso_id') === (string) $curso->id)>
                        {{ $curso->anio ?? '' }}{{ isset($curso->anio) ? '° ' : '' }}{{ $curso->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="se-ad-filtro">
            <label class="se-ad-label" for="legajos-filtro-estado">Estado del legajo</label>
            <select id="legajos-filtro-estado" class="se-ad-input" name="estado">
                <option value="">Todos los estados</option>
                <option value="completo" @selected(request('estado') === 'completo')>Completo</option>
                <option value="incompleto" @selected(request('estado') === 'incompleto')>Incompleto</option>
                <option value="sin_documentos" @selected(request('estado') === 'sin_documentos')>Sin documentos</option>
            </select>
        </div>

        <div class="se-ad-filtro-acciones">
            <button type="submit" class="se-btn">Filtrar</button>
            <a href="{{ route('admin.legajos') }}" class="se-btn se-btn--suave">Limpiar filtros</a>
        </div>
    </form>

    <div class="se-tabla-wrap" tabindex="0" role="region" aria-label="Listado de legajos">
        <table class="se-tabla">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Alumno</th>
                    <th>Curso</th>
                    <th>Estado</th>
                    <th>Documentos</th>
                    <th>Última actualización</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse($alumnos as $alumno)
                    <tr>
                        <td class="se-ad-id">{{ $alumno->id }}</td>
                        <td><span class="se-tabla-titulo">{{ $alumno->usuario->nombre ?? 'Alumno sin nombre' }}</span></td>
                        <td>
                            <span class="se-badge se-badge--info se-badge--sin-punto">
                                @if($alumno->curso)
                                    {{ $alumno->curso->anio ?? '' }}{{ isset($alumno->curso->anio) ? '° ' : '' }}{{ $alumno->curso->nombre }}
                                @else
                                    Sin curso
                                @endif
                            </span>
                        </td>
                        <td>
                            <span class="se-badge se-badge--{{ ['completo' => 'aprobado', 'incompleto' => 'riesgo'][$alumno->estado_legajo] ?? 'reprobado' }}">
                                @if($alumno->estado_legajo === 'completo')
                                    Completo
                                @elseif($alumno->estado_legajo === 'incompleto')
                                    Incompleto
                                @else
                                    Sin documentos
                                @endif
                            </span>
                        </td>
                        <td>{{ $alumno->documentos_entregados }} / {{ $alumno->documentos_total }} documentos</td>
                        <td>
                            {{ $alumno->ultima_actualizacion ? \Illuminate\Support\Carbon::parse($alumno->ultima_actualizacion)->format('d/m/Y H:i') : '—' }}
                        </td>
                        <td>
                            <div class="se-ad-acciones">
                                <button
                                    type="button"
                                    class="se-ad-icono"
                                    title="Ver legajo"
                                    aria-label="Ver legajo"
                                    onclick="abrirModalLegajo({{ $alumno->id }}, 'ver')"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                                        <circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.8" />
                                    </svg>
                                </button>

                                <button
                                    type="button"
                                    class="se-ad-icono"
                                    title="Gestionar documentos"
                                    aria-label="Gestionar documentos"
                                    onclick="abrirModalLegajo({{ $alumno->id }}, 'gestionar')"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M3.5 8.2c0-1 .8-1.7 1.8-1.7h4l1.6 1.7h7.8c1 0 1.8.8 1.8 1.8v7.2c0 1-.8 1.8-1.8 1.8H5.3c-1 0-1.8-.8-1.8-1.8V8.2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="se-vacio">
                            No se encontraron legajos con los filtros seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="se-ad-pie">
        <span>
            Mostrando {{ $alumnos->count() ? $alumnos->firstItem() : 0 }} a {{ $alumnos->count() ? $alumnos->lastItem() : 0 }} de {{ $alumnos->total() }} legajos
        </span>

        {{ $alumnos->onEachSide(1)->links() }}
    </div>
</section>

<input type="hidden" id="legajo-csrf-token" value="{{ csrf_token() }}">

<dialog id="legajo-modal-fondo" class="se-modal se-modal--form" aria-labelledby="legajo-modal-titulo">
    <div class="se-modal-caja">
        <h2 id="legajo-modal-titulo" class="se-modal-titulo">Nuevo legajo</h2>
        <p id="legajo-modal-subtitulo" class="se-modal-mensaje">Seleccioná un alumno para gestionar su legajo.</p>

        <div id="legajo-modal-mensaje" aria-live="polite"></div>

        <div id="legajo-selector-alumno" class="se-ad-campo">
            <label class="se-ad-label" for="legajo-alumno-select">Alumno</label>
            <select id="legajo-alumno-select" class="se-ad-input">
                <option value="">Seleccionar alumno</option>
                @foreach($alumnosParaSelect as $alumnoOpcion)
                    <option value="{{ $alumnoOpcion->id }}">
                        {{ $alumnoOpcion->usuario->nombre ?? 'Alumno #' . $alumnoOpcion->id }}
                        —
                        {{ $alumnoOpcion->curso ? (($alumnoOpcion->curso->anio ?? '') . '° ' . $alumnoOpcion->curso->nombre) : 'Sin curso' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div id="legajo-info-alumno" class="se-ad-info" hidden>
            <strong id="legajo-info-nombre"></strong>
            <small id="legajo-info-progreso"></small>
        </div>

        <div id="legajo-contenido">
            <p class="se-ad-vacio">Elegí un alumno para ver o cargar su documentación.</p>
        </div>

        <div class="se-modal-acciones">
            <button type="button" class="se-btn se-btn--suave" onclick="cerrarModalLegajo()">Cerrar</button>
            <button type="button" class="se-btn" id="legajo-guardar-btn" onclick="guardarLegajoDocumentos()" hidden>
                Guardar cambios
            </button>
        </div>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
    const legajoUrlDocumentosBase = "{{ route('admin.legajos.documentos', ['alumno' => 0]) }}";
    const legajoCsrfToken = document.getElementById('legajo-csrf-token').value;

    let legajoAlumnoActual = null;
    let legajoModoActual = 'ver';

    const vacioLegajo = '<p class="se-ad-vacio">Elegí un alumno para ver o cargar su documentación.</p>';

    function urlDocumentos(alumnoId) {
        return legajoUrlDocumentosBase.replace('/0/', '/' + alumnoId + '/');
    }

    function mensajeLegajo(texto) {
        const contenedor = document.getElementById('legajo-modal-mensaje');
        contenedor.innerHTML = '';

        if (texto) {
            const aviso = document.createElement('p');
            aviso.className = 'se-aviso se-aviso--error';
            aviso.setAttribute('role', 'alert');
            aviso.textContent = texto;
            contenedor.appendChild(aviso);
        }
    }

    function limpiarModalLegajo() {
        mensajeLegajo('');
        document.getElementById('legajo-info-alumno').hidden = true;
        document.getElementById('legajo-contenido').innerHTML = vacioLegajo;
        document.getElementById('legajo-guardar-btn').hidden = true;
    }

    function abrirModalNuevoLegajo() {
        legajoAlumnoActual = null;
        legajoModoActual = 'nuevo';

        document.getElementById('legajo-modal-titulo').textContent = 'Nuevo legajo';
        document.getElementById('legajo-modal-subtitulo').textContent =
            'Seleccioná un alumno para cargar la documentación de su legajo.';
        document.getElementById('legajo-selector-alumno').hidden = false;
        document.getElementById('legajo-alumno-select').value = '';

        limpiarModalLegajo();

        window.seDialogo.abrir(document.getElementById('legajo-modal-fondo'));
    }

    function abrirModalLegajo(alumnoId, modo) {
        legajoAlumnoActual = alumnoId;
        legajoModoActual = modo;

        document.getElementById('legajo-selector-alumno').hidden = true;
        document.getElementById('legajo-modal-titulo').textContent =
            modo === 'ver' ? 'Ver legajo' : 'Gestionar documentos del legajo';
        document.getElementById('legajo-modal-subtitulo').textContent =
            modo === 'ver'
                ? 'Documentación registrada para este alumno.'
                : 'Marcá los documentos que ya fueron entregados.';

        limpiarModalLegajo();
        window.seDialogo.abrir(document.getElementById('legajo-modal-fondo'));

        cargarDocumentosAlumno(alumnoId);
    }

    function cerrarModalLegajo() {
        window.seDialogo.cerrar(document.getElementById('legajo-modal-fondo'));
    }

    document.getElementById('legajo-alumno-select').addEventListener('change', function () {
        const alumnoId = this.value;

        if (!alumnoId) {
            legajoAlumnoActual = null;
            limpiarModalLegajo();
            return;
        }

        legajoAlumnoActual = alumnoId;
        cargarDocumentosAlumno(alumnoId);
    });

    function cargarDocumentosAlumno(alumnoId) {
        const contenedor = document.getElementById('legajo-contenido');
        contenedor.innerHTML = '<p class="se-ad-vacio">Cargando documentación...</p>';

        fetch(urlDocumentos(alumnoId), {
            headers: { 'Accept': 'application/json' },
        })
            .then(function (respuesta) {
                if (!respuesta.ok) {
                    throw new Error('No se pudo cargar la documentación del alumno.');
                }
                return respuesta.json();
            })
            .then(function (datos) {
                renderizarLegajo(datos);
            })
            .catch(function () {
                contenedor.innerHTML = '';
                mensajeLegajo('Ocurrió un error al cargar la documentación. Intentá nuevamente.');
            });
    }

    function renderizarLegajo(datos) {
        document.getElementById('legajo-info-alumno').hidden = false;
        document.getElementById('legajo-info-nombre').textContent =
            datos.alumno.nombre + ' — ' + datos.alumno.curso;
        document.getElementById('legajo-info-progreso').textContent =
            datos.entregados + ' de ' + datos.total + ' documentos entregados';

        const soloLectura = legajoModoActual === 'ver';
        const contenedor = document.getElementById('legajo-contenido');
        contenedor.innerHTML = '';

        datos.categorias.forEach(function (categoria) {
            const bloque = document.createElement('div');
            bloque.className = 'se-ad-cat';

            const titulo = document.createElement('h3');
            titulo.className = 'se-ad-cat-titulo';
            titulo.textContent = categoria.nombre;
            bloque.appendChild(titulo);

            categoria.items.forEach(function (item) {
                const fila = document.createElement('label');
                fila.className = 'se-ad-doc';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.value = item.slug;
                checkbox.checked = item.marcado;
                checkbox.disabled = soloLectura;
                checkbox.dataset.documentoSlug = item.slug;

                const texto = document.createElement('span');
                texto.textContent = item.nombre;

                fila.appendChild(checkbox);
                fila.appendChild(texto);
                bloque.appendChild(fila);
            });

            contenedor.appendChild(bloque);
        });

        document.getElementById('legajo-guardar-btn').hidden = soloLectura;
    }

    function guardarLegajoDocumentos() {
        if (!legajoAlumnoActual) {
            return;
        }

        const checkboxes = document.querySelectorAll('#legajo-contenido input[type="checkbox"]');
        const seleccionados = Array.from(checkboxes)
            .filter(function (checkbox) { return checkbox.checked; })
            .map(function (checkbox) { return checkbox.value; });

        const boton = document.getElementById('legajo-guardar-btn');
        boton.disabled = true;
        boton.textContent = 'Guardando...';
        mensajeLegajo('');

        // Mismo indicador de guardado (y mismo tope de 4 s) que los formularios.
        window.seSpinner.mostrar('Guardando…');

        const cuerpo = new URLSearchParams();
        seleccionados.forEach(function (slug) {
            cuerpo.append('documentos[]', slug);
        });

        fetch(urlDocumentos(legajoAlumnoActual), {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': legajoCsrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: cuerpo.toString(),
        })
            .then(function (respuesta) {
                if (!respuesta.ok) {
                    throw new Error('No se pudo guardar la documentación.');
                }
                return respuesta.json();
            })
            .then(function () {
                window.location.reload();
            })
            .catch(function () {
                window.seSpinner.ocultar();
                boton.disabled = false;
                boton.textContent = 'Guardar cambios';
                mensajeLegajo('Ocurrió un error al guardar los cambios. Intentá nuevamente.');
            });
    }
</script>
@endpush

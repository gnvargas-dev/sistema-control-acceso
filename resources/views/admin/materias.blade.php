@extends('layouts.dashboard')

@section('title', 'Materias')

@section('content')
<header class="se-saludo se-ad-cab">
    <div>
        <h1>Materias</h1>
        <p>Gestione las materias del sistema escolar</p>
    </div>

    <button type="button" class="se-btn" aria-label="Nueva materia" onclick="abrirModalMateria()">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" /></svg>
        Nueva materia
    </button>
</header>

@if(session('success'))
    <div class="se-aviso se-aviso--exito" role="status">{{ session('success') }}</div>
@endif

<section class="se-panel">
    <div class="se-ad-barra">
        <div class="se-ad-buscar">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="10.8" cy="10.8" r="6.3" stroke="currentColor" stroke-width="1.8" />
                <path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>

            <input
                id="materias-busqueda"
                class="se-ad-input"
                type="search"
                placeholder="Buscar materia..."
                autocomplete="off"
                aria-label="Buscar materia"
            >
        </div>

        <select id="materias-filtro-curso" class="se-ad-input se-ad-select" aria-label="Filtrar por curso">
            <option value="">Todos los cursos</option>

            @foreach($cursos as $curso)
                <option value="{{ $curso->id }}">
                    {{ $curso->anio ?? '' }}{{ isset($curso->anio) ? '° ' : '' }}{{ $curso->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="se-tabla-wrap" tabindex="0" role="region" aria-label="Listado de materias">
        <table class="se-tabla">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre de la materia</th>
                    <th>Curso asignado</th>
                    <th>Profesor</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody id="materias-tabla-body">
                @forelse($materias as $materia)
                    <tr
                        class="materia-fila"
                        data-nombre="{{ strtolower($materia->nombre) }}"
                        data-curso="{{ $materia->curso_id }}"
                    >
                        <td class="se-ad-id">{{ $materia->id }}</td>

                        <td><span class="se-tabla-titulo">{{ $materia->nombre }}</span></td>

                        <td>
                            <span class="se-badge se-badge--info se-badge--sin-punto">
                                @if($materia->curso)
                                    {{ $materia->curso->anio ?? '' }}{{ isset($materia->curso->anio) ? '° ' : '' }}{{ $materia->curso->nombre }}
                                @else
                                    Sin curso
                                @endif
                            </span>
                        </td>

                        <td>
                            @if($materia->profesores->isNotEmpty())
                                <div class="se-ad-chips">
                                    @foreach($materia->profesores as $profesor)
                                        <span class="se-ad-chip">{{ $profesor->usuario->nombre ?? 'Sin nombre' }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="se-ad-sin">Sin profesor asignado</span>
                            @endif
                        </td>

                        <td>
                            <div class="se-ad-acciones">
                                <button
                                    type="button"
                                    class="se-ad-icono"
                                    title="Editar materia"
                                    aria-label="Editar materia"
                                    onclick="abrirModalEditarMateria({{ $materia->id }}, @js($materia->nombre), {{ $materia->curso_id }}, @js($materia->profesores->pluck('id')->all()))"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="m4 16.5-.8 3.8 3.8-.8L18.5 8a2.8 2.8 0 0 0-4-4L3 16.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                                        <path d="m13.5 5.5 5 5" stroke="currentColor" stroke-width="1.8" />
                                    </svg>
                                </button>

                                <form
                                    method="POST"
                                    action="{{ route('admin.materias.eliminar', $materia) }}"
                                    data-se-confirmar="¿Seguro que querés eliminar la materia &quot;{{ $materia->nombre }}&quot;? Esta acción no se puede deshacer."
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="se-ad-icono se-ad-icono--peligro"
                                        title="Eliminar materia"
                                        aria-label="Eliminar materia"
                                        data-se-confirmar-titulo="Eliminar materia"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M5 7h14M10 11v6M14 11v6M9 7V4.5h6V7M7 7l.8 13h8.4L17 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="se-vacio">No hay materias registradas.</td>
                    </tr>
                @endforelse

                <tr id="materias-sin-resultados" hidden>
                    <td colspan="5" class="se-vacio">No se encontraron materias con los filtros seleccionados.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="se-ad-pie">
        <span id="materias-resumen">Mostrando 0 a 0 de 0 materias</span>

        <div id="materias-paginacion" class="se-ad-paginas" aria-label="Paginación de materias"></div>
    </div>
</section>

<dialog id="materias-modal-fondo" class="se-modal se-modal--form" aria-labelledby="materias-modal-titulo">
    <div class="se-modal-caja">
        <h2 id="materias-modal-titulo" class="se-modal-titulo">Nueva materia</h2>
        <p id="materias-modal-subtitulo" class="se-modal-mensaje">Complete los datos para agregar una materia al sistema.</p>

        @if($errors->any())
            <div class="se-aviso se-aviso--error" role="alert">
                @foreach($errors->all() as $mensaje)
                    <div>{{ $mensaje }}</div>
                @endforeach
            </div>
        @endif

        <form id="materia-form" method="POST" action="{{ route('admin.materias.guardar') }}" class="se-ad-form se-ad-form--una">
            @csrf
            <input type="hidden" name="_method" id="materia-method" value="">

            <div class="se-ad-campo">
                <label class="se-ad-label" for="materia-nombre">Nombre de la materia</label>
                <input
                    id="materia-nombre"
                    class="se-ad-input"
                    type="text"
                    name="nombre"
                    placeholder="Ej: Matemática"
                    required
                >
            </div>

            <div class="se-ad-campo">
                <label class="se-ad-label" for="materia-curso">Curso asignado</label>
                <select id="materia-curso" class="se-ad-input" name="curso_id" required>
                    <option value="" disabled selected>Seleccionar curso</option>

                    @foreach($cursos as $curso)
                        <option value="{{ $curso->id }}">
                            {{ $curso->anio ?? '' }}{{ isset($curso->anio) ? '° ' : '' }}{{ $curso->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="se-ad-campo">
                <label class="se-ad-label" for="materia-profesores">Profesor(es) de la materia</label>
                <select id="materia-profesores" class="se-ad-input" name="profesor_ids[]" multiple>
                    @foreach($profesores as $profesor)
                        <option value="{{ $profesor->id }}">
                            {{ $profesor->usuario->nombre ?? 'Profesor #' . $profesor->id }}
                        </option>
                    @endforeach
                </select>
                <p class="se-ad-ayuda">
                    Mantené presionada la tecla Ctrl (o Cmd en Mac) para elegir más de un profesor.
                </p>
            </div>

            <div class="se-ad-form-pie">
                <button type="button" class="se-btn se-btn--suave" onclick="cerrarModalMateria()">Cancelar</button>
                <button type="submit" class="se-btn" id="materia-submit-btn">Guardar materia</button>
            </div>
        </form>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
    // URL de creación (fija) y plantilla de la URL de actualización, con
    // "0" como marcador de posición que se reemplaza por el ID real.
    const materiaUrlGuardar = "{{ route('admin.materias.guardar') }}";
    const materiaUrlActualizarBase = "{{ route('admin.materias.actualizar', ['materia' => 0]) }}";

    function abrirModalMateria() {
        const form = document.getElementById('materia-form');
        const titulo = document.getElementById('materias-modal-titulo');
        const subtitulo = document.getElementById('materias-modal-subtitulo');
        const boton = document.getElementById('materia-submit-btn');

        form.reset();
        form.action = materiaUrlGuardar;
        document.getElementById('materia-method').value = '';
        titulo.textContent = 'Nueva materia';
        subtitulo.textContent = 'Complete los datos para agregar una materia al sistema.';
        boton.textContent = 'Guardar materia';

        window.seDialogo.abrir(document.getElementById('materias-modal-fondo'));

        const primerCampo = document.getElementById('materia-nombre');
        if (primerCampo) {
            primerCampo.focus();
        }
    }

    function abrirModalEditarMateria(id, nombre, cursoId, profesorIds) {
        const form = document.getElementById('materia-form');
        const titulo = document.getElementById('materias-modal-titulo');
        const subtitulo = document.getElementById('materias-modal-subtitulo');
        const boton = document.getElementById('materia-submit-btn');

        form.reset();
        form.action = materiaUrlActualizarBase.replace(/0(?=[^/]*$)/, id);
        document.getElementById('materia-method').value = 'PUT';
        titulo.textContent = 'Editar materia';
        subtitulo.textContent = 'Actualizá los datos de la materia seleccionada.';
        boton.textContent = 'Guardar cambios';

        document.getElementById('materia-nombre').value = nombre || '';
        document.getElementById('materia-curso').value = cursoId || '';

        const selectProfesores = document.getElementById('materia-profesores');
        const idsSeleccionados = (profesorIds || []).map(String);
        Array.from(selectProfesores.options).forEach(function (opcion) {
            opcion.selected = idsSeleccionados.includes(opcion.value);
        });

        window.seDialogo.abrir(document.getElementById('materias-modal-fondo'));

        const primerCampo = document.getElementById('materia-nombre');
        if (primerCampo) {
            primerCampo.focus();
        }
    }

    function cerrarModalMateria() {
        window.seDialogo.cerrar(document.getElementById('materias-modal-fondo'));
    }

    document.addEventListener('DOMContentLoaded', function () {
        @if($errors->any())
            abrirModalMateria();
        @endif

        const filas = Array.from(
            document.querySelectorAll('.materia-fila')
        );

        const buscador = document.getElementById('materias-busqueda');
        const filtroCurso = document.getElementById('materias-filtro-curso');
        const resumen = document.getElementById('materias-resumen');
        const paginacion = document.getElementById('materias-paginacion');
        const sinResultados = document.getElementById('materias-sin-resultados');

        const porPagina = 5;
        let paginaActual = 1;
        let filasFiltradas = [...filas];

        function aplicarFiltros() {
            const termino = (buscador.value || '').trim().toLowerCase();
            const cursoId = filtroCurso.value || '';

            filasFiltradas = filas.filter(function (fila) {
                const nombre = fila.dataset.nombre || '';
                const curso = fila.dataset.curso || '';

                const coincideNombre = !termino || nombre.includes(termino);
                const coincideCurso = !cursoId || curso === cursoId;

                return coincideNombre && coincideCurso;
            });

            paginaActual = 1;
            renderizar();
        }

        function renderizar() {
            const total = filasFiltradas.length;
            const totalPaginas = Math.max(1, Math.ceil(total / porPagina));

            if (paginaActual > totalPaginas) {
                paginaActual = totalPaginas;
            }

            filas.forEach(function (fila) {
                fila.hidden = true;
            });

            const inicio = total === 0
                ? 0
                : (paginaActual - 1) * porPagina;

            const fin = Math.min(inicio + porPagina, total);

            filasFiltradas
                .slice(inicio, fin)
                .forEach(function (fila) {
                    fila.hidden = false;
                });

            if (total === 0) {
                resumen.textContent = 'Mostrando 0 a 0 de 0 materias';
                sinResultados.hidden = false;
            } else {
                resumen.textContent =
                    'Mostrando ' + (inicio + 1) +
                    ' a ' + fin +
                    ' de ' + total +
                    ' materias';

                sinResultados.hidden = true;
            }

            renderizarPaginacion(totalPaginas);
        }

        function renderizarPaginacion(totalPaginas) {
            paginacion.innerHTML = '';

            if (filasFiltradas.length === 0 || totalPaginas <= 1) {
                return;
            }

            const anterior = document.createElement('button');
            anterior.type = 'button';
            anterior.className = 'se-ad-pagina';
            anterior.disabled = paginaActual === 1;
            anterior.setAttribute('aria-label', 'Anterior');
            anterior.innerHTML = `
                <svg viewBox="0 0 16 16" fill="none" aria-hidden="true">
                    <path d="M10 2 4 8l6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            `;

            anterior.addEventListener('click', function () {
                if (paginaActual > 1) {
                    paginaActual--;
                    renderizar();
                }
            });

            paginacion.appendChild(anterior);

            for (let pagina = 1; pagina <= totalPaginas; pagina++) {
                const boton = document.createElement('button');

                boton.type = 'button';
                boton.className = 'se-ad-pagina';
                boton.textContent = pagina;
                boton.setAttribute('aria-label', 'Página ' + pagina);

                if (pagina === paginaActual) {
                    boton.setAttribute('aria-current', 'page');
                }

                boton.addEventListener('click', function () {
                    paginaActual = pagina;
                    renderizar();
                });

                paginacion.appendChild(boton);
            }

            const siguiente = document.createElement('button');
            siguiente.type = 'button';
            siguiente.className = 'se-ad-pagina';
            siguiente.disabled = paginaActual === totalPaginas;
            siguiente.setAttribute('aria-label', 'Siguiente');
            siguiente.innerHTML = `
                <svg viewBox="0 0 16 16" fill="none" aria-hidden="true">
                    <path d="m6 2 6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            `;

            siguiente.addEventListener('click', function () {
                if (paginaActual < totalPaginas) {
                    paginaActual++;
                    renderizar();
                }
            });

            paginacion.appendChild(siguiente);
        }

        buscador.addEventListener('input', aplicarFiltros);
        filtroCurso.addEventListener('change', aplicarFiltros);

        renderizar();
    });
</script>
@endpush

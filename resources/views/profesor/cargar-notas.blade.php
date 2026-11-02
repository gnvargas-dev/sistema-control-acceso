@extends('layouts.profesor')

@section('title', 'Cargar notas')

@php
    $fmt = fn ($v) => ($v === null || $v === '') ? '' : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    $nombreSemestre = $semestre === 1 ? 'Primer semestre' : 'Segundo semestre';
    $minParciales = \App\Services\EvaluacionService::MIN_PARCIALES;
    $maxParciales = \App\Services\EvaluacionService::MAX_PARCIALES;
    $puntosMin = \App\Services\EvaluacionService::PUNTOS_MIN;
    $puntosMax = \App\Services\EvaluacionService::PUNTOS_MAX;
    $evaluaciones = $materia ? $parciales->concat($examen ? [$examen] : []) : collect();
    $calculable = $materia && $parciales->count() >= $minParciales && $examen;
    $aprob = \App\Valores\Calificacion::APROBACION;
@endphp

@section('content')
<header class="se-saludo">
    <div>
        <h1>Cargar notas</h1>
        <p>Cargá cada parcial y el examen final de cada semestre cuando los tomes, junto con los puntos de tus alumnos: la nota final se calcula sola.</p>
    </div>
    <span class="se-badge se-badge--claro se-badge--sin-punto">Escala 1 a 5</span>
</header>

@if(session('success'))
    <div class="se-aviso se-aviso--exito" role="status">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="se-aviso se-aviso--error" role="alert">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

@if(! $materia)
    <section class="se-panel se-elevar se-c-azul">
        <p class="se-vacio">Todavía no tenés materias asignadas.</p>
    </section>
@else
    <section class="se-panel se-elevar se-c-azul">
        <div class="se-panel-cab">
            <h2>Materia y semestre</h2>
        </div>

        <form method="GET" action="{{ route('profesor.cargar-notas') }}">
            <div class="se-campos">
                <div class="se-campo">
                    <label for="materia_id">Materia</label>
                    <select id="materia_id" name="materia_id">
                        @foreach($materias as $m)
                            <option value="{{ $m->id }}" @selected($m->id === $materia->id)>{{ $m->nombre }}{{ $m->curso ? ' — ' . ($m->curso->anio ?? '') . '° ' . $m->curso->nombre : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="se-campo">
                    <label for="semestre">Semestre</label>
                    <select id="semestre" name="semestre">
                        <option value="1" @selected($semestre === 1)>Primer semestre</option>
                        <option value="2" @selected($semestre === 2)>Segundo semestre</option>
                    </select>
                </div>
            </div>
            <div class="se-form-pie">
                <div class="se-reglas">
                    <span class="se-badge se-badge--neutro se-badge--sin-punto">Parciales 60%</span>
                    <span class="se-badge se-badge--neutro se-badge--sin-punto">Examen final 40%</span>
                    <span class="se-badge se-badge--neutro se-badge--sin-punto">Mínimo 70% en cada bloque</span>
                </div>
                <button type="submit" class="se-btn se-btn--suave">Ver</button>
            </div>
        </form>
    </section>

    <section class="se-panel se-elevar se-c-verde">
        <div class="se-panel-cab">
            <h2>Evaluaciones · {{ $nombreSemestre }} · {{ $materia->nombre }}</h2>
            <span class="se-badge se-badge--info se-badge--sin-punto">Hasta {{ $maxParciales }} parciales y un examen final</span>
        </div>

        <p class="se-vacio se-vacio--izq">
            Cargá cada evaluación cuando la tomes, una por una: no hace falta definir todas juntas.
            La nota final de cada alumno se calcula cuando hay al menos {{ $minParciales }} parciales y el examen final con todos los puntos cargados.
            Puntaje máximo: número entero de {{ $puntosMin }} a {{ $puntosMax }}.
        </p>

        <div class="se-eval-lista">
            @foreach($parciales as $e)
                <form method="POST" action="{{ route('profesor.guardar-evaluaciones') }}" class="se-eval-fila">
                    @csrf
                    <input type="hidden" name="materia_id" value="{{ $materia->id }}">
                    <input type="hidden" name="semestre" value="{{ $semestre }}">
                    <input type="hidden" name="evaluacion_id" value="{{ $e->id }}">
                    <input type="hidden" name="confirmar_borrado" value="0" data-se-confirmado>
                    <div>
                        <label>Parcial {{ $e->orden }}</label>
                        <input type="text" name="nombre" value="{{ $e->nombre }}" maxlength="100">
                    </div>
                    <div>
                        <label>Puntos máximos</label>
                        <input type="number" name="puntos_max" value="{{ $e->puntos_max }}" min="{{ $puntosMin }}" max="{{ $puntosMax }}" step="1" inputmode="numeric" required>
                    </div>
                    <div class="se-eval-botones">
                        <button type="submit" name="accion" value="parcial" class="se-btn se-btn--mini">Guardar</button>
                        <button type="submit" name="accion" value="quitar" class="se-btn se-btn--mini se-btn--peligro" @if(in_array($e->id, $conPuntos, true)) data-se-confirmar="Este parcial ya tiene puntos cargados: al quitarlo se borran. ¿Continuar?" @endif>Quitar</button>
                    </div>
                </form>
            @endforeach

            @if($parciales->count() < $maxParciales)
                <form method="POST" action="{{ route('profesor.guardar-evaluaciones') }}" class="se-eval-fila @if($parciales->isNotEmpty()) se-eval-nueva @endif">
                    @csrf
                    <input type="hidden" name="materia_id" value="{{ $materia->id }}">
                    <input type="hidden" name="semestre" value="{{ $semestre }}">
                    <div>
                        <label>Nuevo parcial</label>
                        <input type="text" name="nombre" maxlength="100" placeholder="Parcial {{ $parciales->count() + 1 }}">
                    </div>
                    <div>
                        <label>Puntos máximos</label>
                        <input type="number" name="puntos_max" min="{{ $puntosMin }}" max="{{ $puntosMax }}" step="1" inputmode="numeric" required>
                    </div>
                    <div class="se-eval-botones">
                        <button type="submit" name="accion" value="parcial" class="se-btn se-btn--mini se-btn--exito">Agregar parcial</button>
                    </div>
                </form>
            @endif

            <form method="POST" action="{{ route('profesor.guardar-evaluaciones') }}" class="se-eval-fila se-eval-fila--examen">
                @csrf
                <input type="hidden" name="materia_id" value="{{ $materia->id }}">
                <input type="hidden" name="semestre" value="{{ $semestre }}">
                @if($examen)
                    <input type="hidden" name="evaluacion_id" value="{{ $examen->id }}">
                    <input type="hidden" name="confirmar_borrado" value="0" data-se-confirmado>
                @endif
                <div>
                    <label>Examen final</label>
                    <input type="text" name="nombre" value="{{ $examen->nombre ?? 'Examen final' }}" maxlength="100">
                </div>
                <div>
                    <label>Puntos máximos</label>
                    <input type="number" name="puntos_max" value="{{ $examen->puntos_max ?? '' }}" min="{{ $puntosMin }}" max="{{ $puntosMax }}" step="1" inputmode="numeric" required>
                </div>
                <div class="se-eval-botones">
                    <button type="submit" name="accion" value="examen" class="se-btn se-btn--mini {{ $examen ? '' : 'se-btn--exito' }}">{{ $examen ? 'Guardar' : 'Agregar examen final' }}</button>
                    @if($examen)
                        <button type="submit" name="accion" value="quitar" class="se-btn se-btn--mini se-btn--peligro" @if(in_array($examen->id, $conPuntos, true)) data-se-confirmar="El examen final ya tiene puntos cargados: al quitarlo se borran. ¿Continuar?" @endif>Quitar</button>
                    @endif
                </div>
            </form>
        </div>
    </section>

    <section class="se-panel se-elevar se-c-azul">
        <div class="se-panel-cab">
            <h2>Puntos de los alumnos</h2>
            @if($calculable)
                <span class="se-badge se-badge--info se-badge--sin-punto">La nota final se calcula al guardar</span>
            @endif
        </div>

        @if($evaluaciones->isEmpty())
            <p class="se-vacio">Agregá un parcial o el examen final de este semestre para cargar los puntos.</p>
        @elseif($filas->isEmpty())
            <p class="se-vacio">Esta materia todavía no tiene alumnos en su curso.</p>
        @else
            @unless($calculable)
                <p class="se-vacio se-vacio--izq">
                    Podés ir cargando los puntos de cada evaluación. La nota final queda pendiente hasta que haya al menos {{ $minParciales }} parciales y el examen final.
                </p>
            @endunless

            <form method="POST" action="{{ route('profesor.guardar-resultados') }}">
                @csrf
                <input type="hidden" name="materia_id" value="{{ $materia->id }}">
                <input type="hidden" name="semestre" value="{{ $semestre }}">

                <div class="se-tabla-wrap" tabindex="0" role="region" aria-label="Puntos de los alumnos">
                    <table class="se-tabla se-tabla--fija">
                        <thead>
                            <tr>
                                <th>Alumno</th>
                                @foreach($evaluaciones as $e)
                                    <th class="se-centro">{{ $e->nombre }}<span class="se-tabla-sub">de {{ $e->puntos_max }}</span></th>
                                @endforeach
                                <th class="se-centro">Resultado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($filas as $fila)
                                <tr>
                                    <td><span class="se-tabla-titulo">{{ $fila->alumno->usuario->nombre ?? 'Alumno #' . $fila->alumno->id }}</span></td>
                                    @foreach($evaluaciones as $e)
                                        <td class="se-centro">
                                            <input class="se-input-nota" type="number" name="puntos[{{ $fila->alumno->id }}][{{ $e->id }}]" value="{{ old('puntos.' . $fila->alumno->id . '.' . $e->id, $fmt($fila->puntos[$e->id] ?? null)) }}" min="0" max="{{ $e->puntos_max }}" step="0.01" inputmode="decimal" aria-label="{{ $e->nombre }} de {{ $fila->alumno->usuario->nombre ?? 'alumno' }}">
                                        </td>
                                    @endforeach
                                    <td class="se-centro se-col-final">
                                        @if($fila->resultado)
                                            @php $nota = $fila->resultado->nota()->valor(); @endphp
                                            <span class="se-nota se-nota--final {{ $nota >= $aprob ? 'se-nota--ok' : 'se-nota--mal' }}" @if(! $fila->resultado->candadoSuperado()) title="Parciales o examen por debajo del 70%." @endif>{{ (int) $nota }}</span>
                                            <span class="se-tabla-sub">{{ $fila->resultado->total() }}/100</span>
                                        @else
                                            <span class="se-badge se-badge--neutro">Pendiente</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="se-form-pie se-form-pie--aire">
                    <span class="se-vacio se-vacio--nota">Una celda vacía significa que ese puntaje todavía no se cargó.</span>
                    <button type="submit" class="se-btn">Guardar puntos</button>
                </div>
            </form>
        @endif
    </section>
@endif
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-se-confirmar]').forEach(function (boton) {
        boton.addEventListener('click', function (e) {
            if (!window.confirm(boton.getAttribute('data-se-confirmar'))) {
                e.preventDefault();
                return;
            }
            var oculto = boton.form.querySelector('[data-se-confirmado]');
            if (oculto) oculto.value = '1';
        });
    });
</script>
@endpush

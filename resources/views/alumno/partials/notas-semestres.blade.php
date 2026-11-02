{{--
    Calificaciones por semestre · escala MEC 1-5 · reglamento de Paraguay.
    Se incluye desde alumno/notas: @include('alumno.partials.notas-semestres', ['semestres' => $semestres])

    Todo viene calculado del servidor (EvaluacionService::semestresDelAlumno): esta vista no calcula notas.
    Cada parcial y el examen se muestran apenas el profesor los carga (puntos de máximo); la nota final
    aparece cuando están todos los puntos y queda "Pendiente" mientras tanto.

    $semestres = [
        ['id' => 's1', 'nombre' => 'Primer Semestre', 'materias' => [
            ['nombre' => 'Matemática', 'profesor' => 'M. Benítez', 'asistencia' => 92.5,
             'parciales' => [['nombre' => 'Parcial 1', 'max' => 50, 'puntos' => '45.00']],
             'examen' => ['nombre' => 'Examen final', 'max' => 40, 'puntos' => null],
             'resultado' => ResultadoSemestral|null],   // null = Pendiente
        ]],
        ['id' => 's2', ...],
    ];

    Reglas: parciales 60% + examen final 40%; 70% mínimo en parciales Y en examen
    (por separado) o la nota queda en 1; asistencia mínima 70%.
--}}
@php
    $semestres = $semestres ?? [];
    $aprob = \App\Valores\Calificacion::APROBACION;
    $minRend = \App\Valores\ResultadoSemestral::MINIMO_POR_BLOQUE;
    $minAsist = \App\Services\AsistenciaMetricasService::MINIMO;

    $puntos = fn ($v) => ($v === null || $v === '') ? '—' : rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',');
    $claseNota = fn ($n) => $n === null ? 'se-nota--vacia' : ($n >= $aprob ? 'se-nota--ok' : 'se-nota--mal');
@endphp

<div class="se-notas">
    <div class="se-segmento" role="tablist" aria-label="Semestre" data-se-tabs hidden>
        @foreach($semestres as $sem)
            <button type="button" role="tab" data-se-tab="{{ $sem['id'] ?? 's' . $loop->iteration }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $sem['nombre'] }}</button>
        @endforeach
        <button type="button" role="tab" data-se-tab="todos" aria-selected="false">Ambos</button>
    </div>

    @foreach($semestres as $sem)
        @php
            $idSem = $sem['id'] ?? 's' . $loop->iteration;
            $np = max(1, collect($sem['materias'] ?? [])->map(fn ($m) => count($m['parciales'] ?? []))->max() ?? 1);

            $filas = collect($sem['materias'] ?? [])->map(function ($m) use ($minAsist) {
                $m['_nota'] = $m['resultado'] ? $m['resultado']->nota()->valor() : null;
                $m['_porAsistencia'] = $m['_nota'] !== null && isset($m['asistencia']) && (float) $m['asistencia'] < $minAsist;

                return $m;
            });

            $finales = $filas->pluck('_nota')->filter(fn ($n) => $n !== null);
            $pendientes = $filas->count() - $finales->count();
            $reprobadas = $filas->filter(fn ($m) => $m['_nota'] !== null && ($m['_nota'] < $aprob || $m['_porAsistencia']))->count();
            $promedioSem = $finales->count() ? $finales->avg() : null;
        @endphp

        <section class="se-panel se-elevar se-c-azul" data-se-panel="{{ $idSem }}" aria-labelledby="se-sem-{{ $idSem }}">
            <div class="se-panel-cab">
                <h2 id="se-sem-{{ $idSem }}">{{ $sem['nombre'] }}</h2>

                @if($filas->isEmpty() || $pendientes > 0)
                    <span class="se-badge se-badge--info">En curso</span>
                @elseif($reprobadas > 0)
                    <span class="se-badge se-badge--reprobado">{{ $reprobadas }} {{ $reprobadas === 1 ? 'reprobada' : 'reprobadas' }}</span>
                @else
                    <span class="se-badge se-badge--aprobado">Aprobado</span>
                @endif
            </div>

            <div class="se-tabla-wrap" tabindex="0" role="region" aria-label="Calificaciones del {{ $sem['nombre'] }}">
                <table class="se-tabla se-tabla--semestre se-tabla--fija">
                    <thead>
                        <tr class="se-grupo">
                            <th rowspan="2">Materia</th>
                            <th colspan="{{ $np }}">Parciales</th>
                            <th rowspan="2" class="se-centro">Examen final</th>
                            <th rowspan="2" class="se-centro">Nota final</th>
                            <th rowspan="2" class="se-centro">Asistencia</th>
                            <th rowspan="2" class="se-centro">Estado</th>
                        </tr>
                        <tr class="se-subgrupo">
                            @for($i = 1; $i <= $np; $i++)
                                <th>P{{ $i }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($filas as $m)
                            @php
                                $asist = $m['asistencia'] ?? null;
                                $bajaAsist = $asist !== null && (float) $asist < $minAsist;
                                $res = $m['resultado'];
                                $ex = $m['examen'];
                            @endphp
                            <tr>
                                <td>
                                    <span class="se-tabla-titulo">{{ $m['nombre'] }}</span>
                                    @if(! empty($m['profesor']))
                                        <span class="se-tabla-sub">{{ $m['profesor'] }}</span>
                                    @endif
                                </td>
                                @for($i = 0; $i < $np; $i++)
                                    @php $p = $m['parciales'][$i] ?? null; @endphp
                                    <td class="se-centro" @if($p) title="{{ $p['nombre'] }}" @endif>
                                        @if($p)
                                            <span class="se-tabla-titulo">{{ $puntos($p['puntos']) }}</span>
                                            <span class="se-tabla-sub">de {{ $p['max'] }}</span>
                                        @else
                                            <span class="se-nota se-nota--vacia">—</span>
                                        @endif
                                    </td>
                                @endfor
                                <td class="se-centro">
                                    @if($ex)
                                        <span class="se-tabla-titulo">{{ $puntos($ex['puntos']) }}</span>
                                        <span class="se-tabla-sub">de {{ $ex['max'] }}</span>
                                    @else
                                        <span class="se-nota se-nota--vacia">—</span>
                                    @endif
                                </td>
                                <td class="se-centro se-col-final"><span class="se-nota se-nota--final {{ $claseNota($m['_nota']) }}" @if($res && ! $res->candadoSuperado()) title="Parciales o examen por debajo del {{ $minRend }}%." @endif>{{ $m['_nota'] === null ? '—' : (int) $m['_nota'] }}</span></td>
                                <td class="se-centro">
                                    @if($asist === null)
                                        <span class="se-badge se-badge--neutro se-badge--sin-punto">—</span>
                                    @else
                                        <span class="se-progreso {{ $bajaAsist ? 'se-c-rojo' : 'se-c-verde' }}">
                                            <span class="se-progreso-barra"><i style="--w: {{ max(0, min(100, (float) $asist)) }}%"></i></span>
                                            <span class="se-progreso-num">{{ rtrim(rtrim(number_format((float) $asist, 1, ',', ''), '0'), ',') }}%</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="se-centro">
                                    @if($m['_nota'] === null)
                                        <span class="se-badge se-badge--neutro">Pendiente</span>
                                    @elseif($m['_porAsistencia'])
                                        <span class="se-badge se-badge--reprobado">Reprobado por asistencia</span>
                                    @elseif($m['_nota'] >= $aprob)
                                        <span class="se-badge se-badge--aprobado">Aprobado</span>
                                    @elseif(! $res->candadoSuperado())
                                        <span class="se-badge se-badge--reprobado">Reprobado · bajo {{ $minRend }}%</span>
                                    @else
                                        <span class="se-badge se-badge--reprobado">Reprobado</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $np + 5 }}" class="se-vacio">Todavía no hay calificaciones registradas en este semestre.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($promedioSem !== null)
                        <tfoot>
                            <tr>
                                <td colspan="{{ $np + 2 }}">Promedio del semestre</td>
                                <td class="se-centro se-col-final"><span class="se-nota se-nota--final {{ $promedioSem >= $aprob ? 'se-nota--ok' : 'se-nota--mal' }}">{{ number_format($promedioSem, 2, ',', '') }}</span></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </section>
    @endforeach
</div>

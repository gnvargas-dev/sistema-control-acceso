{{--
    Gráficos de asistencia (Etapa 8B): anillo, evolución de 8 semanas y barras de lunes a viernes.
    SVG hecho a mano, animado solo con CSS (tema.css, sección 19). Sin librerías.
    Recibe $graficos, que arma App\Services\AsistenciaMetricasService.
--}}
@if(! $graficos['hayDatos'])
    <section class="se-panel se-elevar se-c-azul">
        <div class="se-panel-cab">
            <h2>Gráficos de asistencia</h2>
        </div>
        <p class="se-vacio">Todavía no hay asistencias registradas para mostrar gráficos.</p>
    </section>
@else
    @php
        $anillo = $graficos['anillo'];
        $semanas = $graficos['semanas'];
        $dias = $graficos['dias'];
        $minimo = (int) $graficos['minimo'];
        $total = (int) $anillo['total'];

        /* Coordenadas y longitudes de SVG: punto decimal, sin separador de miles */
        $coord = fn ($valor) => rtrim(rtrim(number_format((float) $valor, 2, '.', ''), '0'), '.');
        /* Porcentajes en textos: entero dentro del gráfico, hasta un decimal en tooltips y leyendas */
        $entero = fn ($valor) => (string) round((float) $valor);
        $decimal = fn ($valor) => rtrim(rtrim(number_format((float) $valor, 1, '.', ''), '0'), '.');
        $nRegistros = fn ($cantidad) => $cantidad . ' ' . ($cantidad === 1 ? 'registro' : 'registros');

        /* ---------- 1. Anillo ---------- */
        $segmentos = [
            ['color' => 'se-c-verde', 'etiqueta' => 'Presentes', 'cantidad' => (int) $anillo['presentes']],
            ['color' => 'se-c-naranja', 'etiqueta' => 'Tardanzas', 'cantidad' => (int) $anillo['tardanzas']],
            ['color' => 'se-c-rojo', 'etiqueta' => 'Ausentes', 'cantidad' => (int) $anillo['ausentes']],
        ];

        /* Las justificadas solo aparecen si las hay: así el anillo siempre suma el total */
        if ((int) $anillo['justificados'] > 0) {
            $segmentos[] = ['color' => 'se-c-violeta', 'etiqueta' => 'Justificadas', 'cantidad' => (int) $anillo['justificados']];
        }

        $conDatos = count(array_filter($segmentos, fn ($s) => $s['cantidad'] > 0));
        $hueco = $conDatos > 1 ? 1.2 : 0.0;
        $acumulado = 0.0;

        foreach ($segmentos as $i => $s) {
            $largo = $s['cantidad'] / $total * 100;
            $segmentos[$i]['porcentaje'] = round($largo, 1);
            $segmentos[$i]['trazo'] = max($largo - $hueco, 0.0);
            $segmentos[$i]['desde'] = $acumulado + $hueco / 2;
            $acumulado += $largo;
        }

        $descAnillo = implode(' ', array_map(
            fn ($s) => $s['etiqueta'] . ': ' . $s['cantidad'] . ' (' . $decimal($s['porcentaje']) . '%).',
            $segmentos
        ));

        /* ---------- 2. Barras de lunes a viernes ---------- */
        $bAncho = 360; $bAlto = 232; $bIzq = 40; $bDer = 10; $bArr = 26; $bAba = 36;
        $bPlotAncho = $bAncho - $bIzq - $bDer;
        $bPlotAlto = $bAlto - $bArr - $bAba;
        $bRanura = $bPlotAncho / count($dias);
        $bGrosor = 38;
        $bY = fn ($valor) => $bArr + $bPlotAlto * (1 - $valor / 100);

        $descBarras = implode(' ', array_map(
            fn ($d) => $d['nombre'] . ': ' . ($d['porcentaje'] === null ? 'sin registros.' : $decimal($d['porcentaje']) . '%.'),
            $dias
        ));

        /* ---------- 3. Línea de las últimas 8 semanas ---------- */
        $lAncho = 720; $lAlto = 240; $lIzq = 46; $lDer = 18; $lArr = 28; $lAba = 44;
        $lPlotAncho = $lAncho - $lIzq - $lDer;
        $lPlotAlto = $lAlto - $lArr - $lAba;
        $lX = fn ($i) => $lIzq + ($i + 0.5) * $lPlotAncho / count($semanas);
        $lY = fn ($valor) => $lArr + $lPlotAlto * (1 - $valor / 100);
        $lBase = $lArr + $lPlotAlto;

        /* Tramos continuos: una semana sin registros corta la línea en vez de caer a 0 % */
        $tramos = [];
        $tramo = [];
        foreach ($semanas as $i => $s) {
            if ($s['porcentaje'] === null) {
                if ($tramo) {
                    $tramos[] = $tramo;
                    $tramo = [];
                }
                continue;
            }
            $tramo[] = ['x' => $lX($i), 'y' => $lY($s['porcentaje'])];
        }
        if ($tramo) {
            $tramos[] = $tramo;
        }

        $curvas = [];
        $areas = [];
        foreach ($tramos as $t) {
            if (count($t) < 2) {
                continue;
            }
            $puntos = array_map(fn ($p) => $coord($p['x']) . ' ' . $coord($p['y']), $t);
            $curvas[] = 'M' . implode(' L', $puntos);
            $areas[] = 'M' . $coord($t[0]['x']) . ' ' . $coord($lBase)
                . ' L' . implode(' L', $puntos)
                . ' L' . $coord($t[count($t) - 1]['x']) . ' ' . $coord($lBase) . ' Z';
        }

        $descLinea = implode(' ', array_map(
            fn ($s) => 'Semana del ' . $s['etiqueta'] . ': ' . ($s['porcentaje'] === null ? 'sin registros.' : $decimal($s['porcentaje']) . '%.'),
            $semanas
        ));
    @endphp

    <div class="se-ga-fila">
        {{-- Anillo --}}
        <section class="se-panel se-elevar se-c-verde">
            <div class="se-panel-cab">
                <h2>Presentes, tardanzas y ausentes</h2>
                <span class="se-badge se-badge--info se-badge--sin-punto">{{ $nRegistros($total) }}</span>
            </div>

            <div class="se-ga-anillo-caja">
                <svg class="se-ga-svg se-ga-anillo" viewBox="0 0 120 120" role="img" aria-labelledby="se-ga-anillo-t se-ga-anillo-d">
                    <title id="se-ga-anillo-t">Distribución de la asistencia</title>
                    <desc id="se-ga-anillo-d">{{ $descAnillo }}</desc>
                    <circle class="se-ga-anillo-fondo" cx="60" cy="60" r="46" />
                    <g transform="rotate(-90 60 60)">
                        @foreach($segmentos as $i => $s)
                            @if($s['cantidad'] > 0)
                                <circle class="se-ga-seg {{ $s['color'] }}" style="--i: {{ $i }}" cx="60" cy="60" r="46" pathLength="100" stroke-dasharray="{{ $coord($s['trazo']) }} 100" stroke-dashoffset="-{{ $coord($s['desde']) }}">
                                    <title>{{ $s['etiqueta'] }}: {{ $s['cantidad'] }} ({{ $decimal($s['porcentaje']) }}%)</title>
                                </circle>
                            @endif
                        @endforeach
                    </g>
                    <text class="se-ga-centro" x="60" y="62">{{ $anillo['porcentaje'] }}%</text>
                    <text class="se-ga-centro-sub" x="60" y="75">de asistencia</text>
                </svg>

                <ul class="se-ga-leyenda">
                    @foreach($segmentos as $s)
                        <li class="{{ $s['color'] }}">
                            <i class="se-ga-dot"></i>
                            <span>{{ $s['etiqueta'] }}</span>
                            <b>{{ $s['cantidad'] }}</b>
                            <small>{{ $decimal($s['porcentaje']) }}%</small>
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="se-ga-pie">{{ $graficos['regla'] }}</p>
        </section>

        {{-- Barras de lunes a viernes --}}
        <section class="se-panel se-elevar se-c-azul">
            <div class="se-panel-cab">
                <h2>Asistencia de lunes a viernes</h2>
            </div>

            <div class="se-ga-cuerpo">
                <svg class="se-ga-svg" viewBox="0 0 {{ $bAncho }} {{ $bAlto }}" role="img" aria-labelledby="se-ga-barras-t se-ga-barras-d">
                    <title id="se-ga-barras-t">Asistencia según el día de la semana</title>
                    <desc id="se-ga-barras-d">{{ $descBarras }}</desc>

                    @foreach([0, 25, 50, 75, 100] as $g)
                        <line class="se-ga-rejilla {{ $g === 0 ? 'se-ga-rejilla--base' : '' }}" x1="{{ $bIzq }}" x2="{{ $bAncho - $bDer }}" y1="{{ $coord($bY($g)) }}" y2="{{ $coord($bY($g)) }}" />
                        <text class="se-ga-eje se-ga-eje--y" x="{{ $bIzq - 8 }}" y="{{ $coord($bY($g) + 4) }}">{{ $g }}%</text>
                    @endforeach

                    <line class="se-ga-min" x1="{{ $bIzq }}" x2="{{ $bAncho - $bDer }}" y1="{{ $coord($bY($minimo)) }}" y2="{{ $coord($bY($minimo)) }}">
                        <title>Mínimo requerido: {{ $minimo }}%</title>
                    </line>

                    @foreach($dias as $i => $d)
                        @php
                            $centro = $bIzq + ($i + 0.5) * $bRanura;
                        @endphp
                        @if($d['porcentaje'] === null)
                            <text class="se-ga-sin-datos" x="{{ $coord($centro) }}" y="{{ $coord($bArr + $bPlotAlto - 8) }}">sin datos</text>
                        @else
                            @php
                                $alto = max($bPlotAlto * $d['porcentaje'] / 100, 2);
                                $arriba = $bArr + $bPlotAlto - $alto;
                            @endphp
                            <rect class="se-ga-barra {{ $d['porcentaje'] >= $minimo ? 'se-c-azul' : 'se-c-naranja' }}" style="--i: {{ $i }}" x="{{ $coord($centro - $bGrosor / 2) }}" y="{{ $coord($arriba) }}" width="{{ $bGrosor }}" height="{{ $coord($alto) }}" rx="7">
                                <title>{{ $d['nombre'] }}: {{ $decimal($d['porcentaje']) }}% ({{ $nRegistros($d['total']) }})</title>
                            </rect>
                            <text class="se-ga-barra-valor" style="--i: {{ $i }}" x="{{ $coord($centro) }}" y="{{ $coord($arriba - 7) }}">{{ $entero($d['porcentaje']) }}%</text>
                        @endif
                        <text class="se-ga-eje se-ga-eje--x" x="{{ $coord($centro) }}" y="{{ $bAlto - 12 }}">{{ $d['dia'] }}</text>
                    @endforeach
                </svg>
            </div>

            <p class="se-ga-pie">
                <span class="se-ga-clave-min" aria-hidden="true"></span>Mínimo requerido: <strong>{{ $minimo }}%</strong>.
                Las barras naranjas quedan por debajo.
            </p>
        </section>
    </div>

    {{-- Evolución de las últimas 8 semanas --}}
    <section class="se-panel se-elevar se-c-azul">
        <div class="se-panel-cab">
            <h2>Evolución de las últimas {{ count($semanas) }} semanas</h2>
        </div>

        <div class="se-ga-cuerpo">
            <div class="se-ga-scroll" tabindex="0" role="region" aria-label="Gráfico de evolución semanal">
                <svg class="se-ga-svg se-ga-svg--linea" viewBox="0 0 {{ $lAncho }} {{ $lAlto }}" role="img" aria-labelledby="se-ga-linea-t se-ga-linea-d">
                    <title id="se-ga-linea-t">Evolución semanal de la asistencia</title>
                    <desc id="se-ga-linea-d">{{ $descLinea }}</desc>
                    <defs>
                        <linearGradient id="se-ga-degradado" x1="0" y1="0" x2="0" y2="1">
                            <stop class="se-ga-degradado-ini" offset="0" />
                            <stop class="se-ga-degradado-fin" offset="1" />
                        </linearGradient>
                    </defs>

                    @foreach([0, 25, 50, 75, 100] as $g)
                        <line class="se-ga-rejilla {{ $g === 0 ? 'se-ga-rejilla--base' : '' }}" x1="{{ $lIzq }}" x2="{{ $lAncho - $lDer }}" y1="{{ $coord($lY($g)) }}" y2="{{ $coord($lY($g)) }}" />
                        <text class="se-ga-eje se-ga-eje--y" x="{{ $lIzq - 10 }}" y="{{ $coord($lY($g) + 4) }}">{{ $g }}%</text>
                    @endforeach

                    <line class="se-ga-min" x1="{{ $lIzq }}" x2="{{ $lAncho - $lDer }}" y1="{{ $coord($lY($minimo)) }}" y2="{{ $coord($lY($minimo)) }}">
                        <title>Mínimo requerido: {{ $minimo }}%</title>
                    </line>

                    @foreach($areas as $trazado)
                        <path class="se-ga-area" d="{{ $trazado }}" fill="url(#se-ga-degradado)" />
                    @endforeach

                    @foreach($curvas as $trazado)
                        <path class="se-ga-curva" d="{{ $trazado }}" pathLength="1" />
                    @endforeach

                    @foreach($semanas as $i => $s)
                        <text class="se-ga-eje se-ga-eje--x" x="{{ $coord($lX($i)) }}" y="{{ $lAlto - 14 }}">{{ $s['etiqueta'] }}</text>

                        @if($s['porcentaje'] !== null)
                            <circle class="se-ga-nodo {{ $s['porcentaje'] >= $minimo ? 'se-c-azul' : 'se-c-naranja' }}" style="--i: {{ $i }}" cx="{{ $coord($lX($i)) }}" cy="{{ $coord($lY($s['porcentaje'])) }}" r="5.5">
                                <title>Semana del {{ $s['etiqueta'] }}: {{ $decimal($s['porcentaje']) }}% ({{ $nRegistros($s['total']) }})</title>
                            </circle>
                            <text class="se-ga-valor" style="--i: {{ $i }}" x="{{ $coord($lX($i)) }}" y="{{ $coord($lY($s['porcentaje']) - 13) }}">{{ $entero($s['porcentaje']) }}%</text>
                        @endif
                    @endforeach
                </svg>
            </div>
        </div>

        <p class="se-ga-pie">
            Cada punto es la asistencia de la semana que empieza el lunes indicado; las semanas sin registros quedan sin punto.
            <span class="se-ga-clave-min" aria-hidden="true"></span>Mínimo requerido: <strong>{{ $minimo }}%</strong>.
            {{ $graficos['regla'] }}
        </p>
    </section>
@endif

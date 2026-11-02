@extends('layouts.alumno')

@section('title', 'Historial académico')

@section('content')
<header class="se-saludo">
    <div>
        <h1>Historial académico</h1>
        <p>Todos los movimientos registrados en tu trayectoria, ordenados por período.</p>
    </div>
    <span class="se-badge se-badge--claro se-badge--sin-punto">{{ $registros->count() }} {{ $registros->count() === 1 ? 'registro' : 'registros' }}</span>
</header>

@forelse($porPeriodo as $periodo => $items)
    <section class="se-panel se-elevar se-c-violeta" style="--i: {{ $loop->index }}">
        <div class="se-panel-cab">
            <h2>{{ $periodo ?: 'Sin período' }}</h2>
            <span class="se-badge se-badge--info se-badge--sin-punto">{{ $items->count() }} {{ $items->count() === 1 ? 'registro' : 'registros' }}</span>
        </div>

        <ul class="se-hitos">
            @foreach($items as $registro)
                <li class="se-hito">
                    <div class="se-hito-cab">
                        <span class="se-tiempo">{{ $registro->created_at?->format('d/m/Y') }}</span>
                    </div>
                    <p>{{ $registro->detalle }}</p>
                </li>
            @endforeach
        </ul>
    </section>
@empty
    <section class="se-panel">
        <p class="se-vacio">Todavía no hay registros en tu historial académico.</p>
    </section>
@endforelse
@endsection

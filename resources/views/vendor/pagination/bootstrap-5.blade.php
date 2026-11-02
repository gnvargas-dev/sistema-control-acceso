@if ($paginator->hasPages())
    <nav class="pagination-nav" aria-label="Paginación">
        <ul class="pagination mb-0">

            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link" aria-label="Anterior">
                        <svg
                            class="pagination-icon"
                            viewBox="0 0 16 16"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M10 2 4 8l6 6"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a
                        class="page-link"
                        href="{{ $paginator->previousPageUrl() }}"
                        rel="prev"
                        aria-label="Anterior"
                    >
                        <svg
                            class="pagination-icon"
                            viewBox="0 0 16 16"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M10 2 4 8l6 6"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </a>
                </li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">
                            {{ $element }}
                        </span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li
                                class="page-item active"
                                aria-current="page"
                            >
                                <span class="page-link">
                                    {{ $page }}
                                </span>
                            </li>
                        @else
                            <li class="page-item">
                                <a
                                    class="page-link"
                                    href="{{ $url }}"
                                >
                                    {{ $page }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a
                        class="page-link"
                        href="{{ $paginator->nextPageUrl() }}"
                        rel="next"
                        aria-label="Siguiente"
                    >
                        <svg
                            class="pagination-icon"
                            viewBox="0 0 16 16"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="m6 2 6 6-6 6"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link" aria-label="Siguiente">
                        <svg
                            class="pagination-icon"
                            viewBox="0 0 16 16"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="m6 2 6 6-6 6"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </span>
                </li>
            @endif

        </ul>
    </nav>
@endif

@if ($paginator->hasPages())
    <nav class="store-pagination-nav" role="navigation" aria-label="Paginacion">
        <ul class="store-pagination-list">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="store-pagination-link store-pagination-link--control is-disabled">Anterior</span>
                @else
                    <a class="store-pagination-link store-pagination-link--control" href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li>
                        <span class="store-pagination-link store-pagination-link--dots">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="store-pagination-link store-pagination-link--number is-active" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="store-pagination-link store-pagination-link--number" href="{{ $url }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a class="store-pagination-link store-pagination-link--control" href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>
                @else
                    <span class="store-pagination-link store-pagination-link--control is-disabled">Siguiente</span>
                @endif
            </li>
        </ul>
    </nav>
@endif

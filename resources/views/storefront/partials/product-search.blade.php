@if($productSearchEnabled ?? false)
    @php
        $productSearchAction = $productSearchAction ?? route('store.products.index', $store->slug);
        $productSearchValue = trim((string) ($searchQuery ?? request('q', '')));
    @endphp

    <form class="product-search" action="{{ $productSearchAction }}" method="GET" role="search">
        <label class="product-search-label" for="productSearchInput-{{ $productSearchId ?? 'default' }}">Buscar productos</label>
        <div class="product-search-control">
            <input
                id="productSearchInput-{{ $productSearchId ?? 'default' }}"
                type="search"
                name="q"
                value="{{ $productSearchValue }}"
                placeholder="Buscar por nombre"
                autocomplete="off"
            >
            <button type="submit">Buscar</button>
        </div>

        @if($productSearchValue !== '')
            <a href="{{ $productSearchAction }}" class="product-search-clear">Limpiar busqueda</a>
        @endif
    </form>
@endif

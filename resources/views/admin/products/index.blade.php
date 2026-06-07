@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Productos</h2>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        @if(auth()->user()->isAdmin() || (($store ?? $selectedStore ?? null)?->allowsCategories() ?? true))
            <a href="/admin/categories" class="btn btn-secondary">Categorias</a>
        @endif
        <a href="/admin/products/create" class="btn">Nuevo producto</a>
    </div>
</div>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="flash error">{{ session('error') }}</div>
@endif

@php
    $currentProductStore = $selectedStore ?? $store ?? null;
    $productSearch = $productSearch ?? request('q', '');
    $productsTotal = method_exists($products, 'total') ? $products->total() : $products->count();
    $storeProductCount = $currentProductStore ? $currentProductStore->products()->count() : $productsTotal;
    $isProductSearchActive = trim((string) $productSearch) !== '';
@endphp

@if($currentProductStore && $currentProductStore->productLimit())
    <div class="list-card resource-card">
        <div class="resource-card__main">
            <h3 class="resource-card__title">Plan {{ $currentProductStore->planLabel() }}</h3>
            <p class="resource-card__subtitle">
                {{ $storeProductCount }} de {{ $currentProductStore->productLimit() }} productos disponibles.
            </p>
        </div>
    </div>
@endif

<form method="GET" action="{{ url()->current() }}" class="list-card product-search-panel">
    <label class="field-label" for="productSearchInput">
        {{ auth()->user()->isAdmin() && empty($selectedStore) ? 'Buscar tienda o producto' : 'Buscar producto' }}
    </label>
    <div class="product-search-panel__controls">
        <input
            id="productSearchInput"
            type="search"
            name="q"
            value="{{ $productSearch }}"
            placeholder="{{ auth()->user()->isAdmin() && empty($selectedStore) ? 'Nombre de tienda o producto' : 'Nombre, categoria, material o descripcion' }}"
            autocomplete="off"
        >
        <button type="submit" class="btn">Buscar</button>
        @if($isProductSearchActive)
            <a href="{{ url()->current() }}" class="btn btn-secondary">Limpiar</a>
        @endif
    </div>
</form>

@if($products->isEmpty())
    @if(! auth()->user()->isAdmin() || ! empty($selectedStore))
        <div class="panel-empty">
            @if($isProductSearchActive)
                <h3>No encontramos productos</h3>
                <p>Prueba con otro nombre, categoria, material o descripcion.</p>
                <a href="{{ url()->current() }}" class="btn btn-secondary">Limpiar busqueda</a>
            @else
                <h3>No hay productos registrados</h3>
                <p>Agrega el primer producto para empezar a mostrar el catalogo en la tienda.</p>
                <a href="/admin/products/create" class="btn">Nuevo producto</a>
            @endif
        </div>
    @endif
@endif

@if(auth()->user()->isAdmin() && empty($selectedStore))
    <div class="panel-list">
        @foreach(($stores ?? collect()) as $storeOption)
            <div class="list-card resource-card">
                <div class="resource-card__main">
                    <div class="resource-card__header">
                        <div>
                            <h3 class="resource-card__title">{{ $storeOption->name }}</h3>
                            <p class="resource-card__subtitle">Productos registrados en esta tienda</p>
                        </div>
                        <div class="resource-badges">
                            <span class="resource-badge">{{ $storeOption->products_count }} producto(s)</span>
                        </div>
                    </div>
                </div>
                <div class="resource-actions">
                    <a href="{{ route('admin.stores.products.index', $storeOption) }}" class="btn">Ver productos</a>
                </div>
            </div>
        @endforeach
    </div>

    @if(($stores ?? collect())->count() === 0 && $isProductSearchActive)
        <div class="panel-empty">
            <h3>No encontramos tiendas o productos</h3>
            <p>Prueba con otro nombre de tienda o producto.</p>
            <a href="{{ url()->current() }}" class="btn btn-secondary">Limpiar busqueda</a>
        </div>
    @endif

    @if(($stores ?? null) && method_exists($stores, 'hasPages') && $stores->hasPages())
        <div class="list-card admin-pagination">
            {{ $stores->onEachSide(1)->links('pagination::bootstrap-4') }}
        </div>
    @endif
@else
    @if(auth()->user()->isAdmin() && ! empty($selectedStore))
        <div class="list-card resource-card">
            <div class="resource-card__main">
                <h3 class="resource-card__title">{{ $selectedStore->name }}</h3>
                <p class="resource-card__subtitle">Productos de esta tienda</p>
            </div>
            <div class="resource-actions">
                <a href="/admin/products" class="btn btn-secondary">Volver a tiendas</a>
            </div>
        </div>
    @endif

    <div class="panel-list">
        @foreach($products as $product)
            <article class="list-card resource-card {{ $product->image ? 'resource-card--with-media' : '' }}">
            @if ($product->image)
                <div class="resource-card__media">
                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}">
                </div>
            @endif

            <div class="resource-card__main">
                <div class="resource-card__header">
                    <div>
                        <h3 class="resource-card__title">{{ $product->name }}</h3>
                        <p class="resource-card__subtitle">{{ $product->category ?: 'Sin categoria' }}</p>
                    </div>
                    <div class="resource-badges">
                        @if($product->isSoldOut())
                            <span class="resource-badge resource-badge--danger">Agotado</span>
                        @else
                            <span class="resource-badge resource-badge--active">Disponible</span>
                        @endif
                    </div>
                </div>

                <div class="resource-metrics">
                    <div class="resource-metric">
                        <span class="resource-metric__label">Precio</span>
                        <span class="resource-metric__value">${{ number_format($product->price, 0, ',', '.') }}</span>
                    </div>
                    <div class="resource-metric">
                        <span class="resource-metric__label">Material</span>
                        <span class="resource-metric__value">{{ $product->material ?: 'Sin material' }}</span>
                    </div>
                    <div class="resource-metric">
                        <span class="resource-metric__label">Inventario</span>
                        <span class="resource-metric__value">{{ ($product->store?->isReservationStore() ?? false) ? 'No aplica' : ($product->stockLabel() ?? 'Ilimitado') }}</span>
                    </div>
                    <div class="resource-metric">
                        <span class="resource-metric__label">Tienda</span>
                        <span class="resource-metric__value">{{ $product->store?->name ?? 'Sin tienda' }}</span>
                    </div>
                </div>
            </div>

            <div class="resource-actions">
                <a href="{{ route('admin.products.edit', $product) }}" class="btn">Editar</a>
                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" data-confirm-delete data-confirm-message="Seguro que quieres eliminar este producto? Esta accion no se puede deshacer.">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger">Eliminar</button>
                </form>
            </div>
            </article>
        @endforeach
    </div>

    @if(method_exists($products, 'hasPages') && $products->hasPages())
        <div class="list-card admin-pagination">
            {{ $products->onEachSide(1)->links('pagination::bootstrap-4') }}
        </div>
    @endif
@endif
@endsection

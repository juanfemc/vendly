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
@endphp

@if($currentProductStore && $currentProductStore->productLimit())
    <div class="list-card resource-card">
        <div class="resource-card__main">
            <h3 class="resource-card__title">Plan {{ $currentProductStore->planLabel() }}</h3>
            <p class="resource-card__subtitle">
                {{ $products->count() }} de {{ $currentProductStore->productLimit() }} productos disponibles.
            </p>
        </div>
    </div>
@endif

@if($products->isEmpty())
    @if(! auth()->user()->isAdmin() || ! empty($selectedStore))
        <div class="panel-empty">
            <h3>No hay productos registrados</h3>
            <p>Agrega el primer producto para empezar a mostrar el catalogo en la tienda.</p>
            <a href="/admin/products/create" class="btn">Nuevo producto</a>
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
@endif
@endsection

@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Productos</h2>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a href="/admin/categories" class="btn btn-secondary">Categorias</a>
        <a href="/admin/products/create" class="btn">Nuevo producto</a>
    </div>
</div>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

@if($products->isEmpty())
    @if(! auth()->user()->isAdmin() || ! empty($selectedStore))
        <div class="list-card">No hay productos registrados.</div>
    @endif
@endif

@if(auth()->user()->isAdmin() && empty($selectedStore))
    @foreach(($stores ?? collect()) as $storeOption)
        <div class="list-card" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
            <div>
                <strong>{{ $storeOption->name }}</strong><br>
                <span style="color:#6b7280; font-size:13px;">{{ $storeOption->products_count }} producto(s)</span>
            </div>
            <a href="{{ route('admin.stores.products.index', $storeOption) }}" class="btn">Ver productos</a>
        </div>
    @endforeach

    @if(($stores ?? null) && method_exists($stores, 'hasPages') && $stores->hasPages())
        <div class="list-card admin-pagination">
            {{ $stores->onEachSide(1)->links('pagination::bootstrap-4') }}
        </div>
    @endif
@else
    @if(auth()->user()->isAdmin() && ! empty($selectedStore))
        <div class="list-card" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
            <div>
                <strong>{{ $selectedStore->name }}</strong><br>
                <span style="color:#6b7280; font-size:13px;">Productos de esta tienda</span>
            </div>
            <a href="/admin/products" class="btn btn-secondary">Volver a tiendas</a>
        </div>
    @endif

    @foreach($products as $product)
        <div class="list-card">
            @if ($product->image)
                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="thumb">
            @endif

            <strong>{{ $product->name }}</strong><br>
            Categoria: {{ $product->category ?: 'Sin categoria' }}<br>
            Material: {{ $product->material ?: 'Sin material' }}<br>
            Precio: ${{ $product->price }}<br><br>
            @if(! ($product->store?->isReservationStore() ?? false))
                Inventario: {{ $product->stockLabel() ?? 'Ilimitado' }}<br><br>
            @endif

            <a href="{{ route('admin.products.edit', $product) }}" class="btn">Editar</a>

            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" style="margin-top:10px;" data-confirm-delete data-confirm-message="Seguro que quieres eliminar este producto? Esta accion no se puede deshacer.">
                @csrf
                @method('DELETE')
                <button class="btn btn-secondary">Eliminar</button>
            </form>
        </div>
    @endforeach
@endif
@endsection

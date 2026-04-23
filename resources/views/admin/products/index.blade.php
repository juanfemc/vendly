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
    <div class="list-card">No hay productos registrados.</div>
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

        <a href="{{ route('admin.products.edit', $product) }}" class="btn">Editar</a>

        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" style="margin-top:10px;">
            @csrf
            @method('DELETE')
            <button class="btn btn-secondary">Eliminar</button>
        </form>
    </div>
@endforeach
@endsection

@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Categorias</h2>
</div>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="flash error">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="list-card">
    <h3 style="margin-top:0;">Crear categoria</h3>
    <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="text" name="name" value="{{ old('name') }}" placeholder="Nombre de la categoria" required>
        <input type="text" name="slug" value="{{ old('slug') }}" placeholder="Slug opcional">
        <textarea name="description" rows="3" placeholder="Descripcion corta">{{ old('description') }}</textarea>
        <input type="file" name="image" accept="image/*">
        <label>
            <span>Posicion en la tienda</span>
            <select name="sort_order">
                @foreach([
                    0 => 'Normal',
                    10 => 'Primero',
                    20 => 'Segundo',
                    30 => 'Tercero',
                    40 => 'Cuarto',
                    50 => 'Quinto',
                    100 => 'Al final',
                ] as $orderValue => $orderLabel)
                    <option value="{{ $orderValue }}" @selected((int) old('sort_order', 0) === $orderValue)>{{ $orderLabel }}</option>
                @endforeach
            </select>
        </label>
        <label style="display:flex; gap:8px; align-items:center; margin:10px 0;">
            <input type="checkbox" name="is_active" value="1" checked>
            <span>Categoria visible</span>
        </label>
        <button class="btn" type="submit">Agregar categoria</button>
    </form>
</div>

@if ($categories->isEmpty())
    <div class="list-card">No hay categorias registradas.</div>
@endif

@foreach ($categories as $category)
    <div class="list-card">
        <strong>{{ $category->name }}</strong>
        @php
            $positionLabel = [
                0 => 'Normal',
                10 => 'Primero',
                20 => 'Segundo',
                30 => 'Tercero',
                40 => 'Cuarto',
                50 => 'Quinto',
                100 => 'Al final',
            ][$category->sort_order] ?? 'Personalizada';
        @endphp
        <div style="color:#666; margin-top:6px;">/{{ $category->slug }} · {{ $positionLabel }} · {{ $category->is_active ? 'Visible' : 'Oculta' }}</div>
        @if($category->description)
            <p style="margin-bottom:0;">{{ $category->description }}</p>
        @endif
        @if($category->image)
            <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}" style="width:120px; height:80px; object-fit:cover; border-radius:10px; margin-top:12px;">
        @endif

        <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:14px;">
            <a href="{{ route('admin.categories.edit', $category) }}" class="btn">Editar</a>

            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('¿Eliminar esta categoria? Los productos quedaran sin categoria.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-secondary">Eliminar</button>
            </form>
        </div>
    </div>
@endforeach
@endsection

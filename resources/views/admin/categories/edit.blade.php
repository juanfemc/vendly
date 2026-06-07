@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Editar categoria</h2>
</div>

@if ($errors->any())
    <div class="flash error">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="list-card">
    <form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <input type="text" name="name" value="{{ old('name', $category->name) }}" placeholder="Nombre de la categoria" required>
        <input type="text" name="slug" value="{{ old('slug', $category->slug) }}" placeholder="Slug">
        <textarea name="description" rows="4" placeholder="Descripcion corta">{{ old('description', $category->description) }}</textarea>
        <input type="file" name="image" accept="image/*">
        <small style="display:block; margin-top:-6px; color:var(--muted);">Imagen recomendada: JPG, PNG o WebP. Maximo 8 MB.</small>
        @if($category->image)
            <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}" style="width:160px; height:100px; object-fit:cover; border-radius:10px; margin:10px 0;">
        @endif
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
                    <option value="{{ $orderValue }}" @selected((int) old('sort_order', $category->sort_order) === $orderValue)>{{ $orderLabel }}</option>
                @endforeach
            </select>
        </label>
        <label style="display:flex; gap:8px; align-items:center; margin:10px 0;">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active))>
            <span>Categoria visible</span>
        </label>
        <button class="btn" type="submit">Guardar cambios</button>
    </form>
</div>
@endsection

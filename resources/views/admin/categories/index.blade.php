@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Categorias</h2>
</div>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="flash error">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="flash error">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

@if(auth()->user()->isAdmin() && empty($selectedStore))
    <div class="panel-list">
        @foreach(($stores ?? collect()) as $storeOption)
            <div class="list-card resource-card">
                <div class="resource-card__main">
                    <div class="resource-card__header">
                        <div>
                            <h3 class="resource-card__title">{{ $storeOption->name }}</h3>
                            <p class="resource-card__subtitle">Gestiona las categorias de esta tienda</p>
                        </div>
                        <div class="resource-badges">
                            <span class="resource-badge">Plan {{ $storeOption->planLabel() }}</span>
                            <span class="resource-badge">{{ $storeOption->categories_count }} categoria(s)</span>
                        </div>
                    </div>
                </div>
                <div class="resource-actions">
                    <a href="{{ route('admin.stores.categories.index', $storeOption) }}" class="btn">
                        {{ $storeOption->allowsCategories() ? 'Ver categorias' : 'Ver limite' }}
                    </a>
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
                <p class="resource-card__subtitle">Categorias de esta tienda</p>
            </div>
            <div class="resource-actions">
                <a href="/admin/categories" class="btn btn-secondary">Volver a tiendas</a>
            </div>
        </div>
    @endif

    @if(! empty($categoriesLocked))
        <div class="panel-empty">
            <h3>Categorias no disponibles</h3>
            <p>El plan {{ $store->planLabel() }} no incluye categorias. Los productos se muestran como un catalogo simple.</p>
        </div>
    @else
    <div class="list-card">
        <h3 style="margin-top:0;">Crear categoria</h3>
        <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data">
            @csrf
            @if(auth()->user()->isAdmin())
                <input type="hidden" name="store_id" value="{{ $store->id }}">
            @endif
            <input type="text" name="name" value="{{ old('name') }}" placeholder="Nombre de la categoria" required>
            <input type="text" name="slug" value="{{ old('slug') }}" placeholder="Slug opcional">
            <textarea name="description" rows="3" placeholder="Descripcion corta">{{ old('description') }}</textarea>
            <input type="file" name="image" accept="image/*" data-optimize-image data-max-width="1600" data-max-height="1200" data-quality="0.84" data-output="webp" data-max-size="8388608">
            <small style="display:block; margin-top:-6px; color:var(--muted);">Imagen recomendada: JPG, PNG o WebP. Maximo 8 MB.</small>
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
                <input type="checkbox" name="is_active" value="1" checked style="width:auto; margin:0;">
                <span>Categoria visible</span>
            </label>
            <button class="btn" type="submit">Agregar categoria</button>
        </form>
    </div>

    @if ($categories->isEmpty())
        <div class="panel-empty">
            <h3>No hay categorias registradas</h3>
            <p>Crea categorias para ordenar el catalogo y facilitar la exploracion de productos.</p>
        </div>
    @endif

    <div class="panel-list">
        @foreach ($categories as $category)
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

            <article class="list-card resource-card {{ $category->image ? 'resource-card--with-media' : '' }}">
                @if($category->image)
                    <div class="resource-card__media">
                        <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}">
                    </div>
                @endif

                <div class="resource-card__main">
                    <div class="resource-card__header">
                        <div>
                            <h3 class="resource-card__title">{{ $category->name }}</h3>
                            <p class="resource-card__subtitle">/{{ $category->slug }}</p>
                        </div>
                        <div class="resource-badges">
                            <span class="resource-badge {{ $category->is_active ? 'resource-badge--active' : 'resource-badge--inactive' }}">
                                {{ $category->is_active ? 'Visible' : 'Oculta' }}
                            </span>
                            <span class="resource-badge">{{ $positionLabel }}</span>
                        </div>
                    </div>

                    @if($category->description)
                        <p class="resource-card__description">{{ $category->description }}</p>
                    @endif
                </div>

                <div class="resource-actions">
                    <a href="{{ route('admin.categories.edit', $category) }}" class="btn">Editar</a>

                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" data-confirm-delete data-confirm-message="Eliminar esta categoria? Los productos quedaran sin categoria.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </article>
        @endforeach
    </div>
    @endif
@endif
@endsection

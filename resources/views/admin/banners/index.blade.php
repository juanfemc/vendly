@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Banners / Noticias</h2>
    <a href="/admin/banners/create" class="btn">Crear banner</a>
</div>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

@if ($banners->isEmpty())
    <div class="panel-empty">
        <h3>No hay banners registrados</h3>
        <p>Crea un banner para destacar noticias, promociones o mensajes en las tiendas.</p>
        <a href="/admin/banners/create" class="btn">Crear banner</a>
    </div>
@endif

<div class="panel-list">
    @foreach($banners as $banner)
        <article class="list-card resource-card resource-card--with-media">
            <div class="resource-card__media">
                <img src="{{ asset('storage/' . $banner->image) }}" alt="{{ $banner->title ?: 'Banner' }}">
            </div>

            <div class="resource-card__main">
                <div class="resource-card__header">
                    <div>
                        <h3 class="resource-card__title">{{ $banner->title ?: 'Sin titulo' }}</h3>
                        <p class="resource-card__subtitle">{{ $banner->applies_to_all ? 'Todas las tiendas' : ($banner->store->name ?? 'Sin tienda') }}</p>
                    </div>
                    <div class="resource-badges">
                        <span class="resource-badge {{ $banner->is_active ? 'resource-badge--active' : 'resource-badge--inactive' }}">
                            {{ $banner->is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                        <span class="resource-badge">Orden {{ $banner->sort_order }}</span>
                    </div>
                </div>

                <p class="resource-card__description">{{ $banner->subtitle ?: 'Sin subtitulo' }}</p>
            </div>

            <div class="resource-actions">
                <a href="{{ route('admin.banners.edit', $banner) }}" class="btn">Editar</a>

                <form method="POST" action="{{ route('admin.banners.toggle', $banner) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn {{ $banner->is_active ? 'btn-warning' : 'btn-success' }}">
                        {{ $banner->is_active ? 'Desactivar' : 'Activar' }}
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" data-confirm-delete data-confirm-message="Seguro que quieres eliminar este banner? Esta accion no se puede deshacer.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </article>
    @endforeach
</div>
@endsection

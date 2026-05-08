@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Tiendas</h2>
    <a href="/admin/stores/create" class="btn">Crear tienda</a>
</div>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

@if($stores->isEmpty())
    <div class="panel-empty">
        <h3>No hay tiendas registradas</h3>
        <p>Crea una tienda para asociarla a un usuario y publicar su catalogo.</p>
        <a href="/admin/stores/create" class="btn">Crear tienda</a>
    </div>
@endif

<div class="panel-list">
    @foreach($stores as $store)
        <article class="list-card resource-card {{ $store->cover_image ? 'resource-card--with-media' : '' }}">
            @if ($store->cover_image)
                <div class="resource-card__media">
                    <img src="{{ asset('storage/' . $store->cover_image) }}" alt="{{ $store->name }}">
                </div>
            @endif

            <div class="resource-card__main">
                <div class="resource-card__header">
                    <div>
                        <h3 class="resource-card__title">{{ $store->name }}</h3>
                        <p class="resource-card__subtitle">/{{ $store->slug }}</p>
                    </div>
                    <div class="resource-badges">
                        <span class="resource-badge">Plan {{ $store->planLabel() }}</span>
                        <span class="resource-badge">{{ $store->businessTypeLabel() }}</span>
                        <span class="resource-badge {{ $store->isAvailable() ? 'resource-badge--active' : 'resource-badge--inactive' }}">
                            {{ $store->isAvailable() ? 'Publica' : 'No publica' }}
                        </span>
                    </div>
                </div>

                <div class="resource-metrics">
                    <div class="resource-metric">
                        <span class="resource-metric__label">WhatsApp</span>
                        <span class="resource-metric__value">{{ $store->whatsapp ?: 'Sin numero' }}</span>
                    </div>
                    <div class="resource-metric">
                        <span class="resource-metric__label">Usuario</span>
                        <span class="resource-metric__value">{{ $store->user->name ?? 'Sin usuario' }}</span>
                    </div>
                    <div class="resource-metric">
                        <span class="resource-metric__label">Creada por</span>
                        <span class="resource-metric__value">{{ $store->creatorAdmin->name ?? 'No registrado' }}</span>
                    </div>
                    <div class="resource-metric">
                        <span class="resource-metric__label">Visitas</span>
                        <span class="resource-metric__value">{{ number_format($store->views_count ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>

                <p class="resource-card__description">{{ $store->shop_copy ?: 'Sin texto configurado' }}</p>
            </div>

            <div class="resource-actions">
                <a href="{{ url('/' . $store->slug) }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">Ver tienda</a>
                <a href="{{ route('admin.stores.edit', $store) }}" class="btn">Editar</a>
                <form method="POST" action="{{ route('admin.stores.destroy', $store) }}" data-confirm-delete data-confirm-message="Seguro que quieres eliminar esta tienda? Esta accion no se puede deshacer.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </article>
    @endforeach
</div>
@endsection

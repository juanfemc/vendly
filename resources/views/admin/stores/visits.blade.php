@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>{{ isset($selectedStore) ? 'Visitas de tu tienda' : 'Visitas por tienda' }}</h2>
    @if(auth()->user()?->isAdmin())
        <a href="/admin/stores" class="btn btn-secondary">Volver a tiendas</a>
    @else
        <a href="/admin/store-settings" class="btn btn-secondary">Volver a configuracion</a>
    @endif
</div>

<div class="grid" style="margin-bottom:16px;">
    <div class="card">
        <span style="display:block; color:#6b7280; font-size:14px; font-weight:700; margin-bottom:8px;">Visitas totales</span>
        <strong style="font-size:34px;">{{ number_format($totalVisits ?? 0, 0, ',', '.') }}</strong>
    </div>

    <div class="card">
        <span style="display:block; color:#6b7280; font-size:14px; font-weight:700; margin-bottom:8px;">{{ isset($selectedStore) ? 'Plan' : 'Tiendas con visitas' }}</span>
        @if(isset($selectedStore))
            <strong style="font-size:34px;">{{ $selectedStore->planLabel() }}</strong>
        @else
        <strong style="font-size:34px;">{{ $stores->total() }}</strong>
        @endif
    </div>
</div>

@if($needsMigration ?? false)
    <div class="flash error">
        La columna de visitas todavia no existe. Ejecuta <strong>php artisan migrate</strong> para habilitar esta seccion.
    </div>
@elseif($stores->isNotEmpty())
    <div class="panel-list">
        @foreach($stores as $store)
            <article class="list-card resource-card">
                <div class="resource-card__main">
                    <div class="resource-card__header">
                        <div>
                            <h3 class="resource-card__title">{{ $store->name }}</h3>
                            <p class="resource-card__subtitle">/{{ $store->slug }}</p>
                        </div>
                        <div class="resource-badges">
                            <span class="resource-badge">{{ $store->businessTypeLabel() }}</span>
                            <span class="resource-badge resource-badge--active">{{ number_format($store->views_count ?? 0, 0, ',', '.') }} visita(s)</span>
                        </div>
                    </div>

                    <div class="resource-metrics">
                        <div class="resource-metric">
                            <span class="resource-metric__label">Usuario</span>
                            <span class="resource-metric__value">{{ $store->user->name ?? 'Sin usuario' }}</span>
                        </div>
                        <div class="resource-metric">
                            <span class="resource-metric__label">URL publica</span>
                            <span class="resource-metric__value">/{{ $store->slug }}</span>
                        </div>
                        <div class="resource-metric">
                            <span class="resource-metric__label">Tipo</span>
                            <span class="resource-metric__value">{{ $store->businessTypeLabel() }}</span>
                        </div>
                        <div class="resource-metric">
                            <span class="resource-metric__label">Visitas</span>
                            <span class="resource-metric__value">{{ number_format($store->views_count ?? 0, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <div class="resource-actions">
                    <a href="{{ url('/' . $store->slug) }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">Ver tienda</a>
                    <a href="{{ route('admin.stores.edit', $store) }}" class="btn">Editar</a>
                </div>
            </article>
        @endforeach
    </div>

    <div class="list-card admin-pagination" style="margin-top:16px;">
        {{ $stores->links('pagination::bootstrap-4') }}
    </div>
@else
    <div class="panel-empty">
        <h3>Aun no hay visitas registradas</h3>
        <p>Cuando las tiendas reciban trafico publico, veras aqui el conteo por tienda.</p>
    </div>
@endif
@endsection

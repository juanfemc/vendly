@extends('layouts.admin')

@section('meta_title', 'Vendly - Panel.')

@section('content')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">

<div class="header">
    <h2>{{ auth()->user()->isAdmin() ? 'Dashboard admin' : 'Dashboard de tienda' }}</h2>
</div>

@if (!auth()->user()->isAdmin() && !empty($banners) && $banners->isNotEmpty())
    <div class="list-card dashboard-banner-card dashboard-banner-card--top">
        <div id="dashboard-banner-slider" class="dashboard-slider">
            @foreach ($banners as $index => $banner)
                <div class="dashboard-slide {{ $index === 0 ? 'is-active' : '' }}">
                    <div class="dashboard-slide-media">
                        <img src="{{ asset('storage/' . $banner->image) }}" alt="{{ $banner->title ?: 'Banner' }}">
                        @if ($banner->title || $banner->subtitle || $banner->link)
                            <div class="dashboard-slide-overlay">
                                @if ($banner->title)
                                    <div class="dashboard-slide-title">{{ $banner->title }}</div>
                                @endif
                                @if ($banner->subtitle)
                                    <div class="dashboard-slide-text">{{ $banner->subtitle }}</div>
                                @endif
                                @if ($banner->link)
                                    <div class="dashboard-slide-actions">
                                        <a href="{{ $banner->link }}" class="btn dashboard-slide-link">Ver mas</a>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        @if ($banners->count() > 1)
            <div class="dashboard-dots">
                @foreach ($banners as $index => $banner)
                    <button type="button" class="dashboard-dot {{ $index === 0 ? 'is-active' : '' }}" data-slide="{{ $index }}"></button>
                @endforeach
            </div>
        @endif
    </div>
@endif

@if (auth()->user()->isAdmin())
    @if (!empty($expiringUsers) && $expiringUsers->isNotEmpty())
        <div class="dashboard-notification">
            <div>
                <strong>Notificaciones</strong>
                <p>Hay usuarios cuyo tiempo activo esta por finalizar.</p>
            </div>

            <div class="dashboard-notification-list">
                @foreach ($expiringUsers as $expiringUser)
                    <a href="{{ route('admin.users.edit', $expiringUser) }}" class="dashboard-notification-item">
                        <span>{{ $expiringUser->name }}</span>
                        <strong>{{ $expiringUser->active_remaining_label }}</strong>
                        <small>Finaliza: {{ $expiringUser->active_ends_at->format('d/m/Y') }}</small>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="dashboard-admin-stats">
        <div class="card dashboard-stat-card dashboard-stat-card--sales">
            <span class="dashboard-stat-label">Total de ventas</span>
            <strong class="dashboard-stat-value">$ {{ number_format($totalSales ?? 0, 0, ',', '.') }}</strong>
        </div>

        <div class="card dashboard-stat-card">
            <span class="dashboard-stat-label">Usuarios de tienda</span>
            <strong class="dashboard-stat-value">{{ $storeUsersCount ?? 0 }}</strong>
        </div>

        <div class="card dashboard-stat-card">
            <span class="dashboard-stat-label">Tiendas creadas</span>
            <strong class="dashboard-stat-value">{{ $storesCount ?? 0 }}</strong>
        </div>

        <div class="card dashboard-stat-card">
            <span class="dashboard-stat-label">Total de visitas</span>
            <strong class="dashboard-stat-value">{{ number_format($totalVisits ?? 0, 0, ',', '.') }}</strong>
        </div>
    </div>

    <div class="list-card">
        <p>Desde aqui puedes crear usuarios de tienda, asignar tiendas y publicar banners/noticias.</p>
    </div>

    <div class="list-card dashboard-updates-card">
        <div class="dashboard-users-head">
            <strong>Nuevas actualizaciones</strong>
            <span>Ultimas 10</span>
        </div>

        @if (!empty($adminUpdates) && $adminUpdates->isNotEmpty())
            <div class="dashboard-updates-list">
                @foreach ($adminUpdates as $adminUpdate)
                    <a
                        href="{{ $adminUpdate->url ?: '#' }}"
                        class="dashboard-update-item {{ $adminUpdate->url ? '' : 'is-static' }}"
                    >
                        <span class="dashboard-update-type">{{ ucfirst($adminUpdate->type) }}</span>
                        <strong>{{ $adminUpdate->title }}</strong>
                        @if ($adminUpdate->body)
                            <p>{{ $adminUpdate->body }}</p>
                        @endif
                        <small>{{ $adminUpdate->created_at?->diffForHumans() }}</small>
                    </a>
                @endforeach
            </div>
        @else
            <p>No hay actualizaciones recientes.</p>
        @endif
    </div>

    <div class="list-card dashboard-users-card">
        <div class="dashboard-users-head">
            <strong>Tiempo activo de usuarios</strong>
            <a href="/admin/users" class="btn btn-secondary">Gestionar usuarios</a>
        </div>

        @if (!empty($storeUsers) && $storeUsers->isNotEmpty())
            <div class="panel-list">
                @foreach ($storeUsers as $storeUser)
                    @php
                        $remainingLabel = $storeUser->active_remaining_label;
                        $remainingClass = $remainingLabel === 'Vencida' ? 'resource-metric__value--danger' : ($remainingLabel === 'Vence hoy' ? 'resource-metric__value--warning' : '');
                    @endphp
                    <article class="resource-card">
                        <div class="resource-card__main">
                            <div class="resource-card__header">
                                <div>
                                    <h3 class="resource-card__title">{{ $storeUser->name }}</h3>
                                    <p class="resource-card__subtitle">{{ $storeUser->email }}</p>
                                </div>
                                <div class="resource-badges">
                                    <span class="resource-badge {{ $storeUser->isActive() ? 'resource-badge--active' : 'resource-badge--inactive' }}">
                                        {{ $storeUser->isActive() ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </div>
                            </div>

                            <div class="resource-metrics">
                                <div class="resource-metric">
                                    <span class="resource-metric__label">Duracion</span>
                                    <span class="resource-metric__value">{{ $storeUser->active_duration_days ? $storeUser->active_duration_days . ' dia(s)' : 'Sin limite' }}</span>
                                </div>
                                <div class="resource-metric">
                                    <span class="resource-metric__label">Inicio</span>
                                    <span class="resource-metric__value">{{ $storeUser->active_starts_at ? $storeUser->active_starts_at->format('d/m/Y') : 'Sin fecha' }}</span>
                                </div>
                                <div class="resource-metric">
                                    <span class="resource-metric__label">Final</span>
                                    <span class="resource-metric__value">{{ $storeUser->active_ends_at ? $storeUser->active_ends_at->format('d/m/Y') : 'Sin fecha final' }}</span>
                                </div>
                                <div class="resource-metric">
                                    <span class="resource-metric__label">Restante</span>
                                    <span class="resource-metric__value {{ $remainingClass }}">{{ $remainingLabel }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="resource-actions">
                            <a href="{{ route('admin.users.edit', $storeUser) }}" class="btn">Editar</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <p>No hay usuarios de tienda registrados.</p>
        @endif
    </div>
@else
    @if (!empty($accountExpiresSoon) && auth()->user()->active_ends_at)
        <div class="dashboard-notification dashboard-notification--warning">
            <div>
                <strong>Notificacion</strong>
                <p>
                    Tu cuenta esta por finalizar:
                    <b>{{ auth()->user()->active_remaining_label }}</b>.
                    Fecha final: {{ auth()->user()->active_ends_at->format('d/m/Y') }}.
                </p>
            </div>
        </div>
    @endif

    <div class="dashboard-admin-stats">
        <div class="card dashboard-stat-card dashboard-stat-card--sales">
            <span class="dashboard-stat-label">Total de ventas</span>
            <strong class="dashboard-stat-value">$ {{ number_format($totalSales ?? 0, 0, ',', '.') }}</strong>
        </div>

        <div class="card dashboard-stat-card">
            <span class="dashboard-stat-label">Productos publicados</span>
            <strong class="dashboard-stat-value">{{ $productsCount ?? 0 }}</strong>
        </div>

        <div class="card dashboard-stat-card">
            <span class="dashboard-stat-label">Pedidos recibidos</span>
            <strong class="dashboard-stat-value">{{ $ordersCount ?? 0 }}</strong>
        </div>

        <div class="card dashboard-stat-card">
            <span class="dashboard-stat-label">Pedidos pagados</span>
            <strong class="dashboard-stat-value">{{ $paidOrdersCount ?? 0 }}</strong>
        </div>

        <div class="card dashboard-stat-card">
            <span class="dashboard-stat-label">Pedidos enviados</span>
            <strong class="dashboard-stat-value">{{ $shippedOrdersCount ?? 0 }}</strong>
        </div>

        <div class="card dashboard-stat-card">
            <span class="dashboard-stat-label">Total de visitas</span>
            <strong class="dashboard-stat-value">{{ number_format($totalVisits ?? 0, 0, ',', '.') }}</strong>
        </div>
    </div>

    @if (!empty($store) && $store->slug)
        <div class="list-card dashboard-store-link-card">
            <div>
                <strong>Tu tienda ya esta publicada</strong>
                <p>Abrela para revisar como la ven tus clientes y compartir el enlace.</p>
            </div>
            <a href="{{ url('/' . $store->slug) }}" class="btn btn-secondary dashboard-store-link" target="_blank" rel="noopener noreferrer">
                Ir a mi tienda
            </a>
        </div>
    @endif

    <div class="list-card">
        <p>Usa el menu lateral para gestionar tus productos y revisar tus pedidos.</p>
    </div>

    @if (!empty($products) && $products->isNotEmpty())
        <div class="grid">
            @foreach ($products as $product)
                <div class="card dashboard-product-card">
                    @if ($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}">
                    @endif
                    <strong>{{ $product->name }}</strong><br>
                    ${{ $product->price }}
                </div>
            @endforeach
        </div>
    @endif

    @if (!empty($banners) && $banners->count() > 1)
        <script src="{{ asset('js/dashboard.js') }}" defer></script>
    @endif
@endif
@endsection

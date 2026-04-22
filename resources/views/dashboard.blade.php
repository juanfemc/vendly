@extends('layouts.admin')

@section('meta_title', 'Vendly - Panel.')

@section('content')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">

<div class="header">
    <h2>{{ auth()->user()->isAdmin() ? 'Dashboard admin' : 'Dashboard de tienda' }}</h2>
</div>

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

    <div class="list-card dashboard-users-card">
        <div class="dashboard-users-head">
            <strong>Tiempo activo de usuarios</strong>
            <a href="/admin/users" class="btn btn-secondary">Gestionar usuarios</a>
        </div>

        @if (!empty($storeUsers) && $storeUsers->isNotEmpty())
            <div class="dashboard-users-table-wrap">
                <table class="dashboard-users-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Tiempo activo</th>
                            <th>Inicio</th>
                            <th>Final</th>
                            <th>Restante</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($storeUsers as $storeUser)
                            <tr>
                                <td>
                                    <strong>{{ $storeUser->name }}</strong><br>
                                    <span>{{ $storeUser->email }}</span>
                                </td>
                                <td>{{ $storeUser->active_duration_days ? $storeUser->active_duration_days . ' dia(s)' : 'Sin limite' }}</td>
                                <td>{{ $storeUser->active_starts_at ? $storeUser->active_starts_at->format('d/m/Y') : 'Sin fecha' }}</td>
                                <td>{{ $storeUser->active_ends_at ? $storeUser->active_ends_at->format('d/m/Y') : 'Sin fecha final' }}</td>
                                <td>{{ $storeUser->active_remaining_label }}</td>
                                <td>
                                    <span class="dashboard-status {{ $storeUser->isActive() ? 'is-active' : 'is-inactive' }}">
                                        {{ $storeUser->isActive() ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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

    @if (!empty($banners) && $banners->isNotEmpty())
        <div class="list-card dashboard-banner-card">
            <div id="dashboard-banner-slider" class="dashboard-slider">
                @foreach ($banners as $index => $banner)
                    <div class="dashboard-slide {{ $index === 0 ? 'is-active' : '' }}">
                        <div class="dashboard-slide-media">
                            <img src="{{ asset('storage/' . $banner->image) }}" alt="{{ $banner->title }}">
                            <div class="dashboard-slide-overlay">
                                <div class="dashboard-slide-title">{{ $banner->title }}</div>
                                @if ($banner->subtitle)
                                    <div class="dashboard-slide-text">{{ $banner->subtitle }}</div>
                                @endif
                                @if ($banner->link)
                                    <div class="dashboard-slide-actions">
                                        <a href="{{ $banner->link }}" class="btn dashboard-slide-link">Ver mas</a>
                                    </div>
                                @endif
                            </div>
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

@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Pedidos</h2>
</div>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="flash error">{{ $errors->first() }}</div>
@endif

@if (($totalOrders ?? $orders->count()) === 0)
    <div class="panel-empty">
        <h3>No hay pedidos todavia</h3>
        <p>Cuando un cliente envie un carrito o reserva, aparecera aqui para gestionarlo.</p>
    </div>
@endif

@if (($totalOrders ?? $orders->count()) > 0)
    <form method="GET" action="{{ url('/admin/orders') }}" class="list-card order-filter-panel">
        <label class="field-label" for="orderStatusFilter">Filtrar por estado</label>
        <select id="orderStatusFilter" name="status" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            @foreach($statusOptions as $value => $label)
                <option value="{{ $value }}" @selected(($selectedStatus ?? null) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="order-filter-count">
            Mostrando {{ $orders->count() }} de {{ $totalOrders ?? $orders->count() }} pedidos
        </div>
    </form>

    @if ($orders->isEmpty())
        <div class="list-card">
            No hay pedidos con ese estado.
        </div>
    @endif
@endif

<div class="panel-list">
    @foreach($orders as $order)
        @php
            $statusBadgeClass = match ($order->status) {
                'pagado' => 'resource-badge--success',
                'enviado' => 'resource-badge--active',
                'devuelto' => 'resource-badge--danger',
                default => 'resource-badge--warning',
            };
        @endphp
        <article class="list-card resource-card">
            <div class="resource-card__main">
                <div class="resource-card__header">
                    <div>
                        <h3 class="resource-card__title">{{ $order->customer_name ?: 'Sin nombre' }}</h3>
                        <p class="resource-card__subtitle">
                            {{ $order->customer_phone ?: 'Sin telefono' }}
                            @if (auth()->user()->isAdmin())
                                · {{ $order->store?->name ?? 'Sin tienda' }}
                            @endif
                        </p>
                    </div>
                    <div class="resource-badges">
                        <span class="resource-badge {{ $statusBadgeClass }}">{{ $order->statusLabel() }}</span>
                        <span class="resource-badge {{ $order->paymentStatusBadgeClass() }}">{{ $order->paymentStatusLabel() }}</span>
                        <span class="resource-badge">${{ number_format($order->total, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="resource-metrics">
                    <div class="resource-metric">
                        <span class="resource-metric__label">Metodo de pago</span>
                        <span class="resource-metric__value">{{ $order->paymentMethodLabel() }}</span>
                    </div>
                    <div class="resource-metric">
                        <span class="resource-metric__label">Estado de pago</span>
                        <span class="resource-metric__value">{{ $order->paymentStatusLabel() }}</span>
                    </div>
                    <div class="resource-metric">
                        <span class="resource-metric__label">Ciudad</span>
                        <span class="resource-metric__value">{{ $order->customer_city ?: 'Sin ciudad' }}</span>
                    </div>
                    <div class="resource-metric">
                        <span class="resource-metric__label">Direccion</span>
                        <span class="resource-metric__value">{{ $order->customer_address ?: 'Sin direccion' }}</span>
                    </div>
                    <div class="resource-metric">
                        <span class="resource-metric__label">Barrio</span>
                        <span class="resource-metric__value">{{ $order->customer_neighborhood ?: 'Sin barrio' }}</span>
                    </div>
                    <div class="resource-metric">
                        <span class="resource-metric__label">Envio</span>
                        <span class="resource-metric__value order-shipping-value">
                            <span>{{ $order->shipping_method ?: 'Sin envio' }}</span>
                            <strong>
                                @if((float) ($order->shipping_cost ?? 0) > 0)
                                    ${{ number_format((float) $order->shipping_cost, 0, ',', '.') }}
                                @elseif($order->shipping_method)
                                    Gratis
                                @else
                                    -
                                @endif
                            </strong>
                        </span>
                    </div>
                    <div class="resource-metric">
                        <span class="resource-metric__label">Documento</span>
                        <span class="resource-metric__value">{{ $order->customer_document ?: 'Sin documento' }}</span>
                    </div>
                    <div class="resource-metric">
                        <span class="resource-metric__label">{{ $order->store?->isReservationStore() ? 'Reserva' : 'Items' }}</span>
                        <span class="resource-metric__value">
                            @if ($order->store?->isReservationStore())
                                {{ $order->reservation_date?->format('Y-m-d') ?: 'Sin fecha' }} {{ $order->reservation_time ?: '' }}
                            @else
                                {{ $order->items->sum('quantity') }} item(s)
                            @endif
                        </span>
                    </div>
                </div>

                @if ($order->items->isNotEmpty())
                    <div class="resource-card__description">
                        <strong>Productos:</strong>
                        <ul style="margin:8px 0 0; padding-left:18px;">
                            @foreach ($order->items as $item)
                                <li>
                                    {{ $item->displayName() }} x{{ $item->quantity }}
                                    @if ($item->size)
                                        - Talla: {{ $item->size }}
                                    @endif
                                    @if ($item->color)
                                        - Color: {{ $item->color }}
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($order->notes)
                    <p class="resource-card__description"><strong>Notas:</strong> {{ $order->notes }}</p>
                @endif

                @if (\App\Models\Order::supportsTermsAcceptanceColumns() && $order->terms_accepted_at)
                    <p class="resource-card__description">
                        <strong>Terminos aceptados:</strong>
                        {{ $order->terms_accepted_at->format('Y-m-d H:i') }}
                        @if($order->terms_version)
                            · Version {{ $order->terms_version }}
                        @endif
                    </p>
                @endif
            </div>

            <div class="resource-actions">
                <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                    @csrf
                    @method('PATCH')
                    <label class="field-label" for="status-{{ $order->id }}">Estado</label>
                    <select name="status" id="status-{{ $order->id }}">
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn">Guardar estado</button>
                </form>

                <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" data-confirm-delete data-confirm-message="Eliminar este pedido? Esta accion no se puede deshacer.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Eliminar pedido</button>
                </form>
            </div>
        </article>
    @endforeach
</div>

@endsection

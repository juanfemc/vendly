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

@if ($orders->isEmpty())
    <div class="panel-empty">
        <h3>No hay pedidos todavia</h3>
        <p>Cuando un cliente envie un carrito o reserva, aparecera aqui para gestionarlo.</p>
    </div>
@endif

@if ($orders->isNotEmpty())
    <div class="list-card order-filter-panel">
        <label class="field-label" for="orderStatusFilter">Filtrar por estado</label>
        <select id="orderStatusFilter" data-order-status-filter>
            <option value="all">Todos los estados</option>
            @foreach($statusOptions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <div class="order-filter-count" data-order-filter-count>
            Mostrando {{ $orders->count() }} de {{ $orders->count() }} pedidos
        </div>
    </div>

    <div class="list-card" data-order-filter-empty hidden>
        No hay pedidos con ese estado.
    </div>
@endif

<div class="panel-list">
    @foreach($orders as $order)
        @php
            $statusBadgeClass = match ($order->status) {
                'pagado' => 'resource-badge--success',
                'enviado' => 'resource-badge--active',
                default => 'resource-badge--warning',
            };
        @endphp
        <article class="list-card resource-card" data-order-card data-order-status="{{ $order->status }}">
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
                        <span class="resource-badge">${{ number_format($order->total, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="resource-metrics">
                    <div class="resource-metric">
                        <span class="resource-metric__label">Ciudad</span>
                        <span class="resource-metric__value">{{ $order->customer_city ?: 'Sin ciudad' }}</span>
                    </div>
                    <div class="resource-metric">
                        <span class="resource-metric__label">Direccion</span>
                        <span class="resource-metric__value">{{ $order->customer_address ?: 'Sin direccion' }}</span>
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

@push('scripts')
    <script>
        (() => {
            const filter = document.querySelector('[data-order-status-filter]');
            const cards = Array.from(document.querySelectorAll('[data-order-card]'));
            const emptyState = document.querySelector('[data-order-filter-empty]');
            const count = document.querySelector('[data-order-filter-count]');

            if (! filter || cards.length === 0) {
                return;
            }

            const updateOrders = () => {
                const selectedStatus = filter.value;
                let visibleOrders = 0;

                cards.forEach((card) => {
                    const shouldShow = selectedStatus === 'all' || card.dataset.orderStatus === selectedStatus;
                    card.hidden = ! shouldShow;

                    if (shouldShow) {
                        visibleOrders += 1;
                    }
                });

                if (emptyState) {
                    emptyState.hidden = visibleOrders !== 0;
                }

                if (count) {
                    count.textContent = `Mostrando ${visibleOrders} de ${cards.length} pedidos`;
                }
            };

            filter.addEventListener('change', updateOrders);
            updateOrders();
        })();
    </script>
@endpush
@endsection

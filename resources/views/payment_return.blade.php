<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido recibido</title>
    <link rel="stylesheet" href="{{ asset('css/cart-checkout.css') }}">
    @include('storefront.partials.meta-pixel', ['store' => $store])
</head>
@php
    $brandTheme = \App\Support\BrandTheme::from($store?->brand_color);
    $isApproved = $order->payment_status === \App\Models\Order::PAYMENT_STATUS_APPROVED;
    $isRejected = in_array($order->payment_status, [
        \App\Models\Order::PAYMENT_STATUS_REJECTED,
        \App\Models\Order::PAYMENT_STATUS_CANCELLED,
        \App\Models\Order::PAYMENT_STATUS_EXPIRED,
        \App\Models\Order::PAYMENT_STATUS_REFUNDED,
    ], true) || $result === 'failure';
    $paymentConfirmationPending = $paymentConfirmationPending ?? false;
    $paymentMethod = $order->paymentMethodLabel();
    $paymentTitle = $isApproved
        ? 'Pedido recibido'
        : ($isRejected ? 'Pago no completado' : 'Pedido recibido');
    $paymentCopy = $paymentConfirmationPending
        ? "Estamos confirmando tu pago con {$paymentMethod}. La tienda vera el estado actualizado en cuanto recibamos la confirmacion."
        : ($isApproved
        ? 'Tu pago fue aprobado y la tienda ya puede gestionar tu pedido.'
        : ($isRejected
            ? "{$paymentMethod} no completo el pago. Puedes volver a la tienda e intentarlo nuevamente."
            : "Tu pago esta pendiente de confirmacion. La tienda vera el pedido en su panel cuando {$paymentMethod} actualice el estado."));
@endphp
<body class="cart-page" style="--accent: {{ $brandTheme->color }};">
    @include('storefront.partials.meta-pixel-noscript', ['store' => $store])

    <main class="payment-return">
        <section class="payment-return-card">
            <div class="payment-return-status {{ $isApproved ? 'is-approved' : ($isRejected ? 'is-rejected' : 'is-pending') }}">
                @if($isApproved)
                    ✓
                @elseif($isRejected)
                    !
                @else
                    …
                @endif
            </div>

            <p class="cart-store-label">{{ $store?->name ?? 'Vendly' }}</p>
            <h1 class="payment-return-title">{{ $paymentTitle }}</h1>
            <p class="payment-return-copy">{{ $paymentCopy }}</p>

            <div class="payment-return-grid">
                <div class="resource-metric payment-return-metric">
                    <span class="resource-metric__label">Numero de pedido</span>
                    <span class="resource-metric__value">#{{ $order->id }}</span>
                </div>
                <div class="resource-metric payment-return-metric">
                    <span class="resource-metric__label">Estado del pago</span>
                    <span class="resource-metric__value">{{ $order->paymentStatusLabel() }}</span>
                </div>
                <div class="resource-metric payment-return-metric">
                    <span class="resource-metric__label">Metodo</span>
                    <span class="resource-metric__value">{{ $order->paymentMethodLabel() }}</span>
                </div>
                <div class="resource-metric payment-return-metric">
                    <span class="resource-metric__label">Total</span>
                    <span class="resource-metric__value">$ {{ number_format($order->total, 0, ',', '.') }}</span>
                </div>
            </div>

            <a class="primary-btn payment-return-button" href="{{ $storeUrl }}">Volver a la tienda</a>
        </section>
    </main>
</body>
</html>

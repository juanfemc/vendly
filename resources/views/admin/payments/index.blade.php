@extends('layouts.admin')

@section('meta_title', 'Vendly - Metodos de pago.')

@section('content')
<div class="header">
    <h2>Metodos de pago</h2>
    <a href="/dashboard" class="btn btn-secondary">Volver al panel</a>
</div>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="flash error">{{ session('error') }}</div>
@endif

<div class="panel-list">
    <article class="list-card resource-card">
        <div class="resource-card__main">
            <div class="resource-card__header">
                <div>
                    <h3 class="resource-card__title">WhatsApp</h3>
                    <p class="resource-card__subtitle">Los clientes pueden enviar pedidos directo a tu numero configurado.</p>
                </div>
                <div class="resource-badges">
                    <span class="resource-badge resource-badge--active">Activo</span>
                </div>
            </div>

            <div class="resource-metrics">
                <div class="resource-metric">
                    <span class="resource-metric__label">Numero</span>
                    <span class="resource-metric__value">{{ $store->whatsapp ?: 'Sin WhatsApp' }}</span>
                </div>
                <div class="resource-metric">
                    <span class="resource-metric__label">Metodo</span>
                    <span class="resource-metric__value">Pedido manual</span>
                </div>
            </div>
        </div>

        <div class="resource-actions">
            <a href="/admin/store-settings" class="btn">Editar WhatsApp</a>
        </div>
    </article>

    <article class="list-card resource-card">
        <div class="resource-card__main">
            <div class="resource-card__header">
                <div>
                    <h3 class="resource-card__title">Mercado Pago</h3>
                    <p class="resource-card__subtitle">Conecta tu propia cuenta para recibir pagos directamente.</p>
                </div>
                <div class="resource-badges">
                    @if($mercadoPagoAccount?->isConnected())
                        <span class="resource-badge resource-badge--active">Conectado</span>
                    @elseif(($mercadoPagoAccount?->status) === \App\Models\StorePaymentAccount::STATUS_EXPIRED)
                        <span class="resource-badge resource-badge--warning">Requiere revision</span>
                    @else
                        <span class="resource-badge">No conectado</span>
                    @endif
                </div>
            </div>

            <div class="resource-metrics">
                <div class="resource-metric">
                    <span class="resource-metric__label">Estado</span>
                    <span class="resource-metric__value">
                        @if($mercadoPagoAccount?->isConnected())
                            Activo
                        @elseif(($mercadoPagoAccount?->status) === \App\Models\StorePaymentAccount::STATUS_EXPIRED)
                            Token vencido
                        @else
                            Pendiente de conexion
                        @endif
                    </span>
                </div>
                <div class="resource-metric">
                    <span class="resource-metric__label">Cuenta</span>
                    <span class="resource-metric__value">{{ $mercadoPagoAccount?->provider_user_id ? 'ID ' . $mercadoPagoAccount->provider_user_id : 'Sin cuenta conectada' }}</span>
                </div>
                <div class="resource-metric">
                    <span class="resource-metric__label">Conexion</span>
                    <span class="resource-metric__value">{{ $mercadoPagoAccount?->connected_at ? $mercadoPagoAccount->connected_at->format('d/m/Y') : 'Sin fecha' }}</span>
                </div>
                <div class="resource-metric">
                    <span class="resource-metric__label">Seguridad</span>
                    <span class="resource-metric__value">Tokens encriptados</span>
                </div>
            </div>

            <p class="resource-card__description">
                El dinero de las compras entrara a la cuenta de Mercado Pago conectada por esta tienda. Al conectar, seras redirigido a Mercado Pago para autorizar Vendly.
            </p>
        </div>

        <div class="resource-actions">
            @if($mercadoPagoAccount?->isConnected())
                <button type="button" class="btn btn-muted" disabled>Conectado</button>
            @else
                <a href="{{ route('admin.payments.mercadopago.connect') }}" class="btn">Conectar Mercado Pago</a>
            @endif
        </div>
    </article>
</div>
@endsection

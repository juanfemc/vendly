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

    <article class="list-card resource-card">
        <div class="resource-card__main">
            <div class="resource-card__header">
                <div>
                    <h3 class="resource-card__title">Wompi</h3>
                    <p class="resource-card__subtitle">Configura tu propia cuenta Wompi para recibir pagos directamente.</p>
                </div>
                <div class="resource-badges">
                    @if($wompiAccount?->isWompiReady())
                        <span class="resource-badge resource-badge--active">Activo</span>
                    @else
                        <span class="resource-badge">No activo</span>
                    @endif
                </div>
            </div>

            <div class="resource-metrics">
                <div class="resource-metric">
                    <span class="resource-metric__label">Modo</span>
                    <span class="resource-metric__value">{{ ($wompiAccount?->mode ?? 'sandbox') === 'production' ? 'Produccion' : 'Pruebas' }}</span>
                </div>
                <div class="resource-metric">
                    <span class="resource-metric__label">Conexion</span>
                    <span class="resource-metric__value">{{ $wompiAccount?->connected_at ? $wompiAccount->connected_at->format('d/m/Y') : 'Sin fecha' }}</span>
                </div>
                <div class="resource-metric">
                    <span class="resource-metric__label">Seguridad</span>
                    <span class="resource-metric__value">Credenciales encriptadas</span>
                </div>
            </div>

            <p class="resource-card__description">
                Pega las llaves de tu comercio Wompi. Vendly solo las usara para generar pagos y confirmar eventos de esta tienda.
            </p>

            <form method="POST" action="{{ route('admin.payments.wompi.update') }}" class="settings-form">
                @csrf

                <label class="settings-toggle">
                    <input type="checkbox" name="enabled" value="1" @checked($wompiAccount?->isWompiReady())>
                    <span>Activar Wompi en el checkout</span>
                </label>

                <div class="settings-grid">
                    <label class="field-wrap">
                        <span class="field-label">Modo</span>
                        <select class="input" name="mode">
                            <option value="sandbox" @selected(($wompiAccount?->mode ?? 'sandbox') === 'sandbox')>Pruebas</option>
                            <option value="production" @selected(($wompiAccount?->mode ?? 'sandbox') === 'production')>Produccion</option>
                        </select>
                    </label>

                    <label class="field-wrap">
                        <span class="field-label">Llave publica</span>
                        <input class="input" type="text" name="public_key" value="{{ old('public_key', $wompiAccount?->public_key) }}" placeholder="pub_test_...">
                    </label>

                    <label class="field-wrap">
                        <span class="field-label">Llave privada</span>
                        <input class="input" type="password" name="private_key" value="" placeholder="{{ $wompiAccount?->private_key ? 'Guardada. Escribe una nueva para cambiarla.' : 'prv_test_...' }}">
                    </label>

                    <label class="field-wrap">
                        <span class="field-label">Secreto de eventos</span>
                        <input class="input" type="password" name="events_secret" value="" placeholder="{{ $wompiAccount?->events_secret ? 'Guardado. Escribe uno nuevo para cambiarlo.' : 'Secreto de eventos' }}">
                    </label>

                    <label class="field-wrap">
                        <span class="field-label">Secreto de integridad</span>
                        <input class="input" type="password" name="integrity_secret" value="" placeholder="{{ $wompiAccount?->integrity_secret ? 'Guardado. Escribe uno nuevo para cambiarlo.' : 'Secreto de integridad' }}">
                    </label>
                </div>

                @if ($errors->any())
                    <div class="flash error">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="resource-actions">
                    <button type="submit" class="btn">Guardar Wompi</button>
                </div>
            </form>
        </div>
    </article>
</div>
@endsection

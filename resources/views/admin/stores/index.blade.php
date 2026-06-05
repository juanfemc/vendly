@extends('layouts.admin')

@section('content')
@php($aiCreditService = app(\App\Services\AiCreditService::class))

<div class="header">
    <h2>Tiendas</h2>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a href="{{ route('admin.stores.create-with-user') }}" class="btn">Crear cliente + tienda</a>
        <a href="/admin/stores/create" class="btn btn-secondary">Solo tienda</a>
    </div>
</div>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

@if($stores->isEmpty())
    <div class="panel-empty">
        <h3>No hay tiendas registradas</h3>
        <p>Crea una tienda para asociarla a un usuario y publicar su catalogo.</p>
        <a href="{{ route('admin.stores.create-with-user') }}" class="btn">Crear cliente + tienda</a>
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
                        @if(\App\Models\Store::supportsSubscriptionColumns())
                            <span class="resource-badge {{ $store->hasActiveSubscription() ? 'resource-badge--active' : 'resource-badge--inactive' }}">
                                {{ $store->subscriptionStatusLabel() }}
                            </span>
                        @endif
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
                    @if(\App\Models\Store::supportsSubscriptionColumns())
                        <div class="resource-metric">
                            <span class="resource-metric__label">Suscripcion</span>
                            <span class="resource-metric__value">{{ $store->subscriptionRemainingLabel() }}</span>
                        </div>
                    @endif
                    @if($store->allowsAiContent())
                        <div class="resource-metric">
                            <span class="resource-metric__label">Creditos IA</span>
                            <span class="resource-metric__value">{{ number_format($aiCreditService->balance($store), 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>

                <p class="resource-card__description">{{ $store->shop_copy ?: 'Sin texto configurado' }}</p>
            </div>

            <div class="resource-actions">
                <a href="{{ url('/' . $store->slug) }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">Ver tienda</a>
                <a href="{{ route('admin.stores.edit', $store) }}" class="btn">Editar</a>
                @if(\App\Models\Store::supportsSubscriptionColumns())
                    <form
                        method="POST"
                        action="{{ route('admin.stores.subscription.activate', $store) }}"
                        class="ai-credit-admin-form"
                        data-subscription-form
                        data-store-name="{{ $store->name }}"
                    >
                        @csrf
                        @method('PATCH')
                        <select name="plan" aria-label="Plan de suscripcion">
                            @foreach(\App\Models\Store::planOptions() as $planValue => $planLabel)
                                <option value="{{ $planValue }}" @selected(($store->plan ?? \App\Models\Store::PLAN_PRO) === $planValue)>
                                    {{ $planLabel }}
                                </option>
                            @endforeach
                        </select>
                        <select name="duration_days" aria-label="Duracion de suscripcion">
                            <option value="30">30 dias</option>
                            <option value="90">90 dias</option>
                            <option value="365">1 ano</option>
                        </select>
                        <button type="submit" class="btn btn-secondary">Activar</button>
                    </form>
                @endif
                @if($store->allowsAiContent())
                    <form
                        method="POST"
                        action="{{ route('admin.stores.ai-credits.store', $store) }}"
                        class="ai-credit-admin-form"
                        data-ai-credit-form
                        data-store-name="{{ $store->name }}"
                    >
                        @csrf
                        <select name="package_key" aria-label="Paquete de creditos IA">
                            @foreach(\App\Services\AiCreditService::PACKAGES as $packageKey => $package)
                                <option value="{{ $packageKey }}">
                                    {{ $package['credits'] }} creditos - ${{ number_format($package['price_cop'], 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-secondary">Sumar creditos IA</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('admin.stores.destroy', $store) }}" data-confirm-delete data-confirm-message="Seguro que quieres eliminar esta tienda? Esta accion no se puede deshacer.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </article>
    @endforeach
</div>

@push('scripts')
    <script>
        (() => {
            document.querySelectorAll('[data-ai-credit-form]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    const select = form.querySelector('select[name="package_key"]');
                    const packageLabel = select?.selectedOptions[0]?.textContent?.trim() || 'este paquete';
                    const storeName = form.dataset.storeName || 'esta tienda';
                    const confirmed = window.confirm(`Confirmar que el pago fue validado y sumar ${packageLabel} a ${storeName}?`);

                    if (!confirmed) {
                        event.preventDefault();
                        return;
                    }

                    form.querySelector('button[type="submit"]')?.setAttribute('disabled', 'disabled');
                });
            });

            document.querySelectorAll('[data-subscription-form]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    const duration = form.querySelector('select[name="duration_days"]')?.selectedOptions[0]?.textContent?.trim() || 'esta duracion';
                    const plan = form.querySelector('select[name="plan"]')?.selectedOptions[0]?.textContent?.trim() || 'este plan';
                    const storeName = form.dataset.storeName || 'esta tienda';
                    const confirmed = window.confirm(`Confirmar pago validado y activar ${storeName} en ${plan} por ${duration}?`);

                    if (!confirmed) {
                        event.preventDefault();
                        return;
                    }

                    form.querySelector('button[type="submit"]')?.setAttribute('disabled', 'disabled');
                });
            });
        })();
    </script>
@endpush
@endsection

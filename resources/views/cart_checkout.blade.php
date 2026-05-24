<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar compra</title>
    <link rel="stylesheet" href="{{ asset('css/cart-checkout.css') }}">
    @if($store?->isTechnologyStore())
        <link rel="stylesheet" href="{{ asset('css/storefront.css') }}?v={{ filemtime(public_path('css/storefront.css')) }}">
        <link rel="stylesheet" href="{{ asset('css/storefront-technology.css') }}?v={{ filemtime(public_path('css/storefront-technology.css')) }}">
    @endif
</head>
@php
    $page = $store ? \App\View\Models\StorefrontPageViewModel::from($store) : null;
    $isRestaurant = $store?->isRestaurant() ?? false;
    $isTechnologyStore = $store?->isTechnologyStore() ?? false;
    $isReservationStore = $store?->isReservationStore() ?? false;
    $businessLabel = $isRestaurant ? 'restaurante' : ($isReservationStore ? 'negocio de reservas' : 'tienda');
    $cartLabel = $isRestaurant ? 'pedido' : ($isReservationStore ? 'reserva' : 'carrito');
    $itemsLabel = $isRestaurant ? 'platos' : ($isReservationStore ? 'servicios' : 'productos');
    $itemLabel = $isRestaurant ? 'plato' : ($isReservationStore ? 'servicio' : 'producto');
    $brandTheme = \App\Support\BrandTheme::from($store?->brand_color);
    $storefrontUrls = app(\App\Services\StorefrontUrlService::class);
    $cartCount = collect($cart)->sum('quantity');
    $instagramUrl = $page?->instagramUrl;
    $facebookUrl = $page?->facebookUrl;
    $tiktokUrl = $page?->tiktokUrl;
    $showAboutSection = $store && trim((string) $store->mission) !== '' && trim((string) $store->vision) !== '';
    $shippingMethods = collect($shippingMethods ?? []);
    $colombiaDepartments = collect($colombiaDepartments ?? []);
    $colombiaLocations = collect($colombiaLocations ?? []);
    $usesColombiaLocations = $colombiaDepartments->isNotEmpty() && $colombiaLocations->isNotEmpty();
    $localDelivery = $localDelivery ?? null;
    $hasLocalDelivery = (bool) ($store?->localDeliveryEnabled() ?? false);
    $selectedShippingKey = old('shipping_method', $shippingMethods->first()['key'] ?? null);
    $selectedShipping = $shippingMethods->firstWhere('key', (string) $selectedShippingKey) ?? $shippingMethods->first();
    $shippingCost = (float) (($localDelivery['cost'] ?? null) ?? ($selectedShipping['checkout_cost'] ?? 0));
    $checkoutTotal = $total + $shippingCost;
    $hasShippingCost = $hasLocalDelivery || $shippingMethods->isNotEmpty();
    $hasSelectedDeliveryCity = $usesColombiaLocations
        ? filled(old('city_code'))
        : filled(old('city'));
@endphp
<body
    class="cart-page {{ $isTechnologyStore ? 'cart-page--technology storefront-page--technology storefront-page--minimal-grid' : '' }}"
    data-csrf="{{ csrf_token() }}"
    data-feedback-updated="{{ $isRestaurant ? 'Pedido actualizado' : ($isReservationStore ? 'Reserva actualizada' : 'Carrito actualizado') }}"
    data-feedback-update-error="{{ $isRestaurant ? 'No se pudo actualizar el pedido.' : ($isReservationStore ? 'No se pudo actualizar la reserva.' : 'No se pudo actualizar el carrito.') }}"
    data-feedback-empty-error="{{ $isRestaurant ? 'No se pudo vaciar el pedido.' : ($isReservationStore ? 'No se pudo vaciar la reserva.' : 'No se pudo vaciar el carrito.') }}"
    data-store-slug="{{ $store?->slug }}"
    data-cart-subtotal="{{ $total }}"
    data-free-shipping-minimum="{{ $store?->free_shipping_minimum ?? '' }}"
    data-local-delivery-enabled="{{ $hasLocalDelivery ? '1' : '0' }}"
    data-local-delivery-area="{{ $store?->local_delivery_area ?? '' }}"
    data-local-delivery-city-code="{{ \App\Models\Store::supportsLocalDeliveryCityCodeColumn() ? ($store?->local_delivery_city_code ?? '') : '' }}"
    data-local-delivery-cost="{{ $store?->local_delivery_cost ?? '' }}"
    data-outside-delivery-cost="{{ $store?->outside_delivery_cost ?? '' }}"
    style="--accent: {{ $brandTheme->color }};"
>
    @if($isTechnologyStore && $store)
        @include('storefront.partials.header-minimal-grid')
    @endif

    @if (empty($cart))
        <main class="{{ $isTechnologyStore ? 'tech-checkout-shell shell' : '' }}">
        <div class="empty-state">
            <h1 class="section-title">Tu {{ $cartLabel }} esta vacio</h1>
            <p>No hay {{ $itemsLabel }} agregados todavia.</p>
            @if ($store)
                <a href="{{ url('/' . $store->slug) }}">Volver al {{ $businessLabel }}</a>
            @endif
        </div>
        </main>
    @else
        <main class="{{ $isTechnologyStore ? 'tech-checkout-shell shell' : '' }}">
        @if($isTechnologyStore)
            <section class="tech-checkout-head">
                <h1>Finalizar compra</h1>
            </section>
        @endif

        <div class="checkout">
            <main class="checkout-main">
                <div class="checkout-inner">
                    @if (session('error'))
                        <div class="flash-error">{{ session('error') }}</div>
                    @endif

                    @if (session('success'))
                        <div class="flash-success">{{ session('success') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="errors">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('cart.whatsapp', ['store' => $store?->slug]) }}" method="POST">
                        @csrf

                        <section>
                            <h2 class="section-title" style="margin-bottom: 16px;">{{ $isRestaurant ? 'Datos del pedido' : ($isReservationStore ? 'Datos de la reserva' : 'Entrega') }}</h2>

                            <div class="grid-two field-wrap">
                                <input class="field" type="text" name="name" placeholder="Nombre" value="{{ old('name') }}" required>
                                <input class="field" type="text" name="last_name" placeholder="Apellidos" value="{{ old('last_name') }}" required>
                            </div>

                            <div class="field-wrap">
                                <input class="field" type="text" name="document" placeholder="Cédula" value="{{ old('document') }}" required>
                            </div>

                            <div class="field-wrap">
                                <input class="field" type="text" name="phone" placeholder="Teléfono" value="{{ old('phone') }}" required>
                            </div>

                            <div class="field-wrap">
                                <input class="field" type="text" name="address" placeholder="Dirección" value="{{ old('address') }}" required>
                            </div>

                            <div class="field-wrap">
                                <input class="field" type="text" name="apartment" placeholder="Casa, apartamento, etc. (opcional)" value="{{ old('apartment') }}">
                            </div>

                            <div class="field-wrap">
                                <input class="field" type="text" name="neighborhood" placeholder="Barrio" value="{{ old('neighborhood') }}" required>
                            </div>

                            @if($usesColombiaLocations)
                                <div class="grid-two field-wrap">
                                    <select class="field" name="department_code" required data-department-select>
                                        <option value="">Departamento</option>
                                        @foreach($colombiaDepartments as $department)
                                            <option value="{{ $department->department_code }}" @selected(old('department_code') === $department->department_code)>{{ $department->department_name }}</option>
                                        @endforeach
                                    </select>

                                    <select class="field" name="city_code" required data-city-select data-city-input disabled>
                                        <option value="">Ciudad / municipio</option>
                                        @foreach($colombiaLocations as $location)
                                            <option
                                                value="{{ $location->city_code }}"
                                                data-department="{{ $location->department_code }}"
                                                data-city-name="{{ $location->city_name }}"
                                                @selected(old('city_code') === $location->city_code)
                                            >{{ $location->city_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <div class="grid-two field-wrap">
                                    <input class="field" type="text" name="city" placeholder="Ciudad" value="{{ old('city') }}" required data-city-input>
                                    <input class="field" type="text" name="region" placeholder="Provincia / Estado (opcional)" value="{{ old('region') }}">
                                </div>
                            @endif

                            @if($hasLocalDelivery)
                                <div class="field-wrap local-delivery-preview" data-local-delivery-preview>
                                    <div>
                                        <span data-local-delivery-label>Envio por ciudad</span>
                                        <small>Selecciona la ciudad para calcular el costo de envio.</small>
                                    </div>
                                    <strong data-local-delivery-price>Por calcular</strong>
                                </div>
                            @endif

                            @if($isReservationStore)
                                @php
                                    $scheduleSummary = $store?->reservationScheduleSummary();
                                @endphp

                                @if(trim((string) $store?->business_hours) !== '' || $scheduleSummary)
                                    <div class="field-wrap">
                                        <div class="flash-error" style="background:#f8fafc; color:#475569; border-color:#e2e8f0;">
                                            @if(trim((string) $store?->business_hours) !== '')
                                                Horario de atencion:<br>{{ $store->business_hours }}
                                            @endif
                                            @if($scheduleSummary)
                                                @if(trim((string) $store?->business_hours) !== '')<br><br>@endif
                                                {!! nl2br(e($scheduleSummary)) !!}
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <div class="grid-two field-wrap">
                                    <input class="field" type="date" name="reservation_date" value="{{ old('reservation_date') }}" min="{{ now()->toDateString() }}" required>
                                    <input class="field" type="time" name="reservation_time" value="{{ old('reservation_time') }}" required>
                                </div>
                            @endif

                            @if(! $hasLocalDelivery && $shippingMethods->isNotEmpty())
                                <fieldset class="field-wrap shipping-fieldset">
                                    <legend class="checkout-field-label">Metodo de envio</legend>
                                    <div class="shipping-options">
                                        @foreach($shippingMethods as $method)
                                            <label class="shipping-option">
                                                <input
                                                    type="radio"
                                                    name="shipping_method"
                                                    value="{{ $method['key'] }}"
                                                    data-shipping-option
                                                    data-shipping-cost="{{ $method['cost'] }}"
                                                    @checked((string) $selectedShippingKey === (string) $method['key'])
                                                    required
                                                >
                                                <span class="shipping-option-mark" aria-hidden="true"></span>
                                                <span class="shipping-option-copy">
                                                    <strong>{{ $method['name'] }}</strong>
                                                    <small>{{ ((float) $method['cost']) > 0 ? 'Costo de envio' : 'Sin costo adicional' }}</small>
                                                </span>
                                                <span class="shipping-option-price" data-shipping-price>
                                                    {{ ((float) $method['checkout_cost']) > 0 ? '$ ' . number_format((float) $method['checkout_cost'], 0, ',', '.') : 'Gratis' }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                            @endif

                            <div class="field-wrap">
                                <textarea class="textarea" name="notes" placeholder="{{ $isRestaurant ? 'Instrucciones del pedido (opcional)' : ($isReservationStore ? 'Fecha, hora o detalles de la reserva (opcional)' : 'Notas del pedido (opcional)') }}">{{ old('notes') }}</textarea>
                            </div>

                            <button class="primary-btn" type="submit">{{ $isRestaurant ? 'Enviar pedido por WhatsApp' : ($isReservationStore ? 'Solicitar reserva por WhatsApp' : 'Finalizar pedido por WhatsApp') }}</button>

                            @if($mercadoPagoAvailable)
                                <div class="payment-divider"><span>o paga en linea</span></div>

                                <button
                                    class="mercadopago-btn"
                                    type="submit"
                                    formaction="{{ route('cart.mercadopago', ['store' => $store?->slug]) }}"
                                >
                                    <span class="mercadopago-mark">MP</span>
                                    <span>Pagar con Mercado Pago</span>
                                </button>
                            @endif
                        </section>
                    </form>
                </div>
            </main>

            <aside class="checkout-side">
                @if($isTechnologyStore)
                    <div class="tech-checkout-summary-head">
                        <h2>Resumen del pedido</h2>
                        <a href="{{ route('cart.index', ['store' => $store?->slug]) }}">Editar carrito</a>
                    </div>
                @endif

                @if ($store && ! $isRestaurant)
                    <div class="cart-store-card">
                        <div class="cart-store-brand">
                            <div class="cart-store-logo-wrap">
                                @if ($store->logo_image)
                                    <img src="{{ asset('storage/' . $store->logo_image) }}" alt="{{ $store->name }}" class="cart-store-logo">
                                @else
                                    <div class="cart-store-logo-fallback">{{ strtoupper(substr($store->name ?? 'T', 0, 1)) }}</div>
                                @endif
                            </div>

                            <div>
                                <div class="cart-store-label">{{ $isReservationStore ? 'Reservas' : 'Tienda' }}</div>
                                <div class="cart-store-name">{{ $store->name }}</div>
                            </div>
                        </div>
                    </div>
                @endif

                @foreach ($cart as $productId => $item)
                    <div class="cart-item" data-cart-item="{{ $productId }}">
                        <div class="cart-thumb-wrap">
                            @if (!empty($item['image']))
                                <img src="{{ asset('storage/' . $item['image']) }}" alt="{{ $item['name'] }}">
                            @else
                                <div class="fallback-thumb"></div>
                            @endif
                            <span class="qty-badge" data-role="quantity-badge">{{ $item['quantity'] }}</span>
                        </div>

                        <div>
                            <div class="item-title">{{ $item['name'] }}</div>
                            <div class="item-meta">{{ $store->name ?? ($isRestaurant ? 'Restaurante' : ($isReservationStore ? 'Reservas' : 'Tienda')) }}</div>
                            @if (!empty($item['size']) || !empty($item['color']))
                                <div class="item-meta">
                                    @if (!empty($item['size']))
                                        Talla: {{ $item['size'] }}
                                    @endif
                                    @if (!empty($item['size']) && !empty($item['color']))
                                        /
                                    @endif
                                    @if (!empty($item['color']))
                                        Color: {{ $item['color'] }}
                                    @endif
                                </div>
                            @endif
                            <div class="item-actions">
                                <div class="qty-control">
                                    <button type="button" class="qty-btn" data-action="decrease" data-product-id="{{ $productId }}">-</button>
                                    <span class="qty-value" data-role="quantity">{{ $item['quantity'] }}</span>
                                    <button type="button" class="qty-btn" data-action="increase" data-product-id="{{ $productId }}">+</button>
                                </div>
                                <button type="button" class="remove-btn" data-action="remove" data-product-id="{{ $productId }}">Eliminar</button>
                            </div>
                        </div>

                        <div class="item-price" data-role="item-total">$ {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}</div>
                    </div>
                @endforeach

                <div class="summary">
                    <div class="summary-actions">
                        <button type="button" class="clear-cart-btn" id="clearCartButton">Vaciar {{ $cartLabel }}</button>
                    </div>

                    <div class="summary-total">
                        <div class="summary-total-label">{{ $hasShippingCost ? 'Subtotal' : 'Total' }}</div>
                        <div class="summary-total-price">
                            <small>COP</small>
                            <strong data-role="total">$ {{ number_format($total, 0, ',', '.') }}</strong>
                        </div>
                    </div>

                    @if($hasShippingCost)
                        <div class="summary-line">
                            <span>Envio</span>
                            <strong data-role="shipping-total">{{ $hasLocalDelivery && ! $hasSelectedDeliveryCity ? 'Por calcular' : ($shippingCost > 0 ? '$ ' . number_format($shippingCost, 0, ',', '.') : 'Gratis') }}</strong>
                        </div>

                        <div class="summary-total summary-total--grand">
                            <div class="summary-total-label">Total</div>
                            <div class="summary-total-price">
                                <small>COP</small>
                                <strong data-role="grand-total">$ {{ number_format($checkoutTotal, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                    @endif
                </div>
            </aside>
        </div>
        </main>
    @endif

    @if($isTechnologyStore && $store)
        @include('storefront.partials.footer-minimal-grid')
    @endif

    <div class="cart-feedback" id="cartFeedback" aria-live="polite"></div>

    <script src="{{ asset('js/cart-checkout.js') }}?v={{ filemtime(public_path('js/cart-checkout.js')) }}" defer></script>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="{{ asset('css/cart-checkout.css') }}">
</head>
@php
    $isRestaurant = $store?->isRestaurant() ?? false;
    $businessLabel = $isRestaurant ? 'restaurante' : 'tienda';
    $cartLabel = $isRestaurant ? 'pedido' : 'carrito';
    $itemsLabel = $isRestaurant ? 'platos' : 'productos';
    $itemLabel = $isRestaurant ? 'plato' : 'producto';
    $brandTheme = \App\Support\BrandTheme::from($store?->brand_color);
@endphp
<body
    class="cart-page"
    data-csrf="{{ csrf_token() }}"
    data-feedback-updated="{{ $isRestaurant ? 'Pedido actualizado' : 'Carrito actualizado' }}"
    data-feedback-update-error="{{ $isRestaurant ? 'No se pudo actualizar el pedido.' : 'No se pudo actualizar el carrito.' }}"
    data-feedback-empty-error="{{ $isRestaurant ? 'No se pudo vaciar el pedido.' : 'No se pudo vaciar el carrito.' }}"
    style="--accent: {{ $brandTheme->color }};"
>
    @if (empty($cart))
        <div class="empty-state">
            <h1 class="section-title">Tu {{ $cartLabel }} esta vacio</h1>
            <p>No hay {{ $itemsLabel }} agregados todavia.</p>
            @if ($store)
                <a href="{{ url('/' . $store->slug) }}">Volver al {{ $businessLabel }}</a>
            @endif
        </div>
    @else
        <div class="checkout">
            <main class="checkout-main">
                <div class="checkout-inner">
                    @if (session('error'))
                        <div class="flash-error">{{ session('error') }}</div>
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

                    <form action="{{ route('cart.whatsapp') }}" method="POST">
                        @csrf

                        <section>
                            <h2 class="section-title" style="margin-bottom: 16px;">{{ $isRestaurant ? 'Datos del pedido' : 'Entrega' }}</h2>

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

                            <div class="grid-two field-wrap">
                                <input class="field" type="text" name="city" placeholder="Ciudad" value="{{ old('city') }}" required>

                                <input class="field" type="text" name="region" placeholder="Provincia / Estado (opcional)" value="{{ old('region') }}">
                            </div>

                            <div class="field-wrap">
                                <textarea class="textarea" name="notes" placeholder="{{ $isRestaurant ? 'Instrucciones del pedido (opcional)' : 'Notas del pedido (opcional)' }}">{{ old('notes') }}</textarea>
                            </div>

                            <button class="primary-btn" type="submit">{{ $isRestaurant ? 'Enviar pedido por WhatsApp' : 'Finalizar pedido por WhatsApp' }}</button>
                        </section>
                    </form>
                </div>
            </main>

            <aside class="checkout-side">
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
                                <div class="cart-store-label">Tienda</div>
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
                            <span class="qty-badge">{{ $item['quantity'] }}</span>
                        </div>

                        <div>
                            <div class="item-title">{{ $item['name'] }}</div>
                            <div class="item-meta">{{ $store->name ?? ($isRestaurant ? 'Restaurante' : 'Tienda') }}</div>
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
                        <div class="summary-total-label">Total</div>
                        <div class="summary-total-price">
                            <small>COP</small>
                            <strong data-role="total">$ {{ number_format($total, 0, ',', '.') }}</strong>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    @endif

    <div class="cart-feedback" id="cartFeedback" aria-live="polite"></div>

    <script src="{{ asset('js/cart-checkout.js') }}" defer></script>
</body>
</html>

@php
    $subtotal = (float) $total;
    $tax = 0;
    $cartItems = collect($cart);
@endphp

<section class="fashion-checkout">
    <nav class="fashion-checkout-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ url('/' . $store->slug) }}">Home</a>
        <span aria-hidden="true">›</span>
        <span>Checkout</span>
    </nav>

    <ol class="fashion-checkout-steps" aria-label="Checkout steps">
        <li class="is-active"><span>1</span><strong>Shipping</strong></li>
        <li><span>2</span><strong>Payment</strong></li>
        <li><span>3</span><strong>Review</strong></li>
        <li><span>4</span><strong>Confirmation</strong></li>
    </ol>

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

    <div class="fashion-checkout-grid">
        <form class="fashion-checkout-form" action="{{ route('cart.whatsapp', ['store' => $store->slug]) }}" method="POST">
            @csrf

            <section class="fashion-checkout-section">
                <div class="fashion-checkout-section-head">
                    <h1>Contact Information</h1>
                    <p>Already have an account? <a href="{{ route('login') }}">Log in</a></p>
                </div>

                <label class="fashion-field fashion-field--full">
                    <span>Email address</span>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com">
                </label>

                <label class="fashion-checkbox">
                    <input type="checkbox" name="accepts_marketing" value="1" checked>
                    <span>Email me with news and offers</span>
                </label>
            </section>

            <section class="fashion-checkout-section">
                <h2>Shipping Address</h2>

                @if($usesColombiaLocations)
                    <label class="fashion-field fashion-field--full">
                        <span>Country / Region</span>
                        <strong>Colombia</strong>
                    </label>

                    <div class="fashion-field-row">
                        <label class="fashion-field">
                            <span>First name</span>
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="John" required>
                        </label>

                        <label class="fashion-field">
                            <span>Last name</span>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Doe" required>
                        </label>
                    </div>

                    <label class="fashion-field fashion-field--full">
                        <span>Address</span>
                        <input type="text" name="address" value="{{ old('address') }}" placeholder="123 Main Street" required>
                    </label>

                    <label class="fashion-field fashion-field--full">
                        <span>Apartment, suite, etc. (optional)</span>
                        <input type="text" name="apartment" value="{{ old('apartment') }}" placeholder="Apt 4B, Floor 2, etc.">
                    </label>

                    <div class="fashion-field-row fashion-field-row--three">
                        <label class="fashion-field">
                            <span>Department</span>
                            <select name="department_code" required data-department-select>
                                <option value="">Select department</option>
                                @foreach($colombiaDepartments as $department)
                                    <option value="{{ $department->department_code }}" @selected(old('department_code') === $department->department_code)>{{ $department->department_name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="fashion-field">
                            <span>City</span>
                            <select name="city_code" required data-city-select data-city-input disabled>
                                <option value="">Select city</option>
                                @foreach($colombiaLocations as $location)
                                    <option
                                        value="{{ $location->city_code }}"
                                        data-department="{{ $location->department_code }}"
                                        data-city-name="{{ $location->city_name }}"
                                        @selected(old('city_code') === $location->city_code)
                                    >{{ $location->city_name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="fashion-field">
                            <span>ZIP Code</span>
                            <input type="text" name="postal_code" value="{{ old('postal_code') }}" placeholder="000000">
                        </label>
                    </div>
                @else
                    <label class="fashion-field fashion-field--full">
                        <span>Country / Region</span>
                        <strong>Colombia</strong>
                    </label>

                    <div class="fashion-field-row">
                        <label class="fashion-field">
                            <span>First name</span>
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="John" required>
                        </label>

                        <label class="fashion-field">
                            <span>Last name</span>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Doe" required>
                        </label>
                    </div>

                    <label class="fashion-field fashion-field--full">
                        <span>Address</span>
                        <input type="text" name="address" value="{{ old('address') }}" placeholder="123 Main Street" required>
                    </label>

                    <label class="fashion-field fashion-field--full">
                        <span>Apartment, suite, etc. (optional)</span>
                        <input type="text" name="apartment" value="{{ old('apartment') }}" placeholder="Apt 4B, Floor 2, etc.">
                    </label>

                    <div class="fashion-field-row fashion-field-row--three">
                        <label class="fashion-field">
                            <span>City</span>
                            <input type="text" name="city" value="{{ old('city') }}" placeholder="New York" required data-city-input>
                        </label>

                        <label class="fashion-field">
                            <span>State</span>
                            <input type="text" name="region" value="{{ old('region') }}" placeholder="State">
                        </label>

                        <label class="fashion-field">
                            <span>ZIP Code</span>
                            <input type="text" name="postal_code" value="{{ old('postal_code') }}" placeholder="10001">
                        </label>
                    </div>
                @endif

                <label class="fashion-field fashion-field--full">
                    <span>Neighborhood</span>
                    <input type="text" name="neighborhood" value="{{ old('neighborhood') }}" placeholder="Barrio" required>
                </label>

                <div class="fashion-field-row">
                    <label class="fashion-field">
                        <span>Phone</span>
                        <input type="text" name="phone" value="{{ old('phone') }}" placeholder="(555) 123-4567" required>
                    </label>

                    <label class="fashion-field">
                        <span>Document</span>
                        <input type="text" name="document" value="{{ old('document') }}" placeholder="Cédula" required>
                    </label>
                </div>

                <label class="fashion-checkbox">
                    <input type="checkbox" name="save_information" value="1" checked>
                    <span>Save this information for next time</span>
                </label>
            </section>

            <section class="fashion-checkout-section">
                <h2>Shipping Method</h2>

                @if($hasLocalDelivery)
                    <div class="fashion-delivery-preview" data-local-delivery-preview>
                        <span data-local-delivery-label>Envio por ciudad</span>
                        <strong data-local-delivery-price>Por calcular</strong>
                    </div>
                @elseif($shippingMethods->isNotEmpty())
                    <fieldset class="fashion-shipping-options">
                        @foreach($shippingMethods as $method)
                            <label class="fashion-shipping-option">
                                <input
                                    type="radio"
                                    name="shipping_method"
                                    value="{{ $method['key'] }}"
                                    data-shipping-option
                                    data-shipping-cost="{{ $method['cost'] }}"
                                    @checked((string) $selectedShippingKey === (string) $method['key'])
                                    required
                                >
                                <span aria-hidden="true"></span>
                                <strong>{{ $method['name'] }}</strong>
                                <em>{{ ((float) $method['cost']) > 0 ? '1-3 business days' : '3-5 business days' }}</em>
                                <b data-shipping-price>{{ ((float) $method['checkout_cost']) > 0 ? '$ ' . number_format((float) $method['checkout_cost'], 0, ',', '.') : 'Free' }}</b>
                            </label>
                        @endforeach
                    </fieldset>
                @else
                    <p class="fashion-checkout-muted">El vendedor coordinara el envio por WhatsApp.</p>
                @endif
            </section>

            <section class="fashion-checkout-section">
                <h2>Payment Method</h2>
                <p class="fashion-checkout-muted">All transactions are secure and encrypted.</p>

                <div class="fashion-payment-options">
                    <label class="fashion-payment-option is-selected">
                        <input type="radio" checked>
                        <span>WhatsApp Order</span>
                        <b>WA</b>
                    </label>

                    @if($mercadoPagoAvailable)
                        <button class="fashion-payment-option fashion-payment-option--button" type="submit" formaction="{{ route('cart.mercadopago', ['store' => $store->slug]) }}">
                            <span>Mercado Pago</span>
                            <b>MP</b>
                        </button>
                    @endif
                </div>
            </section>

            <label class="fashion-field fashion-field--full">
                <span>Order notes (optional)</span>
                <textarea name="notes" placeholder="Add delivery notes or product details">{{ old('notes') }}</textarea>
            </label>

            <div class="fashion-checkout-actions">
                <a href="{{ route('cart.index', ['store' => $store->slug]) }}">‹ Return to Cart</a>
                <button type="submit">Continue to Review</button>
            </div>
        </form>

        <aside class="fashion-checkout-summary">
            <div class="fashion-checkout-summary-head">
                <h2>Order Summary ({{ $cartCount }})</h2>
                <a href="{{ route('cart.index', ['store' => $store->slug]) }}">Edit Cart</a>
            </div>

            <div class="fashion-summary-items">
                @foreach ($cartItems as $productId => $item)
                    <article class="fashion-summary-item" data-cart-item="{{ $productId }}">
                        <div class="fashion-summary-media">
                            @if (!empty($item['image']))
                                <img src="{{ asset('storage/' . $item['image']) }}" alt="{{ $item['name'] }}">
                            @else
                                <span>{{ substr($item['name'], 0, 1) }}</span>
                            @endif
                            <b data-role="quantity-badge">{{ $item['quantity'] }}</b>
                        </div>

                        <div>
                            <h3>{{ $item['name'] }}</h3>
                            @if (!empty($item['color']) || !empty($item['size']))
                                <p>
                                    {{ $item['color'] ?? 'Navy' }}
                                    @if (!empty($item['size']))
                                        / {{ $item['size'] }}
                                    @endif
                                </p>
                            @else
                                <p>{{ $store->name }}</p>
                            @endif
                            <div class="fashion-summary-qty">
                                <button type="button" data-action="decrease" data-product-id="{{ $productId }}">-</button>
                                <span data-role="quantity">{{ $item['quantity'] }}</span>
                                <button type="button" data-action="increase" data-product-id="{{ $productId }}">+</button>
                                <button type="button" data-action="remove" data-product-id="{{ $productId }}" aria-label="Remove item">×</button>
                            </div>
                        </div>

                        <strong data-role="item-total">$ {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}</strong>
                    </article>
                @endforeach
            </div>

            <div class="fashion-summary-totals">
                <p><span>Subtotal</span><strong data-role="total">$ {{ number_format($subtotal, 0, ',', '.') }}</strong></p>
                @if($hasShippingCost)
                    <p><span>Shipping</span><strong data-role="shipping-total">{{ $hasLocalDelivery && ! $hasSelectedDeliveryCity ? 'Por calcular' : ($shippingCost > 0 ? '$ ' . number_format($shippingCost, 0, ',', '.') : 'Free') }}</strong></p>
                @endif
                <p><span>Estimated Tax</span><strong>$ {{ number_format($tax, 0, ',', '.') }}</strong></p>
                <p class="fashion-summary-grand">
                    <span>Total</span>
                    <small>COP</small>
                    <strong data-role="grand-total">$ {{ number_format($checkoutTotal, 0, ',', '.') }}</strong>
                </p>
            </div>

            <div class="fashion-summary-benefits">
                <article>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h11v10H3z"/><path d="M14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>
                    <div><strong>Free Shipping</strong><span>On all orders over $100</span></div>
                </article>
                <article>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/><path d="M3 21v-5h5"/></svg>
                    <div><strong>Easy Returns</strong><span>30-day return policy</span></div>
                </article>
                <article>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="6" y="10" width="12" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                    <div><strong>Secure Payment</strong><span>100% secure checkout</span></div>
                </article>
            </div>
        </aside>
    </div>
</section>

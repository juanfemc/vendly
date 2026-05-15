@php
    $minimalProductCategory = trim((string) $product->category) !== '' ? $product->category : 'Other';
    $minimalGallery = $productGallery->isNotEmpty() ? $productGallery : collect([null]);
    $minimalRelated = $relatedProducts->take(4);
    $minimalAllowsOnlinePayments = $store->allowsOnlinePayments();
    $minimalInitials = strtoupper(substr($product->name, 0, 2));
    $minimalSwatches = ['#111111', '#ffffff', '#33415f'];
    $minimalDescription = $product->description ?: 'Experience pure performance with ' . $product->name . '. Designed for comfort, simple shopping, and everyday use.';
    $plainFeatures = trim(strip_tags(str_replace(['</li>', '<br>', '<br/>', '<br />'], "\n", (string) $product->features)));
    $minimalFeatureItems = collect(preg_split('/\R+/', $plainFeatures) ?: [])
        ->map(fn ($feature) => trim($feature, " \t\n\r\0\x0B-*"))
        ->filter()
        ->take(6)
        ->values();

    if ($minimalFeatureItems->isEmpty()) {
        $minimalFeatureItems = collect([
            'Active Noise Cancellation',
            'Bluetooth 5.3 Connectivity',
            'Built-in Microphone for Calls',
            'Foldable and Lightweight Design',
        ]);
    }

    $minimalBenefits = [
        ['icon' => 'S', 'title' => 'Free Shipping', 'copy' => 'On orders over $50'],
        ['icon' => 'R', 'title' => 'Easy Returns', 'copy' => '30-day return policy'],
        $minimalAllowsOnlinePayments
            ? ['icon' => 'P', 'title' => 'Secure Payment', 'copy' => '100% secure checkout']
            : ['icon' => 'W', 'title' => 'WhatsApp Checkout', 'copy' => 'Confirm your order directly'],
        ['icon' => 'W', 'title' => '2 Year Warranty', 'copy' => 'Quality guaranteed'],
    ];
@endphp

<main class="shell minimal-product-page">
    <section class="minimal-product-breadcrumb" aria-label="Ruta del producto">
        <a href="{{ $storefrontUrls->home($store) }}">Home</a>
        <span aria-hidden="true">&rsaquo;</span>
        <a href="{{ $storefrontUrls->products($store) }}">Shop</a>
        @if($product->category)
            <span aria-hidden="true">&rsaquo;</span>
            <span>{{ $product->category }}</span>
        @endif
        <span aria-hidden="true">&rsaquo;</span>
        <strong>{{ $product->name }}</strong>
    </section>

    <section class="minimal-product-layout">
        <div class="minimal-product-gallery" data-product-carousel>
            <div class="minimal-product-stage">
                <span class="minimal-product-badge">{{ $minimalProductCategory }}</span>
                @foreach($minimalGallery as $index => $galleryImage)
                    @if($galleryImage)
                        <img
                            src="{{ asset('storage/' . $galleryImage) }}"
                            alt="{{ $product->name }} imagen {{ $index + 1 }}"
                            class="minimal-product-image {{ $index === 0 ? 'is-active' : '' }}"
                            data-carousel-slide="{{ $index }}"
                            loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                            fetchpriority="{{ $index === 0 ? 'high' : 'auto' }}"
                            decoding="async"
                        >
                        <div class="minimal-product-placeholder {{ $index === 0 ? 'is-active' : '' }}" data-carousel-fallback="{{ $index }}" hidden>{{ $minimalInitials }}</div>
                    @else
                        <div class="minimal-product-placeholder is-active" data-carousel-slide="{{ $index }}">{{ $minimalInitials }}</div>
                    @endif
                @endforeach

                @if($minimalGallery->count() > 1)
                    <button type="button" class="minimal-product-arrow minimal-product-arrow--prev" data-carousel-prev aria-label="Imagen anterior">&lsaquo;</button>
                    <button type="button" class="minimal-product-arrow minimal-product-arrow--next" data-carousel-next aria-label="Imagen siguiente">&rsaquo;</button>
                @endif
            </div>

            @if($minimalGallery->count() > 1)
                <div class="minimal-product-thumbs" aria-label="Imagenes del producto">
                    @foreach($minimalGallery as $index => $galleryImage)
                        <button
                            type="button"
                            class="minimal-product-thumb {{ $index === 0 ? 'is-active' : '' }}"
                            data-carousel-thumb="{{ $index }}"
                            aria-label="Ver imagen {{ $index + 1 }}"
                            aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                        >
                            @if($galleryImage)
                                <img src="{{ asset('storage/' . $galleryImage) }}" alt="" loading="lazy" decoding="async">
                            @else
                                        <span>{{ $minimalInitials }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif

            <section class="minimal-product-tabs">
                <nav aria-label="Informacion del producto">
                    <a class="is-active" href="#minimalProductDescription">Description</a>
                    <a href="#minimalProductDescription">Specifications</a>
                    <a href="#minimalProductDescription">Reviews (1.2k)</a>
                    <a href="#minimalProductDescription">Shipping and Returns</a>
                </nav>
                <div id="minimalProductDescription" class="minimal-product-copy">
                    <p>{{ $minimalDescription }}</p>
                    <ul>
                        @foreach($minimalFeatureItems as $feature)
                            <li>{{ $feature }}</li>
                        @endforeach
                    </ul>
                </div>
            </section>
        </div>

        <aside class="minimal-product-summary">
            <div class="minimal-product-main">
                <h1>{{ $product->name }}</h1>
                <div class="minimal-product-rating"><span aria-hidden="true">&#9733;</span> 5.0 (1.2k Reviews)</div>
                <div class="minimal-product-price">
                    @if($store->allowsOfferBadges() && $product->hasOfferPricing())
                        <span class="minimal-product-price-before">${{ number_format((float) $product->offer_original_price, 2, '.', ',') }}</span>
                    @endif
                    <span>${{ number_format((float) $product->price, 2, '.', ',') }}</span>
                </div>
                <p>{{ $minimalDescription }}</p>

                <div class="minimal-product-divider"></div>

                @if($product->hasColors())
                    <fieldset class="minimal-product-colors">
                        <legend>Color</legend>
                        <div>
                            @foreach($product->colors as $color)
                                <label>
                                    <input type="radio" name="visual_color" value="{{ $color }}" {{ $loop->first ? 'checked' : '' }} data-role="selected-color-radio">
                                    <span style="--swatch: {{ $minimalSwatches[($loop->iteration - 1) % count($minimalSwatches)] }}"></span>
                                    <em>{{ $color }}</em>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @else
                    <div class="minimal-product-colors" aria-hidden="true">
                        <strong>Color</strong>
                        <div>
                            @foreach($minimalSwatches as $swatch)
                                <span style="--swatch: {{ $swatch }}"></span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($product->hasSizes())
                    <label class="minimal-product-size">
                        <span>Talla</span>
                        <select data-role="selected-size">
                            <option value="">Selecciona talla</option>
                            @foreach($product->sizes as $size)
                                <option value="{{ $size }}">{{ $size }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif

                <div class="minimal-product-quantity-row">
                    <div>
                        <span>Quantity</span>
                        <div class="minimal-product-stepper">
                            <button type="button" data-quantity-minus aria-label="Restar cantidad">&minus;</button>
                            <input id="quantity" type="number" name="quantity" min="1" max="{{ $quantityMax }}" value="{{ old('quantity', 1) }}" class="product-quantity-input">
                            <button type="button" data-quantity-plus aria-label="Sumar cantidad">+</button>
                        </div>
                    </div>
                    @if($product->stockLabel())
                        <span class="minimal-product-stock {{ $isProductSoldOut ? 'is-sold-out' : '' }}">{{ $product->stockLabel() }}</span>
                    @endif
                </div>

                @if($isProductSoldOut)
                    <div class="product-unavailable-message">Este producto esta agotado por ahora.</div>
                @else
                    <div class="minimal-product-actions">
                        <form action="{{ route('cart.add', $product->id) }}" method="POST" class="add-to-cart-form" data-role="minimal-add-form">
                            @csrf
                            <input type="hidden" name="quantity" value="{{ old('quantity', 1) }}" data-role="add-quantity">
                            <input type="hidden" name="size" value="" data-role="add-size">
                            <input type="hidden" name="color" value="" data-role="add-color">
                            <button type="submit" class="minimal-product-add">Add to Cart</button>
                        </form>

                        <form action="{{ route('cart.buy_now', $product->id) }}" method="POST" data-role="buy-now-form">
                            @csrf
                            <input type="hidden" name="quantity" value="{{ old('quantity', 1) }}" data-role="buy-now-quantity">
                            <input type="hidden" name="size" value="" data-role="buy-now-size">
                            <input type="hidden" name="color" value="" data-role="buy-now-color">
                            <button type="submit" class="minimal-product-buy">Buy Now</button>
                        </form>
                    </div>
                @endif

                <button type="button" class="minimal-product-wishlist">&hearts; Add to Wishlist</button>
            </div>

            <section class="minimal-product-benefits" aria-label="Beneficios">
                @foreach($minimalBenefits as $benefit)
                    <div>
                        <span aria-hidden="true">{{ $benefit['icon'] }}</span>
                        <strong>{{ $benefit['title'] }}</strong>
                        <small>{{ $benefit['copy'] }}</small>
                    </div>
                @endforeach
            </section>

            @if($minimalRelated->isNotEmpty())
                <section class="minimal-product-related">
                    <div class="minimal-shop-section-head">
                        <h2>You may also like</h2>
                        <div aria-hidden="true">&lsaquo; &rsaquo;</div>
                    </div>
                    <div class="minimal-product-related-grid">
                        @foreach($minimalRelated as $relatedProduct)
                            @include('storefront.partials.minimal-product-card', ['product' => $relatedProduct, 'isRecommendation' => true])
                        @endforeach
                    </div>
                </section>
            @endif
        </aside>
    </section>
</main>

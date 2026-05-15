@php
    if (isset($store) && (int) $product->store_id === (int) $store->id && ! $product->relationLoaded('store')) {
        $product->setRelation('store', $store);
    }

    $stockLabel = $product->stockLabel();
    $isSoldOut = $product->isSoldOut();
    $isRestaurantCard = isset($store) && $store->isRestaurant();
    $showsOfferBadge = isset($store) && $store->allowsOfferBadges() && $product->hasOfferBadge();
    $showsOfferPricing = $showsOfferBadge && $product->hasOfferPricing();
@endphp

<article class="product-card {{ $cardClass ?? '' }}">
    <div class="product-image">
        @if($showsOfferBadge)
            <span class="product-offer-badge">Oferta</span>
        @endif
        @if($product->image)
            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
        @endif
    </div>

    <h3>{{ $product->name }}</h3>

    <div class="price-row">
        @if($showsOfferPricing)
            <span class="price-stack">
                <span class="price-before">${{ number_format((float) $product->offer_original_price, 0, ',', '.') }}</span>
                <span class="price">${{ number_format((float) $product->price, 0, ',', '.') }}</span>
            </span>
        @else
            <span class="price">${{ number_format((float) $product->price, 0, ',', '.') }}</span>
        @endif
    </div>

    @if($stockLabel)
        <span class="product-stock-badge {{ $isSoldOut ? 'is-sold-out' : '' }}">{{ $stockLabel }}</span>
    @endif

    <a href="{{ $storefrontUrls->product($store, $product) }}" class="product-preview-link {{ $isSoldOut ? 'is-disabled' : '' }}">
        {{ $isSoldOut ? 'Agotado' : ($isRestaurantCard ? 'Ver plato' : 'Ver mas') }}
    </a>
</article>

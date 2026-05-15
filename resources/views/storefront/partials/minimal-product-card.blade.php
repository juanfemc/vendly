@php
    if (isset($store) && (int) $product->store_id === (int) $store->id && ! $product->relationLoaded('store')) {
        $product->setRelation('store', $store);
    }

    $isSoldOut = $product->isSoldOut();
    $productCategory = trim((string) $product->category) !== '' ? $product->category : 'Other';
    $placeholderText = strtoupper(substr($product->name, 0, 2));
    $showsOfferBadge = isset($store) && $store->allowsOfferBadges() && $product->hasOfferBadge();
    $showsOfferPricing = $showsOfferBadge && $product->hasOfferPricing();
@endphp

<article class="minimal-shop-product-card {{ ($isRecommendation ?? false) ? 'minimal-shop-product-card--recommendation' : '' }}">
    <a href="{{ $storefrontUrls->product($store, $product) }}" class="minimal-shop-card-media" aria-label="{{ $product->name }}">
        @if($showsOfferBadge)
            <span class="minimal-shop-offer-badge">Oferta</span>
        @endif
        <span class="minimal-shop-card-badge">{{ $productCategory }}</span>
        @if($product->image)
            <img
                class="minimal-shop-card-image"
                src="{{ asset('storage/' . $product->image) }}"
                alt="{{ $product->name }}"
                loading="lazy"
                decoding="async"
            >
            <span class="minimal-shop-card-placeholder" hidden>{{ $placeholderText }}</span>
        @else
            <span class="minimal-shop-card-placeholder">{{ $placeholderText }}</span>
        @endif
    </a>

    <div class="minimal-shop-card-info">
        <h3>{{ $product->name }}</h3>
        <div class="minimal-shop-card-meta">
            <span class="minimal-shop-rating" aria-label="5.0 de 5 estrellas">&#9733; 5.0 (12k Reviews)</span>
            <span class="minimal-shop-price-stack">
                @if($showsOfferPricing)
                    <span class="minimal-shop-price-before">${{ number_format((float) $product->offer_original_price, 2, '.', ',') }}</span>
                @endif
                <strong>${{ number_format((float) $product->price, 2, '.', ',') }}</strong>
            </span>
        </div>
    </div>

    <div class="minimal-shop-card-actions">
        @if($isSoldOut)
            <span class="minimal-shop-card-button minimal-shop-card-button--disabled">Sold Out</span>
        @elseif($product->hasVariants())
            <a href="{{ $storefrontUrls->product($store, $product) }}" class="minimal-shop-card-button">Add to Cart</a>
        @else
            <form action="{{ route('cart.add', $product->id) }}" method="POST" class="add-to-cart-form">
                @csrf
                <button type="submit">Add to Cart</button>
            </form>
        @endif
        <a href="{{ $storefrontUrls->product($store, $product) }}">Buy Now</a>
    </div>
</article>

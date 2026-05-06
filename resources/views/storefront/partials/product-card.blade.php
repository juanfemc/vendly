@php
    if (isset($store) && (int) $product->store_id === (int) $store->id && ! $product->relationLoaded('store')) {
        $product->setRelation('store', $store);
    }

    $stockLabel = $product->stockLabel();
    $isSoldOut = $product->isSoldOut();
@endphp

<article class="product-card {{ $cardClass ?? '' }}">
    <div class="product-image">
        @if($product->image)
            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
        @endif
    </div>

    <h3>{{ $product->name }}</h3>

    <div class="price-row">
        <span class="price">${{ number_format($product->price, 0, ',', '.') }}</span>
    </div>

    @if($stockLabel)
        <span class="product-stock-badge {{ $isSoldOut ? 'is-sold-out' : '' }}">{{ $stockLabel }}</span>
    @endif

    <a href="{{ route('store.product.show', ['slug' => $store->slug, 'product' => $product->publicRouteKey()]) }}" class="product-preview-link {{ $isSoldOut ? 'is-disabled' : '' }}">
        {{ $isSoldOut ? 'Agotado' : 'Ver mas' }}
    </a>
</article>

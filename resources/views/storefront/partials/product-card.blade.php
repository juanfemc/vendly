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

    <a href="{{ route('store.product.show', ['slug' => $store->slug, 'product' => $product->publicRouteKey()]) }}" class="product-preview-link">
        Ver más
    </a>
</article>

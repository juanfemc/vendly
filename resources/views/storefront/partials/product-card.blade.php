<article class="product-card {{ $cardClass ?? '' }}">
    <div class="product-image">
        @if($product->image)
            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
        @endif
    </div>

    @if($product->category)
        <span class="product-tag">{{ $product->category }}</span>
    @endif

    <h3>{{ $product->name }}</h3>
    <p>{{ $product->description ?: $productDescriptionFallback }}</p>

    <div class="price-row">
        <span class="price">${{ number_format($product->price, 0, ',', '.') }}</span>
    </div>

    <a href="{{ route('store.product.show', ['slug' => $store->slug, 'product' => $product->publicRouteKey()]) }}" class="product-preview-link">
        Comprar ahora
    </a>

    <form action="{{ route('cart.add', $product->id) }}" method="POST" class="add-to-cart-form">
        @csrf
        @if($product->hasSizes() || $product->hasColors())
            <div class="product-options product-options--card">
                @if($product->hasSizes())
                    <label>
                        <span>Talla</span>
                        <select name="size" required>
                            <option value="">Talla</option>
                            @foreach($product->sizes as $size)
                                <option value="{{ $size }}">{{ $size }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif

                @if($product->hasColors())
                    <label>
                        <span>Color</span>
                        <select name="color" required>
                            <option value="">Color</option>
                            @foreach($product->colors as $color)
                                <option value="{{ $color }}">{{ $color }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
            </div>
        @endif
        <button type="submit">{{ $addLabel }}</button>
    </form>
</article>

<section class="page-head">
    <div class="breadcrumb">{{ $businessLabel }} / {{ $store->name }}</div>
</section>

<section class="store-hero restaurant-hero">
    <div class="store-hero-copy">
        <span class="eyebrow">{{ $heroEyebrow }}</span>
        <h1>{{ $store->name }}</h1>
        <p>{{ $defaultHeroCopy }}</p>
        <div class="store-hero-actions">
            <a href="#catalogo" class="hero-primary-link">Ver menu</a>
            @if($featuredProduct)
                <a href="#destacado" class="hero-secondary-link">{{ $featuredItemLabel }}</a>
            @endif
        </div>
    </div>

    <div class="store-hero-media restaurant-hero-media">
        @if($heroImage)
            <img src="{{ $heroImage }}" alt="{{ $store->name }}" loading="eager" fetchpriority="high" decoding="async">
        @else
            <div class="hero-fallback">{{ $store->name }}</div>
        @endif
    </div>
</section>

<section class="shop-meta restaurant-meta">
    <p class="shop-copy">{{ $defaultShopCopy }}</p>
    <span>{{ $productsTotal }} platos en el menu</span>
</section>

@if($featuredProduct)
    <section class="featured-product restaurant-featured" id="destacado">
        <div class="featured-product-media">
            @if($featuredProduct->image)
                <img src="{{ asset('storage/' . $featuredProduct->image) }}" alt="{{ $featuredProduct->name }}" loading="eager" fetchpriority="high" decoding="async">
            @else
                <div class="featured-product-placeholder">{{ $featuredProduct->name }}</div>
            @endif
        </div>

        <div class="featured-product-panel">
            <span class="featured-label">{{ $featuredItemLabel }}</span>
            <h2>{{ $featuredProduct->name }}</h2>
            <div class="featured-price">${{ number_format($featuredProduct->price, 0, ',', '.') }}</div>
            <p class="featured-description">{{ $featuredProduct->description ?: $featuredDescriptionFallback }}</p>
            <div class="featured-quantity">
                <span>Pedido rapido</span>
                <span class="featured-quantity-box">1</span>
            </div>

            <form action="{{ route('cart.add', $featuredProduct->id) }}" method="POST" class="add-to-cart-form featured-form">
                @csrf
                <button type="submit" class="featured-add-button">{{ $addLabel }}</button>
            </form>

            <form action="{{ route('cart.buy_now', $featuredProduct->id) }}" method="POST" class="featured-buy-form">
                @csrf
                <button type="submit" class="featured-buy-button">{{ $buyNowLabel }}</button>
            </form>
        </div>
    </section>
@endif

@include('storefront.partials.category-sections', ['cardClass' => 'restaurant-product-card'])

@include('storefront.partials.footer')

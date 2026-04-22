<section class="page-head supplements-head">
    <div class="breadcrumb">{{ $businessLabel }} / {{ $store->name }}</div>
</section>

<section class="store-hero supplements-hero">
    <div class="store-hero-copy">
        <span class="eyebrow">{{ $heroEyebrow }}</span>
        <h1>{{ $store->name }}</h1>
        <p>{{ $defaultHeroCopy }}</p>
        <div class="store-hero-actions">
            <a href="#catalogo" class="hero-primary-link">Ver linea de suplementos</a>
            @if($featuredProduct)
                <a href="#destacado" class="hero-secondary-link">{{ $featuredItemLabel }}</a>
            @endif
        </div>
    </div>

    <div class="store-hero-media supplements-hero-media">
        @if($heroImage)
            <img src="{{ $heroImage }}" alt="{{ $store->name }}" loading="eager" fetchpriority="high" decoding="async">
        @else
            <div class="hero-fallback">{{ $store->name }}</div>
        @endif
    </div>
</section>

<section class="shop-meta supplements-meta">
    <p class="shop-copy">{{ $defaultShopCopy }}</p>
    <span>{{ $productsTotal }} suplementos disponibles</span>
</section>

@if($featuredProduct)
    <section class="featured-product supplements-featured" id="destacado">
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
                <span>Por unidad</span>
                <span class="featured-quantity-box">1</span>
            </div>

            <form action="{{ route('cart.add', $featuredProduct->id) }}" method="POST" class="add-to-cart-form featured-form">
                @csrf
                <button type="submit" class="featured-add-button">{{ $addLabel }}</button>
            </form>

            <a href="{{ route('store.product.show', ['slug' => $store->slug, 'product' => $featuredProduct->publicRouteKey()]) }}" class="featured-buy-button featured-preview-link">
                {{ $buyNowLabel }}
            </a>
        </div>
    </section>
@endif

<section class="catalog-section" id="catalogo">
    <div class="catalog-head">
        <h2>Catalogo de suplementos</h2>
        <p>{{ $defaultShopCopy }}</p>
    </div>

    @if($products->isNotEmpty())
        <div class="products-grid supplements-grid">
            @foreach($products as $product)
                <article class="product-card supplements-product-card">
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
                        <button type="submit">{{ $addLabel }}</button>
                    </form>
                </article>
            @endforeach
        </div>
        @if($products->hasPages())
            <div class="store-pagination">
                {{ $products->fragment('catalogo')->links() }}
            </div>
        @endif
    @else
        <div class="empty-state">Aun no hay suplementos publicados.</div>
    @endif
</section>

@include('storefront.partials.footer')

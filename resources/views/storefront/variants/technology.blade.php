<section class="tech-hero" id="destacado">
    <div class="tech-hero-copy">
        <span class="eyebrow">{{ $heroEyebrow }}</span>
        <h1>{{ $store->name }}</h1>
        <p>{{ $defaultHeroCopy }}</p>
        <div class="store-hero-actions">
            <a href="#catalogo" class="hero-primary-link">Ver catalogo</a>
            @if($featuredProduct)
                <a href="#promos" class="hero-secondary-link">Promociones</a>
            @endif
        </div>
    </div>

    <div class="tech-hero-media">
        @if($heroImage)
            <img src="{{ $heroImage }}" alt="{{ $store->name }}" loading="eager" fetchpriority="high" decoding="async">
        @endif
    </div>
</section>

<section class="tech-promo-grid" id="promos">
    @foreach($allProducts->take(5)->values() as $index => $product)
        <article class="tech-promo-card tech-promo-card-{{ $index + 1 }}">
            <div class="tech-promo-copy">
                <span>{{ $product->category ?: 'Tecnologia' }}</span>
                <h3>{{ $product->name }}</h3>
                <div class="tech-promo-media">
                    @if($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
                    @endif
                </div>
                <p>{{ $product->description ?: $productDescriptionFallback }}</p>
                <a href="{{ route('store.product.show', ['slug' => $store->slug, 'product' => $product->publicRouteKey()]) }}">Explorar</a>
            </div>
        </article>
    @endforeach
</section>

@if($featuredProduct)
    <section class="tech-sale-banner tech-sale-banner-primary">
        <div class="tech-sale-copy">
            <span>{{ $featuredItemLabel }}</span>
            <h2>{{ $featuredProduct->name }}</h2>
            <p>{{ $featuredProduct->description ?: $featuredDescriptionFallback }}</p>
                <div class="tech-sale-actions">
                    <form action="{{ route('cart.add', $featuredProduct->id) }}" method="POST" class="add-to-cart-form">
                        @csrf
                        @if($featuredProduct->hasSizes() || $featuredProduct->hasColors())
                            <div class="product-options product-options--card">
                                @if($featuredProduct->hasSizes())
                                    <label>
                                        <span>Talla</span>
                                        <select name="size" required>
                                            <option value="">Talla</option>
                                            @foreach($featuredProduct->sizes as $size)
                                                <option value="{{ $size }}">{{ $size }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                @endif

                                @if($featuredProduct->hasColors())
                                    <label>
                                        <span>Color</span>
                                        <select name="color" required>
                                            <option value="">Color</option>
                                            @foreach($featuredProduct->colors as $color)
                                                <option value="{{ $color }}">{{ $color }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                @endif
                            </div>
                        @endif
                        <button type="submit">{{ $addLabel }}</button>
                    </form>
                    <a href="{{ route('store.product.show', ['slug' => $store->slug, 'product' => $featuredProduct->publicRouteKey()]) }}" class="tech-detail-link">Comprar ahora</a>
                </div>
            </div>

        <div class="tech-sale-media">
            @if($featuredProduct->image)
                <img src="{{ asset('storage/' . $featuredProduct->image) }}" alt="{{ $featuredProduct->name }}" loading="eager" fetchpriority="high" decoding="async">
            @endif
        </div>
    </section>
@endif

<section class="catalog-section" id="catalogo">
    <div class="catalog-head tech-section-head">
        <h2>Catalogo de tecnologia</h2>
        <p>{{ $defaultShopCopy }}</p>
    </div>

    @if($products->isNotEmpty())
        <div class="products-grid tech-products-grid">
            @foreach($products as $product)
                <article class="product-card tech-product-card">
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
            @endforeach
        </div>
        @if($products->hasPages())
            <div class="store-pagination">
                {{ $products->fragment('catalogo')->links() }}
            </div>
        @endif
    @else
        <div class="empty-state">Aun no hay productos publicados.</div>
    @endif
</section>

@if($allProducts->count() > 1)
    @php($secondaryProduct = $allProducts->skip(1)->first())
    @if($secondaryProduct)
        <section class="tech-sale-banner tech-sale-banner-secondary">
            <div class="tech-sale-copy">
                <span>{{ $secondaryProduct->category ?: 'Tecnologia seleccionada' }}</span>
                <h2>{{ $secondaryProduct->name }}</h2>
                <p>{{ $secondaryProduct->description ?: $productDescriptionFallback }}</p>
                <div class="tech-sale-actions">
                    <form action="{{ route('cart.add', $secondaryProduct->id) }}" method="POST" class="add-to-cart-form">
                        @csrf
                        @if($secondaryProduct->hasSizes() || $secondaryProduct->hasColors())
                            <div class="product-options product-options--card">
                                @if($secondaryProduct->hasSizes())
                                    <label>
                                        <span>Talla</span>
                                        <select name="size" required>
                                            <option value="">Talla</option>
                                            @foreach($secondaryProduct->sizes as $size)
                                                <option value="{{ $size }}">{{ $size }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                @endif

                                @if($secondaryProduct->hasColors())
                                    <label>
                                        <span>Color</span>
                                        <select name="color" required>
                                            <option value="">Color</option>
                                            @foreach($secondaryProduct->colors as $color)
                                                <option value="{{ $color }}">{{ $color }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                @endif
                            </div>
                        @endif
                        <button type="submit">{{ $addLabel }}</button>
                    </form>
                    <a href="{{ route('store.product.show', ['slug' => $store->slug, 'product' => $secondaryProduct->publicRouteKey()]) }}" class="tech-detail-link">Comprar ahora</a>
                </div>
            </div>

            <div class="tech-sale-media">
                @if($secondaryProduct->image)
                    <img src="{{ asset('storage/' . $secondaryProduct->image) }}" alt="{{ $secondaryProduct->name }}" loading="lazy" decoding="async">
                @endif
            </div>
        </section>
    @endif
@endif

<section class="tech-news-section" id="novedades">
    <div class="catalog-head tech-section-head">
        <h2>Novedades</h2>
        <p>Historias, lanzamientos y productos que vale la pena destacar en la vitrina tech.</p>
    </div>

    <div class="tech-news-grid">
        @foreach($allProducts->take(3) as $product)
            <article class="tech-news-card">
                <div class="tech-news-image">
                    @if($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
                    @endif
                </div>
                <span>{{ $product->category ?: 'Tecnologia' }}</span>
                <h3>{{ $product->name }}</h3>
                <p>{{ $product->description ?: $productDescriptionFallback }}</p>
            </article>
        @endforeach
    </div>
</section>

@include('storefront.partials.footer')

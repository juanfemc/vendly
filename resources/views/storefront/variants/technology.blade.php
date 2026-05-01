<section class="store-hero tech-hero">
    @if($showHeroProductsAction)
        <div class="store-hero-products-action">
            <p class="store-hero-short-copy">{{ $heroShortCopy }}</p>
            <a href="{{ route('store.products.index', $store->slug) }}" class="catalog-all-link">
                Ver todos los {{ $itemsLabel }}
            </a>
        </div>
    @endif

    <div class="store-hero-media tech-hero-media">
        @if($heroImage)
            <img src="{{ $heroImage }}" alt="{{ $store->name }}" loading="eager" fetchpriority="high" decoding="async">
        @else
            <div class="hero-fallback">{{ $store->name }}</div>
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
                <a href="{{ route('store.product.show', ['slug' => $store->slug, 'product' => $product->publicRouteKey()]) }}">Explorar</a>
            </div>
        </article>
    @endforeach
</section>

@include('storefront.partials.category-sections', ['cardClass' => 'tech-product-card'])

@if($allProducts->count() > 1)
    @php($secondaryProduct = $allProducts->skip(1)->first())
    @if($secondaryProduct)
        <section class="tech-sale-banner tech-sale-banner-secondary">
            <div class="tech-sale-copy">
                <span>{{ $secondaryProduct->category ?: 'Tecnologia seleccionada' }}</span>
                <h2>{{ $secondaryProduct->name }}</h2>
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

@include('storefront.partials.about')

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
            </article>
        @endforeach
    </div>
</section>

@include('storefront.partials.footer')

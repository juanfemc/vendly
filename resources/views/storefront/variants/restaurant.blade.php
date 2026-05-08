<section class="store-hero restaurant-hero">
    @if($showHeroProductsAction)
        <div class="store-hero-products-action">
            <p class="store-hero-short-copy">{{ $heroShortCopy }}</p>
            <a href="{{ $storefrontUrls->products($store) }}" class="catalog-all-link">
                Ver todos los {{ $itemsLabel }}
            </a>
        </div>
    @endif

    <div class="store-hero-media restaurant-hero-media">
        @if($heroImage)
            <img src="{{ $heroImage }}" alt="{{ $store->name }}" loading="eager" fetchpriority="high" decoding="async">
        @else
            <div class="hero-fallback">{{ $store->name }}</div>
        @endif
    </div>
</section>

@include('storefront.partials.product-search', ['productSearchId' => 'home'])

<section class="restaurant-menu" id="catalogo">
    @forelse($visibleCategorySections as $section)
        @php($sectionCategory = $section['category'])
        <section class="restaurant-menu-section" id="categoria-{{ $sectionCategory->slug }}">
            <div class="restaurant-menu-section-head">
                <div>
                    <span>Menu</span>
                    <h2>{{ $sectionCategory->name }}</h2>
                </div>

                @if($sectionCategory->description)
                    <p>{{ $sectionCategory->description }}</p>
                @endif
            </div>

            <div class="restaurant-menu-items">
                @foreach($section['products'] as $product)
                    @php($isSoldOut = $product->isSoldOut())
                    <article class="restaurant-menu-item">
                        @if($product->image)
                            <a href="{{ $storefrontUrls->product($store, $product) }}" class="restaurant-menu-item-image" aria-label="{{ $product->name }}">
                                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
                            </a>
                        @endif

                        <div class="restaurant-menu-item-copy">
                            <div class="restaurant-menu-item-line">
                                <h3>{{ $product->name }}</h3>
                                <span></span>
                                <strong>${{ number_format($product->price, 0, ',', '.') }}</strong>
                            </div>

                            <p>{{ $product->description ?: 'Plato disponible para pedir por WhatsApp.' }}</p>

                            @if($product->hasVariants())
                                <div class="restaurant-menu-tags">
                                    @if($product->hasSizes())
                                        <span>Opciones</span>
                                    @endif
                                    @if($product->hasColors())
                                        <span>Variantes</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <a href="{{ $storefrontUrls->product($store, $product) }}" class="restaurant-menu-order-link {{ $isSoldOut ? 'is-disabled' : '' }}">
                            {{ $isSoldOut ? 'Agotado' : 'Pedir' }}
                        </a>
                    </article>
                @endforeach
            </div>

            <a href="{{ $storefrontUrls->category($store, $sectionCategory) }}" class="restaurant-menu-more-link">
                Ver todo en {{ $sectionCategory->name }}
            </a>
        </section>
    @empty
        @if($otherProducts->isEmpty())
            <div class="empty-state">Aun no hay platos publicados.</div>
        @endif
    @endforelse

    @if($otherProducts->isNotEmpty())
        <section class="restaurant-menu-section">
            <div class="restaurant-menu-section-head">
                <div>
                    <span>Menu</span>
                    <h2>Otros platos</h2>
                </div>
            </div>

            <div class="restaurant-menu-items">
                @foreach($otherProducts as $product)
                    @php($isSoldOut = $product->isSoldOut())
                    <article class="restaurant-menu-item">
                        @if($product->image)
                            <a href="{{ $storefrontUrls->product($store, $product) }}" class="restaurant-menu-item-image" aria-label="{{ $product->name }}">
                                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
                            </a>
                        @endif

                        <div class="restaurant-menu-item-copy">
                            <div class="restaurant-menu-item-line">
                                <h3>{{ $product->name }}</h3>
                                <span></span>
                                <strong>${{ number_format($product->price, 0, ',', '.') }}</strong>
                            </div>
                            <p>{{ $product->description ?: 'Plato disponible para pedir por WhatsApp.' }}</p>
                        </div>

                        <a href="{{ $storefrontUrls->product($store, $product) }}" class="restaurant-menu-order-link {{ $isSoldOut ? 'is-disabled' : '' }}">
                            {{ $isSoldOut ? 'Agotado' : 'Pedir' }}
                        </a>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</section>

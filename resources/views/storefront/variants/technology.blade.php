@php
    $categoryLinks = ($activeCategories ?? collect())->values();
    $fallbackCategories = collect(['All Product', 'For Home', 'For Music', 'For Phone', 'For Storage']);
    $minimalProducts = ($allProducts ?? collect())->values();
    $minimalCatalogProducts = $minimalProducts->take(9);
    $minimalRecommendationProducts = $minimalProducts->skip(1)->take(4);
@endphp

<section class="minimal-shop-hero">
    <div class="minimal-shop-hero-media">
        @if($heroImage)
            <img class="minimal-shop-hero-image" src="{{ $heroImage }}" alt="{{ $store->name }}" loading="eager" fetchpriority="high" decoding="async">
            <div class="minimal-shop-hero-fallback" hidden>{{ $store->name }}</div>
        @else
            <div class="minimal-shop-hero-fallback">{{ $store->name }}</div>
        @endif
        <h1>Shop</h1>
    </div>
</section>

<div class="minimal-shop-content-panel">
    <div class="minimal-shop-search-panel">
        <h2>Give All You Need</h2>
        <form action="{{ $storefrontUrls->products($store) }}" method="GET" role="search">
            <label for="minimalShopSearch">Buscar en {{ $store->name }}</label>
            <input id="minimalShopSearch" type="search" name="q" placeholder="Search on {{ $store->name }}" autocomplete="off">
            <button type="submit">Search</button>
        </form>
    </div>

    <section class="minimal-shop-layout" aria-label="Catalogo">
        <aside class="minimal-shop-sidebar">
            <h2>Category</h2>
            <nav aria-label="Categorias">
                @if($categoryLinks->isNotEmpty())
                    <a href="{{ $storefrontUrls->products($store) }}" class="is-active">
                        <span class="minimal-shop-category-icon" aria-hidden="true"></span>
                        All Product
                        <span class="minimal-shop-category-count">{{ $productsTotal }}</span>
                    </a>
                    @foreach($categoryLinks->take(4) as $categoryLink)
                        <a href="{{ $storefrontUrls->category($store, $categoryLink) }}">
                            <span class="minimal-shop-category-icon" aria-hidden="true"></span>
                            {{ $categoryLink->name }}
                        </a>
                    @endforeach
                @else
                    @foreach($fallbackCategories as $index => $categoryName)
                        <a href="{{ $storefrontUrls->products($store) }}" @class(['is-active' => $index === 0])>
                            <span class="minimal-shop-category-icon" aria-hidden="true"></span>
                            {{ $categoryName }}
                            @if($index === 0)
                                <span class="minimal-shop-category-count">{{ $productsTotal }}</span>
                            @endif
                        </a>
                    @endforeach
                @endif
            </nav>

            <div class="minimal-shop-filter-links">
                <a href="{{ $storefrontUrls->products($store) }}">New Arrival</a>
                <a href="{{ $storefrontUrls->products($store) }}">Best Seller</a>
                <a href="{{ $storefrontUrls->products($store) }}">On Discount</a>
            </div>
        </aside>

        <div class="minimal-shop-catalog-shell">
            <div class="minimal-shop-product-grid">
                @forelse($minimalCatalogProducts as $product)
                    @include('storefront.partials.minimal-product-card', ['product' => $product, 'isRecommendation' => false])
                @empty
                    <div class="minimal-shop-empty-state">Aun no hay productos para mostrar.</div>
                @endforelse
            </div>

            <div class="minimal-shop-pagination" aria-hidden="true">
                <span>Previous</span>
                <span class="is-active">1</span>
                <span>2</span>
                <span>3</span>
                <span>...</span>
                <span>8</span>
                <span>9</span>
                <span>10</span>
                <span>Next</span>
            </div>
        </div>
    </section>

    <section class="minimal-shop-recommendations" aria-label="Recomendaciones">
        <div class="minimal-shop-section-head">
            <h2>Explore our recommendations</h2>
            <div aria-hidden="true">
                <span>&larr;</span>
                <span>&rarr;</span>
            </div>
        </div>

        <div class="minimal-shop-recommendation-track">
            @forelse($minimalRecommendationProducts as $product)
                @include('storefront.partials.minimal-product-card', ['product' => $product, 'isRecommendation' => true])
            @empty
                <div class="minimal-shop-empty-state">Aun no hay recomendaciones.</div>
            @endforelse
        </div>
    </section>
</div>

@php
    $categoryLinks = ($activeCategories ?? collect())->values();
    $minimalProducts = ($allProducts ?? collect())->values();
    $minimalRecommendationProducts = $minimalProducts->skip(1)->take(8);
    $icons = \App\Support\MinimalShopIcons::class;
    $minimalHomeUrl = $storefrontUrls->home($store);
    $minimalCategoryUrl = fn (array $query = []) => $minimalHomeUrl . ($query ? '?' . http_build_query($query) : '') . '#catalogo';
    $selectedHomeCategorySlug = $selectedHomeCategory?->slug;
@endphp

<section class="minimal-shop-hero">
    <div class="minimal-shop-hero-media">
        @if($heroImage)
            <img class="minimal-shop-hero-image" src="{{ $heroImage }}" alt="{{ $store->name }}" loading="eager" fetchpriority="high" decoding="async">
            <div class="minimal-shop-hero-fallback" hidden>{{ $store->name }}</div>
        @else
            <div class="minimal-shop-hero-fallback">{{ $store->name }}</div>
        @endif
    </div>
</section>

<div class="minimal-shop-content-panel">
    <div class="minimal-shop-search-panel">
        <h2>Todo lo que necesitas</h2>
        <form action="{{ $storefrontUrls->products($store) }}" method="GET" role="search">
            <label for="minimalShopSearch">Buscar en {{ $store->name }}</label>
            <input id="minimalShopSearch" type="search" name="q" placeholder="Buscar en {{ $store->name }}" autocomplete="off">
            <button type="submit">Buscar</button>
        </form>
    </div>

    <section class="minimal-shop-layout" id="catalogo" aria-label="Catalogo">
        <aside class="minimal-shop-sidebar">
            <h2>Categorias</h2>
            <nav aria-label="Categorias">
                <a href="{{ $minimalCategoryUrl() }}" data-minimal-category-link @class(['is-active' => ! $selectedHomeCategorySlug])>
                    <span class="minimal-shop-category-icon">{!! $icons::categoryIcon('Todos los productos') !!}</span>
                    <span class="minimal-shop-category-label">Todos los productos</span>
                    <span class="minimal-shop-category-count">{{ $productsTotal }}</span>
                </a>

                @foreach($categoryLinks as $categoryLink)
                    <a href="{{ $minimalCategoryUrl(['categoria' => $categoryLink->slug]) }}" data-minimal-category-link @class(['is-active' => $selectedHomeCategorySlug === $categoryLink->slug])>
                        <span class="minimal-shop-category-icon">{!! $icons::categoryIcon($categoryLink->name) !!}</span>
                        <span class="minimal-shop-category-label">{{ $categoryLink->name }}</span>
                    </a>
                @endforeach
            </nav>

        </aside>

        @include('storefront.partials.minimal-catalog', ['catalogProducts' => $catalogProducts])
    </section>

    <section class="minimal-shop-recommendations" aria-label="Recomendaciones">
        <div class="minimal-shop-section-head">
            <h2>Explora nuestras recomendaciones</h2>
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

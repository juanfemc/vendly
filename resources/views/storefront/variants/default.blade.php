<section class="store-hero">
    @if($showHeroProductsAction)
        <div class="store-hero-products-action">
            <p class="store-hero-short-copy">{{ $heroShortCopy }}</p>
            <a href="{{ $storefrontUrls->products($store) }}" class="catalog-all-link">
                Ver todos los {{ $itemsLabel }}
            </a>
        </div>
    @endif

    <div class="store-hero-media">
        @if($heroImage)
            <img src="{{ $heroImage }}" alt="{{ $store->name }}" loading="eager" fetchpriority="high" decoding="async">
        @else
            <div class="hero-fallback">{{ $store->name }}</div>
        @endif
    </div>
</section>

@include('storefront.partials.home-categories')

@include('storefront.partials.product-search', ['productSearchId' => 'home'])

@include('storefront.partials.category-sections')

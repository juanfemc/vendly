<section class="store-hero">
    @if($showHeroProductsAction)
        <div class="store-hero-products-action">
            <p class="store-hero-short-copy">{{ $heroShortCopy }}</p>
            <a href="{{ route('store.products.index', $store->slug) }}" class="catalog-all-link">
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

@include('storefront.partials.category-sections')

@include('storefront.partials.about')

@include('storefront.partials.footer')

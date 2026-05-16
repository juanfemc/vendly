@php($hasOfferProducts = $store->hasOfferProducts())

<header class="minimal-shop-header">
    <div class="shell minimal-shop-nav">
        <a href="{{ $storefrontUrls->home($store) }}" class="minimal-shop-brand" aria-label="{{ $store->name }}">
            @if($store->logo_image)
                <img src="{{ asset('storage/' . $store->logo_image) }}" alt="{{ $store->name }}" class="minimal-shop-logo" loading="eager" decoding="async">
            @else
                <span class="minimal-shop-logo minimal-shop-logo-fallback">{{ strtoupper(substr($store->name ?? 'S', 0, 1)) }}</span>
            @endif
            <span>{{ $store->name }}</span>
        </a>

        <nav class="minimal-shop-links" aria-label="Navegacion principal">
            <a href="{{ $storefrontUrls->home($store) }}">Inicio</a>
            <a href="{{ $storefrontUrls->products($store) }}">Shop</a>
            @if($hasOfferProducts)
                <a href="{{ $storefrontUrls->offers($store) }}" class="minimal-shop-offers-link">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20.2 12.4 12.4 20.2a2.2 2.2 0 0 1-3.1 0l-5.5-5.5a2.2 2.2 0 0 1 0-3.1l7.8-7.8H20v8.6Z"></path>
                        <circle cx="16.5" cy="7.5" r="1.4"></circle>
                    </svg>
                    <span>Ofertas</span>
                </a>
            @endif
            @if($showAboutSection ?? false)
                <a href="{{ $storefrontUrls->about($store) }}">Blog</a>
            @else
                <a href="#minimalShopFooter">Blog</a>
            @endif
        </nav>

        <div class="minimal-shop-actions">
            <a href="{{ $storefrontUrls->products($store) }}" class="minimal-shop-icon-link" aria-label="Buscar productos">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="6"></circle>
                    <path d="m16 16 4 4"></path>
                </svg>
            </a>
            <a href="{{ route('cart.index', ['store' => $store->slug]) }}" class="minimal-shop-icon-link minimal-shop-cart cart-link" aria-label="{{ $cartLabel }}">
                <svg class="cart-link-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M6.5 7h14l-1.4 8.4a2 2 0 0 1-2 1.6H9.2a2 2 0 0 1-2-1.6L5.8 4.8H3.5"></path>
                    <circle cx="9.5" cy="20" r="1.4"></circle>
                    <circle cx="17" cy="20" r="1.4"></circle>
                </svg>
                @if($cartCount > 0)
                    <span class="cart-badge">{{ $cartCount }}</span>
                @endif
            </a>
            <span class="minimal-shop-avatar" aria-hidden="true">
                @if($store->logo_image)
                    <img src="{{ asset('storage/' . $store->logo_image) }}" alt="">
                @else
                    {{ strtoupper(substr($store->name ?? 'S', 0, 1)) }}
                @endif
            </span>
        </div>
    </div>
</header>

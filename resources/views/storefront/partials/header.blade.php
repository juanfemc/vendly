@php
    $announcementMessages = \App\Models\Store::supportsCommercialNoticeColumns()
        ? $store->announcementMessages()
        : [];
    $hasOfferProducts = $store->hasOfferProducts();
@endphp

<div class="storefront-topbar" data-storefront-topbar>
    @if(! empty($announcementMessages))
        <section class="store-announcement-bar" aria-label="Avisos de la tienda" data-announcement-bar data-announcement-speed="42">
            <div class="shell store-announcement-shell">
                <div class="store-announcement-viewport">
                    <div class="store-announcement-message is-marquee-active" data-announcement-message>
                        @for($announcementLoop = 0; $announcementLoop < 8; $announcementLoop++)
                            <p class="store-announcement-group" @if($announcementLoop > 0) aria-hidden="true" @endif>
                                @foreach($announcementMessages as $announcementMessage)
                                    <span>{{ $announcementMessage }}</span>
                                @endforeach
                            </p>
                        @endfor
                    </div>
                </div>
            </div>
        </section>
    @endif

    <header class="navbar">
    <input class="store-cart-state" type="checkbox" id="minimalShopCartToggle" aria-hidden="true">
    <div class="shell navbar-inner">
        <a href="{{ $storefrontUrls->home($store) }}" class="brand" aria-label="{{ $store->name }}">
            @if($store->logo_image)
                <img src="{{ asset('storage/' . $store->logo_image) }}" alt="{{ $store->name }}" class="brand-logo" loading="eager" decoding="async">
            @endif
            <span class="brand-mark">
                <span>{{ $store->name }}</span>
            </span>
        </a>

        <div class="mobile-nav-actions">
            <label for="minimalShopCartToggle" class="cart-link mobile-cart-link" role="button" tabindex="0" aria-label="{{ $cartLabel }}">
                <svg class="cart-link-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M6.5 7h14l-1.4 8.4a2 2 0 0 1-2 1.6H9.2a2 2 0 0 1-2-1.6L5.8 4.8H3.5"></path>
                    <circle cx="9.5" cy="20" r="1.4"></circle>
                    <circle cx="17" cy="20" r="1.4"></circle>
                </svg>
                @if($cartCount > 0)
                    <span class="cart-badge">{{ $cartCount }}</span>
                @endif
            </label>

            <button
                type="button"
                class="nav-toggle"
                aria-expanded="false"
                aria-controls="storefrontNavPanel"
                aria-label="Abrir menu"
            >
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>

        <div class="nav-panel" id="storefrontNavPanel">
            <div class="nav-panel-head">
                <span>{{ $store->name }}</span>
                <button type="button" class="nav-close" aria-label="Cerrar menu">
                    <span></span>
                    <span></span>
                </button>
            </div>

            <nav class="nav-links" aria-label="Navegacion principal">
                <a href="{{ $storefrontUrls->home($store) }}">Inicio</a>
                @if($showAboutSection ?? false)
                    <a href="{{ $storefrontUrls->about($store) }}">Nosotros</a>
                @endif
                <a href="{{ $storefrontUrls->products($store) }}">{{ $isRestaurant ? 'Carta completa' : ($isReservationStore ? 'Todos los servicios' : 'Productos') }}</a>
                @if($hasOfferProducts)
                    <a href="{{ $storefrontUrls->offers($store) }}" class="nav-offer-link">
                        <svg class="nav-offer-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20.2 12.4 12.4 20.2a2.2 2.2 0 0 1-3.1 0l-5.5-5.5a2.2 2.2 0 0 1 0-3.1l7.8-7.8H20v8.6Z"></path>
                            <circle cx="16.5" cy="7.5" r="1.4"></circle>
                        </svg>
                        <span>Ofertas</span>
                    </a>
                @endif
                @if(($activeCategories ?? collect())->isNotEmpty())
                    <div class="nav-dropdown">
                        <button type="button" class="nav-dropdown-button" aria-haspopup="true" aria-expanded="false" aria-controls="storefrontCategoryMenu">
                            <span>Categorias</span>
                            <span class="nav-dropdown-icon" aria-hidden="true"></span>
                        </button>
                        <div class="nav-dropdown-menu" id="storefrontCategoryMenu">
                            @foreach(($activeCategories ?? collect()) as $categoryLink)
                                <a href="{{ $storefrontUrls->category($store, $categoryLink) }}">
                                    {{ $categoryLink->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @elseif($showStorefrontSectionLinks ?? true)
                    <a href="#catalogo">{{ $collectionLabelTitle }}</a>
                @endif
                @if(($showStorefrontSectionLinks ?? true) && $storefrontVariant === 'technology')
                    <a href="#novedades">Novedades</a>
                @endif
            </nav>

            <div class="nav-actions">
                @if($canManageStore)
                    <a href="{{ route('dashboard') }}" class="dashboard-link">Dashboard</a>
                @endif

                <label for="minimalShopCartToggle" class="cart-link desktop-cart-link" role="button" tabindex="0" aria-label="{{ $cartLabel }}">
                    <svg class="cart-link-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M6.5 7h14l-1.4 8.4a2 2 0 0 1-2 1.6H9.2a2 2 0 0 1-2-1.6L5.8 4.8H3.5"></path>
                        <circle cx="9.5" cy="20" r="1.4"></circle>
                        <circle cx="17" cy="20" r="1.4"></circle>
                    </svg>
                    @if($cartCount > 0)
                        <span class="cart-badge">{{ $cartCount }}</span>
                    @endif
                </label>
            </div>
        </div>

        <button type="button" class="nav-backdrop" aria-label="Cerrar menu"></button>
    </div>
</header>
</div>

@include('storefront.partials.cart-drawer')

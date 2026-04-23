<header class="navbar">
    <div class="shell navbar-inner">
        <a href="{{ route('store.show', $store->slug) }}" class="brand" aria-label="{{ $store->name }}">
            @if($store->logo_image)
                <img src="{{ asset('storage/' . $store->logo_image) }}" alt="{{ $store->name }}" class="brand-logo" loading="eager" decoding="async">
            @endif
            <span class="brand-mark">
                <span>{{ $store->name }}</span>
            </span>
        </a>

        <div class="mobile-nav-actions">
            <a href="{{ route('cart.index', ['store' => $store->slug]) }}" class="cart-link mobile-cart-link">
                <svg class="cart-link-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M6.5 7h14l-1.4 8.4a2 2 0 0 1-2 1.6H9.2a2 2 0 0 1-2-1.6L5.8 4.8H3.5"></path>
                    <circle cx="9.5" cy="20" r="1.4"></circle>
                    <circle cx="17" cy="20" r="1.4"></circle>
                </svg>
                @if($cartCount > 0)
                    <span class="cart-badge">{{ $cartCount }}</span>
                @endif
            </a>

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
            <nav class="nav-links" aria-label="Navegacion principal">
                <a href="{{ route('store.show', $store->slug) }}">Inicio</a>
                @if($showStorefrontSectionLinks ?? true)
                    <a href="#destacado">Destacado</a>
                @endif
                @if(($activeCategories ?? collect())->isNotEmpty())
                    <div class="nav-dropdown">
                        <button type="button" class="nav-dropdown-button" aria-haspopup="true" aria-expanded="false">
                            <span>Categorias</span>
                            <span class="nav-dropdown-icon" aria-hidden="true"></span>
                        </button>
                        <div class="nav-dropdown-menu">
                            @foreach(($activeCategories ?? collect()) as $categoryLink)
                                <a href="{{ route('store.category.show', ['slug' => $store->slug, 'category' => $categoryLink->slug]) }}">
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

                <a href="{{ route('cart.index', ['store' => $store->slug]) }}" class="cart-link desktop-cart-link">
                    <svg class="cart-link-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M6.5 7h14l-1.4 8.4a2 2 0 0 1-2 1.6H9.2a2 2 0 0 1-2-1.6L5.8 4.8H3.5"></path>
                        <circle cx="9.5" cy="20" r="1.4"></circle>
                        <circle cx="17" cy="20" r="1.4"></circle>
                    </svg>
                    @if($cartCount > 0)
                        <span class="cart-badge">{{ $cartCount }}</span>
                    @endif
                </a>
            </div>
        </div>
    </div>
</header>

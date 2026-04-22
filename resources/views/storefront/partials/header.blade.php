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
            <a href="{{ route('cart.index', $store->slug) }}" class="cart-link mobile-cart-link">
                <span>{{ $cartLabel }}</span>
                <span class="cart-badge">{{ $cartCount }}</span>
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
                <a href="#destacado">Destacado</a>
                <a href="#catalogo">{{ $collectionLabelTitle }}</a>
                @if($storefrontVariant === 'technology')
                    <a href="#novedades">Novedades</a>
                @endif
            </nav>

            <div class="nav-actions">
                @if($canManageStore)
                    <a href="{{ route('dashboard') }}" class="dashboard-link">Dashboard</a>
                @endif

                <a href="{{ route('cart.index', $store->slug) }}" class="cart-link">
                    <span>{{ $cartLabel }}</span>
                    <span class="cart-badge">{{ $cartCount }}</span>
                </a>
            </div>
        </div>
    </div>
</header>

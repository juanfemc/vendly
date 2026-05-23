@php
    $hasOfferProducts = $store->hasOfferProducts();
    $mobileMenuId = 'minimalShopMenuToggle';
    $cartDrawerId = 'minimalShopCartToggle';
    $mobileCategories = ($activeCategories ?? collect())->values();
    $visibleMobileCategories = $mobileCategories->take(8);
    $hasMoreMobileCategories = $mobileCategories->count() > $visibleMobileCategories->count();
    $mobileBadgeFilters = ($customBadgeFilters ?? collect())->values();
    $selectedMobileBadge = trim((string) ($selectedHomeBadge ?? request('etiqueta', '')));
    $icons = \App\Support\MinimalShopIcons::class;
    $minimalHomeUrl = $storefrontUrls->home($store);
    $minimalCategoryUrl = fn (array $query = []) => $minimalHomeUrl . ($query ? '?' . http_build_query($query) : '') . '#catalogo';
    $drawerCart = app(\App\Services\CartService::class)->cartForStore($store);
    $drawerSubtotal = collect($drawerCart)->sum(fn ($item) => (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 1));
    $drawerShipping = 0;
    $drawerTax = 0;
    $drawerTotal = $drawerSubtotal + $drawerShipping + $drawerTax;
    $drawerProgress = $drawerSubtotal > 0 ? 72 : 0;
    $mobileProductCount = $productsTotal
        ?? (isset($products) && method_exists($products, 'total')
            ? $products->total()
            : (isset($allProducts) ? $allProducts->count() : $store->products()->count()));
    $mobileMenuSections = [];

    if ($mobileBadgeFilters->isNotEmpty()) {
        $mobileMenuSections[] = [
            'label' => 'Etiquetas personalizadas',
            'items' => $mobileBadgeFilters
                ->map(fn ($badge) => [
                    'icon' => 'tag',
                    'text' => $badge,
                    'url' => $minimalCategoryUrl(['etiqueta' => $badge]),
                    'data' => 'minimal-badge-link',
                    'active' => $selectedMobileBadge === $badge,
                ])
                ->all(),
        ];
    }

    $mobileMenuSections[] = [
            'label' => 'Informacion movil',
            'items' => array_values(array_filter([
                ($showAboutSection ?? false)
                    ? ['icon' => 'users', 'text' => 'Nosotros', 'url' => $storefrontUrls->about($store)]
                    : null,
                ['icon' => 'phone-call', 'text' => 'Contactanos', 'url' => '#minimalShopFooter'],
            ])),
        ];
@endphp

<header class="minimal-shop-header">
    <input class="minimal-shop-menu-state" type="checkbox" id="{{ $mobileMenuId }}" aria-hidden="true">
    <input class="minimal-shop-cart-state" type="checkbox" id="{{ $cartDrawerId }}" aria-hidden="true">
    <div class="shell minimal-shop-nav">
        <label class="minimal-shop-menu-button" for="{{ $mobileMenuId }}" aria-label="Abrir menu">
            {!! $icons::icon('menu') !!}
        </label>

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
            <a href="{{ $storefrontUrls->products($store) }}">Tienda</a>
            @foreach($mobileBadgeFilters as $badge)
                <a
                    href="{{ $minimalCategoryUrl(['etiqueta' => $badge]) }}"
                    data-minimal-badge-link
                    @class(['is-active' => $selectedMobileBadge === $badge])
                >
                    {{ $badge }}
                </a>
            @endforeach
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
                <a href="{{ $storefrontUrls->about($store) }}">Nosotros</a>
            @endif
        </nav>

        <div class="minimal-shop-actions">
            <a href="{{ $storefrontUrls->products($store) }}" class="minimal-shop-icon-link" aria-label="Buscar productos">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="6"></circle>
                    <path d="m16 16 4 4"></path>
                </svg>
            </a>
            <label for="{{ $cartDrawerId }}" class="minimal-shop-icon-link minimal-shop-cart cart-link" aria-label="{{ $cartLabel }}">
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

    <label class="minimal-shop-menu-backdrop" for="{{ $mobileMenuId }}" aria-hidden="true"></label>
    <label class="minimal-shop-cart-backdrop" for="{{ $cartDrawerId }}" aria-hidden="true"></label>

    <aside
        class="minimal-shop-cart-drawer{{ $cartCount < 1 ? ' is-empty' : '' }}"
        aria-label="Carrito"
        data-cart-drawer
        data-cart-subtotal="{{ $drawerSubtotal }}"
        data-cart-shipping="{{ $drawerShipping }}"
        data-cart-tax="{{ $drawerTax }}"
        data-store-url="{{ $storefrontUrls->home($store) }}"
    >
        <div class="minimal-shop-cart-head">
            <h2>Tu carrito (<span data-cart-drawer-count>{{ $cartCount }}</span>)</h2>
            <label for="{{ $cartDrawerId }}" aria-label="Cerrar carrito">{!! $icons::icon('close') !!}</label>
        </div>

        <div class="minimal-shop-cart-free">
            <div>
                <span>&check;</span>
                <p>Tu pedido califica para envio gratis.</p>
                <small>Estas a $20.00<br>del envio gratis</small>
            </div>
            <b style="--cart-progress: {{ $drawerProgress }}%"></b>
        </div>

        <div class="minimal-shop-cart-items" data-cart-drawer-items>
            @forelse($drawerCart as $cartKey => $item)
                @php
                    $drawerItemImage = trim((string) ($item['image'] ?? ''));
                    $drawerItemPrice = (float) ($item['price'] ?? 0);
                    $drawerItemQuantity = (int) ($item['quantity'] ?? 1);
                @endphp
                <article class="minimal-shop-cart-item" data-cart-drawer-item data-cart-key="{{ $cartKey }}">
                    <div class="minimal-shop-cart-thumb">
                        @if($drawerItemImage !== '')
                            <img src="{{ asset('storage/' . $drawerItemImage) }}" alt="{{ $item['name'] ?? 'Producto' }}">
                        @else
                            <span>{{ strtoupper(substr((string) ($item['name'] ?? 'P'), 0, 1)) }}</span>
                        @endif
                    </div>
                    <div class="minimal-shop-cart-info">
                        <span>{{ $item['category'] ?? (($item['color'] ?? null) ?: 'Otros') }}</span>
                        <strong>{{ $item['name'] ?? 'Producto' }}</strong>
                        @if(! empty($item['color']))
                            <small>{{ $item['color'] }}</small>
                        @elseif(! empty($item['size']))
                            <small>{{ $item['size'] }}</small>
                        @else
                            <small>Sin variante</small>
                        @endif
                        <b data-cart-item-total>${{ number_format($drawerItemPrice * $drawerItemQuantity, 2) }}</b>
                    </div>
                    <div class="minimal-shop-cart-controls">
                        <button type="button" data-cart-drawer-minus aria-label="Restar">&minus;</button>
                        <span data-cart-drawer-quantity>{{ $drawerItemQuantity }}</span>
                        <button type="button" data-cart-drawer-plus aria-label="Sumar">+</button>
                        <button type="button" data-cart-drawer-remove aria-label="Eliminar">{!! $icons::icon('trash') !!}</button>
                    </div>
                </article>
            @empty
                <div class="minimal-shop-cart-empty" data-cart-drawer-empty>
                    <strong>Tu carrito está vacío</strong>
                    <a href="{{ $storefrontUrls->home($store) }}">Volver a la tienda</a>
                </div>
            @endforelse
        </div>

        <div class="minimal-shop-cart-summary">
            <p><span>Subtotal</span><strong data-cart-drawer-subtotal>${{ number_format($drawerSubtotal, 2) }}</strong></p>
            <p><span>Envio</span><strong data-cart-drawer-shipping>Gratis</strong></p>
            <p><span>Impuestos</span><strong data-cart-drawer-tax>${{ number_format($drawerTax, 2) }}</strong></p>
            <p class="minimal-shop-cart-total"><span>Total</span><strong data-cart-drawer-total>${{ number_format($drawerTotal, 2) }}</strong></p>
        </div>

        <div class="minimal-shop-cart-actions">
            <a href="{{ route('cart.index', ['store' => $store->slug]) }}">Ver carrito</a>
            <a href="{{ route('cart.index', ['store' => $store->slug]) }}">Finalizar compra</a>
        </div>

        <p class="minimal-shop-cart-secure">{!! $icons::icon('lock') !!} Compra segura</p>
    </aside>

    <aside class="minimal-shop-mobile-menu" aria-label="Menu movil">
        <label class="minimal-shop-menu-close" for="{{ $mobileMenuId }}" aria-label="Cerrar menu">
            {!! $icons::icon('close') !!}
        </label>

        <a href="{{ $storefrontUrls->home($store) }}" class="minimal-shop-mobile-brand" aria-label="{{ $store->name }}">
            @if($store->logo_image)
                <img src="{{ asset('storage/' . $store->logo_image) }}" alt="{{ $store->name }}">
            @else
                <span>{{ strtoupper(substr($store->name ?? 'S', 0, 1)) }}</span>
            @endif
            <strong>{{ $store->name }}</strong>
        </a>

        <nav class="minimal-shop-mobile-section" aria-label="Categorias moviles">
            <p>Comprar por categoria</p>
            <a href="{{ $minimalCategoryUrl() }}" data-minimal-category-link>
                <span>{!! $icons::icon('grid') !!}</span>
                <strong>Todos los productos</strong>
                <em>{{ $mobileProductCount }}</em>
                <i>{!! $icons::icon('arrow') !!}</i>
            </a>

            @foreach($visibleMobileCategories as $categoryLink)
                <a href="{{ $minimalCategoryUrl(['categoria' => $categoryLink->slug]) }}" data-minimal-category-link>
                    <span>{!! $icons::categoryIcon($categoryLink->name) !!}</span>
                    <strong>{{ $categoryLink->name }}</strong>
                    <i>{!! $icons::icon('arrow') !!}</i>
                </a>
            @endforeach

            @if($hasMoreMobileCategories)
                <a href="{{ $minimalCategoryUrl() }}" data-minimal-category-link>
                    <span>{!! $icons::icon('grid') !!}</span>
                    <strong>Ver todas las categorias</strong>
                    <i>{!! $icons::icon('arrow') !!}</i>
                </a>
            @endif
        </nav>

        @foreach($mobileMenuSections as $section)
            <nav class="minimal-shop-mobile-section" aria-label="{{ $section['label'] }}">
                @foreach($section['items'] as $item)
                    <a
                        href="{{ $item['url'] }}"
                        @if(! empty($item['data'])) data-{{ $item['data'] }} @endif
                        @class(['is-active' => ! empty($item['active'])])
                    >
                        <span>{!! $icons::icon($item['icon']) !!}</span>
                        <strong>{{ $item['text'] }}</strong>
                        <i>{!! $icons::icon('arrow') !!}</i>
                    </a>
                @endforeach
            </nav>
        @endforeach

        <a class="minimal-shop-mobile-logout" href="{{ $storefrontUrls->home($store) }}">
            {!! $icons::icon('logout') !!}
            <span>Salir</span>
        </a>
    </aside>
</header>

@php
    $announcementMessages = \App\Models\Store::supportsCommercialNoticeColumns()
        ? $store->announcementMessages()
        : [];
    $storeWhatsappUrl = $store->whatsappInfoUrl();
    $canManageStore = $canManageStore ?? false;
    $cartCount = $cartCount ?? 0;
    $instagramUrl = $instagramUrl ?? null;
    $facebookUrl = $facebookUrl ?? null;
    $tiktokUrl = $tiktokUrl ?? null;
    $drawerCart = app(\App\Services\CartService::class)->cartForStore($store);
    $drawerSubtotal = collect($drawerCart)->sum(fn ($item) => (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 1));
    $drawerShipping = 0;
    $drawerTax = 0;
    $drawerTotal = $drawerSubtotal + $drawerShipping + $drawerTax;
    $drawerProgress = $drawerSubtotal > 0 ? min(100, max(18, ($drawerSubtotal / 100) * 72)) : 0;
@endphp

<div class="storefront-topbar fashion-topbar" data-storefront-topbar>
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

    <header class="fashion-navbar navbar">
        <input class="fashion-cart-state minimal-shop-cart-state" type="checkbox" id="minimalShopCartToggle" aria-hidden="true">

        <div class="fashion-navbar-shell">
            <a href="{{ $storefrontUrls->home($store) }}" class="fashion-brand" aria-label="{{ $store->name }}">
                <span>{{ $store->name }}</span>
            </a>

            <button
                type="button"
                class="fashion-menu-toggle nav-toggle"
                aria-expanded="false"
                aria-controls="storefrontNavPanel"
                aria-label="Abrir menu"
            >
                <span></span>
                <span></span>
                <span></span>
            </button>

            <div class="fashion-mobile-actions" aria-label="Acciones rapidas">
                <a href="{{ $storefrontUrls->products($store) }}" class="fashion-icon-link" aria-label="Buscar">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m16.5 16.5 4 4"></path>
                    </svg>
                </a>
                <a href="{{ $canManageStore ? route('dashboard') : $storefrontUrls->home($store) }}" class="fashion-icon-link" aria-label="Cuenta">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20 21a8 8 0 0 0-16 0"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </a>
                <a href="{{ $storefrontUrls->products($store) }}" class="fashion-icon-link" aria-label="Favoritos">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20.8 5.6a5.2 5.2 0 0 0-7.4 0L12 7l-1.4-1.4a5.2 5.2 0 0 0-7.4 7.4L12 21.8l8.8-8.8a5.2 5.2 0 0 0 0-7.4Z"></path>
                    </svg>
                </a>
                <label for="minimalShopCartToggle" class="fashion-icon-link fashion-cart-link cart-link" aria-label="Carrito">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M6.5 8.5h11l-.8 11h-9.4l-.8-11Z"></path>
                        <path d="M9 8.5a3 3 0 0 1 6 0"></path>
                    </svg>
                    @if($cartCount > 0)
                        <span class="fashion-cart-badge cart-badge">{{ $cartCount }}</span>
                    @endif
                </label>
            </div>

            <div class="fashion-nav-panel nav-panel" id="storefrontNavPanel">
                <div class="nav-panel-head">
                    <span>{{ $store->name }}</span>
                    <button type="button" class="nav-close" aria-label="Cerrar menu">
                        <span></span>
                        <span></span>
                    </button>
                </div>

                <nav class="fashion-nav-links" aria-label="Navegacion principal">
                    <a href="{{ $storefrontUrls->home($store) }}">
                        <span>Home</span>
                        <span class="fashion-nav-chevron" aria-hidden="true"></span>
                    </a>
                    <a href="{{ $storefrontUrls->products($store) }}" class="is-active">
                        <span>Shop</span>
                        <span class="fashion-nav-chevron is-up" aria-hidden="true"></span>
                    </a>
                    <a href="{{ $storefrontUrls->home($store) }}">
                        <span>Pages</span>
                        <span class="fashion-nav-chevron" aria-hidden="true"></span>
                    </a>
                    <a href="{{ $storefrontUrls->home($store) }}#catalogo">
                        <span>Blog</span>
                        <span class="fashion-nav-chevron" aria-hidden="true"></span>
                    </a>
                    <a href="{{ $storeWhatsappUrl ?? $storefrontUrls->home($store) }}">
                        <span>Contact Us</span>
                    </a>
                </nav>

                <div class="fashion-nav-actions">
                    @if($canManageStore)
                        <a href="{{ route('dashboard') }}" class="fashion-icon-link" aria-label="Cuenta">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M20 21a8 8 0 0 0-16 0"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </a>
                    @else
                        <a href="{{ $storefrontUrls->home($store) }}" class="fashion-icon-link" aria-label="Cuenta">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M20 21a8 8 0 0 0-16 0"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </a>
                    @endif
                    <a href="{{ $storefrontUrls->products($store) }}" class="fashion-icon-link" aria-label="Buscar">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"></circle>
                            <path d="m16.5 16.5 4 4"></path>
                        </svg>
                    </a>
                    <a href="{{ $storefrontUrls->products($store) }}" class="fashion-icon-link" aria-label="Favoritos">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20.8 5.6a5.2 5.2 0 0 0-7.4 0L12 7l-1.4-1.4a5.2 5.2 0 0 0-7.4 7.4L12 21.8l8.8-8.8a5.2 5.2 0 0 0 0-7.4Z"></path>
                        </svg>
                    </a>
                    <label for="minimalShopCartToggle" class="fashion-icon-link fashion-cart-link cart-link" aria-label="Carrito">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M6.5 8.5h11l-.8 11h-9.4l-.8-11Z"></path>
                            <path d="M9 8.5a3 3 0 0 1 6 0"></path>
                        </svg>
                        @if($cartCount > 0)
                            <span class="fashion-cart-badge cart-badge">{{ $cartCount }}</span>
                        @endif
                    </label>
                </div>

                <div class="fashion-mobile-drawer">
                    <nav class="fashion-mobile-menu" aria-label="Menu movil">
                        <a href="{{ $storefrontUrls->home($store) }}" class="is-active">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11.5 12 4l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-8.5Z"></path></svg>
                            <span>Inicio</span>
                        </a>
                        <a href="{{ $storefrontUrls->products($store) }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.5 8.5h11l-.8 11h-9.4l-.8-11Z"></path><path d="M9 8.5a3 3 0 0 1 6 0"></path></svg>
                            <span>Tienda</span>
                            <b aria-hidden="true">⌄</b>
                        </a>
                        <a href="{{ $storefrontUrls->products($store) }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4h8l2 4-3 2v10H9V10L6 8l2-4Z"></path></svg>
                            <span>Categorias</span>
                            <b aria-hidden="true">⌄</b>
                        </a>
                        <a href="{{ $storefrontUrls->offers($store) }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.2 12.4 12.4 20.2a2.2 2.2 0 0 1-3.1 0l-5.5-5.5a2.2 2.2 0 0 1 0-3.1l7.8-7.8H20v8.6Z"></path><circle cx="16.5" cy="7.5" r="1.4"></circle></svg>
                            <span>Ofertas</span>
                        </a>
                        <a href="{{ $storefrontUrls->products($store) }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3l-5.6 2.9 1.1-6.2L3 9.6l6.2-.9L12 3Z"></path></svg>
                            <span>Novedades</span>
                        </a>
                        <a href="{{ $storefrontUrls->home($store) }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l3 3v15H6z"></path><path d="M14 3v4h4"></path><path d="M9 12h6"></path><path d="M9 16h6"></path></svg>
                            <span>Paginas</span>
                            <b aria-hidden="true">⌄</b>
                        </a>
                        <a href="{{ $storefrontUrls->home($store) }}#catalogo">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h16"></path><path d="m14 4 6 6-9 9H5v-6l9-9Z"></path></svg>
                            <span>Blog</span>
                        </a>
                        @if($showAboutSection ?? false)
                            <a href="{{ $storefrontUrls->about($store) }}">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="8" r="3"></circle><circle cx="16" cy="7" r="3"></circle><path d="M3 20a5 5 0 0 1 10 0"></path><path d="M12 20a5 5 0 0 1 9 0"></path></svg>
                                <span>Nosotros</span>
                            </a>
                        @endif
                        <a href="{{ $storeWhatsappUrl ?? $storefrontUrls->home($store) }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.7.6 2.5a2 2 0 0 1-.5 2.1L8 9.5a16 16 0 0 0 6.5 6.5l1.2-1.2a2 2 0 0 1 2.1-.5c.8.3 1.6.5 2.5.6a2 2 0 0 1 1.7 2Z"></path></svg>
                            <span>Contacto</span>
                        </a>
                    </nav>

                    <nav class="fashion-mobile-menu fashion-mobile-menu--secondary" aria-label="Cuenta movil">
                        <a href="{{ $canManageStore ? route('dashboard') : $storefrontUrls->home($store) }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <span>Mi cuenta</span>
                        </a>
                        <a href="{{ $storefrontUrls->products($store) }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 5.6a5.2 5.2 0 0 0-7.4 0L12 7l-1.4-1.4a5.2 5.2 0 0 0-7.4 7.4L12 21.8l8.8-8.8a5.2 5.2 0 0 0 0-7.4Z"></path></svg>
                            <span>Favoritos</span>
                        </a>
                        <a href="{{ route('cart.index', ['store' => $store->slug]) }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.5 8.5h11l-.8 11h-9.4l-.8-11Z"></path><path d="M9 8.5a3 3 0 0 1 6 0"></path></svg>
                            <span>Carrito</span>
                            @if($cartCount > 0)
                                <em>{{ $cartCount }}</em>
                            @endif
                        </a>
                    </nav>

                    <a class="fashion-mobile-language" href="{{ $storefrontUrls->home($store) }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M3 12h18"></path><path d="M12 3a14 14 0 0 1 0 18"></path><path d="M12 3a14 14 0 0 0 0 18"></path></svg>
                        <span>Idioma</span>
                        <strong>ES ›</strong>
                    </a>

                    <div class="fashion-mobile-socials" aria-label="Redes sociales">
                        @if($instagramUrl)
                            <a href="{{ $instagramUrl }}" aria-label="Instagram"><span>◎</span></a>
                        @endif
                        @if($facebookUrl)
                            <a href="{{ $facebookUrl }}" aria-label="Facebook"><span>f</span></a>
                        @endif
                        @if($tiktokUrl)
                            <a href="{{ $tiktokUrl }}" aria-label="TikTok"><span>♪</span></a>
                        @endif
                        <a href="{{ $storeWhatsappUrl ?? $storefrontUrls->home($store) }}" aria-label="Contacto"><span>p</span></a>
                    </div>
                </div>
            </div>

            <button type="button" class="nav-backdrop" aria-label="Cerrar menu"></button>
        </div>

        <label class="fashion-cart-backdrop" for="minimalShopCartToggle" aria-hidden="true"></label>

        <aside
            class="fashion-cart-drawer minimal-shop-cart-drawer{{ $cartCount < 1 ? ' is-empty' : '' }}"
            aria-label="Carrito"
            data-cart-drawer
            data-cart-subtotal="{{ $drawerSubtotal }}"
            data-cart-shipping="{{ $drawerShipping }}"
            data-cart-tax="{{ $drawerTax }}"
            data-store-url="{{ $storefrontUrls->home($store) }}"
        >
            <div class="minimal-shop-cart-head">
                <h2>Your Cart (<span data-cart-drawer-count>{{ $cartCount }}</span>)</h2>
                <label for="minimalShopCartToggle" aria-label="Cerrar carrito">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
                </label>
            </div>

            <div class="minimal-shop-cart-free">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h11v10H3z"></path><path d="M14 10h4l3 3v4h-7z"></path><circle cx="7" cy="18" r="2"></circle><circle cx="18" cy="18" r="2"></circle></svg>
                <div>
                    <p>You're $10.01 away from free shipping!</p>
                    <b style="--cart-progress: {{ $drawerProgress }}%"></b>
                </div>
                <small>$100</small>
            </div>

            <div class="minimal-shop-cart-items" data-cart-drawer-items>
                @forelse($drawerCart as $cartKey => $item)
                    @php
                        $drawerItemImage = trim((string) ($item['image'] ?? ''));
                        $drawerItemPrice = (float) ($item['price'] ?? 0);
                        $drawerItemQuantity = (int) ($item['quantity'] ?? 1);
                        $drawerVariant = trim(collect([$item['color'] ?? null, $item['size'] ?? null])->filter()->implode(' / '));
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
                            <strong>{{ $item['name'] ?? 'Producto' }}</strong>
                            <small>{{ $drawerVariant !== '' ? $drawerVariant : 'Sin variante' }}</small>
                            <b data-cart-item-total>${{ number_format($drawerItemPrice * $drawerItemQuantity, 2) }}</b>
                        </div>
                        <div class="minimal-shop-cart-controls">
                            <button type="button" data-cart-drawer-minus aria-label="Restar">&minus;</button>
                            <span data-cart-drawer-quantity>{{ $drawerItemQuantity }}</span>
                            <button type="button" data-cart-drawer-plus aria-label="Sumar">+</button>
                            <button type="button" data-cart-drawer-remove aria-label="Eliminar">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16"></path><path d="M10 11v6M14 11v6"></path><path d="M6 7l1 14h10l1-14"></path><path d="M9 7V4h6v3"></path></svg>
                            </button>
                        </div>
                    </article>
                @empty
                    <div class="minimal-shop-cart-empty" data-cart-drawer-empty>
                        <strong>Tu carrito esta vacio</strong>
                        <a href="{{ $storefrontUrls->home($store) }}">Volver a la tienda</a>
                    </div>
                @endforelse
            </div>

            <details class="fashion-cart-discount">
                <summary>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.2 12.4 12.4 20.2a2.2 2.2 0 0 1-3.1 0l-5.5-5.5a2.2 2.2 0 0 1 0-3.1l7.8-7.8H20v8.6Z"></path><circle cx="16.5" cy="7.5" r="1.4"></circle></svg>
                    <span>Add a discount code</span>
                </summary>
            </details>

            <div class="minimal-shop-cart-summary">
                <p><span>Subtotal</span><strong data-cart-drawer-subtotal>${{ number_format($drawerSubtotal, 2) }}</strong></p>
                <p><span>Shipping</span><strong data-cart-drawer-shipping>Free</strong></p>
                <p><span>Estimated Tax</span><strong data-cart-drawer-tax>${{ number_format($drawerTax, 2) }}</strong></p>
                <p class="minimal-shop-cart-total"><span>Total</span><small>USD</small><strong data-cart-drawer-total>${{ number_format($drawerTotal, 2) }}</strong></p>
            </div>

            <div class="minimal-shop-cart-actions">
                <a href="{{ route('cart.index', ['store' => $store->slug]) }}">View Cart</a>
                <a href="{{ route('cart.index', ['store' => $store->slug]) }}">Checkout</a>
            </div>

            <p class="minimal-shop-cart-secure">
                <span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"></path></svg>
                    Secure checkout
                </span>
                <span>30-day returns</span>
            </p>
        </aside>
    </header>
</div>

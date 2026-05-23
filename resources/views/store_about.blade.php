<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $page = \App\View\Models\StorefrontPageViewModel::from($store);
        $absoluteStorageUrl = fn (?string $path) => $page->storageUrl($path);
        $storageAssetUrl = fn (?string $path) => $path ? asset('storage/' . $path) : null;
        $isRestaurant = $store->isRestaurant();
        $isTechnologyStore = $store->isTechnologyStore();
        $isSupplementStore = $store->isSupplementStore();
        $isReservationStore = $store->isReservationStore();
        $cartCount = $page->cartCount;
        $instagramUrl = $page->instagramUrl;
        $facebookUrl = $page->facebookUrl;
        $tiktokUrl = $page->tiktokUrl;
        $canManageStore = $page->canManageStore;
        $businessLabel = $isRestaurant ? 'Restaurante' : ($isReservationStore ? 'Reservas' : 'Tienda');
        $cartLabel = $isRestaurant ? 'Pedido' : ($isReservationStore ? 'Reserva' : 'Carrito');
        $collectionLabelTitle = $isRestaurant ? 'Menu' : ($isReservationStore ? 'Servicios' : 'Catalogo');
        $itemsLabel = $isRestaurant ? 'platos' : ($isReservationStore ? 'servicios' : 'productos');
        $addLabel = $isRestaurant ? 'Agregar al pedido' : ($isReservationStore ? 'Agregar a la reserva' : 'Agregar al carrito');
        $showStorefrontSectionLinks = false;
        $productDescriptionFallback = $isRestaurant
            ? 'Plato recomendado del restaurante.'
            : ($isTechnologyStore ? 'Producto de tecnologia.' : ($isSupplementStore ? 'Suplemento de la tienda.' : ($isReservationStore ? 'Servicio disponible para reservar.' : 'Producto de la tienda.')));
        $storefrontVariant = $isTechnologyStore ? 'technology' : ($isRestaurant ? 'restaurant' : ($isSupplementStore ? 'supplements' : 'default'));
        $variantStylesheets = [
            'technology' => 'css/storefront-technology.css',
            'restaurant' => 'css/storefront-restaurant.css',
            'supplements' => 'css/storefront-supplements.css',
            'default' => 'css/storefront-default.css',
        ];
        $faviconImage = $storefrontUrls->favicon($store);
        $seoImage = $absoluteStorageUrl($store->cover_image) ?: $absoluteStorageUrl($store->logo_image);
        $metaUrl = $storefrontUrls->about($store);
        $fallbackDescription = 'Conoce la mision y vision de ' . $store->name . '.';
        $seo = \App\Support\SeoMeta::category($store, 'Nosotros', null, $metaUrl, $seoImage, $fallbackDescription, $faviconImage);
        $brandTheme = \App\Support\BrandTheme::from($store->brand_color);
        $responsiveProductColumns = in_array((int) $store->responsive_product_columns, [1, 2, 3], true) ? (int) $store->responsive_product_columns : 2;
        $technologyAboutImage = $storageAssetUrl($store->cover_image) ?: $storageAssetUrl($store->logo_image);
        $storeEmail = $store->user?->email;
    @endphp
    @include('storefront.partials.seo', ['seo' => $seo])
    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}">
    <link rel="stylesheet" href="{{ asset($variantStylesheets[$storefrontVariant]) }}">
</head>

<body
    class="storefront-page storefront-page--{{ $storefrontVariant }} {{ $storefrontVariant === 'technology' ? 'storefront-page--minimal-grid' : '' }}"
    data-csrf="{{ csrf_token() }}"
    data-adding-text="{{ $isRestaurant ? 'Agregando al pedido...' : ($isReservationStore ? 'Agregando a la reserva...' : 'Agregando...') }}"
    data-feedback-added="{{ $isRestaurant ? 'Plato agregado al pedido' : ($isReservationStore ? 'Servicio agregado a la reserva' : 'Producto agregado al carrito') }}"
    data-feedback-error="{{ $isRestaurant ? 'No pudimos agregar el plato' : ($isReservationStore ? 'No pudimos agregar el servicio' : 'No pudimos agregar el producto') }}"
    style="{{ $store->storefrontCssVariables($brandTheme, $responsiveProductColumns) }}"
>
    @if($storefrontVariant === 'technology')
        @include('storefront.partials.header-minimal-grid')
    @else
        @include('storefront.partials.header')
    @endif

    <main class="{{ $storefrontVariant === 'technology' ? 'shell minimal-about-page' : 'shell' }}">
        @if($storefrontVariant === 'technology')
            <nav class="minimal-about-breadcrumb" aria-label="Ruta de navegacion">
                <a href="{{ $storefrontUrls->home($store) }}">Inicio</a>
                <span aria-hidden="true">&gt;</span>
                <strong>Nosotros</strong>
            </nav>

            <section class="minimal-about-layout" aria-label="Nosotros">
                <div class="minimal-about-copy">
                    <h1>Nosotros</h1>
                    <p>{{ $store->mission }}</p>
                    <p>{{ $store->vision }}</p>

                    <div class="minimal-about-contact-list">
                        @if($storeEmail)
                            <a href="mailto:{{ $storeEmail }}" class="minimal-about-contact-item">
                                <span class="minimal-about-contact-icon">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="6" width="16" height="12" rx="2"></rect><path d="m5 7 7 6 7-6"></path></svg>
                                </span>
                                <span>
                                    <strong>Email</strong>
                                    <small>{{ $storeEmail }}</small>
                                </span>
                            </a>
                        @endif

                        @if($store->whatsapp)
                            <a href="{{ $store->whatsappInfoUrl() ?: '#' }}" target="_blank" rel="noopener noreferrer" class="minimal-about-contact-item">
                                <span class="minimal-about-contact-icon">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4 5 6c-.6.6-.8 1.6-.4 2.4 2 4.8 6.2 9 11 11 .8.4 1.8.2 2.4-.4l2-2-4-4-2 2c-2.1-1-3.8-2.7-4.8-4.8l2-2L7 4Z"></path></svg>
                                </span>
                                <span>
                                    <strong>Telefono</strong>
                                    <small>{{ $store->whatsapp }}</small>
                                </span>
                            </a>
                        @endif

                        @if(trim((string) $store->location) !== '')
                            <div class="minimal-about-contact-item">
                                <span class="minimal-about-contact-icon">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-5.2 7-11a7 7 0 0 0-14 0c0 5.8 7 11 7 11Z"></path><circle cx="12" cy="10" r="2.4"></circle></svg>
                                </span>
                                <span>
                                    <strong>Ubicacion</strong>
                                    <small>{{ $store->location }}</small>
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="minimal-about-media">
                    @if($technologyAboutImage)
                        <img src="{{ $technologyAboutImage }}" alt="{{ $store->name }}" loading="eager" decoding="async">
                    @else
                        <div class="minimal-about-media-fallback">
                            @if($store->logo_image)
                                <img src="{{ asset('storage/' . $store->logo_image) }}" alt="{{ $store->name }}">
                            @endif
                            <strong>{{ $store->name }}</strong>
                            <span>{{ $store->shop_copy ?: 'Tecnologia para vivir mejor.' }}</span>
                        </div>
                    @endif
                </div>
            </section>
        @else
            <section class="category-page-hero category-page-hero--catalog store-about-page-hero">
                <div class="category-page-copy">
                    <span class="eyebrow">{{ $businessLabel }}</span>
                    <h1>Nosotros</h1>
                    <p>Conoce mejor la esencia de {{ $store->name }}.</p>
                </div>
            </section>

            @include('storefront.partials.about')
        @endif
    </main>

    @if($storefrontVariant === 'technology')
        @include('storefront.partials.footer-minimal-grid')
    @else
        @include('storefront.partials.footer')
    @endif

    <div class="cart-feedback" id="cartFeedback" aria-live="polite">{{ $isRestaurant ? 'Plato agregado al pedido' : ($isReservationStore ? 'Servicio agregado a la reserva' : 'Producto agregado al carrito') }}</div>

    <script src="{{ asset('js/storefront.js') }}?v={{ filemtime(public_path('js/storefront.js')) }}" defer></script>
</body>

</html>


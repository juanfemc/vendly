<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $page = \App\View\Models\StorefrontPageViewModel::from($store);
        $publicBaseUrl = $page->publicBaseUrl;
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
        $faviconImage = $storageAssetUrl($store->logo_image) ?: asset('images/vendly-logo.svg');
        $seoImage = $absoluteStorageUrl($store->cover_image) ?: $absoluteStorageUrl($store->logo_image);
        $metaUrl = $publicBaseUrl . '/' . $store->slug . '/nosotros';
        $fallbackDescription = 'Conoce la mision y vision de ' . $store->name . '.';
        $seo = \App\Support\SeoMeta::category($store, 'Nosotros', null, $metaUrl, $seoImage, $fallbackDescription, $faviconImage);
        $brandTheme = \App\Support\BrandTheme::from($store->brand_color);
        $responsiveProductColumns = in_array((int) $store->responsive_product_columns, [1, 2, 3], true) ? (int) $store->responsive_product_columns : 2;
    @endphp
    @include('storefront.partials.seo', ['seo' => $seo])
    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}">
    <link rel="stylesheet" href="{{ asset($variantStylesheets[$storefrontVariant]) }}">
</head>

<body
    class="storefront-page storefront-page--{{ $storefrontVariant }}"
    data-csrf="{{ csrf_token() }}"
    data-adding-text="{{ $isRestaurant ? 'Agregando al pedido...' : ($isReservationStore ? 'Agregando a la reserva...' : 'Agregando...') }}"
    data-feedback-added="{{ $isRestaurant ? 'Plato agregado al pedido' : ($isReservationStore ? 'Servicio agregado a la reserva' : 'Producto agregado al carrito') }}"
    data-feedback-error="{{ $isRestaurant ? 'No pudimos agregar el plato' : ($isReservationStore ? 'No pudimos agregar el servicio' : 'No pudimos agregar el producto') }}"
    style="{{ $store->storefrontCssVariables($brandTheme, $responsiveProductColumns) }}"
>
    @include('storefront.partials.header')

    <main class="shell">
        <section class="category-page-hero category-page-hero--catalog store-about-page-hero">
            <div class="category-page-copy">
                <span class="eyebrow">{{ $businessLabel }}</span>
                <h1>Nosotros</h1>
                <p>Conoce mejor la esencia de {{ $store->name }}.</p>
            </div>
        </section>

        @include('storefront.partials.about')
    </main>

    @include('storefront.partials.footer')

    <div class="cart-feedback" id="cartFeedback" aria-live="polite">{{ $isRestaurant ? 'Plato agregado al pedido' : ($isReservationStore ? 'Servicio agregado a la reserva' : 'Producto agregado al carrito') }}</div>

    <script src="{{ asset('js/storefront.js') }}?v={{ filemtime(public_path('js/storefront.js')) }}" defer></script>
</body>

</html>

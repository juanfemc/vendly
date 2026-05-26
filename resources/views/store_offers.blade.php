<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $page = \App\View\Models\StorefrontPageViewModel::from($store);
        $absoluteStorageUrl = fn (?string $path) => $page->storageUrl($path);
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
        $itemsLabel = $isRestaurant ? 'platos' : ($isReservationStore ? 'servicios' : 'productos');
        $collectionLabelTitle = 'Ofertas';
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
        $cardClass = $isTechnologyStore ? 'tech-product-card' : ($isRestaurant ? 'restaurant-product-card' : ($isSupplementStore ? 'supplements-product-card' : ''));
        $faviconImage = $storefrontUrls->favicon($store);
        $seoImage = $absoluteStorageUrl($store->cover_image) ?: $absoluteStorageUrl($store->logo_image);
        $metaUrl = $storefrontUrls->offers($store);
        $fallbackDescription = 'Explora las ofertas activas de ' . $store->name . ' y compra por WhatsApp.';
        $seo = \App\Support\SeoMeta::category($store, 'Ofertas', null, $metaUrl, $seoImage, $fallbackDescription, $faviconImage);
        $brandTheme = \App\Support\BrandTheme::from($store->brand_color);
        $responsiveProductColumns = in_array((int) $store->responsive_product_columns, [1, 2, 3], true) ? (int) $store->responsive_product_columns : 2;
    @endphp
    @include('storefront.partials.seo', ['seo' => $seo])
    @include('storefront.partials.meta-pixel', ['store' => $store])
    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}?v={{ filemtime(public_path('css/storefront.css')) }}">
    <link rel="stylesheet" href="{{ asset($variantStylesheets[$storefrontVariant]) }}?v={{ filemtime(public_path($variantStylesheets[$storefrontVariant])) }}">
</head>

<body
    class="storefront-page storefront-page--{{ $storefrontVariant }} {{ $storefrontVariant === 'technology' ? 'storefront-page--minimal-grid' : '' }}"
    data-csrf="{{ csrf_token() }}"
    data-adding-text="{{ $isRestaurant ? 'Agregando al pedido...' : ($isReservationStore ? 'Agregando a la reserva...' : 'Agregando...') }}"
    data-feedback-added="{{ $isRestaurant ? 'Plato agregado al pedido' : ($isReservationStore ? 'Servicio agregado a la reserva' : 'Producto agregado al carrito') }}"
    data-feedback-error="{{ $isRestaurant ? 'No pudimos agregar el plato' : ($isReservationStore ? 'No pudimos agregar el servicio' : 'No pudimos agregar el producto') }}"
    style="{{ $store->storefrontCssVariables($brandTheme, $responsiveProductColumns) }}"
>
    @include('storefront.partials.meta-pixel-noscript', ['store' => $store])

    @if($storefrontVariant === 'technology')
        @include('storefront.partials.header-minimal-grid')
    @else
        @include('storefront.partials.header')
    @endif

    <main class="shell">
        <section class="category-page-hero category-page-hero--catalog">
            <div class="category-page-copy">
                <span class="eyebrow">{{ $businessLabel }}</span>
                <h1>Ofertas</h1>
                <p>{{ $fallbackDescription }}</p>
            </div>
        </section>

        <section class="catalog-section" id="catalogo">
            <div class="catalog-head">
                <h2>{{ $products->total() }} {{ $itemsLabel }} en oferta</h2>
            </div>

            @include('storefront.partials.product-search', [
                'productSearchId' => 'offers',
                'productSearchAction' => $storefrontUrls->offers($store),
            ])

            @if($products->isNotEmpty())
                <div class="products-grid">
                    @foreach($products as $product)
                        @include('storefront.partials.product-card', ['cardClass' => $cardClass])
                    @endforeach
                </div>

                @if($products->hasPages())
                    <div class="store-pagination">
                        {{ $products->fragment('catalogo')->links('storefront.partials.pagination') }}
                    </div>
                @endif
            @else
                <div class="empty-state">
                    No encontramos {{ $itemsLabel }} en oferta para esa busqueda.
                </div>
            @endif
        </section>
    </main>

    @if($storefrontVariant === 'technology')
        @include('storefront.partials.footer-minimal-grid')
    @else
        @include('storefront.partials.footer')
    @endif

    <div class="cart-feedback" id="cartFeedback" aria-live="polite">{{ $isRestaurant ? 'Plato agregado al pedido' : ($isReservationStore ? 'Servicio agregado a la reserva' : 'Producto agregado al carrito') }}</div>

    <script src="{{ asset('js/storefront.js') }}?v={{ filemtime(public_path('js/storefront.js')) }}" defer></script>
    @if($storefrontVariant === 'technology')
        <script src="{{ asset('js/minimal-shop.js') }}?v={{ filemtime(public_path('js/minimal-shop.js')) }}" defer></script>
    @endif
</body>

</html>

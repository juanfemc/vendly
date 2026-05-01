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
        $catalogProducts = $products;
        $allProducts = $allProducts ?? collect($products->items());
        $heroProduct = $allProducts->firstWhere('image', '!=', null) ?? $allProducts->first();
        $heroImage = $storageAssetUrl($store->cover_image) ?: $storageAssetUrl($heroProduct?->image);
        $heroMetaImage = $absoluteStorageUrl($store->cover_image) ?: $absoluteStorageUrl($heroProduct?->image);
        $logoImage = $absoluteStorageUrl($store->logo_image);
        $faviconImage = $storageAssetUrl($store->logo_image) ?: asset('images/vendly-logo.svg');
        $seoImage = $heroMetaImage ?: $logoImage;
        $cartCount = $page->cartCount;
        $instagramUrl = $page->instagramUrl;
        $facebookUrl = $page->facebookUrl;
        $tiktokUrl = $page->tiktokUrl;
        $canManageStore = $page->canManageStore;
        $isTechnologyStore = $store->isTechnologyStore();
        $isSupplementStore = $store->isSupplementStore();
        $businessLabel = $isRestaurant ? 'Restaurante' : 'Tienda';
        $cartLabel = $isRestaurant ? 'Pedido' : 'Carrito';
        $collectionLabel = $isRestaurant ? 'menu' : 'catalogo';
        $collectionLabelTitle = $isRestaurant ? 'Menu' : 'Catalogo';
        $itemsLabel = $isRestaurant ? 'platos' : 'productos';
        $productsTotal = method_exists($catalogProducts, 'total') ? $catalogProducts->total() : $allProducts->count();
        $buyNowLabel = $isRestaurant ? 'Pedir ahora' : 'Comprar ahora';
        $addLabel = $isRestaurant ? 'Agregar al pedido' : 'Agregar al carrito';
        $heroEyebrow = $isRestaurant
            ? 'Menu recomendado'
            : ($isTechnologyStore ? 'Lo ultimo en tecnologia' : ($isSupplementStore ? 'Bienestar y rendimiento' : 'Nueva coleccion'));
        $defaultHeroCopy = $isRestaurant
            ? 'Explora nuestro menu y haz tu pedido rapido desde una experiencia pensada para cerrar pedidos por WhatsApp.'
            : ($isTechnologyStore
                ? 'Descubre tecnologia seleccionada para tu dia a dia, con una vitrina pensada para resolver compras rapido por WhatsApp.'
                : ($isSupplementStore
                    ? 'Encuentra suplementos para energia, fuerza y bienestar en una experiencia pensada para cerrar pedidos por WhatsApp.'
                    : 'Descubre nuestros productos y compra rapido desde una experiencia pensada para cerrar pedidos por WhatsApp.'));
        $heroShortCopy = trim((string) $store->shop_copy) !== '' ? trim((string) $store->shop_copy) : $defaultHeroCopy;
        $defaultShopCopy = $isRestaurant
            ? 'Explora el menu actual de ' . ($store->name ?? 'el restaurante') . ' y agrega tus favoritos al pedido para enviarlo por WhatsApp.'
            : ($isTechnologyStore
                ? 'Explora la seleccion actual de tecnologia de ' . ($store->name ?? 'la tienda') . ' y agrega tus favoritos al carrito para cerrar tu compra por WhatsApp.'
                : ($isSupplementStore
                    ? 'Explora la linea actual de suplementos de ' . ($store->name ?? 'la tienda') . ' y agrega tus favoritos al carrito para cerrar tu pedido por WhatsApp.'
                    : 'Explora la coleccion actual de ' . ($store->name ?? 'la tienda') . ' y agrega tus favoritos al carrito para finalizar tu pedido por WhatsApp.'));
        $productDescriptionFallback = $isRestaurant
            ? 'Plato recomendado del restaurante.'
            : ($isTechnologyStore ? 'Producto de tecnologia.' : ($isSupplementStore ? 'Suplemento de la tienda.' : 'Producto de la tienda.'));
        $storefrontVariant = $isTechnologyStore ? 'technology' : ($isRestaurant ? 'restaurant' : ($isSupplementStore ? 'supplements' : 'default'));
        $variantStylesheets = [
            'technology' => 'css/storefront-technology.css',
            'restaurant' => 'css/storefront-restaurant.css',
            'supplements' => 'css/storefront-supplements.css',
            'default' => 'css/storefront-default.css',
        ];
        $metaUrl = $publicBaseUrl . '/' . $store->slug;
        $seo = \App\Support\SeoMeta::storeHome($store, $metaUrl, $seoImage, $defaultShopCopy, $faviconImage);
        $brandTheme = \App\Support\BrandTheme::from($store->brand_color);
        $responsiveProductColumns = in_array((int) $store->responsive_product_columns, [1, 2, 3], true) ? (int) $store->responsive_product_columns : 2;
        $showHeroProductsAction = (bool) ($store->show_hero_products_action ?? false);
        $showAboutSection = trim((string) $store->mission) !== '' && trim((string) $store->vision) !== '';
    @endphp
    @include('storefront.partials.seo', ['seo' => $seo])
    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}">
    <link rel="stylesheet" href="{{ asset($variantStylesheets[$storefrontVariant]) }}">
</head>

<body
    class="storefront-page storefront-page--{{ $storefrontVariant }}"
    data-csrf="{{ csrf_token() }}"
    data-adding-text="{{ $isRestaurant ? 'Agregando al pedido...' : 'Agregando...' }}"
    data-feedback-added="{{ $isRestaurant ? 'Plato agregado al pedido' : 'Producto agregado al carrito' }}"
    data-feedback-error="{{ $isRestaurant ? 'No pudimos agregar el plato' : 'No pudimos agregar el producto' }}"
    style="--brand-color: {{ $brandTheme->color }}; --brand-contrast: {{ $brandTheme->contrast }}; --responsive-product-columns: {{ $responsiveProductColumns }};"
>
    @include('storefront.partials.header')

    <main class="shell">
        @include('storefront.variants.' . $storefrontVariant)
    </main>

    <div class="cart-feedback" id="cartFeedback" aria-live="polite">{{ $isRestaurant ? 'Plato agregado al pedido' : 'Producto agregado al carrito' }}</div>

    <script src="{{ asset('js/storefront.js') }}?v={{ filemtime(public_path('js/storefront.js')) }}" defer></script>
</body>

</html>

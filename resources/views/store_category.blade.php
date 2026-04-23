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
        $cartCount = $page->cartCount;
        $instagramUrl = $page->instagramUrl;
        $facebookUrl = $page->facebookUrl;
        $tiktokUrl = $page->tiktokUrl;
        $canManageStore = $page->canManageStore;
        $businessLabel = $isRestaurant ? 'Restaurante' : 'Tienda';
        $cartLabel = $isRestaurant ? 'Pedido' : 'Carrito';
        $collectionLabelTitle = $isRestaurant ? 'Menu' : 'Catalogo';
        $itemsLabel = $isRestaurant ? 'platos' : 'productos';
        $addLabel = $isRestaurant ? 'Agregar al pedido' : 'Agregar al carrito';
        $showStorefrontSectionLinks = false;
        $productDescriptionFallback = $isRestaurant
            ? 'Plato recomendado del restaurante.'
            : ($isTechnologyStore ? 'Producto destacado de tecnologia.' : ($isSupplementStore ? 'Suplemento destacado de la tienda.' : 'Producto destacado de la tienda.'));
        $storefrontVariant = $isTechnologyStore ? 'technology' : ($isRestaurant ? 'restaurant' : ($isSupplementStore ? 'supplements' : 'default'));
        $variantStylesheets = [
            'technology' => 'css/storefront-technology.css',
            'restaurant' => 'css/storefront-restaurant.css',
            'supplements' => 'css/storefront-supplements.css',
            'default' => 'css/storefront-default.css',
        ];
        $faviconImage = $storageAssetUrl($store->logo_image) ?: asset('images/vendly-logo.svg');
        $seoImage = $absoluteStorageUrl($category->image) ?: $absoluteStorageUrl($store->cover_image) ?: $absoluteStorageUrl($store->logo_image);
        $metaUrl = $publicBaseUrl . '/' . $store->slug . '/categorias/' . $category->slug;
        $fallbackDescription = 'Explora ' . $category->name . ' de ' . $store->name . ' y compra por WhatsApp.';
        $seo = \App\Support\SeoMeta::category($store, $category->name, $category->description, $metaUrl, $seoImage, $fallbackDescription, $faviconImage);
        $brandTheme = \App\Support\BrandTheme::from($store->brand_color);
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
    style="--brand-color: {{ $brandTheme->color }}; --brand-contrast: {{ $brandTheme->contrast }};"
>
    @include('storefront.partials.header')

    <main class="shell">
        <section class="page-head">
            <div class="breadcrumb">
                <a href="{{ route('store.show', $store->slug) }}">{{ $store->name }}</a> / {{ $category->name }}
            </div>
        </section>

        <section class="category-page-hero">
            <div class="category-page-copy">
                <span class="eyebrow">{{ $businessLabel }}</span>
                <h1>{{ $category->name }}</h1>
                <p>{{ $category->description ?: $fallbackDescription }}</p>
            </div>

            @if($category->image)
                <div class="category-page-media">
                    <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}" loading="eager" fetchpriority="high" decoding="async">
                </div>
            @endif
        </section>

        <section class="catalog-section" id="catalogo">
            <div class="catalog-head">
                <h2>{{ $products->total() }} {{ $itemsLabel }}</h2>
                <p>{{ $category->description ?: $fallbackDescription }}</p>
            </div>

            @if($products->isNotEmpty())
                <div class="products-grid">
                    @foreach($products as $product)
                        @include('storefront.partials.product-card')
                    @endforeach
                </div>

                @if($products->hasPages())
                    <div class="store-pagination">
                        {{ $products->fragment('catalogo')->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state">Aun no hay {{ $itemsLabel }} en esta categoria.</div>
            @endif
        </section>

        @include('storefront.partials.footer')
    </main>

    <div class="cart-feedback" id="cartFeedback" aria-live="polite">{{ $isRestaurant ? 'Plato agregado al pedido' : 'Producto agregado al carrito' }}</div>

    <script src="{{ asset('js/storefront.js') }}?v={{ filemtime(public_path('js/storefront.js')) }}" defer></script>
</body>

</html>

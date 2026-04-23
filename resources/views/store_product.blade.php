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
        $logoImage = $absoluteStorageUrl($store->logo_image);
        $faviconImage = $storageAssetUrl($store->logo_image) ?: asset('images/vendly-logo.svg');
        $productImage = $absoluteStorageUrl($product->image);
        $seoImage = $productImage ?: $logoImage;
        $cartCount = $page->cartCount;
        $instagramUrl = $page->instagramUrl;
        $facebookUrl = $page->facebookUrl;
        $tiktokUrl = $page->tiktokUrl;
        $canManageStore = $page->canManageStore;
        $cartLabel = $isRestaurant ? 'Pedido' : 'Carrito';
        $collectionLabelTitle = $isRestaurant ? 'Menu' : 'Catalogo';
        $showStorefrontSectionLinks = false;
        $storefrontVariant = $isTechnologyStore ? 'technology' : ($isRestaurant ? 'restaurant' : ($isSupplementStore ? 'supplements' : 'default'));
        $variantStylesheets = [
            'technology' => 'css/storefront-technology.css',
            'restaurant' => 'css/storefront-restaurant.css',
            'supplements' => 'css/storefront-supplements.css',
            'default' => 'css/storefront-default.css',
        ];
        $previewTitle = $isSupplementStore ? 'Vista previa del suplemento' : ($isTechnologyStore ? 'Vista previa del producto' : 'Vista previa del producto');
        $previewCopy = $isSupplementStore
            ? 'Revisa el detalle del suplemento, ajusta la cantidad y decide si quieres agregarlo al carrito o ir directo al flujo de compra por WhatsApp.'
            : ($isTechnologyStore
                ? 'Explora el producto, ajusta la cantidad y decide si quieres agregarlo al carrito o pasar al flujo de compra por WhatsApp.'
                : 'Explora el producto, ajusta la cantidad y decide si quieres agregarlo al carrito o pasar al flujo de compra por WhatsApp.');
        $metaUrl = $publicBaseUrl . '/' . $store->slug . '/productos/' . $product->publicRouteKey();
        $seo = \App\Support\SeoMeta::product($store, $product, $metaUrl, $seoImage, $previewCopy, $faviconImage);
        $brandTheme = \App\Support\BrandTheme::from($store->brand_color);
    @endphp
    @include('storefront.partials.seo', ['seo' => $seo])
    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}">
    <link rel="stylesheet" href="{{ asset($variantStylesheets[$storefrontVariant]) }}">
    <link rel="stylesheet" href="{{ asset('css/store-product.css') }}">
</head>

<body
    class="storefront-page storefront-page--{{ $storefrontVariant }}"
    data-csrf="{{ csrf_token() }}"
    data-adding-text="Agregando..."
    data-feedback-added="Producto agregado al carrito"
    data-feedback-error="No pudimos agregar el producto"
    style="--brand-color: {{ $brandTheme->color }}; --brand-contrast: {{ $brandTheme->contrast }};"
>
    @include('storefront.partials.header')

    <main class="shell product-shell">
        <section class="product-breadcrumb">
            <a href="{{ route('store.show', $store->slug) }}">{{ $store->name }}</a>
            <span>/</span>
            <span>{{ $product->name }}</span>
        </section>

        <section class="product-detail">
            <div class="product-detail-media">
                @if($product->image)
                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" loading="eager" fetchpriority="high" decoding="async">
                @else
                    <div class="product-detail-fallback">{{ $product->name }}</div>
                @endif
            </div>

            <div class="product-detail-panel">
                @if($product->category)
                    <span class="product-detail-tag">{{ $product->category }}</span>
                @endif

                <div class="product-detail-head">
                    <span class="product-detail-label">{{ $previewTitle }}</span>
                    <h1>{{ $product->name }}</h1>
                </div>

                <div class="product-detail-price">${{ number_format($product->price, 0, ',', '.') }}</div>

                @if($product->material)
                    <div class="product-detail-description">
                        <h2>Material</h2>
                        <p>{{ $product->material }}</p>
                    </div>
                @endif

                <div class="product-detail-description">
                    <h2>Descripcion</h2>
                    <p>{{ $product->description ?: 'Este producto aun no tiene una descripcion amplia configurada, pero ya esta listo para venderse desde la tienda.' }}</p>
                </div>

                @if($product->features)
                    <div class="product-detail-description product-detail-features">
                        <h2>Caracteristicas</h2>
                        <div class="product-rich-content">{!! $product->features !!}</div>
                    </div>
                @endif

                <form action="{{ route('cart.add', $product->id) }}" method="POST" class="product-detail-form add-to-cart-form">
                    @csrf
                    @if($product->hasSizes() || $product->hasColors())
                        <div class="product-options product-options--detail">
                            @if($product->hasSizes())
                                <label>
                                    <span>Talla</span>
                                    <select name="size" data-role="selected-size" required>
                                        <option value="">Selecciona talla</option>
                                        @foreach($product->sizes as $size)
                                            <option value="{{ $size }}">{{ $size }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @endif

                            @if($product->hasColors())
                                <label>
                                    <span>Color</span>
                                    <select name="color" data-role="selected-color" required>
                                        <option value="">Selecciona color</option>
                                        @foreach($product->colors as $color)
                                            <option value="{{ $color }}">{{ $color }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @endif
                        </div>
                    @endif

                    <div class="product-quantity-block">
                        <label for="quantity">Cantidad</label>
                        <input id="quantity" type="number" name="quantity" min="1" max="99" value="{{ old('quantity', 1) }}" class="product-quantity-input">
                    </div>

                    <button type="submit" class="product-detail-primary">Agregar al carrito</button>
                </form>

                <form action="{{ route('cart.buy_now', $product->id) }}" method="POST" class="product-detail-form" data-role="buy-now-form">
                    @csrf
                    <input type="hidden" name="quantity" value="{{ old('quantity', 1) }}" data-role="buy-now-quantity">
                    <input type="hidden" name="size" value="" data-role="buy-now-size">
                    <input type="hidden" name="color" value="" data-role="buy-now-color">
                    <button type="submit" class="product-detail-secondary">Comprar por WhatsApp</button>
                </form>
            </div>
        </section>

        @if($relatedProducts->isNotEmpty())
            <section class="product-related">
                <div class="catalog-head">
                    <h2>Tambien te puede interesar</h2>
                    <p>Otros productos disponibles en {{ $store->name }}.</p>
                </div>

                <div class="products-grid">
                    @foreach($relatedProducts as $relatedProduct)
                        <article class="product-card">
                            <div class="product-image">
                                @if($relatedProduct->image)
                                    <img src="{{ asset('storage/' . $relatedProduct->image) }}" alt="{{ $relatedProduct->name }}" loading="lazy" decoding="async">
                                @endif
                            </div>

                            @if($relatedProduct->category)
                                <span class="product-tag">{{ $relatedProduct->category }}</span>
                            @endif

                            <h3>{{ $relatedProduct->name }}</h3>
                            <p>{{ $relatedProduct->description ?: 'Disponible para compra inmediata desde la tienda.' }}</p>

                            <div class="price-row">
                                <span class="price">${{ number_format($relatedProduct->price, 0, ',', '.') }}</span>
                            </div>

                            <a href="{{ route('store.product.show', ['slug' => $store->slug, 'product' => $relatedProduct->publicRouteKey()]) }}" class="product-preview-link">
                                Comprar ahora
                            </a>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </main>

    @include('storefront.partials.footer')

    <div class="cart-feedback" id="cartFeedback" aria-live="polite">Producto agregado al carrito</div>

    <script src="{{ asset('js/storefront.js') }}?v={{ filemtime(public_path('js/storefront.js')) }}" defer></script>
    <script>
        (() => {
            const quantityInput = document.getElementById('quantity');
            const buyNowQuantity = document.querySelector('[data-role="buy-now-quantity"]');
            const selectedSize = document.querySelector('[data-role="selected-size"]');
            const selectedColor = document.querySelector('[data-role="selected-color"]');
            const buyNowSize = document.querySelector('[data-role="buy-now-size"]');
            const buyNowColor = document.querySelector('[data-role="buy-now-color"]');
            const buyNowForm = document.querySelector('[data-role="buy-now-form"]');

            if (!quantityInput || !buyNowQuantity) {
                return;
            }

            const syncBuyNowFields = () => {
                buyNowQuantity.value = quantityInput.value || 1;

                if (selectedSize && buyNowSize) {
                    buyNowSize.value = selectedSize.value;
                }

                if (selectedColor && buyNowColor) {
                    buyNowColor.value = selectedColor.value;
                }
            };

            quantityInput.addEventListener('input', syncBuyNowFields);
            selectedSize?.addEventListener('change', syncBuyNowFields);
            selectedColor?.addEventListener('change', syncBuyNowFields);
            buyNowForm?.addEventListener('submit', (event) => {
                syncBuyNowFields();

                if ((selectedSize && !selectedSize.value) || (selectedColor && !selectedColor.value)) {
                    event.preventDefault();
                    selectedSize?.reportValidity();
                    selectedColor?.reportValidity();
                }
            });
            syncBuyNowFields();
        })();
    </script>
</body>

</html>

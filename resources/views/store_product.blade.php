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
        $isFashionStore = $store->isFashionStore();
        $isSupplementStore = $store->isSupplementStore();
        $isReservationStore = $store->isReservationStore();
        $logoImage = $absoluteStorageUrl($store->logo_image);
        $faviconImage = $storefrontUrls->favicon($store);
        $productImage = $absoluteStorageUrl($product->image);
        $productGallery = collect([$product->image])
            ->merge($store->allowsProductGallery() ? ($product->images ?? []) : [])
            ->filter()
            ->unique()
            ->values();
        $seoImage = $productImage ?: $logoImage;
        $cartCount = $page->cartCount;
        $instagramUrl = $page->instagramUrl;
        $facebookUrl = $page->facebookUrl;
        $tiktokUrl = $page->tiktokUrl;
        $canManageStore = $page->canManageStore;
        $cartLabel = $isRestaurant ? 'Pedido' : ($isReservationStore ? 'Reserva' : 'Carrito');
        $collectionLabelTitle = $isRestaurant ? 'Carta' : ($isReservationStore ? 'Servicios' : 'Catalogo');
        $showStorefrontSectionLinks = false;
        $storefrontVariant = $isTechnologyStore ? 'technology' : ($isFashionStore ? 'fashion' : ($isRestaurant ? 'restaurant' : ($isSupplementStore ? 'supplements' : 'default')));
        $variantStylesheets = [
            'technology' => 'css/storefront-technology.css',
            'fashion' => 'css/storefront-fashion.css',
            'restaurant' => 'css/storefront-restaurant.css',
            'supplements' => 'css/storefront-supplements.css',
            'default' => 'css/storefront-default.css',
        ];
        $previewTitle = $isRestaurant
            ? 'Detalle del plato'
            : ($isReservationStore ? 'Vista previa del servicio' : ($isSupplementStore ? 'Vista previa del suplemento' : ($isTechnologyStore ? 'Vista previa del producto' : 'Vista previa del producto')));
        $previewCopy = $isRestaurant
            ? 'Revisa el plato, ajusta la cantidad y agregalo al pedido para enviarlo por WhatsApp.'
            : ($isSupplementStore
                ? 'Revisa el detalle del suplemento, ajusta la cantidad y decide si quieres agregarlo al carrito o ir directo al flujo de compra por WhatsApp.'
                : ($isTechnologyStore
                    ? 'Explora el producto, ajusta la cantidad y decide si quieres agregarlo al carrito o pasar al flujo de compra por WhatsApp.'
                    : ($isReservationStore
                        ? 'Explora el servicio, ajusta la cantidad y solicita tu reserva por WhatsApp.'
                        : 'Explora el producto, ajusta la cantidad y decide si quieres agregarlo al carrito o pasar al flujo de compra por WhatsApp.')));
        $metaUrl = $storefrontUrls->product($store, $product);
        $seo = \App\Support\SeoMeta::product($store, $product, $metaUrl, $seoImage, $previewCopy, $faviconImage);
        $shareText = $product->name . ' | ' . $store->name;
        $shareUrlEncoded = rawurlencode($metaUrl);
        $shareTextEncoded = rawurlencode($shareText);
        $brandTheme = \App\Support\BrandTheme::from($store->brand_color);
        $responsiveProductColumns = in_array((int) $store->responsive_product_columns, [1, 2, 3], true) ? (int) $store->responsive_product_columns : 2;
        $isProductSoldOut = $product->isSoldOut();
        $quantityMax = $product->stock_quantity !== null && ! $isReservationStore ? max(1, min(99, (int) $product->stock_quantity)) : 99;
        $showsOfferPricing = $store->allowsOfferBadges() && $product->hasOfferPricing();
        $productBadges = $product->displayBadges($store);
        $reviewsEnabled = $store->allowsProductReviews();
        $reviews = $reviewsEnabled ? $product->approvedReviews()->latest()->take(6)->get() : collect();
        $reviewCount = $reviewsEnabled ? $product->reviewCount() : 0;
        $reviewAverage = $reviewsEnabled ? $product->reviewAverage() : null;
        $reviewLabel = $reviewCount > 0
            ? number_format($reviewAverage, 1) . ' (' . $reviewCount . ' ' . \Illuminate\Support\Str::plural('resena', $reviewCount) . ')'
            : null;
    @endphp
    @include('storefront.partials.seo', ['seo' => $seo])
    @include('storefront.partials.meta-pixel', ['store' => $store])
    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}?v={{ filemtime(public_path('css/storefront.css')) }}">
    <link rel="stylesheet" href="{{ asset($variantStylesheets[$storefrontVariant]) }}?v={{ filemtime(public_path($variantStylesheets[$storefrontVariant])) }}">
    <link rel="stylesheet" href="{{ asset('css/store-product.css') }}?v={{ filemtime(public_path('css/store-product.css')) }}">
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
        @include('storefront.partials.minimal-product-detail')
    @elseif($storefrontVariant === 'fashion')
        @include('storefront.partials.header-fashion')
        @include('storefront.partials.fashion-product-detail')
    @else
        @include('storefront.partials.header')

    <main class="shell product-shell">
        <section class="product-breadcrumb">
            <a href="{{ $storefrontUrls->home($store) }}">{{ $store->name }}</a>
            <span>/</span>
            <span>{{ $product->name }}</span>
        </section>

        <section class="product-detail">
            @if($productGallery->isNotEmpty())
                <div class="product-carousel" data-product-carousel>
                    <div class="product-carousel-stage">
                        @foreach($productGallery as $index => $galleryImage)
                            <img
                                src="{{ asset('storage/' . $galleryImage) }}"
                                alt="{{ $product->name }} imagen {{ $index + 1 }}"
                                class="product-carousel-image {{ $index === 0 ? 'is-active' : '' }}"
                                data-carousel-slide="{{ $index }}"
                                loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                                fetchpriority="{{ $index === 0 ? 'high' : 'auto' }}"
                                decoding="async"
                            >
                        @endforeach

                        @if($productGallery->count() > 1)
                            <button type="button" class="product-carousel-control product-carousel-control--prev" data-carousel-prev aria-label="Imagen anterior">
                                <span aria-hidden="true"></span>
                            </button>
                            <button type="button" class="product-carousel-control product-carousel-control--next" data-carousel-next aria-label="Imagen siguiente">
                                <span aria-hidden="true"></span>
                            </button>
                        @endif
                    </div>

                    @if($productGallery->count() > 1)
                        <div class="product-carousel-thumbs" aria-label="Imagenes del producto">
                            @foreach($productGallery as $index => $galleryImage)
                                <button
                                    type="button"
                                    class="product-carousel-thumb {{ $index === 0 ? 'is-active' : '' }}"
                                    data-carousel-thumb="{{ $index }}"
                                    aria-label="Ver imagen {{ $index + 1 }}"
                                    aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                                >
                                    <img src="{{ asset('storage/' . $galleryImage) }}" alt="" loading="lazy" decoding="async">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="product-detail-media">
                    <div class="product-detail-fallback">{{ $product->name }}</div>
                </div>
            @endif

            <div class="product-detail-panel">
                @if($product->category)
                    <span class="product-detail-tag">{{ $product->category }}</span>
                @endif
                @if($productBadges !== [])
                    <div class="product-detail-badges">
                        @foreach($productBadges as $badge)
                            <span class="product-detail-tag">{{ $badge }}</span>
                        @endforeach
                    </div>
                @endif

                <div class="product-detail-head">
                    <span class="product-detail-label">{{ $previewTitle }}</span>
                    <h1>{{ $product->name }}</h1>
                </div>

                <div class="product-detail-price">
                    @if($showsOfferPricing)
                        <span class="product-detail-price-before">${{ number_format((float) $product->offer_original_price, 0, ',', '.') }}</span>
                    @endif
                    <span>${{ number_format((float) $product->price, 0, ',', '.') }}</span>
                </div>

                @if($product->stockLabel())
                    <div class="product-stock-state {{ $isProductSoldOut ? 'is-sold-out' : '' }}">{{ $product->stockLabel() }}</div>
                @endif

                @if($product->material)
                    <div class="product-detail-description">
                        <h2>{{ $isRestaurant ? 'Detalle' : 'Material' }}</h2>
                        <p>{{ $product->material }}</p>
                    </div>
                @endif

                <div class="product-detail-description">
                    <h2>{{ $isRestaurant ? 'Sobre este plato' : 'Descripción' }}</h2>
                    <p>{{ $product->description ?: ($isRestaurant ? 'Este plato aun no tiene una descripcion amplia, pero ya esta disponible para pedir por WhatsApp.' : ($isReservationStore ? 'Este servicio aun no tiene una descripcion amplia configurada, pero ya esta listo para reservarse.' : 'Este producto aun no tiene una descripcion amplia configurada, pero ya esta listo para venderse.')) }}</p>
                </div>

                @if($product->features)
                    <div class="product-detail-description product-detail-features">
                        <h2>{{ $isRestaurant ? 'Ingredientes y detalles' : 'Características' }}</h2>
                        <div class="product-rich-content">{!! $product->features !!}</div>
                    </div>
                @endif

                @if($reviewsEnabled)
                    <section class="product-reviews" aria-label="Resenas del producto">
                        <div class="product-reviews-head">
                            <div>
                                <h2>Resenas</h2>
                                <p>Opiniones de clientes sobre este producto.</p>
                            </div>
                            @if($reviewCount > 0)
                                <div class="product-review-score" aria-label="{{ $reviewLabel }}">
                                    <span aria-hidden="true">&#9733;</span>
                                    <strong>{{ number_format($reviewAverage, 1) }}</strong>
                                    <small>{{ $reviewCount }} {{ \Illuminate\Support\Str::plural('resena', $reviewCount) }}</small>
                                </div>
                            @endif
                        </div>

                        @if(session('review_success'))
                            <div class="product-review-alert">{{ session('review_success') }}</div>
                        @endif

                        <div class="product-review-list">
                            @foreach($reviews as $review)
                                <article>
                                    <div>
                                        <strong>{{ $review->name }}</strong>
                                        <span>{{ number_format((float) $review->rating, 1) }} &#9733;</span>
                                    </div>
                                    @if($review->comment)
                                        <p>{{ $review->comment }}</p>
                                    @endif
                                </article>
                            @endforeach
                        </div>

                        <form action="{{ route('product.reviews.store', $product) }}" method="POST" class="product-review-form">
                            @csrf
                            <div class="product-review-form-head">
                                <h3>Comparte tu experiencia</h3>
                                <p>Tu resena sera revisada antes de publicarse.</p>
                            </div>
                            <label>
                                <span>Nombre</span>
                                <input type="text" name="name" value="{{ old('name') }}" maxlength="80" required>
                            </label>
                            <label>
                                <span>Calificacion</span>
                                <select name="rating" required>
                                    @for($rating = 5; $rating >= 1; $rating--)
                                        <option value="{{ $rating }}" @selected((int) old('rating', 5) === $rating)>{{ $rating }} estrellas</option>
                                    @endfor
                                </select>
                            </label>
                            <label class="product-review-form-comment">
                                <span>Comentario</span>
                                <textarea name="comment" rows="3" maxlength="1000">{{ old('comment') }}</textarea>
                            </label>
                            <button type="submit">Publicar resena</button>
                        </form>
                    </section>
                @endif

                <section class="product-share" aria-label="Compartir producto">
                    <div>
                        <h2>Compartir</h2>
                    </div>

                    <div class="product-share-actions">
                        <a
                            href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrlEncoded }}"
                            class="product-share-button product-share-button--facebook"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="Compartir en Facebook"
                        >
                            <img src="{{ asset('images/icons/icon-facebook.png') }}" alt="" aria-hidden="true">
                            <span class="product-share-label">Facebook</span>
                        </a>
                        <a
                            href="https://wa.me/?text={{ $shareTextEncoded }}%20{{ $shareUrlEncoded }}"
                            class="product-share-button product-share-button--whatsapp"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="Compartir por WhatsApp"
                        >
                            <img src="{{ asset('images/icons/icon-whatsapp.png') }}" alt="" aria-hidden="true">
                            <span class="product-share-label">WhatsApp</span>
                        </a>
                        <a
                            href="https://twitter.com/intent/tweet?url={{ $shareUrlEncoded }}&text={{ $shareTextEncoded }}"
                            class="product-share-button product-share-button--x"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="Compartir en X"
                        >
                            <img src="{{ asset('images/icons/icon-x.png') }}" alt="" aria-hidden="true">
                            <span class="product-share-label">X</span>
                        </a>
                        <button
                            type="button"
                            class="product-share-button product-share-button--copy"
                            data-copy-product-link="{{ $metaUrl }}"
                            aria-label="Copiar enlace del producto"
                        >
                            <img src="{{ asset('images/icons/icon-copiar-enlace.png') }}" alt="" aria-hidden="true">
                            <span class="product-share-label">Copiar enlace</span>
                        </button>
                    </div>
                </section>

                @if($isProductSoldOut)
                    <div class="product-unavailable-message">{{ $isRestaurant ? 'Este plato esta agotado por ahora.' : 'Este producto esta agotado por ahora.' }}</div>
                @else
                    <form action="{{ route('cart.add', $product->id) }}" method="POST" class="product-detail-form add-to-cart-form">
                        @csrf
                        @if($product->hasSizes() || $product->hasColors())
                            <div class="product-options product-options--detail">
                                @if($product->hasSizes())
                                    <label>
                                        <span>{{ $isRestaurant ? 'Porcion' : 'Talla' }}</span>
                                        <select name="size" data-role="selected-size" required>
                                            <option value="">{{ $isRestaurant ? 'Selecciona porcion' : 'Selecciona talla' }}</option>
                                            @foreach($product->sizes as $size)
                                                <option value="{{ $size }}">{{ $size }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                @endif

                                @if($product->hasColors())
                                    <label>
                                        <span>{{ $isRestaurant ? 'Opcion' : 'Color' }}</span>
                                        <select name="color" data-role="selected-color" required>
                                            <option value="">{{ $isRestaurant ? 'Selecciona opcion' : 'Selecciona color' }}</option>
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
                            <input id="quantity" type="number" name="quantity" min="1" max="{{ $quantityMax }}" value="{{ old('quantity', 1) }}" class="product-quantity-input">
                        </div>

                        <button type="submit" class="product-detail-secondary">{{ $isRestaurant ? 'Agregar al pedido' : ($isReservationStore ? 'Agregar a la reserva' : 'Agregar al carrito') }}</button>
                    </form>

                    <form action="{{ route('cart.buy_now', $product->id) }}" method="POST" class="product-detail-form" data-role="buy-now-form">
                        @csrf
                        <input type="hidden" name="quantity" value="{{ old('quantity', 1) }}" data-role="buy-now-quantity">
                        <input type="hidden" name="size" value="" data-role="buy-now-size">
                        <input type="hidden" name="color" value="" data-role="buy-now-color">
                        <button type="submit" class="product-detail-primary">
                            <span>{{ $isRestaurant ? 'Pedir ahora' : ($isReservationStore ? 'Reservar ahora' : 'Comprar ahora') }}</span>
                        </button>
                        @unless($isRestaurant || $isReservationStore)
                            <p class="product-payment-note">Podras elegir WhatsApp o Mercado Pago en el checkout.</p>
                        @endunless
                    </form>
                @endif
            </div>
        </section>

        @if($relatedProducts->isNotEmpty())
            <section class="product-related">
                <div class="catalog-head">
                    <h2>{{ $isRestaurant ? 'Tambien puedes pedir' : 'Tambien te puede interesar' }}</h2>
                </div>

                <div class="products-grid">
                    @foreach($relatedProducts as $relatedProduct)
                        <article class="product-card">
                            @php($relatedBadges = $relatedProduct->displayBadges($store))
                            <div class="product-image">
                                @if($relatedBadges !== [])
                                    <div class="product-badges">
                                        @foreach($relatedBadges as $badge)
                                            <span class="product-offer-badge">{{ $badge }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                @if($relatedProduct->image)
                                    <img src="{{ asset('storage/' . $relatedProduct->image) }}" alt="{{ $relatedProduct->name }}" loading="lazy" decoding="async">
                                @endif
                            </div>

                            <h3>{{ $relatedProduct->name }}</h3>

                            <div class="price-row">
                                @if($store->allowsOfferBadges() && $relatedProduct->hasOfferPricing())
                                    <span class="price-stack">
                                        <span class="price-before">${{ number_format((float) $relatedProduct->offer_original_price, 0, ',', '.') }}</span>
                                        <span class="price">${{ number_format((float) $relatedProduct->price, 0, ',', '.') }}</span>
                                    </span>
                                @else
                                    <span class="price">${{ number_format((float) $relatedProduct->price, 0, ',', '.') }}</span>
                                @endif
                            </div>

                            <a href="{{ $storefrontUrls->product($store, $relatedProduct) }}" class="product-preview-link">
                                Ver más
                            </a>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </main>

    @endif

    @if($storefrontVariant === 'technology')
        @include('storefront.partials.footer-minimal-grid')
    @elseif($storefrontVariant === 'fashion')
        @include('storefront.partials.footer-fashion')
    @else
        @include('storefront.partials.footer')
    @endif

    <div class="cart-feedback" id="cartFeedback" aria-live="polite">{{ $isReservationStore ? 'Servicio agregado a la reserva' : 'Producto agregado al carrito' }}</div>

    <script src="{{ asset('js/storefront.js') }}?v={{ filemtime(public_path('js/storefront.js')) }}" defer></script>
    @if($storefrontVariant === 'technology')
        <script src="{{ asset('js/minimal-shop.js') }}?v={{ filemtime(public_path('js/minimal-shop.js')) }}" defer></script>
    @endif
    <script>
        (() => {
            const carousel = document.querySelector('[data-product-carousel]');

            if (!carousel) {
                return;
            }

            const slides = [...carousel.querySelectorAll('[data-carousel-slide]')];
            const fallbacks = [...carousel.querySelectorAll('[data-carousel-fallback]')];
            const thumbs = [...carousel.querySelectorAll('[data-carousel-thumb]')];
            const prev = carousel.querySelector('[data-carousel-prev]');
            const next = carousel.querySelector('[data-carousel-next]');
            let current = 0;

            const showSlide = (index) => {
                if (slides.length === 0) {
                    return;
                }

                current = (index + slides.length) % slides.length;

                slides.forEach((slide, slideIndex) => {
                    slide.classList.toggle('is-active', slideIndex === current);
                });

                fallbacks.forEach((fallback) => {
                    fallback.classList.toggle('is-active', Number(fallback.dataset.carouselFallback) === current);
                });

                thumbs.forEach((thumb, thumbIndex) => {
                    const isActive = thumbIndex === current;
                    thumb.classList.toggle('is-active', isActive);
                    thumb.setAttribute('aria-current', isActive ? 'true' : 'false');
                });
            };

            prev?.addEventListener('click', () => showSlide(current - 1));
            next?.addEventListener('click', () => showSlide(current + 1));
            thumbs.forEach((thumb, index) => {
                thumb.addEventListener('click', () => showSlide(index));
            });
        })();

        (() => {
            const quantityInput = document.getElementById('quantity');
            const buyNowQuantity = document.querySelector('[data-role="buy-now-quantity"]');
            const addQuantity = document.querySelector('[data-role="add-quantity"]');
            const selectedSize = document.querySelector('[data-role="selected-size"]');
            const selectedColor = document.querySelector('[data-role="selected-color"]');
            const selectedColorRadio = () => document.querySelector('[data-role="selected-color-radio"]:checked');
            const buyNowSize = document.querySelector('[data-role="buy-now-size"]');
            const buyNowColor = document.querySelector('[data-role="buy-now-color"]');
            const addSize = document.querySelector('[data-role="add-size"]');
            const addColor = document.querySelector('[data-role="add-color"]');
            const buyNowForm = document.querySelector('[data-role="buy-now-form"]');
            const addForm = document.querySelector('[data-role="minimal-add-form"]');
            const quantityMinus = document.querySelector('[data-quantity-minus]');
            const quantityPlus = document.querySelector('[data-quantity-plus]');

            if (!quantityInput || !buyNowQuantity) {
                return;
            }

            const syncField = (field, value) => {
                if (field) {
                    field.value = value;
                }
            };

            const syncBuyNowFields = () => {
                const quantity = quantityInput.value || 1;
                const size = selectedSize?.value || '';
                const color = selectedColor?.value || selectedColorRadio()?.value || '';

                syncField(buyNowQuantity, quantity);
                syncField(addQuantity, quantity);
                syncField(buyNowSize, size);
                syncField(addSize, size);
                syncField(buyNowColor, color);
                syncField(addColor, color);
            };

            const requireOptions = (event) => {
                syncBuyNowFields();
                const invalidControl = (selectedSize && !selectedSize.value)
                    ? selectedSize
                    : ((selectedColor && !selectedColor.value) ? selectedColor : null);

                if (invalidControl) {
                    event.preventDefault();
                    invalidControl.reportValidity();
                    return false;
                }

                return true;
            };

            quantityInput.addEventListener('input', syncBuyNowFields);
            quantityMinus?.addEventListener('click', () => {
                const min = Number(quantityInput.min || 1);
                quantityInput.value = Math.max(min, Number(quantityInput.value || min) - 1);
                syncBuyNowFields();
            });
            quantityPlus?.addEventListener('click', () => {
                const max = Number(quantityInput.max || 99);
                quantityInput.value = Math.min(max, Number(quantityInput.value || 1) + 1);
                syncBuyNowFields();
            });
            selectedSize?.addEventListener('change', syncBuyNowFields);
            selectedColor?.addEventListener('change', syncBuyNowFields);
            document.querySelectorAll('[data-role="selected-color-radio"]').forEach((radio) => {
                radio.addEventListener('change', syncBuyNowFields);
            });
            addForm?.addEventListener('submit', requireOptions);
            buyNowForm?.addEventListener('submit', (event) => {
                if (!requireOptions(event)) {
                    return;
                }
            });
            syncBuyNowFields();
        })();

        (() => {
            const copyButton = document.querySelector('[data-copy-product-link]');
            const feedback = document.getElementById('cartFeedback');
            let feedbackTimer;

            if (!copyButton) {
                return;
            }

            const showFeedback = (message) => {
                if (!feedback) {
                    return;
                }

                feedback.textContent = message;
                feedback.classList.add('is-visible');

                window.clearTimeout(feedbackTimer);
                feedbackTimer = window.setTimeout(() => {
                    feedback.classList.remove('is-visible');
                }, 1800);
            };

            const fallbackCopy = (value) => {
                const input = document.createElement('textarea');
                input.value = value;
                input.setAttribute('readonly', '');
                input.style.position = 'fixed';
                input.style.opacity = '0';
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
            };

            copyButton.addEventListener('click', async () => {
                const link = copyButton.dataset.copyProductLink;

                if (!link) {
                    return;
                }

                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(link);
                    } else {
                        fallbackCopy(link);
                    }

                    showFeedback('Link del producto copiado');
                } catch (error) {
                    showFeedback('No pudimos copiar el link');
                }
            });
        })();
    </script>
</body>

</html>


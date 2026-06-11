@php
    $minimalProductCategory = trim((string) $product->category) !== '' ? $product->category : 'Otros';
    $minimalGallery = $productGallery->isNotEmpty() ? $productGallery : collect([null]);
    $minimalRelated = $relatedProducts->take(4);
    $minimalAllowsOnlinePayments = $store->allowsOnlinePayments();
    $minimalReviewsEnabled = $store->allowsProductReviews();
    $minimalReviews = $minimalReviewsEnabled
        ? $product->approvedReviews()->latest()->take(6)->get()
        : collect();
    $minimalReviewCount = $minimalReviewsEnabled ? $product->reviewCount() : 0;
    $minimalReviewAverage = $minimalReviewsEnabled ? $product->reviewAverage() : null;
    $minimalReviewLabel = $minimalReviewCount > 0
        ? number_format($minimalReviewAverage, 1) . ' (' . $minimalReviewCount . ' ' . \Illuminate\Support\Str::plural('resena', $minimalReviewCount) . ')'
        : null;
    $minimalInitials = strtoupper(substr($product->name, 0, 2));
    $minimalBadges = $product->displayBadges($store);
    $minimalSwatches = ['#111111', '#ffffff', '#33415f'];
    $minimalDescription = \App\Support\ProductText::plain($product->description) ?: 'Disfruta ' . $product->name . ' con una experiencia pensada para comprar facil, rapido y con confianza.';
    $minimalFeatureItems = collect(preg_split('/\R+/', \App\Support\ProductText::featureLines($product->features)) ?: [])
        ->map(fn ($feature) => trim($feature, " \t\n\r\0\x0B-*"))
        ->filter()
        ->take(6)
        ->values();

    if ($minimalFeatureItems->isEmpty()) {
        $minimalFeatureItems = collect([
            'Cancelacion activa de ruido',
            'Conectividad Bluetooth 5.3',
            'Microfono integrado para llamadas',
            'Diseno plegable y liviano',
        ]);
    }

    $minimalBenefits = [
        ['icon' => 'E', 'title' => 'Envio gratis', 'copy' => 'En pedidos seleccionados'],
        ['icon' => 'D', 'title' => 'Devoluciones faciles', 'copy' => 'Politica de devolucion disponible'],
        $minimalAllowsOnlinePayments
            ? ['icon' => 'P', 'title' => 'Pago seguro', 'copy' => 'Compra protegida']
            : ['icon' => 'W', 'title' => 'Compra por WhatsApp', 'copy' => 'Confirma tu pedido directo'],
        ['icon' => 'G', 'title' => 'Garantia', 'copy' => 'Calidad garantizada'],
    ];
@endphp

<main class="shell minimal-product-page">
    <section class="minimal-product-breadcrumb" aria-label="Ruta del producto">
        <a href="{{ $storefrontUrls->home($store) }}">Inicio</a>
        <span aria-hidden="true">&rsaquo;</span>
        <a href="{{ $storefrontUrls->products($store) }}">Tienda</a>
        @if($product->category)
            <span aria-hidden="true">&rsaquo;</span>
            <span>{{ $product->category }}</span>
        @endif
        <span aria-hidden="true">&rsaquo;</span>
        <strong>{{ $product->name }}</strong>
    </section>

    <section class="minimal-product-layout">
        <div class="minimal-product-gallery" data-product-carousel>
            <div class="minimal-product-stage">
                <span class="minimal-product-badge">{{ $minimalProductCategory }}</span>
                @if($minimalBadges !== [])
                    <div class="minimal-product-badges">
                        @foreach($minimalBadges as $badge)
                            <span class="minimal-product-badge">{{ $badge }}</span>
                        @endforeach
                    </div>
                @endif
                @foreach($minimalGallery as $index => $galleryImage)
                    @if($galleryImage)
                        <img
                            src="{{ asset('storage/' . $galleryImage) }}"
                            alt="{{ $product->name }} imagen {{ $index + 1 }}"
                            class="minimal-product-image {{ $index === 0 ? 'is-active' : '' }}"
                            data-carousel-slide="{{ $index }}"
                            loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                            fetchpriority="{{ $index === 0 ? 'high' : 'auto' }}"
                            decoding="async"
                        >
                        <div class="minimal-product-placeholder {{ $index === 0 ? 'is-active' : '' }}" data-carousel-fallback="{{ $index }}" hidden>{{ $minimalInitials }}</div>
                    @else
                        <div class="minimal-product-placeholder is-active" data-carousel-slide="{{ $index }}">{{ $minimalInitials }}</div>
                    @endif
                @endforeach

                @if($minimalGallery->count() > 1)
                    <button type="button" class="minimal-product-arrow minimal-product-arrow--prev" data-carousel-prev aria-label="Imagen anterior">&lsaquo;</button>
                    <button type="button" class="minimal-product-arrow minimal-product-arrow--next" data-carousel-next aria-label="Imagen siguiente">&rsaquo;</button>
                @endif
            </div>

            @if($minimalGallery->count() > 1)
                <div class="minimal-product-thumbs" aria-label="Imagenes del producto">
                    @foreach($minimalGallery as $index => $galleryImage)
                        <button
                            type="button"
                            class="minimal-product-thumb {{ $index === 0 ? 'is-active' : '' }}"
                            data-carousel-thumb="{{ $index }}"
                            aria-label="Ver imagen {{ $index + 1 }}"
                            aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                        >
                            @if($galleryImage)
                                <img src="{{ asset('storage/' . $galleryImage) }}" alt="" loading="lazy" decoding="async">
                            @else
                                        <span>{{ $minimalInitials }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif

            <section class="minimal-product-tabs">
                <nav aria-label="Informacion del producto">
                    <a class="is-active" href="#minimalProductDescription">Descripcion</a>
                    <a href="#minimalProductDescription">Especificaciones</a>
                    @if($minimalReviewsEnabled && $minimalReviewCount > 0)
                        <a href="#minimalProductReviews">Resenas ({{ $minimalReviewCount }})</a>
                    @endif
                    <a href="#minimalProductDescription">Envios y devoluciones</a>
                </nav>
                <div id="minimalProductDescription" class="minimal-product-copy">
                    <p>{{ $minimalDescription }}</p>
                    <ul>
                        @foreach($minimalFeatureItems as $feature)
                            <li>{{ $feature }}</li>
                        @endforeach
                    </ul>
                </div>

                @if($minimalReviewsEnabled)
                    <section id="minimalProductReviews" class="minimal-product-reviews">
                        <div class="minimal-product-reviews-head">
                            <div>
                                <h2>Resenas</h2>
                                <p>Opiniones de clientes sobre este producto.</p>
                            </div>
                            @if($minimalReviewCount > 0)
                                <div class="minimal-product-review-score" aria-label="{{ $minimalReviewLabel }}">
                                    <span aria-hidden="true">&#9733;</span>
                                    <strong>{{ number_format($minimalReviewAverage, 1) }}</strong>
                                    <small>{{ $minimalReviewCount }} {{ \Illuminate\Support\Str::plural('resena', $minimalReviewCount) }}</small>
                                </div>
                            @endif
                        </div>

                        @if(session('review_success'))
                            <div class="minimal-product-review-alert">{{ session('review_success') }}</div>
                        @endif

                        <div class="minimal-product-review-list">
                            @foreach($minimalReviews as $review)
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

                        <form action="{{ route('product.reviews.store', $product) }}" method="POST" class="minimal-product-review-form">
                            @csrf
                            <div class="minimal-product-review-form-head">
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
                            <label class="minimal-product-review-form-comment">
                                <span>Comentario</span>
                                <textarea name="comment" rows="3" maxlength="1000">{{ old('comment') }}</textarea>
                            </label>
                            <button type="submit">Publicar resena</button>
                        </form>
                    </section>
                @endif
            </section>
        </div>

        <aside class="minimal-product-summary">
            <div class="minimal-product-main">
                <h1>{{ $product->name }}</h1>
                @if($minimalReviewsEnabled && $minimalReviewCount > 0)
                    <div class="minimal-product-rating"><span aria-hidden="true">&#9733;</span> {{ $minimalReviewLabel }}</div>
                @endif
                <div class="minimal-product-price">
                    @if($store->allowsOfferBadges() && $product->hasOfferPricing())
                        <span class="minimal-product-price-before">${{ number_format((float) $product->offer_original_price, 2, '.', ',') }}</span>
                    @endif
                    <span>${{ number_format((float) $product->price, 2, '.', ',') }}</span>
                </div>
                <p>{{ $minimalDescription }}</p>

                <div class="minimal-product-divider"></div>

                @if($product->hasColors())
                    <fieldset class="minimal-product-colors">
                        <legend>Color</legend>
                        <div>
                            @foreach($product->colors as $color)
                                <label>
                                    <input type="radio" name="visual_color" value="{{ $color }}" {{ $loop->first ? 'checked' : '' }} data-role="selected-color-radio">
                                    <span style="--swatch: {{ $minimalSwatches[($loop->iteration - 1) % count($minimalSwatches)] }}"></span>
                                    <em>{{ $color }}</em>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @else
                    <div class="minimal-product-colors" aria-hidden="true">
                        <strong>Color</strong>
                        <div>
                            @foreach($minimalSwatches as $swatch)
                                <span style="--swatch: {{ $swatch }}"></span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($product->hasSizes())
                    <label class="minimal-product-size">
                        <span>Talla</span>
                        <select data-role="selected-size">
                            <option value="">Selecciona talla</option>
                            @foreach($product->sizes as $size)
                                <option value="{{ $size }}">{{ $size }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif

                <div class="minimal-product-quantity-row">
                    <div>
                        <span>Cantidad</span>
                        <div class="minimal-product-stepper">
                            <button type="button" data-quantity-minus aria-label="Restar cantidad">&minus;</button>
                            <input id="quantity" type="number" name="quantity" min="1" max="{{ $quantityMax }}" value="{{ old('quantity', 1) }}" class="product-quantity-input">
                            <button type="button" data-quantity-plus aria-label="Sumar cantidad">+</button>
                        </div>
                    </div>
                    @if($product->stockLabel())
                        <span class="minimal-product-stock {{ $isProductSoldOut ? 'is-sold-out' : '' }}">{{ $product->stockLabel() }}</span>
                    @endif
                </div>

                @if($isProductSoldOut)
                    <div class="product-unavailable-message">Este producto esta agotado por ahora.</div>
                @else
                    <div class="minimal-product-actions">
                        <form action="{{ route('cart.add', $product->id) }}" method="POST" class="add-to-cart-form" data-role="minimal-add-form">
                            @csrf
                            <input type="hidden" name="quantity" value="{{ old('quantity', 1) }}" data-role="add-quantity">
                            <input type="hidden" name="size" value="" data-role="add-size">
                            <input type="hidden" name="color" value="" data-role="add-color">
                            <button type="submit" class="minimal-product-add">Agregar al carrito</button>
                        </form>

                        <form action="{{ route('cart.buy_now', $product->id) }}" method="POST" data-role="buy-now-form">
                            @csrf
                            <input type="hidden" name="quantity" value="{{ old('quantity', 1) }}" data-role="buy-now-quantity">
                            <input type="hidden" name="size" value="" data-role="buy-now-size">
                            <input type="hidden" name="color" value="" data-role="buy-now-color">
                            <button type="submit" class="minimal-product-buy">Comprar ahora</button>
                        </form>
                    </div>
                @endif

                <button type="button" class="minimal-product-wishlist">&hearts; Agregar a favoritos</button>
            </div>

            <section class="minimal-product-benefits" aria-label="Beneficios">
                @foreach($minimalBenefits as $benefit)
                    <div>
                        <span aria-hidden="true">{{ $benefit['icon'] }}</span>
                        <strong>{{ $benefit['title'] }}</strong>
                        <small>{{ $benefit['copy'] }}</small>
                    </div>
                @endforeach
            </section>

            @if($minimalRelated->isNotEmpty())
                <section class="minimal-product-related">
                    <div class="minimal-shop-section-head">
                        <h2>Tambien te puede gustar</h2>
                        <div aria-hidden="true">&lsaquo; &rsaquo;</div>
                    </div>
                    <div class="minimal-product-related-grid">
                        @foreach($minimalRelated as $relatedProduct)
                            @include('storefront.partials.minimal-product-card', ['product' => $relatedProduct, 'isRecommendation' => true])
                        @endforeach
                    </div>
                </section>
            @endif
        </aside>
    </section>
</main>

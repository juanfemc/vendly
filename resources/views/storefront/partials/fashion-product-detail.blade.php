@php
    $fashionGallery = $productGallery->isNotEmpty() ? $productGallery : collect([null]);
    $fashionRelated = $relatedProducts->take(4);
    $fashionSizes = $product->hasSizes() ? collect($product->sizes)->values() : collect(['S', 'M', 'L', 'XL', 'XXL']);
    $fashionColors = $product->hasColors() ? collect($product->colors)->values() : collect(['Navy', 'Green', 'Black', 'Gray']);
    $fashionColorMap = [
        'navy' => '#173a63',
        'azul' => '#173a63',
        'blue' => '#173a63',
        'green' => '#12643f',
        'verde' => '#12643f',
        'black' => '#111111',
        'negro' => '#111111',
        'gray' => '#b8b8b8',
        'gris' => '#b8b8b8',
        'white' => '#f8f8f8',
        'blanco' => '#f8f8f8',
    ];
    $fashionCurrentColor = $fashionColors->first();
    $fashionReviewAverage = $reviewCount > 0 ? number_format($reviewAverage, 1) : '4.8';
    $fashionReviewCount = $reviewCount > 0 ? $reviewCount : 24;
    $fashionDescription = $product->description
        ?: 'A timeless piece reimagined for today. Soft fabric and a full zip front make it perfect for everyday wear.';
    $fashionFeatureItems = collect([
        'Regular fit',
        'Full zip with stand-up collar',
        'Soft fabric for everyday comfort',
        'Side zip pockets',
        'Ribbed cuffs and hem',
        'Color: ' . ($fashionCurrentColor ?: 'Navy'),
    ]);
@endphp

<main class="fashion-product-shell">
    <nav class="fashion-product-breadcrumb" aria-label="Ruta de navegacion">
        <a href="{{ $storefrontUrls->home($store) }}">Home</a>
        <span>&rsaquo;</span>
        <a href="{{ $storefrontUrls->products($store) }}">Shop</a>
        @if($product->category)
            <span>&rsaquo;</span>
            <a href="{{ $storefrontUrls->products($store) }}">{{ $product->category }}</a>
        @endif
        <span>&rsaquo;</span>
        <span>{{ $product->name }}</span>
    </nav>

    <section class="fashion-product-hero">
        <div class="fashion-product-gallery product-carousel" data-product-carousel>
            <div class="fashion-product-thumbs" aria-label="Imagenes del producto">
                @foreach($fashionGallery as $index => $galleryImage)
                    <button
                        type="button"
                        class="fashion-product-thumb {{ $index === 0 ? 'is-active' : '' }}"
                        data-carousel-thumb="{{ $index }}"
                        aria-label="Ver imagen {{ $index + 1 }}"
                        aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                    >
                        @if($galleryImage)
                            <img src="{{ asset('storage/' . $galleryImage) }}" alt="" loading="lazy" decoding="async">
                        @else
                            <span>{{ substr($product->name, 0, 1) }}</span>
                        @endif
                    </button>
                @endforeach
                @if($fashionGallery->count() < 4)
                    @for($thumbIndex = $fashionGallery->count(); $thumbIndex < 4; $thumbIndex++)
                        <button type="button" class="fashion-product-thumb" disabled aria-hidden="true">
                            <span>{{ substr($product->name, 0, 1) }}</span>
                        </button>
                    @endfor
                @endif
            </div>

            <div class="fashion-product-stage">
                @foreach($fashionGallery as $index => $galleryImage)
                    @if($galleryImage)
                        <img
                            src="{{ asset('storage/' . $galleryImage) }}"
                            alt="{{ $product->name }} imagen {{ $index + 1 }}"
                            class="fashion-product-image product-carousel-image {{ $index === 0 ? 'is-active' : '' }}"
                            data-carousel-slide="{{ $index }}"
                            loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                            fetchpriority="{{ $index === 0 ? 'high' : 'auto' }}"
                            decoding="async"
                        >
                    @else
                        <div class="fashion-product-fallback product-carousel-image is-active" data-carousel-slide="{{ $index }}">
                            {{ $product->name }}
                        </div>
                    @endif
                @endforeach
                <button type="button" class="fashion-product-zoom" aria-label="Ampliar imagen">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="11" cy="11" r="6"></circle>
                        <path d="m16 16 4 4"></path>
                    </svg>
                </button>
            </div>
        </div>

        <aside class="fashion-product-info">
            @if($product->category)
                <span class="fashion-product-kicker">{{ $product->category }}</span>
            @endif
            <h1>{{ $product->name }}</h1>
            <div class="fashion-product-price">
                @if($showsOfferPricing)
                    <span>${{ number_format((float) $product->offer_original_price, 0, ',', '.') }}</span>
                @endif
                <strong>${{ number_format((float) $product->price, 0, ',', '.') }}</strong>
            </div>

            <div class="fashion-product-stars" aria-label="{{ $fashionReviewAverage }} de 5 basado en {{ $fashionReviewCount }} reseñas">
                <span>&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                <small>({{ $fashionReviewCount }} reviews)</small>
            </div>

            <p class="fashion-product-summary">{{ $fashionDescription }}</p>

            @if($isProductSoldOut)
                <div class="fashion-product-unavailable">Este producto está agotado por ahora.</div>
            @else
                <form action="{{ route('cart.add', $product->id) }}" method="POST" class="fashion-product-form add-to-cart-form">
                    @csrf
                    <input type="hidden" name="quantity" value="1">

                    <div class="fashion-option-group">
                        <div class="fashion-option-head">
                            <span>COLOR: <b>{{ $fashionCurrentColor }}</b></span>
                        </div>
                        <div class="fashion-color-options">
                            @foreach($fashionColors as $color)
                                @php($colorKey = \Illuminate\Support\Str::lower(trim((string) $color)))
                                <label title="{{ $color }}">
                                    <input type="radio" name="color" value="{{ $color }}" @checked($loop->first) @required($product->hasColors())>
                                    <span style="--swatch-color: {{ $fashionColorMap[$colorKey] ?? '#173a63' }}"></span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="fashion-option-group">
                        <div class="fashion-option-head">
                            <span>SIZE:</span>
                        </div>
                        <div class="fashion-size-options">
                            @foreach($fashionSizes as $size)
                                <label>
                                    <input type="radio" name="size" value="{{ $size }}" @required($product->hasSizes())>
                                    <span>{{ $size }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="fashion-product-actions">
                        <button type="submit">Add to Cart</button>
                        <a href="{{ $storefrontUrls->products($store) }}" aria-label="Agregar a favoritos">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M20.8 5.6a5.2 5.2 0 0 0-7.4 0L12 7l-1.4-1.4a5.2 5.2 0 0 0-7.4 7.4L12 21.8l8.8-8.8a5.2 5.2 0 0 0 0-7.4Z"></path>
                            </svg>
                        </a>
                    </div>
                </form>
            @endif

            <ul class="fashion-product-benefits">
                <li>Free Shipping on orders over $100</li>
                <li>Easy 30-Day Returns</li>
                <li>Secure Payments</li>
            </ul>
        </aside>
    </section>

    <section class="fashion-product-story">
        <div class="fashion-product-tabs" aria-label="Informacion del producto">
            <a href="#fashionDescription" class="is-active">Description</a>
            <a href="#fashionDetails">Details</a>
            <a href="#fashionShipping">Shipping & Returns</a>
            <a href="#fashionReviews">Reviews ({{ $fashionReviewCount }})</a>
        </div>

        <div class="fashion-product-story-grid">
            <article id="fashionDescription">
                <p>{{ $fashionDescription }}</p>
                <ul>
                    @foreach($fashionFeatureItems as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </article>

            <div class="fashion-product-lifestyle" aria-hidden="true">
                @if($productGallery->first())
                    <img src="{{ asset('storage/' . $productGallery->first()) }}" alt="">
                @else
                    <span>{{ $product->name }}</span>
                @endif
            </div>
        </div>
    </section>

    <section class="fashion-review-summary" id="fashionReviews">
        <div class="fashion-review-score">
            <h2>Customer Reviews</h2>
            <strong>{{ $fashionReviewAverage }}</strong>
            <span>&#9733;&#9733;&#9733;&#9733;&#9733;</span>
            <p>Based on {{ $fashionReviewCount }} reviews</p>
            @if($reviewsEnabled)
                <a href="#fashionReviewForm">Write a Review</a>
            @endif
        </div>

        <div class="fashion-review-list">
            @forelse($reviews->take(3) as $review)
                <article>
                    <strong>{{ $review->name }}</strong>
                    <span>{{ str_repeat('★', (int) $review->rating) }}</span>
                    <p>{{ $review->comment ?: 'Great fit and comfortable.' }}</p>
                    <time>{{ $review->created_at?->format('M d, Y') }}</time>
                </article>
            @empty
                @foreach([
                    ['name' => 'James T.', 'copy' => 'Great fit and super comfortable. Classic look that never goes out of style.'],
                    ['name' => 'Alex P.', 'copy' => 'Love the material and details. Shipping was fast too!'],
                    ['name' => 'Kevin L.', 'copy' => 'Perfect for everyday wear. Highly recommend.'],
                ] as $placeholderReview)
                    <article>
                        <strong>{{ $placeholderReview['name'] }}</strong>
                        <span>&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                        <p>{{ $placeholderReview['copy'] }}</p>
                        <time>May 12, 2024</time>
                    </article>
                @endforeach
            @endforelse
            @if($reviewsEnabled)
                <form id="fashionReviewForm" action="{{ route('product.reviews.store', $product) }}" method="POST" class="fashion-review-form">
                    @csrf
                    <input type="text" name="name" placeholder="Tu nombre" maxlength="80" required>
                    <select name="rating" required>
                        @for($rating = 5; $rating >= 1; $rating--)
                            <option value="{{ $rating }}">{{ $rating }} estrellas</option>
                        @endfor
                    </select>
                    <textarea name="comment" rows="3" maxlength="1000" placeholder="Escribe tu reseña"></textarea>
                    <button type="submit">Enviar reseña</button>
                </form>
            @endif
        </div>
    </section>

    @if($fashionRelated->isNotEmpty())
        <section class="fashion-related">
            <h2>You May Also Like</h2>
            <div class="fashion-related-grid">
                @foreach($fashionRelated as $relatedProduct)
                    <article class="fashion-related-card">
                        <a href="{{ $storefrontUrls->product($store, $relatedProduct) }}" class="fashion-related-media">
                            @if($relatedProduct->image)
                                <img src="{{ asset('storage/' . $relatedProduct->image) }}" alt="{{ $relatedProduct->name }}" loading="lazy" decoding="async">
                            @else
                                <span>{{ $relatedProduct->name }}</span>
                            @endif
                        </a>
                        <p>{{ $relatedProduct->category ?: 'Jackets' }}</p>
                        <h3>{{ $relatedProduct->name }}</h3>
                        <strong>${{ number_format((float) $relatedProduct->price, 0, ',', '.') }}</strong>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</main>

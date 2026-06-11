@php
    $fashionProducts = ($allProducts ?? collect())->values();
    $fashionInitialLimit = 8;
    $placeholderProducts = collect([
        ['category' => 'Jackets', 'name' => 'Urban Pop Polo shirt, navy / blue', 'price' => 69000],
        ['category' => 'Jackets', 'name' => 'Pop TRX Vintage, navy / white', 'price' => 69000],
        ['category' => 'Jackets', 'name' => 'Pop Beckenbauer Track Jacket', 'price' => 120000],
        ['category' => 'Jackets', 'name' => 'Pop Classic t-shirt, grey / navy', 'price' => 120000],
        ['category' => 'Jackets', 'name' => 'Pop SL Cap, navy / white', 'price' => 65000],
        ['category' => 'Jackets', 'name' => 'Border Yard Pullover Hood, denim', 'price' => 110000],
        ['category' => 'Jackets', 'name' => 'Rug Pull t-shirt, white', 'price' => 69000],
        ['category' => 'Jackets', 'name' => 'Knock Knock Sweat', 'price' => 130000],
    ]);
    $fashionCategoryLinks = ($activeCategories ?? collect())->take(5)->values();
    $fashionProductCategoryTabs = $fashionProducts
        ->pluck('category')
        ->filter()
        ->unique()
        ->take(5)
        ->values();
    $fashionPlaceholderCategoryTabs = $placeholderProducts
        ->pluck('category')
        ->unique()
        ->take(5)
        ->values();
    $fashionTabs = $fashionProductCategoryTabs->isNotEmpty()
        ? $fashionProductCategoryTabs->map(fn ($category) => [
            'name' => $category,
            'slug' => \Illuminate\Support\Str::slug($category),
        ])
        : ($fashionProducts->isEmpty()
        ? $fashionPlaceholderCategoryTabs->map(fn ($category) => [
            'name' => $category,
            'slug' => \Illuminate\Support\Str::slug($category),
        ])
        : ($fashionCategoryLinks->isNotEmpty()
        ? $fashionCategoryLinks->map(fn ($category) => [
            'name' => $category->name,
            'slug' => $category->slug ?: \Illuminate\Support\Str::slug($category->name),
        ])
        : collect(['Mujer', 'Hombre', 'Zapatos', 'Bolsos', 'Accesorios'])->map(fn ($category) => [
            'name' => $category,
            'slug' => \Illuminate\Support\Str::slug($category),
        ])));
    $fashionHeroImage = $heroImage;
    $fashionRecommended = $fashionProducts->take(6)->map(fn ($product, $index) => [
        'category' => $product->category ?: 'Jackets',
        'name' => $product->name,
        'price' => (float) $product->price,
        'image' => $product->image ? asset('storage/' . $product->image) : null,
        'url' => $storefrontUrls->product($store, $product),
        'reviews' => 12 + $index,
    ])->values();

    if ($fashionRecommended->count() < 6) {
        $fashionRecommended = $fashionRecommended
            ->concat($placeholderProducts->skip($fashionRecommended->count())->take(6 - $fashionRecommended->count())->map(fn ($product, $index) => [
                'category' => $product['category'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => null,
                'url' => $storefrontUrls->products($store),
                'reviews' => 12 + $index,
            ]))
            ->values();
    }
@endphp

<section class="fashion-hero">
    <div class="fashion-hero-copy">
        <span>Urban Edge</span>
        <h1>Jackets for the Modern Man</h1>
        <a href="{{ $storefrontUrls->products($store) }}">Discover Now</a>
    </div>

    <button class="fashion-hero-arrow fashion-hero-arrow--left" type="button" aria-label="Anterior">&lsaquo;</button>
    <button class="fashion-hero-arrow fashion-hero-arrow--right" type="button" aria-label="Siguiente">&rsaquo;</button>

    @if($fashionHeroImage)
        <img src="{{ $fashionHeroImage }}" alt="{{ $store->name }}" loading="eager" fetchpriority="high" decoding="async">
    @else
        <div class="fashion-hero-fallback">
            <span>{{ $store->name }}</span>
        </div>
    @endif
</section>

<section class="fashion-recommended" aria-label="Recomendados para ti">
    <div class="fashion-recommended-head">
        <div>
            <h2>Recomendados para ti</h2>
            <p>Hemos seleccionado estos productos que pueden interesarte.</p>
        </div>

        <div class="fashion-recommended-controls" aria-hidden="true">
            <button type="button">&lsaquo;</button>
            <button type="button">&rsaquo;</button>
        </div>
    </div>

    <div class="fashion-recommended-track">
        @foreach($fashionRecommended as $recommended)
            <article class="fashion-recommended-card">
                <a class="fashion-recommended-media" href="{{ $recommended['url'] }}">
                    @if($recommended['image'])
                        <img src="{{ $recommended['image'] }}" alt="{{ $recommended['name'] }}" loading="lazy" decoding="async">
                    @else
                        <span>{{ $recommended['name'] }}</span>
                    @endif
                    <span class="fashion-recommended-heart" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M20.8 5.6a5.2 5.2 0 0 0-7.4 0L12 7l-1.4-1.4a5.2 5.2 0 0 0-7.4 7.4L12 21.8l8.8-8.8a5.2 5.2 0 0 0 0-7.4Z"></path>
                        </svg>
                    </span>
                </a>
                <p>{{ $recommended['category'] }}</p>
                <h3>
                    <a href="{{ $recommended['url'] }}">{{ $recommended['name'] }}</a>
                </h3>
                <strong>${{ number_format($recommended['price'], 0, ',', '.') }}</strong>
                <div class="fashion-recommended-rating" aria-label="Producto recomendado">
                    <span>&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                    <small>({{ $recommended['reviews'] }})</small>
                </div>
            </article>
        @endforeach
    </div>

    <a class="fashion-recommended-more" href="{{ $storefrontUrls->products($store) }}">Ver más productos</a>
</section>

<section class="fashion-arrivals" id="catalogo">
    <div class="fashion-section-head">
        <h2>New Arrivals</h2>
        <nav aria-label="Categorias destacadas" data-fashion-category-tabs>
            @foreach($fashionTabs as $tab)
                <button
                    type="button"
                    data-fashion-category-filter="{{ $tab['slug'] }}"
                    aria-pressed="false"
                >
                    {{ $tab['name'] }}
                </button>
            @endforeach
        </nav>
    </div>

    <div class="fashion-product-grid" data-fashion-product-grid>
        @forelse($fashionProducts as $product)
            @php($fashionProductCategorySlug = \Illuminate\Support\Str::slug($product->category ?: 'Jackets'))
            <article
                class="fashion-product"
                data-fashion-product
                data-fashion-category="{{ $fashionProductCategorySlug }}"
                @if($loop->iteration > $fashionInitialLimit) hidden data-fashion-initial-hidden="true" @endif
            >
                <a class="fashion-product-media" href="{{ $storefrontUrls->product($store, $product) }}">
                    @if($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
                    @else
                        <span>{{ $product->name }}</span>
                    @endif
                </a>
                <p>{{ $product->category ?: 'Jackets' }}</p>
                <h3>{{ $product->name }}</h3>
                @if($loop->iteration === 3)
                    <div class="fashion-rating" aria-label="Producto destacado">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                @endif
                <strong>${{ number_format((float) $product->price, 0, ',', '.') }}</strong>
            </article>
        @empty
            @foreach($placeholderProducts as $placeholder)
                <article class="fashion-product" data-fashion-product data-fashion-category="{{ \Illuminate\Support\Str::slug($placeholder['category']) }}">
                    <div class="fashion-product-media fashion-product-media--placeholder">
                        <span>{{ $placeholder['name'] }}</span>
                    </div>
                    <p>{{ $placeholder['category'] }}</p>
                    <h3>{{ $placeholder['name'] }}</h3>
                    @if($loop->iteration === 3)
                        <div class="fashion-rating" aria-label="Producto destacado">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                    @endif
                    <strong>${{ number_format($placeholder['price'], 0, ',', '.') }}</strong>
                </article>
            @endforeach
        @endforelse
    </div>

    <p class="fashion-empty-state" data-fashion-empty-state hidden>No hay productos en esta categoría por ahora.</p>
</section>

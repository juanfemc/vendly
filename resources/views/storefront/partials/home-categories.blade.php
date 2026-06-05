@php
    $homeCategories = ($activeCategories ?? collect())
        ->filter(fn ($category) => (int) (($categoryProductCounts ?? collect())[$category->name] ?? 0) > 0)
        ->values();
@endphp

@if($homeCategories->isNotEmpty())
    <section class="home-categories" aria-label="Categorias">
        <div class="home-categories-head">
            <span>{{ $businessLabel }}</span>
            <h2>Compra por categoria</h2>
        </div>

        <div class="home-categories-track">
            @foreach($homeCategories as $homeCategory)
                @php
                    $homeCategoryCount = (int) (($categoryProductCounts ?? collect())[$homeCategory->name] ?? 0);
                    $homeCategoryInitial = strtoupper(substr((string) $homeCategory->name, 0, 1));
                @endphp
                <a href="{{ $storefrontUrls->category($store, $homeCategory) }}" class="home-category-card">
                    <span class="home-category-media" aria-hidden="true">
                        @if($homeCategory->image)
                            <img src="{{ asset('storage/' . $homeCategory->image) }}" alt="" loading="lazy" decoding="async">
                        @else
                            <strong>{{ $homeCategoryInitial }}</strong>
                        @endif
                    </span>
                    <span class="home-category-copy">
                        <strong>{{ $homeCategory->name }}</strong>
                        <small>{{ $homeCategoryCount }} {{ $homeCategoryCount === 1 ? 'producto' : $itemsLabel }}</small>
                    </span>
                </a>
            @endforeach
        </div>
    </section>
@endif

@php
    $homeCategories = ($activeCategories ?? collect())
        ->filter(fn ($category) => (int) (($categoryProductCounts ?? collect())[$category->name] ?? 0) > 0)
        ->values();
    $homeCategoryItemsLabel = $itemsLabel ?? 'productos';
    $homeCategoriesAreCompact = $homeCategories->count() > 3;
    $homeCenteredCategoryWidth = match ($homeCategories->count()) {
        1 => '420px',
        2 => 'min(520px, calc((100% - 14px) / 2))',
        default => 'min(360px, calc((100% - 28px) / 3))',
    };
@endphp

@if($homeCategories->isNotEmpty())
    <section class="home-categories {{ $homeCategoriesAreCompact ? 'home-categories--scroll' : 'home-categories--centered' }}" aria-label="Categorias">
        <div class="home-categories-scroll-shell">
            <div class="home-categories-track {{ $homeCategoriesAreCompact ? 'is-scrollable' : 'is-centered' }}" @if(! $homeCategoriesAreCompact) style="--home-category-width: {{ $homeCenteredCategoryWidth }};" @endif>
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
                            <small>{{ $homeCategoryCount }} {{ $homeCategoryCount === 1 ? 'producto' : $homeCategoryItemsLabel }}</small>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

    </section>
@endif

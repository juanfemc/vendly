<section class="catalog-section category-showcase" id="catalogo">
    @forelse($visibleCategorySections as $section)
        @php($sectionCategory = $section['category'])
        <section class="category-block" id="categoria-{{ $sectionCategory->slug }}">
            <div class="category-block-head">
                <div class="category-block-copy">
                    <h2>{{ $sectionCategory->name }}</h2>
                </div>
            </div>

            <div class="products-grid">
                @foreach($section['products'] as $product)
                    @include('storefront.partials.product-card', ['cardClass' => $cardClass ?? ''])
                @endforeach
            </div>

            <a href="{{ $storefrontUrls->category($store, $sectionCategory) }}" class="category-more-link">
                Ver mas de {{ $sectionCategory->name }}
            </a>
        </section>
    @empty
        @if($otherProducts->isEmpty())
            <div class="empty-state">Aun no hay {{ $itemsLabel }} publicados.</div>
        @endif
    @endforelse

    @if($otherProducts->isNotEmpty())
        <section class="category-block category-block--other">
            <div class="category-block-head">
                <div class="category-block-copy">
                    <h2>{{ $store->isBasicPlan() ? 'Todos los productos' : 'Otros productos' }}</h2>
                </div>
            </div>

            <div class="products-grid">
                @foreach($otherProducts as $product)
                    @include('storefront.partials.product-card', ['cardClass' => $cardClass ?? ''])
                @endforeach
            </div>
        </section>
    @endif
</section>

<section class="catalog-section category-showcase" id="catalogo">
    <div class="catalog-head catalog-head--default">
        <div class="catalog-head-copy">
            <h2>{{ $collectionLabelTitle }}</h2>
            <p>{{ $defaultShopCopy }}</p>
        </div>
        <span class="catalog-head-pill">{{ $productsTotal }} {{ $itemsLabel }}</span>
    </div>

    @forelse($visibleCategorySections as $section)
        @php($sectionCategory = $section['category'])
        <section class="category-block" id="categoria-{{ $sectionCategory->slug }}">
            <div class="category-block-head">
                <div class="category-block-copy">
                    <span class="product-tag">{{ $section['total'] }} {{ $itemsLabel }}</span>
                    <h2>{{ $sectionCategory->name }}</h2>
                    @if($sectionCategory->description)
                        <p>{{ $sectionCategory->description }}</p>
                    @endif
                </div>

                @if($sectionCategory->image)
                    <img src="{{ asset('storage/' . $sectionCategory->image) }}" alt="{{ $sectionCategory->name }}" loading="lazy" decoding="async">
                @endif
            </div>

            <div class="products-grid">
                @foreach($section['products'] as $product)
                    @include('storefront.partials.product-card', ['cardClass' => $cardClass ?? ''])
                @endforeach
            </div>

            <a href="{{ route('store.category.show', ['slug' => $store->slug, 'category' => $sectionCategory->slug]) }}" class="category-more-link">
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
                    <span class="product-tag">{{ $otherProducts->count() }} {{ $itemsLabel }}</span>
                    <h2>Otros productos</h2>
                    <p>Productos que todavia no tienen una categoria asignada.</p>
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

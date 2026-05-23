@php
    $catalogProducts = $catalogProducts ?? $products;
    $minimalProducts = ($allProducts ?? collect())->values();
    $minimalCatalogProducts = method_exists($catalogProducts, 'items')
        ? collect($catalogProducts->items())->values()
        : $minimalProducts->take(6);
@endphp

<div class="minimal-shop-catalog-shell" data-minimal-catalog-shell>
    <div class="minimal-shop-product-grid">
        @forelse($minimalCatalogProducts as $product)
            @include('storefront.partials.minimal-product-card', ['product' => $product, 'isRecommendation' => false])
        @empty
            <div class="minimal-shop-empty-state">Aun no hay productos para mostrar.</div>
        @endforelse
    </div>

    @if(method_exists($catalogProducts, 'hasPages') && $catalogProducts->hasPages())
        <div class="minimal-shop-pagination">
            {{ $catalogProducts->fragment('catalogo')->links('storefront.partials.pagination') }}
        </div>
    @endif
</div>

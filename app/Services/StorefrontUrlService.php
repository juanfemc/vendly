<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Store;
use App\Models\StoreCategory;
use Illuminate\Http\Request;

class StorefrontUrlService
{
    public function __construct(
        private StoreSubdomainService $subdomains,
    ) {
    }

    public function home(Store $store, ?Request $request = null): string
    {
        return $this->storeUrl($store, $request, '/', fn () => route('store.show', $store->slug));
    }

    public function products(Store $store, ?Request $request = null, array $query = []): string
    {
        return $this->withQuery(
            $this->storeUrl($store, $request, '/productos', fn () => route('store.products.index', $store->slug)),
            $query
        );
    }

    public function product(Store $store, Product $product, ?Request $request = null): string
    {
        return $this->storeUrl($store, $request, '/productos/' . $product->publicRouteKey(), fn () => route(
            'store.product.show',
            [
                'slug' => $store->slug,
                'product' => $product->publicRouteKey(),
            ]
        ));
    }

    public function category(Store $store, StoreCategory $category, ?Request $request = null, array $query = []): string
    {
        return $this->withQuery(
            $this->storeUrl($store, $request, '/categorias/' . $category->slug, fn () => route(
                'store.category.show',
                [
                    'slug' => $store->slug,
                    'category' => $category->slug,
                ]
            )),
            $query
        );
    }

    public function about(Store $store, ?Request $request = null): string
    {
        return $this->storeUrl($store, $request, '/nosotros', fn () => route('store.about', $store->slug));
    }

    public function favicon(Store $store, ?Request $request = null): string
    {
        return $this->storeUrl($store, $request, '/favicon.svg', fn () => route('store.favicon', $store->slug));
    }

    public function usesCurrentSubdomain(Store $store, ?Request $request = null): bool
    {
        $request ??= request();
        $subdomain = $this->subdomains->subdomainFromRequest($request);

        return $subdomain
            && $store->allowsSubdomain()
            && $store->subdomain === $subdomain;
    }

    public function usesCurrentCustomDomain(Store $store, ?Request $request = null): bool
    {
        $request ??= request();
        $customDomain = $this->subdomains->customDomainFromRequest($request);

        return $customDomain
            && $store->allowsCustomDomain()
            && $store->custom_domain_status === Store::CUSTOM_DOMAIN_VERIFIED
            && $store->custom_domain === $customDomain;
    }

    private function withQuery(string $url, array $query): string
    {
        $query = array_filter($query, fn ($value) => $value !== null && $value !== '');

        if ($query === []) {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
    }

    private function storeUrl(Store $store, ?Request $request, string $subdomainPath, callable $slugUrl): string
    {
        return $this->usesCurrentSubdomain($store, $request) || $this->usesCurrentCustomDomain($store, $request)
            ? $this->subdomainUrl($request, $subdomainPath)
            : $slugUrl();
    }

    private function subdomainUrl(?Request $request, string $path): string
    {
        $request ??= request();
        $baseUrl = rtrim($request->getSchemeAndHttpHost(), '/');

        return $path === '/'
            ? $baseUrl
            : $baseUrl . '/' . ltrim($path, '/');
    }
}

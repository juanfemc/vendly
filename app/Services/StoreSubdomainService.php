<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Http\Request;

class StoreSubdomainService
{
    public function subdomainFromRequest(Request $request): ?string
    {
        return $this->subdomainFromHost($request->getHost());
    }

    public function isCustomDomainRequest(Request $request): bool
    {
        return $this->customDomainFromRequest($request) !== null;
    }

    public function customDomainFromRequest(Request $request): ?string
    {
        if (! Store::supportsCustomDomainColumns()) {
            return null;
        }

        $host = $this->normalizeHost($request->getHost());
        $baseHost = $this->baseHost();

        if (! $host || ! $baseHost || $host === $baseHost || str_ends_with($host, '.' . $baseHost)) {
            return null;
        }

        return Store::normalizeCustomDomain($host);
    }

    public function subdomainFromHost(?string $host): ?string
    {
        if (! Store::supportsSubdomainColumn()) {
            return null;
        }

        $host = $this->normalizeHost($host);
        $baseHost = $this->baseHost();

        if (! $host || ! $baseHost || $host === $baseHost) {
            return null;
        }

        if (! str_ends_with($host, '.' . $baseHost)) {
            return null;
        }

        $subdomain = substr($host, 0, -1 * (strlen($baseHost) + 1));

        if ($subdomain === '' || str_contains($subdomain, '.')) {
            return null;
        }

        $subdomain = Store::normalizeSubdomain($subdomain);

        return $subdomain && ! in_array($subdomain, Store::reservedSubdomains(), true)
            ? $subdomain
            : null;
    }

    public function publicStoreFromRequest(Request $request): ?Store
    {
        $customDomain = $this->customDomainFromRequest($request);

        if ($customDomain) {
            $store = Store::publiclyAvailable()
                ->where('custom_domain', $customDomain)
                ->where('custom_domain_status', Store::CUSTOM_DOMAIN_VERIFIED)
                ->first();

            return $store?->allowsCustomDomain() ? $store : null;
        }

        $subdomain = $this->subdomainFromRequest($request);

        if (! $subdomain) {
            return null;
        }

        $store = Store::publiclyAvailable()
            ->where('subdomain', $subdomain)
            ->first();

        return $store?->allowsSubdomain() ? $store : null;
    }

    private function baseHost(): ?string
    {
        return $this->normalizeHost(parse_url(config('app.url'), PHP_URL_HOST));
    }

    private function normalizeHost(?string $host): ?string
    {
        $host = strtolower(trim((string) $host));
        $host = preg_replace('/:\d+$/', '', $host) ?? '';

        return $host === '' ? null : $host;
    }
}

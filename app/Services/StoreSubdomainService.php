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

<?php

namespace App\View\Models;

use App\Models\Store;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorefrontPageViewModel
{
    public function __construct(
        private Store $store,
        public string $publicBaseUrl,
        public int $cartCount,
        public string $instagramUrl,
        public string $facebookUrl,
        public string $tiktokUrl,
        public bool $canManageStore,
    ) {
    }

    public static function from(Store $store): self
    {
        return new self(
            store: $store,
            publicBaseUrl: rtrim(config('app.url') ?: request()->getSchemeAndHttpHost(), '/'),
            cartCount: (int) collect(session('cart', []))->sum('quantity'),
            instagramUrl: $store->instagram_url ?: 'https://instagram.com',
            facebookUrl: $store->facebook_url ?: 'https://facebook.com',
            tiktokUrl: $store->tiktok_url ?: 'https://tiktok.com',
            canManageStore: auth()->check() && auth()->user()->role !== 'admin' && auth()->id() === $store->user_id,
        );
    }

    public function storageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $url = Storage::url($path);

        return Str::startsWith($url, ['http://', 'https://'])
            ? $url
            : $this->publicBaseUrl . $url;
    }
}

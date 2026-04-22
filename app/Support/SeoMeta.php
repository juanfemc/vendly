<?php

namespace App\Support;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Str;

class SeoMeta
{
    public function __construct(
        public string $title,
        public string $description,
        public string $url,
        public ?string $image = null,
        public ?string $imageAlt = null,
        public ?string $favicon = null,
        public string $type = 'website',
    ) {
    }

    public static function storeHome(Store $store, string $url, ?string $image, string $fallbackDescription, ?string $favicon = null): self
    {
        $copy = trim((string) $store->shop_copy);
        $description = $copy !== '' ? $copy : $fallbackDescription;
        $title = $copy !== '' ? $copy : ($store->name ?? $store->businessTypeLabel());

        return new self(
            title: Str::limit(trim($title), 70, ''),
            description: Str::limit(trim($description), 160, ''),
            url: $url,
            image: $image,
            imageAlt: $image ? 'Portada de ' . $store->name : null,
            favicon: $favicon,
            type: 'website',
        );
    }

    public static function product(Store $store, Product $product, string $url, ?string $image, string $fallbackDescription, ?string $favicon = null): self
    {
        $description = trim((string) $product->description);

        return new self(
            title: Str::limit($product->name . ' | ' . $store->name, 70, ''),
            description: Str::limit($description !== '' ? $description : $fallbackDescription, 160, ''),
            url: $url,
            image: $image,
            imageAlt: $image ? $product->name : null,
            favicon: $favicon,
            type: 'product',
        );
    }
}

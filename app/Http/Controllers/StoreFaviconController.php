<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Services\StoreSubdomainService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class StoreFaviconController extends Controller
{
    public function current(Request $request, StoreSubdomainService $subdomains): Response
    {
        return $this->svgResponse($subdomains->publicStoreFromRequest($request));
    }

    public function show(string $slug): Response
    {
        $store = Store::publiclyAvailable()
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->svgResponse($store);
    }

    private function svgResponse(?Store $store): Response
    {
        return response($this->svg($store), 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function svg(?Store $store): string
    {
        $name = trim((string) ($store?->name ?: config('app.name', 'Vendly')));
        $initial = Str::upper(Str::substr($name, 0, 1)) ?: 'V';
        $brandColor = $this->validHexColor($store?->brand_color) ?: '#111827';
        $logoDataUri = $this->logoDataUri($store);

        $content = $logoDataUri
            ? '<image href="' . e($logoDataUri) . '" x="0" y="0" width="96" height="96" preserveAspectRatio="xMidYMid slice" clip-path="url(#faviconClip)" />'
            : '<text x="48" y="58" text-anchor="middle" font-family="Arial, sans-serif" font-size="42" font-weight="800" fill="#ffffff">' . e($initial) . '</text>';

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96">
    <defs>
        <clipPath id="faviconClip">
            <rect width="96" height="96" rx="20" ry="20" />
        </clipPath>
    </defs>
    <rect width="96" height="96" rx="20" ry="20" fill="{$brandColor}" />
    {$content}
</svg>
SVG;
    }

    private function logoDataUri(?Store $store): ?string
    {
        if (! $store?->logo_image || ! Storage::disk('public')->exists($store->logo_image)) {
            return null;
        }

        $contents = Storage::disk('public')->get($store->logo_image);

        if ($contents === '' || strlen($contents) > 250000) {
            return null;
        }

        $mimeType = Storage::disk('public')->mimeType($store->logo_image)
            ?: $this->mimeTypeFromExtension($store->logo_image);

        return 'data:' . $mimeType . ';base64,' . base64_encode($contents);
    }

    private function mimeTypeFromExtension(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }

    private function validHexColor(?string $color): ?string
    {
        $color = trim((string) $color);

        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : null;
    }
}

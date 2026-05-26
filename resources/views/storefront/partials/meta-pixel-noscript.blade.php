@php
    $pixelStore = $store ?? null;
    $metaPixelId = $pixelStore?->allowsMetaPixel() ? trim((string) $pixelStore->meta_pixel_id) : '';
@endphp

@if($metaPixelId !== '' && preg_match('/^[0-9]+$/', $metaPixelId))
    <noscript>
        <img height="1" width="1" style="display:none" alt="" src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=PageView&noscript=1">
    </noscript>
@endif

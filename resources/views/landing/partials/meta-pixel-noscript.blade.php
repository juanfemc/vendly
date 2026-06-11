@php
    $landingMetaPixelId = trim((string) config('services.meta.landing_pixel_id'));
@endphp

@if($landingMetaPixelId !== '' && preg_match('/^[0-9]+$/', $landingMetaPixelId))
    <noscript>
        <img height="1" width="1" style="display:none" alt="" src="https://www.facebook.com/tr?id={{ $landingMetaPixelId }}&ev=PageView&noscript=1">
    </noscript>
@endif

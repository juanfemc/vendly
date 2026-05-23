@php
    $storeWhatsappUrl = $store->whatsappInfoUrl();
    $storeEmail = $store->user?->email;
@endphp

<footer class="minimal-shop-footer" id="minimalShopFooter">
    <div class="shell">
        <div class="minimal-shop-footer-grid">
            <div>
                <strong>Acerca de</strong>
                <a href="#minimalShopFooter">Noticias</a>
                @if($showAboutSection ?? false)
                    <a href="{{ $storefrontUrls->about($store) }}">Conoce el equipo</a>
                @else
                    <a href="#minimalShopFooter">Conoce el equipo</a>
                @endif
                @if($storeWhatsappUrl)
                    <a href="{{ $storeWhatsappUrl }}" target="_blank" rel="noopener noreferrer">Contactanos</a>
                @endif
            </div>

            <div class="minimal-shop-social-block">
                <span>Redes sociales</span>
                <div class="minimal-shop-socials">
                    <a href="{{ $tiktokUrl }}" target="_blank" rel="noopener noreferrer" aria-label="X">X</a>
                    <a href="{{ $facebookUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook">f</a>
                    <a href="{{ $instagramUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram">ig</a>
                </div>
            </div>
        </div>
    </div>
</footer>

@if($storeWhatsappUrl)
    <a
        href="{{ $storeWhatsappUrl }}"
        class="store-whatsapp-float"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Contactar por WhatsApp"
    >
        <img src="{{ asset('images/icons/icon-whatsapp.png') }}" alt="" aria-hidden="true">
    </a>
@endif

@php
    $storeWhatsappUrl = $store->whatsappInfoUrl();
    $storeEmail = $store->user?->email;
@endphp

<footer class="minimal-shop-footer" id="minimalShopFooter">
    <div class="shell">
        <section class="minimal-shop-cta">
            <div>
                <h2>Ready to Get<br>Our New Stuff?</h2>
                <form class="minimal-shop-email-form" action="{{ $storeWhatsappUrl ?: '#' }}" method="GET">
                    <label for="minimalShopEmail">Tu email</label>
                    <input id="minimalShopEmail" type="email" placeholder="Tu email">
                    <button type="submit">Enviar</button>
                </form>
            </div>
            <p>{{ trim((string) $store->shop_copy) !== '' ? $store->shop_copy : 'Stuffs for homes and needs. Creamos una experiencia simple para encontrar productos y comprar rapido.' }}</p>
        </section>

        <div class="minimal-shop-footer-grid">
            <div>
                <strong>About</strong>
                <a href="#minimalShopFooter">Blog</a>
                @if($showAboutSection ?? false)
                    <a href="{{ $storefrontUrls->about($store) }}">Meet The Team</a>
                @else
                    <a href="#minimalShopFooter">Meet The Team</a>
                @endif
                @if($storeWhatsappUrl)
                    <a href="{{ $storeWhatsappUrl }}" target="_blank" rel="noopener noreferrer">Contact Us</a>
                @endif
            </div>

            <div>
                <strong>Support</strong>
                @if($storeEmail)
                    <a href="mailto:{{ $storeEmail }}">Contact Us</a>
                @else
                    <a href="#minimalShopFooter">Contact Us</a>
                @endif
                <a href="{{ $storefrontUrls->products($store) }}">Shipping</a>
                <a href="{{ route('cart.index', ['store' => $store->slug]) }}">Return</a>
                <a href="#minimalShopFooter">FAQ</a>
            </div>

            <div class="minimal-shop-social-block">
                <span>Social Media</span>
                <div class="minimal-shop-socials">
                    <a href="{{ $tiktokUrl }}" target="_blank" rel="noopener noreferrer" aria-label="X">X</a>
                    <a href="{{ $facebookUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook">f</a>
                    <a href="{{ $instagramUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram">ig</a>
                </div>
            </div>
        </div>

        <div class="minimal-shop-legal">
            <span>Copyright © {{ now()->year }} {{ $store->name }}. All Rights Reserved.</span>
            <span>
                <a href="#minimalShopFooter">Terms of Service</a>
                <a href="#minimalShopFooter">Privacy Policy</a>
            </span>
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

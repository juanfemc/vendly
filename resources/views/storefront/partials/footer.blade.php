@php
    $storeWhatsappUrl = $store->whatsappInfoUrl();
    $storeFooterCopy = trim((string) $store->shop_copy);
    $storeEmail = $store->user?->email;
@endphp

<footer class="footer">
    <div class="shell">
        <div class="store-footer-main">
            <div class="store-footer-brand">
                <div class="store-footer-logo">
                    @if($store->logo_image)
                        <img src="{{ asset('storage/' . $store->logo_image) }}" alt="{{ $store->name }}">
                    @else
                        <span>{{ strtoupper(substr($store->name ?? 'T', 0, 1)) }}</span>
                    @endif
                </div>

                <div>
                    <strong>{{ $store->name }}</strong>
                    @if($storeFooterCopy !== '')
                        <p>{{ $storeFooterCopy }}</p>
                    @endif
                </div>
            </div>

            <div class="store-footer-contact">
                <strong>Contacto</strong>
                @if($store->whatsapp)
                    <a href="{{ $storeWhatsappUrl ?: '#' }}" target="_blank" rel="noopener noreferrer" class="store-footer-contact-item">
                        <img src="{{ asset('images/icons/icon-contacto.png') }}" alt="" aria-hidden="true">
                        <span>{{ $store->whatsapp }}</span>
                    </a>
                @endif
                @if($store->location)
                    <span class="store-footer-contact-item">
                        <img src="{{ asset('images/icons/icon-ubicacion.png') }}" alt="" aria-hidden="true">
                        <span>{{ $store->location }}</span>
                    </span>
                @endif
                @if($storeEmail)
                    <a href="mailto:{{ $storeEmail }}" class="store-footer-contact-item">
                        <img src="{{ asset('images/icons/icon-mail.png') }}" alt="" aria-hidden="true">
                        <span>{{ $storeEmail }}</span>
                    </a>
                @endif
            </div>
        </div>

        <div class="socials">
            <a href="{{ $instagramUrl }}" class="social-link" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="3" y="3" width="18" height="18" rx="5"></rect>
                    <circle cx="12" cy="12" r="4.2"></circle>
                    <circle cx="17.4" cy="6.6" r="1.2" class="social-icon-fill"></circle>
                </svg>
            </a>
            <a href="{{ $facebookUrl }}" class="social-link" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M13.5 21v-7h2.6l.4-3h-3v-1.9c0-.9.3-1.6 1.7-1.6H16.7V5c-.3 0-1.2-.1-2.3-.1-2.3 0-3.9 1.4-3.9 4V11H8v3h2.5v7h3z" class="social-icon-fill"></path>
                </svg>
            </a>
            <a href="{{ $tiktokUrl }}" class="social-link" target="_blank" rel="noopener noreferrer" aria-label="TikTok">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M14.6 4c.4 1.8 1.5 3.1 3.4 3.6v2.7c-1.2 0-2.4-.4-3.4-1.1v5.2a4.8 4.8 0 1 1-4.8-4.8c.3 0 .6 0 .9.1v2.8a2.2 2.2 0 1 0 1.3 2V4h2.6z" class="social-icon-fill"></path>
                </svg>
            </a>
        </div>

        <p class="store-footer-credit">
            Desarrollado por <a href="https://vendlysuite.com" target="_blank" rel="noopener noreferrer">vendlysuite.com</a>
        </p>
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

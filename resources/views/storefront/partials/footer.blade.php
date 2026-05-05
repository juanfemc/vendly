@php
    $storeWhatsappUrl = $store->whatsappInfoUrl();
@endphp

<footer class="footer">
    <div class="shell">
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
            Diseñado y desarrollado por <a href="https://vendlysuite.com" target="_blank" rel="noopener noreferrer">vendlysuite.com</a>
        </p>
    </div>
</footer>

@if($storeWhatsappUrl)
    <a
        href="{{ $storeWhatsappUrl }}"
        class="store-whatsapp-float"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Pedir mas información por WhatsApp"
    >
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M20.5 11.8a8.5 8.5 0 0 1-12.7 7.4L4 20.2l1-3.7a8.5 8.5 0 1 1 15.5-4.7Z"></path>
            <path d="M8.9 8.6c.2-.5.4-.5.7-.5h.5c.2 0 .4 0 .6.4l.8 1.9c.1.3.1.5-.1.7l-.4.5c-.2.2-.2.4 0 .7.4.7 1 1.3 1.6 1.8.7.5 1.2.7 1.5.5l.7-.8c.2-.2.4-.2.7-.1l1.8.9c.3.1.4.4.4.6 0 .5-.2 1.2-.6 1.5-.4.4-1.3.7-2.7.3-1.5-.4-3.1-1.3-4.4-2.6-1.4-1.4-2.2-3-2.5-4.3-.2-.8.1-1.3.4-1.7Z"></path>
        </svg>
        <span>Mas información</span>
    </a>
@endif

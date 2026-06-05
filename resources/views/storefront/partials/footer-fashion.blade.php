@php
    $storeWhatsappUrl = $store->whatsappInfoUrl();
    $storeFooterCopy = trim((string) $store->shop_copy);
    $footerDescription = $storeFooterCopy !== ''
        ? $storeFooterCopy
        : 'Modern styles for every lifestyle. Discover quality and comfort in one place.';
@endphp

<footer class="fashion-footer">
    <div class="fashion-footer-shell">
        <div class="fashion-footer-main">
            <section class="fashion-footer-brand" aria-label="{{ $store->name }}">
                <h2>{{ $store->name }}</h2>
                <p>{{ $footerDescription }}</p>

                <div class="fashion-footer-socials" aria-label="Redes sociales">
                    <a href="{{ $instagramUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="4" y="4" width="16" height="16" rx="4"></rect>
                            <circle cx="12" cy="12" r="3.5"></circle>
                            <circle cx="17" cy="7" r="1"></circle>
                        </svg>
                    </a>
                    <a href="{{ $facebookUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M14 8.6h2.1V5.1c-.4-.1-1.7-.2-3.1-.2-3 0-5 1.8-5 5.2v2.9H4.7v4H8v8h4v-8h3.3l.5-4H12v-2.5c0-1.1.3-1.9 2-1.9Z"></path>
                        </svg>
                    </a>
                    <a href="{{ $tiktokUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Twitter">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M21.5 6.5c-.7.3-1.4.5-2.2.6.8-.5 1.3-1.2 1.6-2.1-.7.4-1.6.8-2.5.9A3.8 3.8 0 0 0 11.8 9v.9A10.7 10.7 0 0 1 4 6a3.8 3.8 0 0 0 1.2 5.1c-.6 0-1.2-.2-1.7-.5 0 1.8 1.3 3.3 3 3.7-.5.1-1.1.2-1.7.1.5 1.5 2 2.7 3.7 2.7A7.7 7.7 0 0 1 3 18.8 10.8 10.8 0 0 0 8.8 20c7 0 10.9-5.8 10.9-10.9v-.5c.7-.6 1.3-1.3 1.8-2.1Z"></path>
                        </svg>
                    </a>
                    <a href="{{ $storefrontUrls->products($store) }}" aria-label="Pinterest">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12.2 3.2a8.7 8.7 0 0 0-3.1 16.8c-.1-.7-.2-1.7 0-2.5l1-4.2s-.3-.6-.3-1.5c0-1.4.8-2.4 1.8-2.4.9 0 1.3.6 1.3 1.4 0 .9-.6 2.2-.9 3.4-.2 1 .5 1.8 1.6 1.8 1.9 0 3.4-2 3.4-5 0-2.6-1.9-4.4-4.6-4.4-3.1 0-5 2.3-5 4.8 0 .9.4 1.9.8 2.4.1.1.1.2.1.4l-.3 1.1c0 .2-.2.3-.4.2-1.3-.6-2.1-2.4-2.1-3.9 0-3.2 2.3-6.1 6.7-6.1 3.5 0 6.2 2.5 6.2 5.8 0 3.5-2.2 6.3-5.3 6.3-1 0-2-.5-2.4-1.1l-.6 2.4c-.2.9-.8 1.9-1.2 2.6.9.3 1.9.5 2.9.5a8.7 8.7 0 0 0-.1-17.4Z"></path>
                        </svg>
                    </a>
                </div>
            </section>

            <nav class="fashion-footer-column" aria-label="Shop">
                <h3>Shop</h3>
                <a href="{{ $storefrontUrls->products($store) }}">All Products</a>
                <a href="{{ $storefrontUrls->home($store) }}#catalogo">New Arrivals</a>
                <a href="{{ $storefrontUrls->products($store) }}">Best Sellers</a>
                <a href="{{ $storefrontUrls->offers($store) }}">Sale</a>
                <a href="{{ $storefrontUrls->products($store) }}">Accessories</a>
            </nav>

            <nav class="fashion-footer-column" aria-label="Customer Care">
                <h3>Customer Care</h3>
                <a href="{{ $storeWhatsappUrl ?: $storefrontUrls->home($store) }}">Contact Us</a>
                <a href="{{ $storefrontUrls->home($store) }}">Shipping & Returns</a>
                <a href="{{ $storefrontUrls->home($store) }}">FAQs</a>
                <a href="{{ $storefrontUrls->products($store) }}">Size Guide</a>
                <a href="{{ $storefrontUrls->home($store) }}">Secure Payments</a>
            </nav>

            <nav class="fashion-footer-column" aria-label="About Us">
                <h3>About Us</h3>
                <a href="{{ $storefrontUrls->about($store) }}">Our Story</a>
                <a href="{{ $storefrontUrls->home($store) }}">Careers</a>
                <a href="{{ $storefrontUrls->home($store) }}">Press</a>
                <a href="{{ $storefrontUrls->home($store) }}">Sustainability</a>
            </nav>

            <section class="fashion-footer-newsletter">
                <h3>Newsletter</h3>
                <p>Subscribe and get 10% off your first order.</p>
                <form action="{{ $storeWhatsappUrl ?: $storefrontUrls->home($store) }}" method="get">
                    <input type="email" name="email" placeholder="Enter your email" aria-label="Enter your email">
                    <button type="submit">Subscribe</button>
                </form>
            </section>
        </div>

        <div class="fashion-footer-bottom">
            <p>&copy; 2024 {{ $store->name }}. All rights reserved.</p>
            <nav aria-label="Legal">
                <a href="{{ $storefrontUrls->home($store) }}">Privacy Policy</a>
                <a href="{{ $storefrontUrls->home($store) }}">Terms of Service</a>
                <a href="{{ $storefrontUrls->home($store) }}">Cookie Policy</a>
            </nav>
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

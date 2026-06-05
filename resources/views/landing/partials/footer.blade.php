<footer class="landing-footer">
    <div class="landing-shell footer-grid">
        <div>
            <a href="/" class="brand-link">
                <img src="{{ asset('images/vendly-whatsapp-dark.png') }}" alt="Vendly">
                <span>vendly</span>
            </a>
            <p>La plataforma creada para vender mas por WhatsApp.</p>
        </div>
        <div>
            <strong>Siguenos</strong>
            <div class="footer-socials">
                @foreach([
                    ['name' => 'Instagram', 'url' => config('services.landing_social.instagram'), 'icon' => 'instagram.png'],
                    ['name' => 'Facebook', 'url' => config('services.landing_social.facebook'), 'icon' => 'facebook.png'],
                    ['name' => 'TikTok', 'url' => config('services.landing_social.tiktok'), 'icon' => 'tiktok.png'],
                ] as $social)
                    <a
                        href="{{ $social['url'] ?: '#' }}"
                        aria-label="{{ $social['name'] }}"
                        @class(['is-disabled' => ! $social['url']])
                        @if($social['url']) target="_blank" rel="noopener noreferrer" @else aria-disabled="true" @endif
                    >
                        <img src="{{ asset('images/landing/social/' . $social['icon']) }}" alt="" loading="lazy">
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</footer>

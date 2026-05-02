<section class="landing-section landing-section--soft" id="portafolio">
    <div class="landing-shell">
        <div class="section-kicker">Ejemplos</div>
        <div class="section-head section-head--row">
            <h2>Tiendas reales que ya venden con presencia propia.</h2>
            <p>Mira cómo puede verse tu negocio online: portada, catálogo, categorías, carrito y contacto directo para cerrar pedidos.</p>
        </div>

        <div class="portfolio-grid">
            @foreach($portfolioStores as $portfolioStore)
                @php($portfolioImage = $portfolioStore->cover_image ?: $portfolioStore->logo_image)
                <a href="{{ url('/' . $portfolioStore->slug) }}" class="card portfolio-card" target="_blank" rel="noopener noreferrer">
                    <div class="portfolio-media">
                        <img
                            src="{{ $portfolioImage ? asset('storage/' . $portfolioImage) : asset('images/vendly-logo.svg') }}"
                            alt="{{ $portfolioStore->name }}"
                            loading="lazy"
                            decoding="async"
                        >
                    </div>
                    <div class="portfolio-copy">
                        <span>{{ $portfolioStore->businessTypeLabel() }}</span>
                        <strong>{{ $portfolioStore->name }}</strong>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>

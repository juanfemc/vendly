<section class="landing-section portfolio-section" id="portafolio">
    <div class="landing-shell">
        <div class="proof-strip">
            <div>
                <p>Mas de 50 negocios ya venden mas con Vendly</p>
                <div class="proof-logos">
                    @foreach($proofStores as $proofStore)
                        <a
                            href="{{ url('/' . $proofStore->slug) }}"
                            aria-label="Visitar {{ $proofStore->name }}"
                            title="{{ $proofStore->name }}"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <img
                                src="{{ asset('storage/' . $proofStore->logo_image) }}"
                                alt="Logo de {{ $proofStore->name }}"
                                loading="lazy"
                                decoding="async"
                            >
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="proof-metrics">
                <strong>+50 <span>Tiendas activas</span></strong>
                <strong>+20K <span>Pedidos procesados</span></strong>
                <strong>+35% <span>Aumento promedio en ventas</span></strong>
            </div>
        </div>

        @if($hasPortfolio)
            <div class="section-head section-head--center">
                <span class="section-kicker">Ejemplos</span>
                <h2>Tiendas reales que ya venden con presencia propia.</h2>
                <p>Mira como puede verse tu negocio online: portada, catalogo, categorias, carrito y contacto directo para cerrar pedidos.</p>
            </div>

            <div class="portfolio-grid">
                @foreach($portfolioStores as $portfolioStore)
                    @php($portfolioImage = $portfolioStore->cover_image ?: $portfolioStore->logo_image)
                    <a href="{{ url('/' . $portfolioStore->slug) }}" class="portfolio-card" target="_blank" rel="noopener noreferrer">
                        <div class="portfolio-media">
                            <img
                                src="{{ $portfolioImage ? asset('storage/' . $portfolioImage) : asset('images/vendly-whatsapp-dark.png') }}"
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
        @endif
    </div>
</section>

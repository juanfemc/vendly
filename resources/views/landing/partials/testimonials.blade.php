<section class="landing-section testimonials-section" id="testimonios">
    <div class="landing-shell">
        <div class="section-head testimonials-head">
            <span class="section-kicker">Testimonios</span>
            <h2>Negocios que ya se ven mas profesionales online.</h2>
            <p>Historias cortas de tiendas que ordenaron su catalogo, compartieron un mejor enlace y recibieron pedidos mas claros.</p>
        </div>

        <div class="testimonial-grid">
            @foreach($testimonials as $testimonial)
                <article class="card testimonial-card">
                    <div class="testimonial-person">
                        <span>{{ $testimonial->initials }}</span>
                        <div>
                            <strong>{{ $testimonial->name }}</strong>
                            <small>{{ $testimonial->role }}</small>
                        </div>
                    </div>
                    <p>"{{ $testimonial->quote }}"</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

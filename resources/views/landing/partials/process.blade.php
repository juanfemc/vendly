<section class="landing-section process-section" id="proceso">
    <div class="landing-shell">
        <div class="section-head section-head--center">
            <h2>Cómo funciona</h2>
            <p>Tu tienda lista en minutos, ventas para siempre.</p>
        </div>

        <div class="steps">
            @foreach($steps as $step)
                <article class="step-card">
                    <span>{{ $step['number'] }}</span>
                    <strong>{{ $step['title'] }}</strong>
                    <p>{{ $step['copy'] }}</p>
                </article>
            @endforeach
        </div>

        <div class="catalog-preview">
            <div class="catalog-copy">
                <div class="catalog-copy-main">
                    <span class="section-kicker">Tu tienda, en todos lados</span>
                    <h2>Catálogo moderno. Experiencia simple. <strong>Ventas que crecen.</strong></h2>
                    <a href="#portafolio" class="btn btn--ghost">Ver ejemplos de tiendas</a>
                </div>
                <ul>
                    <li>Catálogo moderno y fácil de navegar</li>
                    <li>Carrito y pedidos por WhatsApp</li>
                    <li>Envíos, métodos de pago y más</li>
                    <li>Optimizado para móvil y redes sociales</li>
                </ul>
            </div>
        </div>
    </div>
</section>

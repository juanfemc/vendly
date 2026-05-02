<section class="landing-section" id="funciones">
    <div class="landing-shell">
        <div class="section-kicker">Ventajas</div>
        <div class="section-head">
            <h2>Una tienda pensada para que el cliente compre sin perderse.</h2>
            <p>Diseñamos una experiencia simple: tus productos lucen mejor, el pedido fluye rápido y el cliente sabe exactamente cómo comprar.</p>
        </div>

        <div class="feature-grid">
            @foreach($features as $feature)
                <article class="card feature-card">
                    <span>{{ $feature['number'] }}</span>
                    <h3>{{ $feature['title'] }}</h3>
                    <p>{{ $feature['copy'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="landing-section" id="funciones">
    <div class="landing-shell">
        <div class="section-head section-head--center">
            <h2>Todo lo que necesitas para vender mas</h2>
        </div>

        <div class="feature-grid">
            @foreach($features as $feature)
                <article class="feature-card">
                    <span>{{ $feature['tag'] }}</span>
                    <h3>{{ $feature['title'] }}</h3>
                    <p>{{ $feature['copy'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="landing-section plans-section" id="planes">
    <div class="landing-shell">
        <div class="section-head section-head--row">
            <div>
                <span class="section-kicker">Planes</span>
                <h2>Elige como quieres empezar</h2>
            </div>
            <p>El plan Básico queda pensado para empezar simple. Pro suma más catálogo y herramientas para vender mejor. Premium agrega personalización, analítica y prioridad.</p>
        </div>

        <div class="plans-grid">
            @foreach($plans as $plan)
                <article class="plan-card card {{ ($plan['name'] ?? '') === 'Pro' ? 'plan-card--featured' : '' }}">
                    <div class="plan-card-head">
                        <div>
                            <span class="plan-eyebrow">{{ $plan['eyebrow'] }}</span>
                            <h3>{{ $plan['name'] }}</h3>
                        </div>

                        @if(! empty($plan['badge']))
                            <span class="plan-badge">{{ $plan['badge'] }}</span>
                        @endif
                    </div>

                    @if(! empty($plan['summary']))
                        <p class="plan-summary">{{ $plan['summary'] }}</p>
                    @endif

                    @if(! empty($plan['features']))
                        <ul class="plan-features">
                            @foreach($plan['features'] as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="plan-pending">Funciones por definir.</p>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>

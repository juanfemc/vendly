<section class="landing-section plans-section" id="planes">
    <div class="landing-shell">
        <div class="section-head section-head--center">
            <h2>Planes simples. Sin letras pequeñas.</h2>
        </div>

        <div class="plans-grid">
            @foreach($plans as $plan)
                <article class="plan-card {{ ($plan['name'] ?? '') === 'Pro' ? 'plan-card--featured' : '' }}">
                    <div class="plan-card-head">
                        <div>
                            <h3>{{ $plan['name'] }}</h3>
                            <span>{{ $plan['summary'] }}</span>
                        </div>

                        @if(! empty($plan['badge']))
                            <span class="plan-badge">{{ $plan['badge'] }}</span>
                        @endif
                    </div>

                    <div class="plan-price">
                        <strong>{{ $plan['price'] }}</strong>
                        <span>/mes</span>
                    </div>

                    @if(! empty($plan['features']))
                        <ul class="plan-features">
                            @foreach($plan['features'] as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="plan-pending">Funciones por definir.</p>
                    @endif

                    <a href="{{ route('trial-signup.create') }}" class="btn {{ ($plan['name'] ?? '') === 'Pro' ? 'btn--primary' : 'btn--dark' }}">
                        {{ $plan['button'] }}
                    </a>
                </article>
            @endforeach
        </div>
    </div>
</section>

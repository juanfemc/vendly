<header class="landing-nav">
    <div class="landing-shell landing-nav-inner">
        <a href="/" class="brand-link" aria-label="Vendly">
            <img src="{{ asset('images/vendly-logo.svg') }}" alt="Vendly">
            <span>Vendly</span>
        </a>

        <nav class="landing-menu" aria-label="Navegación principal">
            @if($hasPortfolio)
                <a href="#portafolio">Portafolio</a>
            @endif
            <a href="#funciones">Ventajas</a>
            @if($hasTestimonials)
                <a href="#testimonios">Testimonios</a>
            @endif
            <a href="#proceso">Cómo funciona</a>
            <a href="#contacto">Contacto</a>
        </nav>

        <a href="{{ route('login') }}" class="btn btn--dark btn--sm">Acceso</a>
    </div>
</header>

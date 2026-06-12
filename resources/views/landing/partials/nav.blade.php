<input class="landing-mobile-toggle" type="checkbox" id="landing-mobile-menu" aria-hidden="true">

<header class="landing-nav">
    <div class="landing-shell landing-nav-inner">
        <label class="landing-menu-open" for="landing-mobile-menu" aria-label="Abrir menu">
            <span></span>
        </label>

        <a href="/" class="brand-link" aria-label="Vendly">
            <img src="{{ asset('images/vendly-whatsapp-dark.png') }}" alt="Vendly">
            <span>vendly</span>
        </a>

        <nav class="landing-menu" aria-label="Navegacion principal">
            <a href="#producto">Producto</a>
            <a href="#funciones">Funciones</a>
            <a href="#planes">Precios</a>
            <a href="#portafolio">Portafolio</a>
            <a href="#contacto">Contacto</a>
        </nav>

        <div class="landing-nav-actions">
            <a href="{{ route('login') }}" class="login-link">Iniciar sesion</a>
            <a href="{{ route('trial-signup.create') }}" class="btn btn--primary btn--sm" data-meta-event="Lead">Crear mi tienda gratis</a>
        </div>
    </div>
</header>

<label class="landing-mobile-backdrop" for="landing-mobile-menu" aria-label="Cerrar menu"></label>

<aside class="landing-mobile-drawer" aria-label="Menu movil">
    <div class="mobile-drawer-head">
        <a href="/" class="brand-link" data-menu-close>
            <img src="{{ asset('images/vendly-whatsapp-dark.png') }}" alt="Vendly">
            <span>vendly</span>
        </a>
        <label class="landing-menu-close" for="landing-mobile-menu" aria-label="Cerrar menu"></label>
    </div>

    <nav class="mobile-drawer-menu">
        <a href="#producto" class="is-active" data-menu-close>
            <span class="drawer-icon" aria-hidden="true">⌂</span>
            <strong>Inicio</strong>
        </a>
        <a href="#producto" data-menu-close>
            <span class="drawer-icon" aria-hidden="true">▱</span>
            <strong>Producto</strong>
            <em aria-hidden="true">›</em>
        </a>
        <a href="#funciones" data-menu-close>
            <span class="drawer-icon" aria-hidden="true">☆</span>
            <strong>Funciones</strong>
        </a>
        <a href="#planes" data-menu-close>
            <span class="drawer-icon" aria-hidden="true">◇</span>
            <strong>Precios</strong>
        </a>
        <a href="#portafolio" data-menu-close>
            <span class="drawer-icon" aria-hidden="true">▣</span>
            <strong>Ejemplos</strong>
        </a>
        <a href="#contacto" data-menu-close>
            <span class="drawer-icon" aria-hidden="true">▤</span>
            <strong>Contacto</strong>
            <em aria-hidden="true">›</em>
        </a>
    </nav>

    <div class="mobile-drawer-actions">
        <a href="{{ route('login') }}" data-menu-close>
            <span class="drawer-icon" aria-hidden="true">♙</span>
            <strong>Iniciar sesión</strong>
        </a>
        <a href="{{ route('trial-signup.create') }}" class="mobile-drawer-cta" data-menu-close data-meta-event="Lead">
            <span class="drawer-icon" aria-hidden="true">□</span>
            <strong>Crear mi tienda gratis</strong>
        </a>
    </div>
</aside>

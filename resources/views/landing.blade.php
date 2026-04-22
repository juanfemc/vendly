<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendly</title>
    <meta name="description" content="Vendly te permite crear tiendas online para varios negocios, recibir pedidos con carrito, capturar datos del cliente y cerrar ventas por WhatsApp desde un solo sistema.">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/vendly-logo.svg') }}">
    <link rel="shortcut icon" href="{{ asset('images/vendly-logo.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
</head>
<body class="landing-page">
    <header class="landing-nav">
        <div class="landing-shell landing-nav-inner">
            <a href="/" class="brand-lockup" aria-label="Vendly">
                <img src="{{ asset('images/vendly-logo.svg') }}" alt="Vendly" class="brand-logo-image">
            </a>

            <div class="nav-actions">
                <div class="social-links" aria-label="Redes sociales de Vendly">
                    <a href="https://www.instagram.com/vendlysuite" class="social-button" aria-label="Instagram">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="3" y="3" width="18" height="18" rx="5"></rect>
                            <circle cx="12" cy="12" r="4"></circle>
                            <circle cx="17.5" cy="6.5" r="1"></circle>
                        </svg>
                    </a>
                    <a href="https://web.facebook.com/people/Vendly/61570873165766/" class="social-button" aria-label="Facebook">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M14 8h3V4h-3c-3 0-5 2-5 5v3H6v4h3v4h4v-4h3l1-4h-4V9c0-.6.4-1 1-1Z"></path>
                        </svg>
                    </a>
                </div>

                <a href="{{ route('login') }}" class="nav-cta">
                    Login
                </a>
            </div>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="landing-shell hero-content">
                <div class="eyebrow">Tu sistema para vender m&aacute;s</div>
                <h1>Tu tienda lista para vender por WhatsApp.</h1>
                <p class="hero-copy">
                    Vendly te permite crear tiendas online para varios negocios, recibir pedidos con carrito, capturar datos del cliente y cerrar ventas por WhatsApp desde un solo sistema.
                </p>

                <div class="hero-actions">
                    <a href="{{ route('login') }}" class="primary-cta">Entrar al sistema</a>
                    <a href="#funciones" class="secondary-cta">Ver funciones</a>
                </div>

                <div class="hero-social">
                    <span>S&iacute;guenos</span>
                    <div class="social-links social-links-large">
                        <a href="#" class="social-button" aria-label="Instagram">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <rect x="3" y="3" width="18" height="18" rx="5"></rect>
                                <circle cx="12" cy="12" r="4"></circle>
                                <circle cx="17.5" cy="6.5" r="1"></circle>
                            </svg>
                        </a>
                        <a href="#" class="social-button" aria-label="Facebook">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M14 8h3V4h-3c-3 0-5 2-5 5v3H6v4h3v4h4v-4h3l1-4h-4V9c0-.6.4-1 1-1Z"></path>
                            </svg>
                        </a>
                        <a href="#" class="social-button" aria-label="TikTok">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M14 4v10.2a4.2 4.2 0 1 1-3.4-4.1v3.2a1.2 1.2 0 1 0 .8 1.1V4h2.6c.4 2 2 3.5 4 3.8V11c-1.6-.1-3-.7-4-1.7Z"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="features" id="funciones">
            <div class="landing-shell">
                <div class="section-head">
                    <div>
                        <h2>Todo lo que necesita una tienda para empezar</h2>
                        <p>Desde el panel admin puedes crear tiendas, usuarios, banners y controlar la experiencia completa de cada negocio.</p>
                    </div>
                </div>

                <div class="features-grid">
                    <article class="feature-card">
                        <h3>Cat&aacute;logo por tienda</h3>
                        <p>Sube productos con im&aacute;genes, organiza la tienda y deja una experiencia visual m&aacute;s profesional para cada cliente.</p>
                    </article>

                    <article class="feature-card">
                        <h3>Pedidos por WhatsApp</h3>
                        <p>El cliente agrega al carrito, completa sus datos y el sistema genera el pedido para enviarlo directo por WhatsApp.</p>
                    </article>

                    <article class="feature-card">
                        <h3>Admin multi tienda</h3>
                        <p>Un solo sistema para administrar varias tiendas, usuarios, branding, banners y pedidos desde el mismo panel.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="bottom-cta">
            <div class="landing-shell">
                <div class="bottom-card">
                    <div>
                        <h2>Lanza tu tienda con Vendly.</h2>
                        <p>Entra al sistema, configura la tienda, sube productos y empieza a recibir pedidos hoy mismo.</p>
                    </div>

                    <a href="{{ route('login') }}" class="primary-cta">Login</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <div class="landing-shell footer-inner">
            <a href="/" class="footer-brand" aria-label="Vendly">
                <img src="{{ asset('images/vendly-logo.svg') }}" alt="Vendly">
                <span>Vendly</span>
            </a>

            <div class="footer-social">
                <span>Redes sociales</span>
                <div class="social-links">
                    <a href="#" class="social-button" aria-label="Instagram">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="3" y="3" width="18" height="18" rx="5"></rect>
                            <circle cx="12" cy="12" r="4"></circle>
                            <circle cx="17.5" cy="6.5" r="1"></circle>
                        </svg>
                    </a>
                    <a href="#" class="social-button" aria-label="Facebook">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M14 8h3V4h-3c-3 0-5 2-5 5v3H6v4h3v4h4v-4h3l1-4h-4V9c0-.6.4-1 1-1Z"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <a href="https://wa.me/573170613664" class="whatsapp-float" aria-label="Contactar por WhatsApp">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 3a8.7 8.7 0 0 0-7.3 13.4L4 21l4.8-1.2A8.7 8.7 0 1 0 12 3Zm0 2a6.7 6.7 0 0 1 0 13.4c-1.1 0-2.2-.3-3.1-.8l-.4-.2-1.9.5.5-1.8-.3-.4A6.7 6.7 0 0 1 12 5Zm-2.3 3.4c-.2 0-.5.1-.7.4-.2.3-.8.8-.8 2s.8 2.3.9 2.5c.1.2 1.6 2.5 3.9 3.4 1.9.8 2.3.6 2.7.6.4 0 1.4-.6 1.6-1.1.2-.5.2-1 .1-1.1l-.6-.3-1.6-.8c-.2-.1-.5-.1-.7.2l-.7.9c-.1.2-.3.2-.6.1-.3-.1-1.1-.4-2-1.2-.8-.7-1.3-1.5-1.4-1.8-.2-.3 0-.4.1-.6l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.7-1.6c-.2-.5-.4-.5-.6-.5h-.6Z"></path>
        </svg>
    </a>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 | Vendly</title>
    <link rel="stylesheet" href="{{ asset('css/errors.css') }}">
</head>
<body class="error-page">
    <header class="error-nav">
        <div class="error-shell error-nav-inner">
            <a href="/" class="brand-lockup" aria-label="Vendly">
                <img src="{{ asset('images/vendly-logo.svg') }}" alt="Vendly" class="brand-logo-image">
            </a>

            <a href="{{ route('login') }}" class="ghost-link">Login</a>
        </div>
    </header>

    <main class="error-main">
        <div class="error-shell error-grid">
            <section>
                <div class="error-eyebrow">Página no encontrada</div>
                <div class="error-code">404</div>
                <h1 class="error-title">Esta página no existe.</h1>
                <p class="error-copy">
                    Puede que el enlace haya cambiado, que la tienda ya no esté disponible o que la URL se haya escrito mal. Vamos a devolverte a un punto útil.
                </p>

                <div class="error-actions">
                    <a href="/" class="primary-link">Ir al inicio</a>
                    <a href="{{ route('login') }}" class="secondary-link">Entrar al sistema</a>
                </div>
            </section>

            <aside class="error-card">
                <h3>Mientras tanto</h3>
                <p>Vendly te ayuda a crear tiendas listas para vender con catálogo, carrito y pedidos por WhatsApp.</p>

                <div class="error-list">
                    <div class="error-list-item">
                        <strong>Revisa el dominio</strong>
                        <span>Confirma que la dirección esté escrita correctamente.</span>
                    </div>
                    <div class="error-list-item">
                        <strong>Vuelve al inicio</strong>
                        <span>Desde allí podrás entrar al sistema o explorar la plataforma.</span>
                    </div>
                    <div class="error-list-item">
                        <strong>Intenta más tarde</strong>
                        <span>Si la tienda cambió de ruta, pronto debería estar disponible otra vez.</span>
                    </div>
                </div>
            </aside>
        </div>
    </main>
</body>
</html>

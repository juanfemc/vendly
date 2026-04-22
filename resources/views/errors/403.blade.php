<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 | Vendly</title>
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
                <div class="error-eyebrow">Acceso restringido</div>
                <div class="error-code">403</div>
                <h1 class="error-title">No tienes permiso para entrar aquí.</h1>
                <p class="error-copy">
                    Esta sección está protegida o no pertenece a tu cuenta. Si crees que esto es un error, vuelve al panel o inicia sesión con el usuario correcto.
                </p>

                <div class="error-actions">
                    <a href="/dashboard" class="primary-link">Ir al dashboard</a>
                    <a href="/" class="secondary-link">Volver al inicio</a>
                </div>
            </section>

            <aside class="error-card">
                <h3>Qué puedes hacer</h3>
                <p>Vendly protege el acceso entre tiendas para que cada negocio solo vea y gestione lo suyo.</p>

                <div class="error-list">
                    <div class="error-list-item">
                        <strong>Verifica tu sesión</strong>
                        <span>Confirma que entraste con el usuario correcto.</span>
                    </div>
                    <div class="error-list-item">
                        <strong>Regresa al panel</strong>
                        <span>Desde allí puedes seguir administrando tu tienda.</span>
                    </div>
                    <div class="error-list-item">
                        <strong>Prueba otra ruta</strong>
                        <span>Puede que esta URL no corresponda a tu perfil.</span>
                    </div>
                </div>
            </aside>
        </div>
    </main>
</body>
</html>

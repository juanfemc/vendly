@php
    $portfolioStores = $portfolioStores ?? collect();
    $testimonials = $testimonials ?? collect();
    $hasPortfolio = $portfolioStores->isNotEmpty();
    $hasTestimonials = $testimonials->isNotEmpty();

    $features = [
        ['number' => '01', 'title' => 'Se ve como tu marca', 'copy' => 'Logo, portada, colores, banners y textos alineados con la personalidad de tu negocio.'],
        ['number' => '02', 'title' => 'Productos que venden', 'copy' => 'Fotos, precios, variantes, categorías y descripciones organizadas para que el cliente decida más fácil.'],
        ['number' => '03', 'title' => 'Pedido sin fricción', 'copy' => 'El cliente navega, arma su carrito y envía el pedido directo a tu WhatsApp.'],
        ['number' => '04', 'title' => 'Lista para crecer', 'copy' => 'Cuando tengas nuevos productos, promociones o cambios de marca, tu tienda puede actualizarse rápido.'],
    ];

    $steps = [
        ['title' => 'Armamos tu vitrina', 'copy' => 'Organizamos la portada, logo, colores, categorías y estructura principal de la tienda.'],
        ['title' => 'Cargamos tus productos', 'copy' => 'Mostramos tus productos con fotos, precios, variantes y textos que ayudan a comprar.'],
        ['title' => 'Compartes y vendes', 'copy' => 'Envías tu enlace en redes, estados o campañas y recibes pedidos ordenados por WhatsApp.'],
    ];

    $plans = [
        [
            'eyebrow' => 'Plan 01',
            'name' => 'Básico',
            'summary' => 'Para negocios que quieren empezar con una tienda simple y funcional.',
            'badge' => 'Inicio simple',
            'features' => [
                'Productos publicados',
                'Sin categorías',
                'Carrito por WhatsApp',
                'Logo y portada',
                'Personalización básica',
                'Sin avisos superiores',
                'Límite de 20 productos',
                'Soporte básico',
            ],
        ],
        [
            'eyebrow' => 'Plan 02',
            'name' => 'Pro',
            'summary' => 'Para tiendas que necesitan más catálogo, categorías y herramientas de venta.',
            'badge' => 'Recomendado',
            'features' => [
                'Productos publicados',
                'Categorías',
                'Carrito por WhatsApp',
                'Logo y portada',
                'Personalización completa',
                'Varios avisos superiores rotativos',
                'Estadística de visitas',
                'Galería de imágenes por producto',
                'Límite de 100 productos',
                'Soporte prioritario',
            ],
        ],
        [
            'eyebrow' => 'Plan 03',
            'name' => 'Premium',
            'summary' => 'Para marcas que quieren una experiencia más personalizada y medición avanzada.',
            'badge' => 'Más completo',
            'features' => [
                'Todo lo del plan Pro',
                'Diseño personalizado',
                'Dominio personalizado',
                'Pixel / Analytics',
                'Cupones o promociones avanzadas',
                'Reportes avanzados',
                'Prioridad de soporte',
                'Primeros en ver actualizaciones del sistema',
            ],
        ],
    ];

@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendly | Tiendas online listas para vender</title>
    <meta name="description" content="Vendly crea tiendas online profesionales para negocios que quieren mostrar productos, recibir pedidos y vender por WhatsApp.">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/vendly-logo.svg') }}">
    <link rel="shortcut icon" href="{{ asset('images/vendly-logo.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ filemtime(public_path('css/landing.css')) }}">
</head>
<body class="landing-page">
    @include('landing.partials.nav', ['hasPortfolio' => $hasPortfolio, 'hasTestimonials' => $hasTestimonials])

    <main>
        @include('landing.partials.hero', ['hasPortfolio' => $hasPortfolio])
        @includeWhen($hasPortfolio, 'landing.partials.portfolio', ['portfolioStores' => $portfolioStores])
        @include('landing.partials.features', ['features' => $features])
        @includeWhen($hasTestimonials, 'landing.partials.testimonials', ['testimonials' => $testimonials])
        @include('landing.partials.plans', ['plans' => $plans])
        @include('landing.partials.process', ['steps' => $steps])
        @include('landing.partials.cta')
    </main>

    @include('landing.partials.footer')
    @include('landing.partials.whatsapp')
</body>
</html>

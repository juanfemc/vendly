@php
    $portfolioStores = $portfolioStores ?? collect();
    $proofStores = $proofStores ?? collect();
    $testimonials = $testimonials ?? collect();
    $hasPortfolio = $portfolioStores->isNotEmpty();
    $hasTestimonials = $testimonials->isNotEmpty();
    $landingTitle = 'Vendly | Tiendas online listas para vender';
    $landingDescription = 'Vendly crea tiendas online profesionales para negocios que quieren mostrar productos, recibir pedidos y vender por WhatsApp.';
    $landingImage = asset('images/vendly-whatsapp-dark.png');
    $landingWhatsappUrl = 'https://wa.me/573170613664?text=' . rawurlencode('Hola, vengo desde la landing page de Vendly y quiero más información para crear mi tienda.');
    $landingCssPath = public_path('css/landing.css');
    $landingJsPath = public_path('js/landing.js');
    $landingCssVersion = file_exists($landingCssPath) ? filemtime($landingCssPath) : null;
    $landingJsVersion = file_exists($landingJsPath) ? filemtime($landingJsPath) : null;

    $features = [
        ['tag' => 'ICONO CATALOGO', 'title' => 'Catalogo profesional', 'copy' => 'Muestra tus productos con estilo y genera confianza desde el primer vistazo.'],
        ['tag' => 'LOGO WHATSAPP', 'title' => 'Pedidos por WhatsApp', 'copy' => 'Tus clientes compran por el canal que ya usan todos los dias.'],
        ['tag' => 'ICONO IA', 'title' => 'IA que te ayuda a vender', 'copy' => 'Genera descripciones, titulos, etiquetas y avisos para impulsar tu tienda.'],
        ['tag' => 'ICONO PANEL', 'title' => 'Panel inteligente', 'copy' => 'Gestiona pedidos, productos, clientes y estadisticas desde un solo lugar.'],
        ['tag' => 'ICONO COMPARTIR', 'title' => 'Comparte sin limites', 'copy' => 'Comparte tu tienda en redes, WhatsApp, Instagram y campanas.'],
    ];

    $steps = [
        ['number' => '1', 'title' => 'Crea tu tienda', 'copy' => 'Registra tu negocio y configura tu catalogo en minutos.'],
        ['number' => '2', 'title' => 'Comparte por WhatsApp', 'copy' => 'Envia tu catalogo a clientes y empieza a recibir pedidos claros.'],
        ['number' => '3', 'title' => 'Gestiona y vende mas', 'copy' => 'Administra pedidos, productos y clientes desde tu panel.'],
    ];

    $plans = [
        [
            'eyebrow' => 'Plan 01',
            'name' => 'Básico',
            'summary' => 'Ideal para empezar',
            'badge' => 'Inicio simple',
            'price' => 'S/ 0',
            'button' => 'Comenzar gratis',
            'features' => [
                '1 tienda',
                'Catálogo básico',
                'Productos publicados',
                'Sin categorías',
                'Pedidos por WhatsApp',
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
            'summary' => 'Para negocios en crecimiento',
            'badge' => 'Mas popular',
            'price' => 'S/ 39',
            'button' => 'Probar Pro gratis',
            'features' => [
                'Todo lo del Starter',
                'Todo lo del Basico',
                'Catálogo ilimitado',
                'Categorías',
                'Panel avanzado',
                'Descuentos y cupones',
                'Envíos y métodos de pago',
                'Varios avisos superiores rotativos',
                'Estadística de visitas',
                'Galería de imágenes por producto',
                'Límite de 100 productos',
                'Personalización completa',
                'Soporte prioritario',
            ],
        ],
        [
            'eyebrow' => 'Plan 03',
            'name' => 'Premium',
            'summary' => 'Para marcas que escalan',
            'badge' => 'Más completo',
            'price' => 'S/ 79',
            'button' => 'Probar Premium',
            'features' => [
                'Todo lo del plan Pro',
                'Múltiples tiendas',
                'Integraciones avanzadas',
                'IA avanzada',
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
    <title>{{ $landingTitle }}</title>
    <meta name="description" content="{{ $landingDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $landingTitle }}">
    <meta property="og:description" content="{{ $landingDescription }}">
    <meta property="og:image" content="{{ $landingImage }}">
    <meta property="og:url" content="{{ url('/') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $landingTitle }}">
    <meta name="twitter:description" content="{{ $landingDescription }}">
    <meta name="twitter:image" content="{{ $landingImage }}">
    <link rel="icon" type="image/png" href="{{ asset('images/vendly-whatsapp-dark.png') }}">
    <link rel="shortcut icon" href="{{ asset('images/vendly-whatsapp-dark.png') }}">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}{{ $landingCssVersion ? '?v=' . $landingCssVersion : '' }}">
</head>
<body class="landing-page">
    @include('landing.partials.nav')

    <main>
        @include('landing.partials.hero', ['hasPortfolio' => $hasPortfolio])
        @include('landing.partials.portfolio', ['portfolioStores' => $portfolioStores, 'proofStores' => $proofStores, 'hasPortfolio' => $hasPortfolio])
        @include('landing.partials.process', ['steps' => $steps])
        @include('landing.partials.features', ['features' => $features])
        @include('landing.partials.plans', ['plans' => $plans])
        @includeWhen($hasTestimonials, 'landing.partials.testimonials', ['testimonials' => $testimonials])
        @include('landing.partials.cta')
    </main>

    @include('landing.partials.footer')
    @include('landing.partials.whatsapp')
    <script src="{{ asset('js/landing.js') }}{{ $landingJsVersion ? '?v=' . $landingJsVersion : '' }}" defer></script>
</body>
</html>

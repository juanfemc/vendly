<?php

use App\Models\Store;
use App\Models\AdminUpdate;
use App\Models\ColombiaLocation;
use App\Models\LandingTestimonial;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\StoreBanner;
use App\Models\StoreCategory;
use App\Models\StorePaymentAccount;
use App\Models\StoreVisit;
use App\Models\User;
use App\Services\AdminUpdateService;
use App\Services\StoreSubdomainService;
use App\Services\StorefrontUrlService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

test('public store is hidden when owner account is expired', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDays(10),
        'active_ends_at' => now()->subDay(),
    ]);

    Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda vencida',
        'slug' => 'tienda-vencida',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $this->get('/tienda-vencida')->assertNotFound();
});

test('landing page renders without compiled assets', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Vendly | Tiendas online listas para vender')
        ->assertSee('css/landing.css', false)
        ->assertSee('Planes')
        ->assertSee('Básico')
        ->assertSee('Pro')
        ->assertSee('Premium')
        ->assertSee('Productos publicados')
        ->assertSee('Carrito por WhatsApp')
        ->assertSee('Logo y portada')
        ->assertSee('Personalización básica')
        ->assertSee('Límite de 20 productos')
        ->assertSee('Límite de 100 productos')
        ->assertSee('Sin categorías')
        ->assertSee('Categorías')
        ->assertSee('Sin avisos superiores')
        ->assertSee('Varios avisos superiores rotativos')
        ->assertSee('Estadística de visitas')
        ->assertSee('Galería de imágenes por producto')
        ->assertSee('Personalización completa')
        ->assertSee('Soporte básico')
        ->assertSee('Soporte prioritario')
        ->assertSee('Todo lo del plan Pro')
        ->assertSee('Diseño personalizado')
        ->assertSee('Dominio personalizado')
        ->assertSee('Pixel / Analytics')
        ->assertSee('Cupones o promociones avanzadas')
        ->assertSee('Reportes avanzados')
        ->assertSee('Prioridad de soporte')
        ->assertSee('Primeros en ver actualizaciones del sistema')
        ->assertDontSee('Funciones por definir.')
        ->assertSee('Quiero mi tienda')
        ->assertSee('Testimonios')
        ->assertSee('Negocios que ya se ven más profesionales online.')
        ->assertSee(route('login'), false);
});

test('landing page shows the three most visited public stores as portfolio', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    foreach ([
        ['Tienda Uno', 'tienda-uno', 40],
        ['Tienda Dos', 'tienda-dos', 30],
        ['Tienda Tres', 'tienda-tres', 20],
        ['Tienda Cuatro', 'tienda-cuatro', 10],
    ] as [$name, $slug, $views]) {
        Store::create([
            'user_id' => $user->id,
            'name' => $name,
            'slug' => $slug,
            'whatsapp' => '573001112233',
            'is_active' => true,
            'views_count' => $views,
        ]);
    }

    $this->get('/')
        ->assertOk()
        ->assertSee('Portafolio')
        ->assertSee('Tiendas reales que ya venden con presencia propia.')
        ->assertSee('Tienda Uno')
        ->assertSee('Tienda Dos')
        ->assertSee('Tienda Tres')
        ->assertDontSee('40 visitas')
        ->assertDontSee('Tienda Cuatro');
});

test('landing page only shows active testimonials', function () {
    LandingTestimonial::query()->delete();

    LandingTestimonial::create([
        'name' => 'Cliente Activo',
        'role' => 'Moda',
        'initials' => 'CA',
        'quote' => 'Mi tienda se ve mas profesional.',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    LandingTestimonial::create([
        'name' => 'Cliente Inactivo',
        'role' => 'Belleza',
        'initials' => 'CI',
        'quote' => 'Este testimonio no debe salir.',
        'is_active' => false,
        'sort_order' => 2,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Testimonios')
        ->assertSee('Cliente Activo')
        ->assertSee('Mi tienda se ve mas profesional.')
        ->assertDontSee('Cliente Inactivo')
        ->assertDontSee('Este testimonio no debe salir.');
});

test('admin can create edit toggle and delete landing testimonials', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.testimonials.index'))
        ->assertOk()
        ->assertSee('Testimonios')
        ->assertSee('Crear testimonio');

    $this->actingAs($admin)
        ->post(route('admin.testimonials.store'), [
            'name' => 'Maria Gomez',
            'role' => 'Hogar',
            'initials' => 'MG',
            'quote' => 'Ahora puedo compartir mi catalogo mas facil.',
            'sort_order' => 9,
            'is_active' => '1',
        ])
        ->assertRedirect('/admin/testimonials');

    $testimonial = LandingTestimonial::where('name', 'Maria Gomez')->firstOrFail();

    $this->assertDatabaseHas('landing_testimonials', [
        'id' => $testimonial->id,
        'quote' => 'Ahora puedo compartir mi catalogo mas facil.',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.testimonials.update', $testimonial), [
            'name' => 'Maria Gomez Editada',
            'role' => 'Hogar',
            'initials' => 'MG',
            'quote' => 'Mis clientes entienden mejor lo que vendo.',
            'sort_order' => 3,
            'is_active' => '1',
        ])
        ->assertRedirect('/admin/testimonials');

    $testimonial->refresh();

    $this->assertSame('Maria Gomez Editada', $testimonial->name);
    $this->assertSame(3, $testimonial->sort_order);

    $this->actingAs($admin)
        ->patch(route('admin.testimonials.toggle', $testimonial))
        ->assertRedirect('/admin/testimonials');

    $this->assertFalse($testimonial->refresh()->is_active);

    $this->actingAs($admin)
        ->delete(route('admin.testimonials.destroy', $testimonial))
        ->assertRedirect('/admin/testimonials');

    $this->assertDatabaseMissing('landing_testimonials', ['id' => $testimonial->id]);
});

test('public store is visible when store and owner are active', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda activa',
        'slug' => 'tienda-activa',
        'whatsapp' => '573001112233',
        'location' => 'Calle 45 #10-20, Bogota',
        'shop_copy' => 'Tienda online con atencion cercana.',
        'is_active' => true,
    ]);

    $this->get('/tienda-activa')
        ->assertOk()
        ->assertSee(route('store.products.index', 'tienda-activa'), false)
        ->assertSee('Productos')
        ->assertSee('https://wa.me/573001112233', false)
        ->assertSee('images/icons/icon-whatsapp.png', false)
        ->assertSee('Contactar por WhatsApp')
        ->assertSee('Tienda online con atencion cercana.')
        ->assertSee('Contacto')
        ->assertSee('images/icons/icon-contacto.png', false)
        ->assertSee('images/icons/icon-ubicacion.png', false)
        ->assertSee('images/icons/icon-mail.png', false)
        ->assertSee('573001112233')
        ->assertSee('Calle 45 #10-20, Bogota')
        ->assertSee($user->email)
        ->assertDontSee('Mas información')
        ->assertSee('vendlysuite.com');
});

test('public store visits are counted once per visitor each day', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda visitada',
        'slug' => 'tienda-visitada',
        'whatsapp' => '573001112233',
        'is_active' => true,
        'views_count' => 0,
    ]);

    $visitor = $this
        ->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
        ->withHeaders(['User-Agent' => 'Vendly Test Browser']);

    $visitor->get('/tienda-visitada')->assertOk();
    $visitor->get('/tienda-visitada')->assertOk();

    $this->assertDatabaseCount('store_visits', 1);
    expect((int) $store->refresh()->views_count)->toBe(1);

    StoreVisit::query()->update(['visited_on' => now()->subDay()->toDateString()]);

    $visitor->get('/tienda-visitada')->assertOk();

    $this->assertDatabaseCount('store_visits', 2);
    expect((int) $store->refresh()->views_count)->toBe(2);
});

test('store about information is shown on its own page', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda Nosotros',
        'slug' => 'tienda-nosotros',
        'whatsapp' => '573001112233',
        'location' => 'Calle 10 #20-30, Bogota',
        'business_hours' => "Lunes a viernes 8:00 AM - 6:00 PM\nSabado 9:00 AM - 1:00 PM",
        'is_active' => true,
        'mission' => 'Nuestra mision es vender con atencion cercana.',
        'vision' => 'Nuestra vision es crecer con clientes felices.',
    ]);

    $this->get('/tienda-nosotros')
        ->assertOk()
        ->assertSee(route('store.about', 'tienda-nosotros'), false)
        ->assertDontSee('Nuestra mision es vender con atencion cercana.')
        ->assertDontSee('Nuestra vision es crecer con clientes felices.');

    $this->get('/tienda-nosotros/nosotros')
        ->assertOk()
        ->assertSee('Nosotros')
        ->assertSee('Nuestra mision es vender con atencion cercana.')
        ->assertSee('Nuestra vision es crecer con clientes felices.')
        ->assertSee('WhatsApp: 573001112233')
        ->assertSee('Calle 10 #20-30, Bogota')
        ->assertSee('Lunes a viernes 8:00 AM - 6:00 PM');
});

test('store about page is hidden when mission and vision are missing', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda Sin Nosotros',
        'slug' => 'tienda-sin-nosotros',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $this->get('/tienda-sin-nosotros')
        ->assertOk()
        ->assertDontSee('/tienda-sin-nosotros/nosotros');

    $this->get('/tienda-sin-nosotros/nosotros')->assertNotFound();
});

test('store settings save optional location for about page', function () {
    $storeUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Ubicacion',
        'slug' => 'tienda-ubicacion',
        'whatsapp' => '573001112233',
        'business_type' => 'store',
        'is_active' => true,
        'mission' => 'Mision inicial',
        'vision' => 'Vision inicial',
    ]);

    $this->actingAs($storeUser)
        ->get('/admin/store-settings')
        ->assertOk()
        ->assertSee('Vista previa en vivo')
        ->assertSee('Producto destacado')
        ->assertSee('Paletas prearmadas')
        ->assertSee('Claro limpio')
        ->assertSee('Boutique')
        ->assertSee('Tecnologia')
        ->assertSee('Paleta de color principal')
        ->assertSee('Paleta de color de fondo')
        ->assertSee('El color de letras se ajusta automaticamente')
        ->assertSee('Ejemplos de fuente')
        ->assertSee('type="color"', false)
        ->assertSee('data-theme-picker="brand_color"', false)
        ->assertSee('data-theme-picker="background_color"', false)
        ->assertSee('NovaShop vende facil');

    $this->actingAs($storeUser)
        ->post('/admin/store-settings', [
            'name' => $store->name,
            'business_type' => $store->business_type,
            'whatsapp' => $store->whatsapp,
            'location' => 'Local 5, Centro Comercial Central',
            'business_hours' => 'Lunes a sabado 10:00 AM - 7:00 PM',
            'brand_color' => '#0f766e',
            'background_color' => '#111827',
            'text_color' => '#111111',
            'font_family' => 'serif',
            'mission' => $store->mission,
            'vision' => $store->vision,
            'responsive_product_columns' => 2,
            'show_hero_products_action' => 0,
        ])
        ->assertRedirect('/admin/store-settings');

    expect($store->refresh()->location)->toBe('Local 5, Centro Comercial Central');
    expect($store->business_hours)->toBe('Lunes a sabado 10:00 AM - 7:00 PM');
    expect($store->brand_color)->toBe('#0f766e');
    expect($store->background_color)->toBe('#111827');
    expect($store->text_color)->toBe('#ffffff');
    expect($store->font_family)->toBe('serif');

    $this->get('/tienda-ubicacion/nosotros')
        ->assertOk()
        ->assertSee('--store-bg: #111827', false)
        ->assertSee('--store-text: #ffffff', false)
        ->assertSee('--store-font: Georgia, &quot;Times New Roman&quot;, serif', false)
        ->assertSee('Local 5, Centro Comercial Central')
        ->assertSee('Lunes a sabado 10:00 AM - 7:00 PM');
});

test('store settings save commercial notices and storefront shows rotating bar', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Avisos',
        'slug' => 'tienda-avisos',
        'whatsapp' => '573001112233',
        'business_type' => 'store',
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->post('/admin/store-settings', [
            'name' => $store->name,
            'business_type' => $store->business_type,
            'whatsapp' => $store->whatsapp,
            'free_shipping_minimum' => 150000,
            'announcement_items' => [
                ['text' => '10% OFF pagando por transferencia'],
                ['text' => 'Entregas hoy hasta las 6:00 p.m.'],
                ['text' => ''],
            ],
            'brand_color' => '#111111',
            'background_color' => '#ffffff',
            'font_family' => 'system',
            'responsive_product_columns' => 2,
            'show_hero_products_action' => 0,
        ])
        ->assertRedirect('/admin/store-settings');

    $store->refresh();

    expect($store->free_shipping_minimum)->toBe('150000.00')
        ->and($store->announcement_items)->toBe([
            ['text' => '10% OFF pagando por transferencia'],
            ['text' => 'Entregas hoy hasta las 6:00 p.m.'],
        ]);

    $this->get('/tienda-avisos')
        ->assertOk()
        ->assertSee('store-announcement-bar', false)
        ->assertSee('data-storefront-topbar', false)
        ->assertSee('<span>10% OFF pagando por transferencia</span>', false)
        ->assertSee('<span>Entregas hoy hasta las 6:00 p.m.</span>', false)
        ->assertSee('Envio gratis desde $150.000')
        ->assertSee('10% OFF pagando por transferencia')
        ->assertSee('Entregas hoy hasta las 6:00 p.m.');
});

test('storefront repeats a single commercial notice without leaving the bar empty', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Aviso Unico',
        'slug' => 'tienda-aviso-unico',
        'whatsapp' => '573001112233',
        'business_type' => 'store',
        'is_active' => true,
        'announcement_items' => [
            ['text' => 'Solo hoy 15% de descuento'],
        ],
    ]);

    $response = $this->get('/tienda-aviso-unico')
        ->assertOk()
        ->assertSee('--announcement-step-duration: 28s', false)
        ->assertSee('data-announcement-message', false)
        ->assertSee('is-marquee-active', false)
        ->assertSee('store-announcement-group', false);

    expect(substr_count($response->getContent(), 'Solo hoy 15% de descuento'))->toBe(8);
});

test('basic plan hides commercial notices and clears them when settings are saved', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Basica Avisos',
        'slug' => 'tienda-basica-avisos',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_BASIC,
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->get('/admin/store-settings')
        ->assertOk()
        ->assertSee('Avisos comerciales')
        ->assertSee('Tu plan actual no incluye avisos superiores');

    $this->actingAs($storeUser)
        ->post('/admin/store-settings', [
            'name' => $store->name,
            'business_type' => 'store',
            'plan' => Store::PLAN_BASIC,
            'whatsapp' => $store->whatsapp,
            'free_shipping_minimum' => 150000,
            'announcement_items' => [
                ['text' => 'Descuento solo hoy'],
            ],
            'brand_color' => '#111111',
            'background_color' => '#ffffff',
            'font_family' => 'system',
            'responsive_product_columns' => 2,
            'show_hero_products_action' => 0,
        ])
        ->assertRedirect('/admin/store-settings');

    $store->refresh();

    expect($store->free_shipping_minimum)->toBeNull()
        ->and($store->announcement_items)->toBe([]);

    $this->get('/tienda-basica-avisos')
        ->assertOk()
        ->assertDontSee('store-announcement-bar', false)
        ->assertDontSee('Descuento solo hoy');
});

test('store settings cannot change the store plan from a crafted request', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Plan Seguro',
        'slug' => 'tienda-plan-seguro',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_BASIC,
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->post('/admin/store-settings', [
            'name' => $store->name,
            'business_type' => 'store',
            'plan' => Store::PLAN_PREMIUM,
            'whatsapp' => $store->whatsapp,
            'brand_color' => '#111111',
            'background_color' => '#ffffff',
            'font_family' => 'system',
            'responsive_product_columns' => 2,
            'show_hero_products_action' => 0,
        ])
        ->assertRedirect('/admin/store-settings');

    expect($store->refresh()->plan)->toBe(Store::PLAN_BASIC);
});

test('pro plan store can save a normalized subdomain from settings', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Pro Subdominio',
        'slug' => 'tienda-pro-subdominio',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PRO,
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->post('/admin/store-settings', [
            'name' => $store->name,
            'business_type' => 'store',
            'subdomain' => ' Mi Tienda Pro! ',
            'whatsapp' => $store->whatsapp,
            'brand_color' => '#111111',
            'background_color' => '#ffffff',
            'font_family' => 'system',
            'responsive_product_columns' => 2,
            'show_hero_products_action' => 0,
        ])
        ->assertRedirect('/admin/store-settings');

    expect($store->refresh()->subdomain)->toBe('mi-tienda-pro');
});

test('basic plan cannot save a subdomain from a crafted settings request', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Basica Subdominio',
        'slug' => 'tienda-basica-subdominio',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_BASIC,
        'subdomain' => 'subdominio-antiguo',
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->post('/admin/store-settings', [
            'name' => $store->name,
            'business_type' => 'store',
            'subdomain' => 'quiero-pro',
            'whatsapp' => $store->whatsapp,
            'brand_color' => '#111111',
            'background_color' => '#ffffff',
            'font_family' => 'system',
            'responsive_product_columns' => 2,
            'show_hero_products_action' => 0,
        ])
        ->assertRedirect('/admin/store-settings');

    expect($store->refresh()->subdomain)->toBeNull();
});

test('premium plan can save a normalized custom domain from settings', function () {
    config(['app.url' => 'https://vendlysuite.com']);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Premium Dominio',
        'slug' => 'tienda-premium-dominio',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PREMIUM,
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->post('/admin/store-settings', [
            'name' => $store->name,
            'business_type' => 'store',
            'subdomain' => 'premium-demo',
            'custom_domain' => 'https://WWW.MiDominioPremium.com/tienda',
            'whatsapp' => $store->whatsapp,
            'brand_color' => '#111111',
            'background_color' => '#ffffff',
            'font_family' => 'system',
            'responsive_product_columns' => 2,
            'show_hero_products_action' => 0,
        ])
        ->assertRedirect('/admin/store-settings');

    expect($store->refresh()->custom_domain)->toBe('www.midominiopremium.com')
        ->and($store->custom_domain_status)->toBe(Store::CUSTOM_DOMAIN_PENDING)
        ->and($store->custom_domain_verified_at)->toBeNull();
});

test('non premium plans cannot keep custom domains from crafted requests', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Pro Sin Dominio',
        'slug' => 'tienda-pro-sin-dominio',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PRO,
        'custom_domain' => 'antiguo.com',
        'custom_domain_status' => Store::CUSTOM_DOMAIN_VERIFIED,
        'custom_domain_verified_at' => now(),
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->post('/admin/store-settings', [
            'name' => $store->name,
            'business_type' => 'store',
            'subdomain' => 'tienda-pro',
            'custom_domain' => 'www.no-premium.com',
            'whatsapp' => $store->whatsapp,
            'brand_color' => '#111111',
            'background_color' => '#ffffff',
            'font_family' => 'system',
            'responsive_product_columns' => 2,
            'show_hero_products_action' => 0,
        ])
        ->assertRedirect('/admin/store-settings');

    expect($store->refresh()->custom_domain)->toBeNull()
        ->and($store->custom_domain_status)->toBe(Store::CUSTOM_DOMAIN_PENDING)
        ->and($store->custom_domain_verified_at)->toBeNull();
});

test('subdomain must be unique and cannot use reserved words', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $firstUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $secondUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    Store::create([
        'user_id' => $firstUser->id,
        'name' => 'Tienda Subdominio Uno',
        'slug' => 'tienda-subdominio-uno',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PRO,
        'subdomain' => 'ocupado',
        'is_active' => true,
    ]);
    $store = Store::create([
        'user_id' => $secondUser->id,
        'name' => 'Tienda Subdominio Dos',
        'slug' => 'tienda-subdominio-dos',
        'whatsapp' => '573001112244',
        'plan' => Store::PLAN_PRO,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.stores.update', $store), [
            'user_id' => $secondUser->id,
            'name' => $store->name,
            'business_type' => 'store',
            'plan' => Store::PLAN_PRO,
            'slug' => $store->slug,
            'subdomain' => 'ocupado',
            'whatsapp' => $store->whatsapp,
            'brand_color' => '#111111',
            'background_color' => '#ffffff',
            'font_family' => 'system',
            'responsive_product_columns' => 2,
            'show_hero_products_action' => 0,
        ])
        ->assertSessionHasErrors('subdomain');

    $this->actingAs($admin)
        ->put(route('admin.stores.update', $store), [
            'user_id' => $secondUser->id,
            'name' => $store->name,
            'business_type' => 'store',
            'plan' => Store::PLAN_PRO,
            'slug' => $store->slug,
            'subdomain' => 'admin',
            'whatsapp' => $store->whatsapp,
            'brand_color' => '#111111',
            'background_color' => '#ffffff',
            'font_family' => 'system',
            'responsive_product_columns' => 2,
            'show_hero_products_action' => 0,
        ])
        ->assertSessionHasErrors('subdomain');
});

test('custom domain must be unique and cannot use the app domain', function () {
    config(['app.url' => 'https://vendlysuite.com']);

    $admin = User::factory()->create(['role' => 'admin']);
    $firstUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $secondUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    Store::create([
        'user_id' => $firstUser->id,
        'name' => 'Tienda Dominio Uno',
        'slug' => 'tienda-dominio-uno',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PREMIUM,
        'custom_domain' => 'www.ocupado.com',
        'custom_domain_status' => Store::CUSTOM_DOMAIN_VERIFIED,
        'is_active' => true,
    ]);
    $store = Store::create([
        'user_id' => $secondUser->id,
        'name' => 'Tienda Dominio Dos',
        'slug' => 'tienda-dominio-dos',
        'whatsapp' => '573001112244',
        'plan' => Store::PLAN_PREMIUM,
        'is_active' => true,
    ]);

    $payload = [
        'user_id' => $secondUser->id,
        'name' => $store->name,
        'business_type' => 'store',
        'plan' => Store::PLAN_PREMIUM,
        'slug' => $store->slug,
        'subdomain' => 'dominio-dos',
        'whatsapp' => $store->whatsapp,
        'brand_color' => '#111111',
        'background_color' => '#ffffff',
        'font_family' => 'system',
        'responsive_product_columns' => 2,
        'show_hero_products_action' => 0,
    ];

    $this->actingAs($admin)
        ->put(route('admin.stores.update', $store), $payload + ['custom_domain' => 'www.ocupado.com'])
        ->assertSessionHasErrors('custom_domain');

    $this->actingAs($admin)
        ->put(route('admin.stores.update', $store), $payload + ['custom_domain' => 'vendlysuite.com'])
        ->assertSessionHasErrors('custom_domain');

    $this->actingAs($admin)
        ->put(route('admin.stores.update', $store), $payload + ['custom_domain' => 'cliente.vendlysuite.com'])
        ->assertSessionHasErrors('custom_domain');

    $this->actingAs($admin)
        ->put(route('admin.stores.update', $store), $payload + ['custom_domain' => 'tienda-.com'])
        ->assertSessionHasErrors('custom_domain');

    $this->actingAs($admin)
        ->put(route('admin.stores.update', $store), $payload + ['custom_domain' => 'mi.-marca.com'])
        ->assertSessionHasErrors('custom_domain');
});

test('admin verification stores custom domain verified timestamp', function () {
    config(['app.url' => 'https://vendlysuite.com']);

    $admin = User::factory()->create(['role' => 'admin']);
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Dominio Verificado',
        'slug' => 'tienda-dominio-verificado',
        'whatsapp' => '573001112244',
        'plan' => Store::PLAN_PREMIUM,
        'custom_domain' => 'www.verificado.com',
        'custom_domain_status' => Store::CUSTOM_DOMAIN_PENDING,
        'custom_domain_verified_at' => null,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.stores.update', $store), [
            'user_id' => $storeUser->id,
            'name' => $store->name,
            'business_type' => 'store',
            'plan' => Store::PLAN_PREMIUM,
            'slug' => $store->slug,
            'subdomain' => 'dominio-verificado',
            'custom_domain' => 'www.verificado.com',
            'custom_domain_status' => Store::CUSTOM_DOMAIN_VERIFIED,
            'whatsapp' => $store->whatsapp,
            'brand_color' => '#111111',
            'background_color' => '#ffffff',
            'font_family' => 'system',
            'responsive_product_columns' => 2,
            'show_hero_products_action' => 0,
        ])
        ->assertRedirect('/admin/stores');

    expect($store->refresh()->custom_domain_status)->toBe(Store::CUSTOM_DOMAIN_VERIFIED)
        ->and($store->custom_domain_verified_at)->not->toBeNull();
});

test('subdomain service extracts the store subdomain from the configured app host', function () {
    config(['app.url' => 'https://vendlysuite.com']);

    $service = app(StoreSubdomainService::class);

    expect($service->subdomainFromHost('cristina.vendlysuite.com'))->toBe('cristina')
        ->and($service->subdomainFromHost('mi-tienda.vendlysuite.com'))->toBe('mi-tienda')
        ->and($service->subdomainFromHost('vendlysuite.com'))->toBeNull()
        ->and($service->subdomainFromHost('www.vendlysuite.com'))->toBeNull()
        ->and($service->subdomainFromHost('demo.otrodominio.com'))->toBeNull()
        ->and($service->subdomainFromHost('a.b.vendlysuite.com'))->toBeNull();
});

test('subdomain service resolves only public pro or premium stores from request host', function () {
    config(['app.url' => 'https://vendlysuite.com']);

    $proUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $basicUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $proStore = Store::create([
        'user_id' => $proUser->id,
        'name' => 'Tienda Subdominio Publica',
        'slug' => 'tienda-subdominio-publica',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PRO,
        'subdomain' => 'publica',
        'is_active' => true,
    ]);
    Store::create([
        'user_id' => $basicUser->id,
        'name' => 'Tienda Basica Legacy Subdominio',
        'slug' => 'tienda-basica-legacy-subdominio',
        'whatsapp' => '573001112244',
        'plan' => Store::PLAN_BASIC,
        'subdomain' => 'basica',
        'is_active' => true,
    ]);

    $service = app(StoreSubdomainService::class);

    expect($service->publicStoreFromRequest(request()->create('https://publica.vendlysuite.com'))?->id)->toBe($proStore->id)
        ->and($service->publicStoreFromRequest(request()->create('https://basica.vendlysuite.com')))->toBeNull()
        ->and($service->publicStoreFromRequest(request()->create('https://vendlysuite.com')))->toBeNull();
});

test('custom domain service resolves only verified public premium stores', function () {
    config(['app.url' => 'https://vendlysuite.com']);

    $premiumUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $proUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $pendingUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $premiumStore = Store::create([
        'user_id' => $premiumUser->id,
        'name' => 'Tienda Dominio Publica',
        'slug' => 'tienda-dominio-publica',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PREMIUM,
        'custom_domain' => 'www.marca.com',
        'custom_domain_status' => Store::CUSTOM_DOMAIN_VERIFIED,
        'custom_domain_verified_at' => now(),
        'is_active' => true,
    ]);
    Store::create([
        'user_id' => $proUser->id,
        'name' => 'Tienda Pro Dominio Legacy',
        'slug' => 'tienda-pro-dominio-legacy',
        'whatsapp' => '573001112244',
        'plan' => Store::PLAN_PRO,
        'custom_domain' => 'www.prolegacy.com',
        'custom_domain_status' => Store::CUSTOM_DOMAIN_VERIFIED,
        'custom_domain_verified_at' => now(),
        'is_active' => true,
    ]);
    Store::create([
        'user_id' => $pendingUser->id,
        'name' => 'Tienda Dominio Pendiente',
        'slug' => 'tienda-dominio-pendiente',
        'whatsapp' => '573001112255',
        'plan' => Store::PLAN_PREMIUM,
        'custom_domain' => 'www.pendiente.com',
        'custom_domain_status' => Store::CUSTOM_DOMAIN_PENDING,
        'is_active' => true,
    ]);

    $service = app(StoreSubdomainService::class);

    expect($service->publicStoreFromRequest(request()->create('https://www.marca.com'))?->id)->toBe($premiumStore->id)
        ->and($service->publicStoreFromRequest(request()->create('https://www.prolegacy.com')))->toBeNull()
        ->and($service->publicStoreFromRequest(request()->create('https://www.pendiente.com')))->toBeNull()
        ->and($service->publicStoreFromRequest(request()->create('https://vendlysuite.com')))->toBeNull()
        ->and($service->publicStoreFromRequest(request()->create('https://demo.vendlysuite.com')))->toBeNull();
});

test('store payment account stores mercadopago tokens encrypted', function () {
    $storeUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Pagos Seguros',
        'slug' => 'tienda-pagos-seguros',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $account = StorePaymentAccount::create([
        'store_id' => $store->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'access-token-secreto',
        'refresh_token' => 'refresh-token-secreto',
        'public_key' => 'public-key-secreta',
        'provider_user_id' => '123456789',
        'expires_at' => now()->addHours(6),
        'connected_at' => now(),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);

    $rawAccount = DB::table('store_payment_accounts')->where('id', $account->id)->first();

    expect($account->fresh()->access_token)->toBe('access-token-secreto')
        ->and($rawAccount->access_token)->not->toBe('access-token-secreto')
        ->and($store->mercadoPagoAccount()->first()?->isConnected())->toBeTrue();
});

test('store user can see payment methods panel', function () {
    config([
        'services.mercadopago.client_id' => null,
        'services.mercadopago.client_secret' => null,
    ]);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Metodos Pago',
        'slug' => 'tienda-metodos-pago',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PREMIUM,
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->get('/admin/payments')
        ->assertOk()
        ->assertSee('Metodos de pago')
        ->assertSee('WhatsApp')
        ->assertSee('573001112233')
        ->assertSee('Mercado Pago')
        ->assertSee('No conectado')
        ->assertSee('Conectar Mercado Pago')
        ->assertSee(route('admin.payments.mercadopago.connect'), false);

    $this->actingAs($storeUser)
        ->get(route('admin.payments.mercadopago.connect'))
        ->assertRedirect(route('admin.payments.index'))
        ->assertSessionHas('error');
});

test('pro store users cannot see or open payment methods', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Pro Sin Pagos',
        'slug' => 'tienda-pro-sin-pagos',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PRO,
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Metodos de pago')
        ->assertDontSee(route('admin.payments.index'), false);

    $this->actingAs($storeUser)
        ->get('/admin/payments')
        ->assertForbidden();

    $this->actingAs($storeUser)
        ->get(route('admin.payments.mercadopago.connect'))
        ->assertForbidden();
});

test('pro store users can manage templates', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Plantillas',
        'slug' => 'tienda-plantillas',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PRO,
        'business_type' => 'store',
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Plantillas')
        ->assertSee(route('admin.templates.index'), false);

    $this->actingAs($storeUser)
        ->get(route('admin.templates.index'))
        ->assertOk()
        ->assertSee('Plantilla Tecnologia')
        ->assertSee('Pro y Premium');

    $this->actingAs($storeUser)
        ->post(route('admin.templates.apply', 'technology'))
        ->assertRedirect(route('admin.templates.index', ['store_id' => $store->id]))
        ->assertSessionHas('success');

    expect($store->fresh()->business_type)->toBe('technology');
    $this->assertDatabaseHas('store_categories', [
        'store_id' => $store->id,
        'name' => 'Audio',
    ]);
});

test('premium store users can manage templates', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Premium Plantillas',
        'slug' => 'tienda-premium-plantillas',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PREMIUM,
        'business_type' => 'store',
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->get(route('admin.templates.index'))
        ->assertOk()
        ->assertSee('Plantilla Tecnologia')
        ->assertSee('Pro y Premium');

    $this->actingAs($storeUser)
        ->post(route('admin.templates.apply', 'technology'))
        ->assertRedirect(route('admin.templates.index', ['store_id' => $store->id]))
        ->assertSessionHas('success');

    expect($store->fresh()->business_type)->toBe('technology');
});

test('template selection applies to the selected owned store', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $firstStore = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Primera Tienda',
        'slug' => 'primera-tienda',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PRO,
        'business_type' => 'store',
        'is_active' => true,
    ]);
    $secondStore = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Segunda Tienda',
        'slug' => 'segunda-tienda',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PRO,
        'business_type' => 'store',
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->get(route('admin.templates.index', ['store_id' => $secondStore->id]))
        ->assertOk()
        ->assertSee('Segunda Tienda')
        ->assertSee('Primera Tienda');

    $this->actingAs($storeUser)
        ->post(route('admin.templates.apply', 'technology'), [
            'store_id' => $secondStore->id,
        ])
        ->assertRedirect(route('admin.templates.index', ['store_id' => $secondStore->id]))
        ->assertSessionHas('success');

    expect($firstStore->fresh()->business_type)->toBe('store')
        ->and($secondStore->fresh()->business_type)->toBe('technology');
});

test('template panel defaults to an eligible store when user also has a basic store', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $basicStore = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Basica Principal',
        'slug' => 'tienda-basica-principal',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_BASIC,
        'business_type' => 'store',
        'is_active' => true,
    ]);
    $proStore = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Pro Elegible',
        'slug' => 'tienda-pro-elegible',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PRO,
        'business_type' => 'store',
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->get(route('admin.templates.index'))
        ->assertOk()
        ->assertSee('Tienda Pro Elegible')
        ->assertDontSee('Tienda Basica Principal')
        ->assertSee(route('admin.templates.index'), false);

    $this->actingAs($storeUser)
        ->post(route('admin.templates.apply', 'technology'), [
            'store_id' => $basicStore->id,
        ])
        ->assertNotFound();

    $this->actingAs($storeUser)
        ->post(route('admin.templates.apply', 'technology'), [
            'store_id' => $proStore->id,
        ])
        ->assertRedirect(route('admin.templates.index', ['store_id' => $proStore->id]));

    expect($basicStore->fresh()->business_type)->toBe('store')
        ->and($proStore->fresh()->business_type)->toBe('technology');
});

test('template selection cannot target another users store', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $otherUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Propia',
        'slug' => 'tienda-propia',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PRO,
        'business_type' => 'store',
        'is_active' => true,
    ]);
    $otherStore = Store::create([
        'user_id' => $otherUser->id,
        'name' => 'Tienda Ajena',
        'slug' => 'tienda-ajena',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PRO,
        'business_type' => 'store',
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->get(route('admin.templates.index', ['store_id' => $otherStore->id]))
        ->assertNotFound();

    $this->actingAs($storeUser)
        ->post(route('admin.templates.apply', 'technology'), [
            'store_id' => $otherStore->id,
        ])
        ->assertNotFound();

    expect($otherStore->fresh()->business_type)->toBe('store');
});

test('basic store users cannot manage templates', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Basica Sin Plantillas',
        'slug' => 'tienda-basica-sin-plantillas',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_BASIC,
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Plantillas')
        ->assertDontSee(route('admin.templates.index'), false);

    $this->actingAs($storeUser)
        ->get(route('admin.templates.index'))
        ->assertForbidden();

    $this->actingAs($storeUser)
        ->post(route('admin.templates.apply', 'technology'))
        ->assertForbidden();
});

test('cart shows mercadopago button only for connected stores', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Checkout Pago',
        'slug' => 'tienda-checkout-pago',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PREMIUM,
        'is_active' => true,
    ]);
    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto pagable',
        'price' => 45000,
    ]);

    $this->post(route('cart.add', $product->id))->assertRedirect();

    $this->get(route('cart.index', ['store' => $store->slug]))
        ->assertOk()
        ->assertDontSee('Pagar con Mercado Pago');

    StorePaymentAccount::create([
        'store_id' => $store->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'access-token',
        'refresh_token' => 'refresh-token',
        'provider_user_id' => '521008171',
        'connected_at' => now(),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);

    $this->get(route('cart.index', ['store' => $store->slug]))
        ->assertOk()
        ->assertSee('Pagar con Mercado Pago')
        ->assertSee(route('cart.mercadopago', ['store' => $store->slug]), false);

    Http::fake([
        'https://api.mercadopago.com/checkout/preferences' => Http::response([
            'id' => 'pref_123',
            'init_point' => 'https://www.mercadopago.com.co/checkout/v1/redirect?pref_id=pref_123',
        ]),
    ]);

    $this->post(route('cart.mercadopago', ['store' => $store->slug]), [
        'name' => 'Cliente',
        'last_name' => 'Pago',
        'phone' => '3001234567',
        'address' => 'Calle 1',
        'neighborhood' => 'Chapinero',
        'city' => 'Bogota',
        'document' => '123456',
    ])->assertRedirect('https://www.mercadopago.com.co/checkout/v1/redirect?pref_id=pref_123');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.mercadopago.com/checkout/preferences'
        && $request->hasHeader('Authorization', 'Bearer access-token')
        && $request['items'][0]['title'] === 'Producto pagable'
        && $request['items'][0]['quantity'] === 1
        && $request['items'][0]['unit_price'] === 45000.0
        && $request['items'][0]['currency_id'] === 'COP'
        && str_ends_with((string) $request['notification_url'], '/webhooks/mercadopago')
        && $request['expires'] === true
        && $request['date_of_expiration']
        && $request['external_reference']
    );

    $order = Order::where('store_id', $store->id)->latest('id')->firstOrFail();

    expect($order->payment_method)->toBe(Order::PAYMENT_METHOD_MERCADOPAGO)
        ->and($order->payment_provider)->toBe(StorePaymentAccount::PROVIDER_MERCADOPAGO)
        ->and($order->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING)
        ->and($order->payment_provider_reference)->toBe('pref_123')
        ->and($order->payment_preference_id)->toBe('pref_123')
        ->and($order->payment_expires_at)->not->toBeNull()
        ->and(session()->has('carts.' . $store->id))->toBeFalse();
});

test('pro stores cannot use mercadopago even with a connected account', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Pro Checkout',
        'slug' => 'tienda-pro-checkout',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PRO,
        'is_active' => true,
    ]);
    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto pro pago',
        'price' => 45000,
    ]);
    StorePaymentAccount::create([
        'store_id' => $store->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'access-token',
        'refresh_token' => 'refresh-token',
        'provider_user_id' => '521008171',
        'connected_at' => now(),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);

    $this->post(route('cart.add', $product->id))->assertRedirect();

    $this->get(route('cart.index', ['store' => $store->slug]))
        ->assertOk()
        ->assertDontSee('Pagar con Mercado Pago')
        ->assertDontSee(route('cart.mercadopago', ['store' => $store->slug]), false);

    Http::fake();

    $this->post(route('cart.mercadopago', ['store' => $store->slug]), [
        'name' => 'Cliente',
        'last_name' => 'Pro',
        'phone' => '3001234567',
        'address' => 'Calle 1',
        'neighborhood' => 'Chapinero',
        'city' => 'Bogota',
        'document' => '123456',
    ])
        ->assertRedirect(route('cart.index', ['store' => $store->slug]))
        ->assertSessionHas('error', 'Los pagos en linea estan disponibles solo en el plan Premium.');

    Http::assertNothingSent();
});

test('expired mercadopago account is not available at checkout', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Pago Expirado',
        'slug' => 'tienda-pago-expirado',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PREMIUM,
        'is_active' => true,
    ]);
    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto pago expirado',
        'price' => 45000,
    ]);
    StorePaymentAccount::create([
        'store_id' => $store->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'expired-token',
        'refresh_token' => 'refresh-token',
        'provider_user_id' => '521008171',
        'expires_at' => now()->subMinute(),
        'connected_at' => now()->subMonths(6),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);

    $this->post(route('cart.add', $product->id))->assertRedirect();

    $this->get(route('cart.index', ['store' => $store->slug]))
        ->assertOk()
        ->assertDontSee('Pagar con Mercado Pago');

    Http::fake();

    $this->post(route('cart.mercadopago', ['store' => $store->slug]), [
        'name' => 'Cliente',
        'last_name' => 'Pago',
        'phone' => '3001234567',
        'address' => 'Calle 1',
        'neighborhood' => 'Chapinero',
        'city' => 'Bogota',
        'document' => '123456',
    ])
        ->assertRedirect(route('cart.index', ['store' => $store->slug]))
        ->assertSessionHas('error');

    Http::assertNothingSent();
});

test('mercadopago webhook verifies payment and marks order as paid', function () {
    config(['services.mercadopago.webhook_secret' => 'webhook-secret']);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Webhook Pago',
        'slug' => 'tienda-webhook-pago',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    StorePaymentAccount::create([
        'store_id' => $store->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'seller-token',
        'refresh_token' => 'refresh-token',
        'provider_user_id' => '521008171',
        'connected_at' => now(),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);
    $order = Order::create([
        'store_id' => $store->id,
        'customer_name' => 'Cliente Pago',
        'customer_phone' => '3001234567',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pendiente',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
        'payment_provider_reference' => 'pref_123',
        'total' => 45000,
    ]);

    Http::fake([
        'https://api.mercadopago.com/v1/payments/pay_123' => Http::response([
            'id' => 123,
            'status' => 'approved',
            'transaction_amount' => 45000,
            'external_reference' => $order->admin_token,
            'date_approved' => '2026-05-08T12:00:00.000-05:00',
        ]),
    ]);

    $requestId = 'request-123';
    $timestamp = '1715191200';
    $hash = hash_hmac('sha256', "id:pay_123;request-id:{$requestId};ts:{$timestamp};", 'webhook-secret');

    $this->postJson(route('cart.mercadopago.webhook') . '?type=payment&data.id=pay_123', [], [
        'x-request-id' => $requestId,
        'x-signature' => "ts={$timestamp},v1={$hash}",
    ])->assertOk();

    $order->refresh();

    expect($order->status)->toBe('pagado')
        ->and($order->payment_status)->toBe(Order::PAYMENT_STATUS_APPROVED)
        ->and($order->payment_provider_reference)->toBe('123')
        ->and($order->payment_id)->toBe('123')
        ->and($order->paid_at?->toDateString())->toBe('2026-05-08');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.mercadopago.com/v1/payments/pay_123'
        && $request->hasHeader('Authorization', 'Bearer seller-token'));
});

test('global mercadopago webhook finds order by payment external reference', function () {
    config(['services.mercadopago.webhook_secret' => 'webhook-secret']);

    $firstUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $secondUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $firstStore = Store::create([
        'user_id' => $firstUser->id,
        'name' => 'Tienda Webhook Global Uno',
        'slug' => 'tienda-webhook-global-uno',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    $secondStore = Store::create([
        'user_id' => $secondUser->id,
        'name' => 'Tienda Webhook Global Dos',
        'slug' => 'tienda-webhook-global-dos',
        'whatsapp' => '573001112244',
        'is_active' => true,
    ]);
    StorePaymentAccount::create([
        'store_id' => $firstStore->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'wrong-seller-token',
        'refresh_token' => 'wrong-refresh-token',
        'provider_user_id' => '111',
        'connected_at' => now(),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);
    StorePaymentAccount::create([
        'store_id' => $secondStore->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'seller-token',
        'refresh_token' => 'refresh-token',
        'provider_user_id' => '222',
        'connected_at' => now(),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);
    $order = Order::create([
        'store_id' => $secondStore->id,
        'customer_name' => 'Cliente Global',
        'customer_phone' => '3001234567',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pendiente',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
        'payment_provider_reference' => 'pref_global',
        'total' => 45000,
    ]);

    Http::fake([
        'https://api.mercadopago.com/v1/payments/pay_global' => function ($request) use ($order) {
            if ($request->hasHeader('Authorization', 'Bearer wrong-seller-token')) {
                return Http::response(['message' => 'not found'], 404);
            }

            return Http::response([
                'id' => 456,
                'status' => 'approved',
                'transaction_amount' => 45000,
                'external_reference' => $order->admin_token,
                'date_approved' => '2026-05-08T12:00:00.000-05:00',
            ]);
        },
    ]);

    $requestId = 'request-global';
    $timestamp = '1715191200';
    $hash = hash_hmac('sha256', "id:pay_global;request-id:{$requestId};ts:{$timestamp};", 'webhook-secret');

    $this->postJson(route('cart.mercadopago.webhook') . '?type=payment&data.id=pay_global', [], [
        'x-request-id' => $requestId,
        'x-signature' => "ts={$timestamp},v1={$hash}",
    ])->assertOk();

    expect($order->refresh()->status)->toBe('pagado')
        ->and($order->payment_status)->toBe(Order::PAYMENT_STATUS_APPROVED)
        ->and($order->payment_id)->toBe('456');
});

test('mercadopago webhook refreshes expired token before verifying payment', function () {
    config([
        'services.mercadopago.client_id' => '123456789',
        'services.mercadopago.client_secret' => 'client-secret',
        'services.mercadopago.webhook_secret' => 'webhook-secret',
    ]);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Webhook Token',
        'slug' => 'tienda-webhook-token',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    StorePaymentAccount::create([
        'store_id' => $store->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'expired-token',
        'refresh_token' => 'refresh-token',
        'provider_user_id' => '521008171',
        'expires_at' => now()->subMinute(),
        'connected_at' => now()->subMonths(6),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);
    $order = Order::create([
        'store_id' => $store->id,
        'customer_name' => 'Cliente Token Webhook',
        'customer_phone' => '3001234567',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pendiente',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
        'payment_provider_reference' => 'pref_123',
        'total' => 45000,
    ]);

    Http::fake([
        'https://api.mercadopago.com/oauth/token' => Http::response([
            'access_token' => 'fresh-token',
            'refresh_token' => 'fresh-refresh-token',
            'public_key' => 'fresh-public-key',
            'user_id' => 521008171,
            'expires_in' => 15552000,
        ]),
        'https://api.mercadopago.com/v1/payments/pay_123' => Http::response([
            'id' => 123,
            'status' => 'approved',
            'transaction_amount' => 45000,
            'external_reference' => $order->admin_token,
            'date_approved' => '2026-05-08T12:00:00.000-05:00',
        ]),
    ]);

    $requestId = 'request-123';
    $timestamp = '1715191200';
    $hash = hash_hmac('sha256', "id:pay_123;request-id:{$requestId};ts:{$timestamp};", 'webhook-secret');

    $this->postJson(route('cart.mercadopago.webhook') . '?type=payment&data.id=pay_123', [], [
        'x-request-id' => $requestId,
        'x-signature' => "ts={$timestamp},v1={$hash}",
    ])->assertOk();

    $order->refresh();
    $account = $store->mercadoPagoAccount()->first();

    expect($account?->access_token)->toBe('fresh-token')
        ->and($order->status)->toBe('pagado')
        ->and($order->payment_status)->toBe(Order::PAYMENT_STATUS_APPROVED)
        ->and($order->payment_id)->toBe('123');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.mercadopago.com/oauth/token'
        && $request['grant_type'] === 'refresh_token'
        && $request['refresh_token'] === 'refresh-token');
    Http::assertSent(fn ($request) => $request->url() === 'https://api.mercadopago.com/v1/payments/pay_123'
        && $request->hasHeader('Authorization', 'Bearer fresh-token'));
});

test('mercadopago webhook does not refresh manually disconnected account', function () {
    config([
        'services.mercadopago.client_id' => '123456789',
        'services.mercadopago.client_secret' => 'client-secret',
        'services.mercadopago.webhook_secret' => 'webhook-secret',
    ]);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Webhook Desconectado',
        'slug' => 'tienda-webhook-desconectado',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    $account = StorePaymentAccount::create([
        'store_id' => $store->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'expired-token',
        'refresh_token' => 'refresh-token',
        'provider_user_id' => '521008171',
        'expires_at' => now()->subMinute(),
        'connected_at' => now()->subMonths(6),
        'disconnected_at' => now(),
        'status' => StorePaymentAccount::STATUS_DISCONNECTED,
    ]);
    $order = Order::create([
        'store_id' => $store->id,
        'customer_name' => 'Cliente Desconectado',
        'customer_phone' => '3001234567',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pendiente',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
        'payment_provider_reference' => 'pref_123',
        'total' => 45000,
    ]);

    Http::fake();

    $requestId = 'request-123';
    $timestamp = '1715191200';
    $hash = hash_hmac('sha256', "id:pay_123;request-id:{$requestId};ts:{$timestamp};", 'webhook-secret');

    $this->postJson(route('cart.mercadopago.webhook') . '?type=payment&data.id=pay_123', [], [
        'x-request-id' => $requestId,
        'x-signature' => "ts={$timestamp},v1={$hash}",
    ])->assertOk();

    expect($account->refresh()->status)->toBe(StorePaymentAccount::STATUS_DISCONNECTED)
        ->and($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING);

    Http::assertNothingSent();
});

test('mercadopago webhook keeps pending when token refresh has connection failure', function () {
    config([
        'services.mercadopago.client_id' => '123456789',
        'services.mercadopago.client_secret' => 'client-secret',
        'services.mercadopago.webhook_secret' => 'webhook-secret',
    ]);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Webhook Conexion',
        'slug' => 'tienda-webhook-conexion',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    StorePaymentAccount::create([
        'store_id' => $store->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'expired-token',
        'refresh_token' => 'refresh-token',
        'provider_user_id' => '521008171',
        'expires_at' => now()->subMinute(),
        'connected_at' => now()->subMonths(6),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);
    $order = Order::create([
        'store_id' => $store->id,
        'customer_name' => 'Cliente Conexion',
        'customer_phone' => '3001234567',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pendiente',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
        'payment_provider_reference' => 'pref_123',
        'total' => 45000,
    ]);

    $refreshAttempted = false;

    Http::fake([
        'https://api.mercadopago.com/oauth/token' => function () use (&$refreshAttempted) {
            $refreshAttempted = true;

            throw new ConnectionException('timeout');
        },
    ]);

    $requestId = 'request-123';
    $timestamp = '1715191200';
    $hash = hash_hmac('sha256', "id:pay_123;request-id:{$requestId};ts:{$timestamp};", 'webhook-secret');

    $this->postJson(route('cart.mercadopago.webhook') . '?type=payment&data.id=pay_123', [], [
        'x-request-id' => $requestId,
        'x-signature' => "ts={$timestamp},v1={$hash}",
    ])->assertOk();

    expect($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING);

    expect($refreshAttempted)->toBeTrue();
});

test('mercadopago webhook returns controlled error when payment lookup has connection failure', function () {
    config(['services.mercadopago.webhook_secret' => 'webhook-secret']);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Lookup Conexion',
        'slug' => 'tienda-lookup-conexion',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    StorePaymentAccount::create([
        'store_id' => $store->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'seller-token',
        'refresh_token' => 'refresh-token',
        'provider_user_id' => '521008171',
        'expires_at' => now()->addHour(),
        'connected_at' => now(),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);
    $order = Order::create([
        'store_id' => $store->id,
        'customer_name' => 'Cliente Lookup Conexion',
        'customer_phone' => '3001234567',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pendiente',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
        'payment_provider_reference' => 'pref_123',
        'total' => 45000,
    ]);

    Http::fake([
        'https://api.mercadopago.com/v1/payments/pay_123' => fn () => throw new ConnectionException('timeout'),
    ]);

    $requestId = 'request-123';
    $timestamp = '1715191200';
    $hash = hash_hmac('sha256', "id:pay_123;request-id:{$requestId};ts:{$timestamp};", 'webhook-secret');

    $this->postJson(route('cart.mercadopago.webhook') . '?type=payment&data.id=pay_123', [], [
        'x-request-id' => $requestId,
        'x-signature' => "ts={$timestamp},v1={$hash}",
    ])->assertStatus(502);

    expect($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING);
});

test('mercadopago rejected webhook releases reserved stock once', function () {
    config(['services.mercadopago.webhook_secret' => 'webhook-secret']);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Pago Rechazado Stock',
        'slug' => 'tienda-pago-rechazado-stock',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    StorePaymentAccount::create([
        'store_id' => $store->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'seller-token',
        'refresh_token' => 'refresh-token',
        'provider_user_id' => '521008171',
        'connected_at' => now(),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);
    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto rechazado',
        'price' => 45000,
        'stock_quantity' => 0,
        'is_sold_out' => true,
    ]);
    $order = Order::create([
        'store_id' => $store->id,
        'customer_name' => 'Cliente Rechazado',
        'customer_phone' => '3001234567',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pendiente',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
        'payment_provider_reference' => 'pref_rejected',
        'payment_expires_at' => now()->addHour(),
        'total' => 45000,
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 1,
        'price' => 45000,
    ]);

    Http::fake([
        'https://api.mercadopago.com/v1/payments/pay_rejected' => Http::response([
            'id' => 987,
            'status' => 'rejected',
            'transaction_amount' => 45000,
            'external_reference' => $order->admin_token,
        ]),
    ]);

    $requestId = 'request-123';
    $timestamp = '1715191200';
    $hash = hash_hmac('sha256', "id:pay_rejected;request-id:{$requestId};ts:{$timestamp};", 'webhook-secret');
    $headers = [
        'x-request-id' => $requestId,
        'x-signature' => "ts={$timestamp},v1={$hash}",
    ];
    $url = route('cart.mercadopago.webhook') . '?type=payment&data.id=pay_rejected';

    $this->postJson($url, [], $headers)->assertOk();
    $this->postJson($url, [], $headers)->assertOk();

    expect($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_REJECTED)
        ->and($order->payment_expires_at)->toBeNull()
        ->and($product->refresh()->stock_quantity)->toBe(1)
        ->and($product->is_sold_out)->toBeFalse();
});

test('mercadopago checkout failure removes pending order and restores stock', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Pago Fallido',
        'slug' => 'tienda-pago-fallido',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto con stock',
        'price' => 45000,
        'stock_quantity' => 2,
    ]);
    StorePaymentAccount::create([
        'store_id' => $store->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'access-token',
        'refresh_token' => 'refresh-token',
        'provider_user_id' => '521008171',
        'connected_at' => now(),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);

    $this->post(route('cart.add', $product->id), ['quantity' => 2])->assertRedirect();

    Http::fake([
        'https://api.mercadopago.com/checkout/preferences' => Http::response([
            'message' => 'error',
        ], 500),
    ]);

    $this->post(route('cart.mercadopago', ['store' => $store->slug]), [
        'name' => 'Cliente',
        'last_name' => 'Pago',
        'phone' => '3001234567',
        'address' => 'Calle 1',
        'neighborhood' => 'Chapinero',
        'city' => 'Bogota',
        'document' => '123456',
    ])->assertRedirect(route('cart.index', ['store' => $store->slug]))
        ->assertSessionHas('error');

    expect(Order::where('store_id', $store->id)->count())->toBe(0)
        ->and($product->refresh()->stock_quantity)->toBe(2)
        ->and($product->is_sold_out)->toBeFalse()
        ->and(session()->has('carts.' . $store->id))->toBeTrue();
});

test('mercadopago checkout connection failure removes pending order and restores stock', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Pago Conexion',
        'slug' => 'tienda-pago-conexion',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto conexion',
        'price' => 45000,
        'stock_quantity' => 1,
    ]);
    StorePaymentAccount::create([
        'store_id' => $store->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'access-token',
        'refresh_token' => 'refresh-token',
        'provider_user_id' => '521008171',
        'connected_at' => now(),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);

    $this->post(route('cart.add', $product->id), ['quantity' => 1])->assertRedirect();

    Http::fake([
        'https://api.mercadopago.com/checkout/preferences' => fn () => throw new ConnectionException('timeout'),
    ]);

    $this->post(route('cart.mercadopago', ['store' => $store->slug]), [
        'name' => 'Cliente',
        'last_name' => 'Conexion',
        'phone' => '3001234567',
        'address' => 'Calle 1',
        'neighborhood' => 'Chapinero',
        'city' => 'Bogota',
        'document' => '123456',
    ])->assertRedirect(route('cart.index', ['store' => $store->slug]))
        ->assertSessionHas('error');

    expect(Order::where('store_id', $store->id)->count())->toBe(0)
        ->and($product->refresh()->stock_quantity)->toBe(1)
        ->and($product->is_sold_out)->toBeFalse()
        ->and(session()->has('carts.' . $store->id))->toBeTrue();
});

test('mercadopago cancelled payment stops showing order as paid', function () {
    config(['services.mercadopago.webhook_secret' => 'webhook-secret']);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Pago Cancelado',
        'slug' => 'tienda-pago-cancelado',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    StorePaymentAccount::create([
        'store_id' => $store->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'seller-token',
        'refresh_token' => 'refresh-token',
        'provider_user_id' => '521008171',
        'connected_at' => now(),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);
    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto pagado cancelado',
        'price' => 45000,
        'stock_quantity' => 0,
        'is_sold_out' => true,
    ]);
    $order = Order::create([
        'store_id' => $store->id,
        'customer_name' => 'Cliente Cancelado',
        'customer_phone' => '3001234567',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pagado',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_APPROVED,
        'payment_provider_reference' => '123',
        'payment_expires_at' => now()->addHour(),
        'paid_at' => now(),
        'total' => 45000,
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 1,
        'price' => 45000,
    ]);

    Http::fake([
        'https://api.mercadopago.com/v1/payments/pay_123' => Http::response([
            'id' => 123,
            'status' => 'cancelled',
            'transaction_amount' => 45000,
            'external_reference' => $order->admin_token,
        ]),
    ]);

    $requestId = 'request-123';
    $timestamp = '1715191200';
    $hash = hash_hmac('sha256', "id:pay_123;request-id:{$requestId};ts:{$timestamp};", 'webhook-secret');

    $this->postJson(route('cart.mercadopago.webhook') . '?type=payment&data.id=pay_123', [], [
        'x-request-id' => $requestId,
        'x-signature' => "ts={$timestamp},v1={$hash}",
    ])->assertOk();

    $order->refresh();

    expect($order->status)->toBe('pendiente')
        ->and($order->payment_status)->toBe(Order::PAYMENT_STATUS_CANCELLED)
        ->and($order->paid_at)->toBeNull()
        ->and($order->payment_expires_at)->not->toBeNull()
        ->and($product->refresh()->stock_quantity)->toBe(0)
        ->and($product->is_sold_out)->toBeTrue();
});

test('expired mercadopago pending orders release reserved stock', function () {
    config(['services.mercadopago.payment_expiration_grace_minutes' => 0]);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Pago Vencido',
        'slug' => 'tienda-pago-vencido',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto reservado',
        'price' => 45000,
        'stock_quantity' => 0,
        'is_sold_out' => true,
    ]);
    $order = Order::create([
        'store_id' => $store->id,
        'customer_name' => 'Cliente Vencido',
        'customer_phone' => '3001234567',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pendiente',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
        'payment_expires_at' => now()->subMinute(),
        'total' => 90000,
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 2,
        'price' => 45000,
    ]);

    $this->artisan('payments:expire-pending')
        ->assertSuccessful()
        ->expectsOutput('Pedidos Mercado Pago vencidos: 1');

    $order->refresh();

    expect($order->payment_status)->toBe(Order::PAYMENT_STATUS_CANCELLED)
        ->and($product->refresh()->stock_quantity)->toBe(2)
        ->and($product->is_sold_out)->toBeFalse();
});

test('expired mercadopago pending order keeps stock when connected account token cannot refresh', function () {
    config([
        'services.mercadopago.client_id' => '123456789',
        'services.mercadopago.client_secret' => 'client-secret',
        'services.mercadopago.payment_expiration_grace_minutes' => 0,
    ]);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Token Expirado',
        'slug' => 'tienda-token-expirado',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    StorePaymentAccount::create([
        'store_id' => $store->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'expired-token',
        'refresh_token' => 'refresh-token',
        'provider_user_id' => '521008171',
        'expires_at' => now()->subMinute(),
        'connected_at' => now()->subMonths(6),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);
    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto token expirado',
        'price' => 45000,
        'stock_quantity' => 0,
        'is_sold_out' => true,
    ]);
    $order = Order::create([
        'store_id' => $store->id,
        'customer_name' => 'Cliente Token Expirado',
        'customer_phone' => '3001234567',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pendiente',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
        'payment_preference_id' => 'pref_expired_token',
        'payment_expires_at' => now()->subMinutes(10),
        'total' => 45000,
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 1,
        'price' => 45000,
    ]);

    Http::fake([
        'https://api.mercadopago.com/oauth/token' => Http::response([
            'message' => 'invalid refresh token',
        ], 401),
    ]);

    $this->artisan('payments:expire-pending')
        ->assertSuccessful()
        ->expectsOutput('Pedidos Mercado Pago vencidos: 0');

    expect($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING)
        ->and($product->refresh()->stock_quantity)->toBe(0)
        ->and($product->is_sold_out)->toBeTrue();

    Http::assertSent(fn ($request) => $request->url() === 'https://api.mercadopago.com/oauth/token'
        && $request['grant_type'] === 'refresh_token'
        && $request['refresh_token'] === 'refresh-token');
});

test('expired mercadopago pending order keeps stock when refresh has connection failure', function () {
    config([
        'services.mercadopago.client_id' => '123456789',
        'services.mercadopago.client_secret' => 'client-secret',
        'services.mercadopago.payment_expiration_grace_minutes' => 0,
    ]);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Refresh Conexion',
        'slug' => 'tienda-refresh-conexion',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    StorePaymentAccount::create([
        'store_id' => $store->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'expired-token',
        'refresh_token' => 'refresh-token',
        'provider_user_id' => '521008171',
        'expires_at' => now()->subMinute(),
        'connected_at' => now()->subMonths(6),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);
    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto refresh conexion',
        'price' => 45000,
        'stock_quantity' => 0,
        'is_sold_out' => true,
    ]);
    $order = Order::create([
        'store_id' => $store->id,
        'customer_name' => 'Cliente Refresh Conexion',
        'customer_phone' => '3001234567',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pendiente',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
        'payment_preference_id' => 'pref_connection_failure',
        'payment_expires_at' => now()->subMinutes(10),
        'total' => 45000,
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 1,
        'price' => 45000,
    ]);

    $refreshAttempted = false;

    Http::fake([
        'https://api.mercadopago.com/oauth/token' => function () use (&$refreshAttempted) {
            $refreshAttempted = true;

            throw new ConnectionException('timeout');
        },
    ]);

    $this->artisan('payments:expire-pending')
        ->assertSuccessful()
        ->expectsOutput('Pedidos Mercado Pago vencidos: 0');

    expect($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING)
        ->and($product->refresh()->stock_quantity)->toBe(0)
        ->and($product->is_sold_out)->toBeTrue();

    expect($refreshAttempted)->toBeTrue();
});

test('expired mercadopago pending order keeps stock when refresh response is incomplete', function () {
    config([
        'services.mercadopago.client_id' => '123456789',
        'services.mercadopago.client_secret' => 'client-secret',
        'services.mercadopago.payment_expiration_grace_minutes' => 0,
    ]);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Refresh Incompleto',
        'slug' => 'tienda-refresh-incompleto',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    $account = StorePaymentAccount::create([
        'store_id' => $store->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'expired-token',
        'refresh_token' => 'refresh-token',
        'provider_user_id' => '521008171',
        'expires_at' => now()->subMinute(),
        'connected_at' => now()->subMonths(6),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);
    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto refresh incompleto',
        'price' => 45000,
        'stock_quantity' => 0,
        'is_sold_out' => true,
    ]);
    $order = Order::create([
        'store_id' => $store->id,
        'customer_name' => 'Cliente Refresh Incompleto',
        'customer_phone' => '3001234567',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pendiente',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
        'payment_preference_id' => 'pref_incomplete_refresh',
        'payment_expires_at' => now()->subMinutes(10),
        'total' => 45000,
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 1,
        'price' => 45000,
    ]);

    Http::fake([
        'https://api.mercadopago.com/oauth/token' => Http::response([
            'refresh_token' => 'fresh-refresh-token',
        ]),
    ]);

    $this->artisan('payments:expire-pending')
        ->assertSuccessful()
        ->expectsOutput('Pedidos Mercado Pago vencidos: 0');

    expect($account->refresh()->access_token)->toBe('expired-token')
        ->and($account->refresh()->expires_at?->isPast())->toBeTrue()
        ->and($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING)
        ->and($product->refresh()->stock_quantity)->toBe(0)
        ->and($product->is_sold_out)->toBeTrue();

    Http::assertSentCount(1);
});

test('expired mercadopago pending order refreshes token before checking merchant order', function () {
    config([
        'services.mercadopago.client_id' => '123456789',
        'services.mercadopago.client_secret' => 'client-secret',
        'services.mercadopago.payment_expiration_grace_minutes' => 0,
    ]);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Token Renovado',
        'slug' => 'tienda-token-renovado',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    $account = StorePaymentAccount::create([
        'store_id' => $store->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'expired-token',
        'refresh_token' => 'refresh-token',
        'provider_user_id' => '521008171',
        'expires_at' => now()->subMinute(),
        'connected_at' => now()->subMonths(6),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);
    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto token renovado',
        'price' => 45000,
        'stock_quantity' => 0,
        'is_sold_out' => true,
    ]);
    $order = Order::create([
        'store_id' => $store->id,
        'customer_name' => 'Cliente Token Renovado',
        'customer_phone' => '3001234567',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pendiente',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
        'payment_preference_id' => 'pref_refreshed_token',
        'payment_expires_at' => now()->subMinutes(10),
        'total' => 45000,
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 1,
        'price' => 45000,
    ]);

    Http::fake([
        'https://api.mercadopago.com/oauth/token' => Http::response([
            'access_token' => 'fresh-token',
            'refresh_token' => 'fresh-refresh-token',
            'public_key' => 'fresh-public-key',
            'user_id' => 521008171,
            'expires_in' => 15552000,
        ]),
        'https://api.mercadopago.com/merchant_orders/search*' => Http::response([
            'elements' => [[
                'id' => 987,
                'status' => 'closed',
                'external_reference' => $order->admin_token,
                'preference_id' => 'pref_refreshed_token',
                'payments' => [[
                    'id' => 456,
                    'status' => 'approved',
                    'transaction_amount' => 45000,
                    'date_approved' => '2026-05-08T12:00:00.000-05:00',
                ]],
            ]],
        ]),
    ]);

    $this->artisan('payments:expire-pending')
        ->assertSuccessful()
        ->expectsOutput('Pedidos Mercado Pago vencidos: 0');

    expect($account->refresh()->access_token)->toBe('fresh-token')
        ->and($account->refresh()->isConnected())->toBeTrue()
        ->and($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_APPROVED)
        ->and($order->status)->toBe('pagado')
        ->and($product->refresh()->stock_quantity)->toBe(0)
        ->and($product->is_sold_out)->toBeTrue();

    Http::assertSent(fn ($request) => $request->url() === 'https://api.mercadopago.com/oauth/token'
        && $request['grant_type'] === 'refresh_token'
        && $request['refresh_token'] === 'refresh-token');
    Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://api.mercadopago.com/merchant_orders/search')
        && $request->hasHeader('Authorization', 'Bearer fresh-token'));
});

test('expired mercadopago pending order is paid when merchant order is approved', function () {
    config(['services.mercadopago.payment_expiration_grace_minutes' => 0]);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Pago Aprobado Tarde',
        'slug' => 'tienda-pago-aprobado-tarde',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    StorePaymentAccount::create([
        'store_id' => $store->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'seller-token',
        'refresh_token' => 'refresh-token',
        'provider_user_id' => '521008171',
        'connected_at' => now(),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);
    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto aprobado tarde',
        'price' => 45000,
        'stock_quantity' => 0,
        'is_sold_out' => true,
    ]);
    $order = Order::create([
        'store_id' => $store->id,
        'customer_name' => 'Cliente Aprobado Tarde',
        'customer_phone' => '3001234567',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pendiente',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
        'payment_preference_id' => 'pref_late_approved',
        'payment_expires_at' => now()->subMinutes(10),
        'total' => 45000,
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 1,
        'price' => 45000,
    ]);

    Http::fake([
        'https://api.mercadopago.com/merchant_orders/search*' => Http::response([
            'elements' => [[
                'id' => 987,
                'status' => 'closed',
                'external_reference' => $order->admin_token,
                'preference_id' => 'pref_late_approved',
                'payments' => [[
                    'id' => 456,
                    'status' => 'approved',
                    'transaction_amount' => 45000,
                    'date_approved' => '2026-05-08T12:00:00.000-05:00',
                ]],
            ]],
        ]),
    ]);

    $this->artisan('payments:expire-pending')
        ->assertSuccessful()
        ->expectsOutput('Pedidos Mercado Pago vencidos: 0');

    $order->refresh();

    expect($order->status)->toBe('pagado')
        ->and($order->payment_status)->toBe(Order::PAYMENT_STATUS_APPROVED)
        ->and($order->payment_id)->toBe('456')
        ->and($product->refresh()->stock_quantity)->toBe(0)
        ->and($product->is_sold_out)->toBeTrue();
});

test('expired mercadopago pending order keeps stock when merchant order has active payment', function () {
    config(['services.mercadopago.payment_expiration_grace_minutes' => 0]);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Pago Activo',
        'slug' => 'tienda-pago-activo',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    StorePaymentAccount::create([
        'store_id' => $store->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'seller-token',
        'refresh_token' => 'refresh-token',
        'provider_user_id' => '521008171',
        'connected_at' => now(),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);
    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto pago activo',
        'price' => 45000,
        'stock_quantity' => 0,
        'is_sold_out' => true,
    ]);
    $order = Order::create([
        'store_id' => $store->id,
        'customer_name' => 'Cliente Activo',
        'customer_phone' => '3001234567',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pendiente',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
        'payment_preference_id' => 'pref_active',
        'payment_expires_at' => now()->subMinutes(10),
        'total' => 45000,
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 1,
        'price' => 45000,
    ]);

    Http::fake([
        'https://api.mercadopago.com/merchant_orders/search*' => Http::response([
            'elements' => [[
                'id' => 654,
                'status' => 'opened',
                'external_reference' => $order->admin_token,
                'preference_id' => 'pref_active',
                'payments' => [[
                    'id' => 789,
                    'status' => 'pending',
                    'transaction_amount' => 45000,
                ]],
            ]],
        ]),
    ]);

    $this->artisan('payments:expire-pending')
        ->assertSuccessful()
        ->expectsOutput('Pedidos Mercado Pago vencidos: 0');

    expect($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING)
        ->and($product->refresh()->stock_quantity)->toBe(0)
        ->and($product->is_sold_out)->toBeTrue();
});

test('expired mercadopago pending order keeps stock when merchant order lookup has connection failure', function () {
    config(['services.mercadopago.payment_expiration_grace_minutes' => 0]);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Merchant Conexion',
        'slug' => 'tienda-merchant-conexion',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    StorePaymentAccount::create([
        'store_id' => $store->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'seller-token',
        'refresh_token' => 'refresh-token',
        'provider_user_id' => '521008171',
        'expires_at' => now()->addHour(),
        'connected_at' => now(),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);
    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto merchant conexion',
        'price' => 45000,
        'stock_quantity' => 0,
        'is_sold_out' => true,
    ]);
    $order = Order::create([
        'store_id' => $store->id,
        'customer_name' => 'Cliente Merchant Conexion',
        'customer_phone' => '3001234567',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pendiente',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
        'payment_preference_id' => 'pref_connection_failure',
        'payment_expires_at' => now()->subMinutes(10),
        'total' => 45000,
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 1,
        'price' => 45000,
    ]);

    Http::fake([
        'https://api.mercadopago.com/merchant_orders/search*' => fn () => throw new ConnectionException('timeout'),
    ]);

    $this->artisan('payments:expire-pending')
        ->assertSuccessful()
        ->expectsOutput('Pedidos Mercado Pago vencidos: 0');

    expect($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING)
        ->and($product->refresh()->stock_quantity)->toBe(0)
        ->and($product->is_sold_out)->toBeTrue();
});

test('mercadopago pending orders inside grace window keep reserved stock', function () {
    config(['services.mercadopago.payment_expiration_grace_minutes' => 30]);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Gracia Pago',
        'slug' => 'tienda-gracia-pago',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto en gracia',
        'price' => 45000,
        'stock_quantity' => 0,
        'is_sold_out' => true,
    ]);
    $order = Order::create([
        'store_id' => $store->id,
        'customer_name' => 'Cliente Gracia',
        'customer_phone' => '3001234567',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pendiente',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
        'payment_expires_at' => now()->subMinutes(10),
        'total' => 45000,
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 1,
        'price' => 45000,
    ]);

    $this->artisan('payments:expire-pending')
        ->assertSuccessful()
        ->expectsOutput('Pedidos Mercado Pago vencidos: 0');

    expect($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING)
        ->and($product->refresh()->stock_quantity)->toBe(0)
        ->and($product->is_sold_out)->toBeTrue();
});

test('mercadopago return shows received order page', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Regreso Pago',
        'slug' => 'tienda-regreso-pago',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    $order = Order::create([
        'store_id' => $store->id,
        'customer_name' => 'Cliente Regreso',
        'customer_phone' => '3001234567',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pagado',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_APPROVED,
        'payment_provider_reference' => '123',
        'total' => 45000,
        'paid_at' => now(),
    ]);

    $this->get(route('cart.mercadopago.return', ['order' => $order, 'result' => 'success']))
        ->assertOk()
        ->assertSee('Pedido recibido')
        ->assertSee('Numero de pedido')
        ->assertSee('#' . $order->id)
        ->assertSee('Estado del pago')
        ->assertSee('Aprobado')
        ->assertSee('Mercado Pago')
        ->assertSee('Volver a la tienda')
        ->assertSee(route('store.show', $store->slug), false);
});

test('mercadopago return explains when immediate confirmation fails', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Confirmacion Lenta',
        'slug' => 'tienda-confirmacion-lenta',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    StorePaymentAccount::create([
        'store_id' => $store->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'seller-token',
        'refresh_token' => 'refresh-token',
        'provider_user_id' => '521008171',
        'connected_at' => now(),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);
    $order = Order::create([
        'store_id' => $store->id,
        'customer_name' => 'Cliente Confirmacion',
        'customer_phone' => '3001234567',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pendiente',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
        'total' => 45000,
    ]);

    Http::fake([
        'https://api.mercadopago.com/v1/payments/pay_123' => Http::response([], 500),
    ]);

    $this->get(route('cart.mercadopago.return', [
        'order' => $order,
        'result' => 'success',
        'payment_id' => 'pay_123',
    ]))
        ->assertOk()
        ->assertSee('Estamos confirmando tu pago con Mercado Pago');
});

test('mercadopago webhook rejects invalid signatures', function () {
    config(['services.mercadopago.webhook_secret' => 'webhook-secret']);

    $storeUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Firma Mala',
        'slug' => 'tienda-firma-mala',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    $order = Order::create([
        'store_id' => $store->id,
        'customer_name' => 'Cliente Firma',
        'customer_phone' => '3001234567',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pendiente',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
        'total' => 45000,
    ]);

    Http::fake();

    $this->postJson(route('cart.mercadopago.webhook') . '?type=payment&data.id=pay_123', [], [
        'x-request-id' => 'request-123',
        'x-signature' => 'ts=1715191200,v1=invalid',
    ])->assertUnauthorized();

    expect($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING);
    Http::assertNothingSent();
});

test('payment methods panel shows connected mercadopago account without exposing tokens', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Mercado Pago',
        'slug' => 'tienda-mercado-pago',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PREMIUM,
        'is_active' => true,
    ]);
    StorePaymentAccount::create([
        'store_id' => $store->id,
        'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'access_token' => 'access-token-no-visible',
        'refresh_token' => 'refresh-token-no-visible',
        'provider_user_id' => '987654321',
        'connected_at' => now(),
        'status' => StorePaymentAccount::STATUS_CONNECTED,
    ]);

    $this->actingAs($storeUser)
        ->get('/admin/payments')
        ->assertOk()
        ->assertSee('Conectado')
        ->assertSee('ID 987654321')
        ->assertSee('Tokens encriptados')
        ->assertDontSee('access-token-no-visible')
        ->assertDontSee('refresh-token-no-visible');
});

test('store user can start mercadopago oauth connection', function () {
    config([
        'services.mercadopago.client_id' => '123456789',
        'services.mercadopago.client_secret' => 'client-secret',
        'services.mercadopago.redirect_uri' => 'https://vendlysuite.com/admin/payments/mercadopago/callback',
    ]);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda OAuth Inicio',
        'slug' => 'tienda-oauth-inicio',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PREMIUM,
        'is_active' => true,
    ]);

    $response = $this->actingAs($storeUser)
        ->get(route('admin.payments.mercadopago.connect'));

    $response->assertRedirectContains('https://auth.mercadopago.com/authorization');
    $response->assertRedirectContains('client_id=123456789');
    $response->assertRedirectContains('response_type=code');
    $response->assertRedirectContains('platform_id=mp');
    $response->assertRedirectContains('redirect_uri=https%3A%2F%2Fvendlysuite.com%2Fadmin%2Fpayments%2Fmercadopago%2Fcallback');
    $this->assertNotEmpty(session('mercadopago_oauth.state'));
});

test('mercadopago oauth callback stores connected account tokens', function () {
    config([
        'services.mercadopago.client_id' => '123456789',
        'services.mercadopago.client_secret' => 'client-secret',
        'services.mercadopago.redirect_uri' => 'https://vendlysuite.com/admin/payments/mercadopago/callback',
    ]);

    Http::fake([
        'https://api.mercadopago.com/oauth/token' => Http::response([
            'access_token' => 'seller-access-token',
            'refresh_token' => 'seller-refresh-token',
            'public_key' => 'seller-public-key',
            'user_id' => 987654321,
            'expires_in' => 15552000,
        ], 200),
    ]);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda OAuth Callback',
        'slug' => 'tienda-oauth-callback',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PREMIUM,
        'is_active' => true,
    ]);
    $state = Str::random(48);

    $this->withSession([
        'mercadopago_oauth' => [
            'state' => $state,
            'store_id' => $store->id,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ],
    ])
        ->actingAs($storeUser)
        ->get(route('admin.payments.mercadopago.callback', [
            'code' => 'TG-test-code',
            'state' => $state,
        ]))
        ->assertRedirect(route('admin.payments.index'))
        ->assertSessionHas('success');

    $account = $store->mercadoPagoAccount()->first();
    $rawAccount = DB::table('store_payment_accounts')->where('id', $account->id)->first();

    expect($account)->not->toBeNull()
        ->and($account->access_token)->toBe('seller-access-token')
        ->and($account->refresh_token)->toBe('seller-refresh-token')
        ->and($account->public_key)->toBe('seller-public-key')
        ->and($account->provider_user_id)->toBe('987654321')
        ->and($account->isConnected())->toBeTrue()
        ->and($rawAccount->access_token)->not->toBe('seller-access-token');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.mercadopago.com/oauth/token'
        && $request['grant_type'] === 'authorization_code'
        && $request['code'] === 'TG-test-code'
        && $request['client_id'] === '123456789');
});

test('mercadopago oauth callback rejects incomplete credentials response', function () {
    config([
        'services.mercadopago.client_id' => '123456789',
        'services.mercadopago.client_secret' => 'client-secret',
        'services.mercadopago.redirect_uri' => 'https://vendlysuite.com/admin/payments/mercadopago/callback',
    ]);

    Http::fake([
        'https://api.mercadopago.com/oauth/token' => Http::response([
            'refresh_token' => 'seller-refresh-token',
        ], 200),
    ]);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda OAuth Incompleto',
        'slug' => 'tienda-oauth-incompleto',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PREMIUM,
        'is_active' => true,
    ]);
    $state = Str::random(48);

    $this->withSession([
        'mercadopago_oauth' => [
            'state' => $state,
            'store_id' => $store->id,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ],
    ])
        ->actingAs($storeUser)
        ->get(route('admin.payments.mercadopago.callback', [
            'code' => 'TG-test-code',
            'state' => $state,
        ]))
        ->assertRedirect(route('admin.payments.index'))
        ->assertSessionHas('error');

    expect($store->mercadoPagoAccount()->exists())->toBeFalse();

    Http::assertSent(fn ($request) => $request->url() === 'https://api.mercadopago.com/oauth/token'
        && $request['grant_type'] === 'authorization_code'
        && $request['code'] === 'TG-test-code');
});

test('mercadopago oauth callback handles token connection failure', function () {
    config([
        'services.mercadopago.client_id' => '123456789',
        'services.mercadopago.client_secret' => 'client-secret',
        'services.mercadopago.redirect_uri' => 'https://vendlysuite.com/admin/payments/mercadopago/callback',
    ]);

    Http::fake([
        'https://api.mercadopago.com/oauth/token' => fn () => throw new ConnectionException('timeout'),
    ]);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda OAuth Conexion',
        'slug' => 'tienda-oauth-conexion',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PREMIUM,
        'is_active' => true,
    ]);
    $state = Str::random(48);

    $this->withSession([
        'mercadopago_oauth' => [
            'state' => $state,
            'store_id' => $store->id,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ],
    ])
        ->actingAs($storeUser)
        ->get(route('admin.payments.mercadopago.callback', [
            'code' => 'TG-test-code',
            'state' => $state,
        ]))
        ->assertRedirect(route('admin.payments.index'))
        ->assertSessionHas('error');

    expect($store->mercadoPagoAccount()->exists())->toBeFalse();
});

test('mercadopago oauth callback rejects invalid state', function () {
    Http::fake();

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda OAuth Invalido',
        'slug' => 'tienda-oauth-invalido',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PREMIUM,
        'is_active' => true,
    ]);

    $this->withSession([
        'mercadopago_oauth' => [
            'state' => 'estado-correcto',
            'store_id' => $store->id,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ],
    ])
        ->actingAs($storeUser)
        ->get(route('admin.payments.mercadopago.callback', [
            'code' => 'TG-test-code',
            'state' => 'estado-malo',
        ]))
        ->assertRedirect(route('admin.payments.index'))
        ->assertSessionHas('error');

    expect($store->mercadoPagoAccount()->exists())->toBeFalse();
    Http::assertNothingSent();
});

test('public storefront routes work from a pro store subdomain', function () {
    config(['app.url' => 'https://vendlysuite.com']);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Subdominio Navegable',
        'slug' => 'tienda-subdominio-navegable',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PRO,
        'subdomain' => 'navegable',
        'shop_copy' => 'Somos una tienda navegable por subdominio.',
        'mission' => 'Atender pedidos desde un subdominio.',
        'vision' => 'Crecer con una direccion facil de compartir.',
        'is_active' => true,
    ]);
    $category = StoreCategory::create([
        'store_id' => $store->id,
        'name' => 'Destacados',
        'slug' => 'destacados',
        'is_active' => true,
    ]);
    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto por subdominio',
        'category' => $category->name,
        'price' => 25000,
    ]);

    $this->get('https://navegable.vendlysuite.com/')
        ->assertOk()
        ->assertSee('Tienda Subdominio Navegable')
        ->assertSee('Producto por subdominio')
        ->assertSee('<link rel="canonical" href="https://navegable.vendlysuite.com">', false)
        ->assertSee('<meta property="og:url" content="https://navegable.vendlysuite.com">', false)
        ->assertSee('https://navegable.vendlysuite.com/productos', false)
        ->assertSee('https://navegable.vendlysuite.com/productos/' . $product->publicRouteKey(), false)
        ->assertSee('https://navegable.vendlysuite.com/categorias/' . $category->slug, false)
        ->assertDontSee('/tienda-subdominio-navegable/productos', false);

    $this->get('https://navegable.vendlysuite.com/productos')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="https://navegable.vendlysuite.com/productos">', false)
        ->assertSee('Producto por subdominio');

    $subdomainProductUrl = 'https://navegable.vendlysuite.com/productos/' . $product->publicRouteKey();
    $encodedSubdomainProductUrl = rawurlencode($subdomainProductUrl);

    $this->get($subdomainProductUrl)
        ->assertOk()
        ->assertSee('<link rel="canonical" href="' . $subdomainProductUrl . '">', false)
        ->assertSee('Producto por subdominio')
        ->assertSee('https://www.facebook.com/sharer/sharer.php?u=' . $encodedSubdomainProductUrl, false)
        ->assertSee('https://wa.me/?text=', false)
        ->assertSee($encodedSubdomainProductUrl, false)
        ->assertSee('https://twitter.com/intent/tweet?url=' . $encodedSubdomainProductUrl, false)
        ->assertSee('data-copy-product-link="' . $subdomainProductUrl . '"', false)
        ->assertDontSee('/tienda-subdominio-navegable/productos/' . $product->publicRouteKey(), false);

    $this->get('https://navegable.vendlysuite.com/categorias/' . $category->slug)
        ->assertOk()
        ->assertSee('<link rel="canonical" href="https://navegable.vendlysuite.com/categorias/' . $category->slug . '">', false)
        ->assertSee('Destacados')
        ->assertSee('Producto por subdominio');

    $this->get('https://navegable.vendlysuite.com/nosotros')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="https://navegable.vendlysuite.com/nosotros">', false)
        ->assertSee('Somos una tienda navegable por subdominio.');

    $this->get('https://vendlysuite.com/tienda-subdominio-navegable/productos/' . $product->publicRouteKey())
        ->assertOk()
        ->assertSee('<link rel="canonical" href="' . route('store.product.show', ['slug' => $store->slug, 'product' => $product->publicRouteKey()]) . '">', false);
});

test('storefront url service generates subdomain aware public links', function () {
    config(['app.url' => 'https://vendlysuite.com']);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Links Subdominio',
        'slug' => 'tienda-links-subdominio',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PRO,
        'subdomain' => 'links',
        'is_active' => true,
    ]);
    $category = StoreCategory::create([
        'store_id' => $store->id,
        'name' => 'Ropa',
        'slug' => 'ropa',
        'is_active' => true,
    ]);
    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto Link',
        'price' => 25000,
    ]);

    $service = app(StorefrontUrlService::class);
    $subdomainRequest = request()->create('https://links.vendlysuite.com/productos');
    $mainDomainRequest = request()->create('https://vendlysuite.com/tienda-links-subdominio/productos');

    expect($service->home($store, $subdomainRequest))->toBe('https://links.vendlysuite.com')
        ->and($service->products($store, $subdomainRequest))->toBe('https://links.vendlysuite.com/productos')
        ->and($service->product($store, $product, $subdomainRequest))->toBe('https://links.vendlysuite.com/productos/' . $product->publicRouteKey())
        ->and($service->category($store, $category, $subdomainRequest))->toBe('https://links.vendlysuite.com/categorias/ropa')
        ->and($service->about($store, $subdomainRequest))->toBe('https://links.vendlysuite.com/nosotros')
        ->and($service->products($store, $subdomainRequest, ['q' => 'camisa']))->toBe('https://links.vendlysuite.com/productos?q=camisa')
        ->and($service->products($store, $mainDomainRequest))->toBe(route('store.products.index', $store->slug));
});

test('storefront url service keeps verified custom domain links on the current host', function () {
    config(['app.url' => 'https://vendlysuite.com']);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Links Dominio',
        'slug' => 'tienda-links-dominio',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PREMIUM,
        'custom_domain' => 'www.marca.com',
        'custom_domain_status' => Store::CUSTOM_DOMAIN_VERIFIED,
        'custom_domain_verified_at' => now(),
        'is_active' => true,
    ]);
    $category = StoreCategory::create([
        'store_id' => $store->id,
        'name' => 'Ropa',
        'slug' => 'ropa',
        'is_active' => true,
    ]);
    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto Dominio',
        'price' => 25000,
    ]);

    $service = app(StorefrontUrlService::class);
    $customDomainRequest = request()->create('https://www.marca.com/productos');
    $mainDomainRequest = request()->create('https://vendlysuite.com/tienda-links-dominio/productos');

    expect($service->home($store, $customDomainRequest))->toBe('https://www.marca.com')
        ->and($service->products($store, $customDomainRequest))->toBe('https://www.marca.com/productos')
        ->and($service->product($store, $product, $customDomainRequest))->toBe('https://www.marca.com/productos/' . $product->publicRouteKey())
        ->and($service->category($store, $category, $customDomainRequest))->toBe('https://www.marca.com/categorias/ropa')
        ->and($service->about($store, $customDomainRequest))->toBe('https://www.marca.com/nosotros')
        ->and($service->products($store, $customDomainRequest, ['q' => 'camisa']))->toBe('https://www.marca.com/productos?q=camisa')
        ->and($service->products($store, $mainDomainRequest))->toBe(route('store.products.index', $store->slug));
});

test('main domain keeps landing and basic legacy subdomain returns not found', function () {
    config(['app.url' => 'https://vendlysuite.com']);

    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Basica Subdominio Legacy',
        'slug' => 'tienda-basica-subdominio-legacy',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_BASIC,
        'subdomain' => 'legacy',
        'is_active' => true,
    ]);

    $this->get('https://vendlysuite.com/')
        ->assertOk()
        ->assertSee('Vendly | Tiendas online listas para vender');

    $this->get('https://dominio-no-configurado.com/')
        ->assertOk()
        ->assertSee('Vendly | Tiendas online listas para vender');

    $this->get('https://legacy.vendlysuite.com/')
        ->assertNotFound();

    $this->get('https://fantasma.vendlysuite.com/')
        ->assertNotFound();
});

test('basic plan ignores full customization fields from settings', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Basica Apariencia',
        'slug' => 'tienda-basica-apariencia',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_BASIC,
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->post('/admin/store-settings', [
            'name' => $store->name,
            'business_type' => 'store',
            'whatsapp' => $store->whatsapp,
            'brand_color' => '#be123c',
            'background_color' => '#111827',
            'font_family' => 'serif',
            'responsive_product_columns' => 3,
            'show_hero_products_action' => 1,
        ])
        ->assertRedirect('/admin/store-settings');

    $store->refresh();

    expect($store->brand_color)->toBeNull()
        ->and($store->background_color)->toBeNull()
        ->and($store->text_color)->toBe('#111111')
        ->and($store->font_family)->toBe('system')
        ->and($store->responsive_product_columns)->toBe(2)
        ->and($store->show_hero_products_action)->toBeFalse();
});

test('basic plan blocks category management and public category pages', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Basica Categorias',
        'slug' => 'tienda-basica-categorias',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_BASIC,
        'is_active' => true,
    ]);
    StoreCategory::create([
        'store_id' => $store->id,
        'name' => 'Audio',
        'slug' => 'audio',
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->get('/admin/categories')
        ->assertOk()
        ->assertSee('Categorias no disponibles')
        ->assertSee('El plan Basico no incluye categorias');

    $this->actingAs($storeUser)
        ->post('/admin/categories', [
            'name' => 'Ropa',
            'slug' => 'ropa',
        ])
        ->assertRedirect(route('admin.categories.index'))
        ->assertSessionHas('error');

    $this->get('/tienda-basica-categorias/categorias/audio')->assertNotFound();
});

test('basic plan storefront labels uncategorized block as all products', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Basica Catalogo',
        'slug' => 'tienda-basica-catalogo',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_BASIC,
        'is_active' => true,
    ]);
    Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto simple',
        'price' => 25000,
    ]);

    $this->get('/tienda-basica-catalogo')
        ->assertOk()
        ->assertSee('Todos los productos')
        ->assertDontSee('Otros productos');
});

test('basic plan hides existing product galleries after downgrade', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Galeria Downgrade',
        'slug' => 'tienda-galeria-downgrade',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PRO,
        'is_active' => true,
    ]);
    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto con galeria',
        'price' => 25000,
        'image' => 'products/principal.webp',
        'images' => ['products/extra.webp'],
    ]);

    $this->get('/tienda-galeria-downgrade/productos/' . $product->publicRouteKey())
        ->assertOk()
        ->assertSee('products/extra.webp', false);

    $this->actingAs($admin)
        ->put(route('admin.stores.update', $store), [
            'user_id' => $storeUser->id,
            'name' => $store->name,
            'business_type' => 'store',
            'plan' => Store::PLAN_BASIC,
            'slug' => $store->slug,
            'whatsapp' => $store->whatsapp,
            'brand_color' => '#111111',
            'background_color' => '#ffffff',
            'font_family' => 'system',
            'responsive_product_columns' => 2,
            'show_hero_products_action' => 0,
        ])
        ->assertRedirect('/admin/stores');

    $product->refresh();

    expect($product->images)->toBeNull();

    $this->get('/tienda-galeria-downgrade/productos/' . $product->publicRouteKey())
        ->assertOk()
        ->assertDontSee('products/extra.webp', false);
});

test('basic plan limits product creation to twenty products', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Basica Productos',
        'slug' => 'tienda-basica-productos',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_BASIC,
        'is_active' => true,
    ]);

    foreach (range(1, Store::BASIC_PRODUCT_LIMIT) as $index) {
        Product::create([
            'user_id' => $storeUser->id,
            'store_id' => $store->id,
            'name' => 'Producto ' . $index,
            'price' => 10000 + $index,
        ]);
    }

    $this->actingAs($storeUser)
        ->get('/admin/products/create')
        ->assertRedirect('/admin/products')
        ->assertSessionHas('error');

    $this->actingAs($storeUser)
        ->post('/admin/products', [
            'name' => 'Producto extra',
            'price' => 50000,
            'category' => 'Categoria bloqueada',
        ])
        ->assertSessionHas('error');

    expect(Product::where('store_id', $store->id)->count())->toBe(Store::BASIC_PRODUCT_LIMIT);
});

test('pro plan limits product creation to one hundred products', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Pro Productos',
        'slug' => 'tienda-pro-productos',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PRO,
        'is_active' => true,
    ]);

    foreach (range(1, Store::PRO_PRODUCT_LIMIT) as $index) {
        Product::create([
            'user_id' => $storeUser->id,
            'store_id' => $store->id,
            'name' => 'Producto Pro ' . $index,
            'price' => 10000 + $index,
        ]);
    }

    $this->actingAs($storeUser)
        ->get('/admin/products/create')
        ->assertRedirect('/admin/products')
        ->assertSessionHas('error');

    $this->actingAs($storeUser)
        ->post('/admin/products', [
            'name' => 'Producto Pro extra',
            'price' => 50000,
        ])
        ->assertSessionHas('error');

    expect(Product::where('store_id', $store->id)->count())->toBe(Store::PRO_PRODUCT_LIMIT);
});

test('admin cannot downgrade a store when existing products exceed the target plan limit', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Pro Grande',
        'slug' => 'tienda-pro-grande',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PRO,
        'is_active' => true,
    ]);

    foreach (range(1, Store::BASIC_PRODUCT_LIMIT + 1) as $index) {
        Product::create([
            'user_id' => $storeUser->id,
            'store_id' => $store->id,
            'name' => 'Producto Grande ' . $index,
            'price' => 10000 + $index,
        ]);
    }

    $this->actingAs($admin)
        ->put(route('admin.stores.update', $store), [
            'user_id' => $storeUser->id,
            'name' => $store->name,
            'business_type' => 'store',
            'plan' => Store::PLAN_BASIC,
            'slug' => $store->slug,
            'whatsapp' => $store->whatsapp,
            'brand_color' => '#111111',
            'background_color' => '#ffffff',
            'font_family' => 'system',
            'responsive_product_columns' => 2,
            'show_hero_products_action' => 0,
        ])
        ->assertSessionHas('error');

    expect($store->refresh()->plan)->toBe(Store::PLAN_PRO)
        ->and(Product::where('store_id', $store->id)->count())->toBe(Store::BASIC_PRODUCT_LIMIT + 1);
});

test('admin cannot move a product into a store that reached its plan limit', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $basicUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $sourceUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $basicStore = Store::create([
        'user_id' => $basicUser->id,
        'name' => 'Tienda Basica Llena',
        'slug' => 'tienda-basica-llena',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_BASIC,
        'is_active' => true,
    ]);
    $sourceStore = Store::create([
        'user_id' => $sourceUser->id,
        'name' => 'Tienda Origen Producto',
        'slug' => 'tienda-origen-producto',
        'whatsapp' => '573001112244',
        'plan' => Store::PLAN_PRO,
        'is_active' => true,
    ]);

    foreach (range(1, Store::BASIC_PRODUCT_LIMIT) as $index) {
        Product::create([
            'user_id' => $basicUser->id,
            'store_id' => $basicStore->id,
            'name' => 'Producto Basico ' . $index,
            'price' => 10000 + $index,
        ]);
    }

    $product = Product::create([
        'user_id' => $sourceUser->id,
        'store_id' => $sourceStore->id,
        'name' => 'Producto a mover',
        'price' => 75000,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), [
            'store_id' => $basicStore->id,
            'name' => $product->name,
            'price' => $product->price,
        ])
        ->assertSessionHas('error');

    expect($product->refresh()->store_id)->toBe($sourceStore->id)
        ->and(Product::where('store_id', $basicStore->id)->count())->toBe(Store::BASIC_PRODUCT_LIMIT);
});

test('existing products can be edited when a legacy store is already over its plan limit', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Basica Heredada',
        'slug' => 'tienda-basica-heredada',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_BASIC,
        'is_active' => true,
    ]);

    foreach (range(1, Store::BASIC_PRODUCT_LIMIT + 1) as $index) {
        $product = Product::create([
            'user_id' => $storeUser->id,
            'store_id' => $store->id,
            'name' => 'Producto Heredado ' . $index,
            'price' => 10000 + $index,
        ]);
    }

    $this->actingAs($storeUser)
        ->put(route('admin.products.update', $product), [
            'name' => 'Producto heredado editado',
            'price' => 45000,
        ])
        ->assertRedirect('/admin/products')
        ->assertSessionHas('success');

    expect($product->refresh()->name)->toBe('Producto heredado editado')
        ->and(Product::where('store_id', $store->id)->count())->toBe(Store::BASIC_PRODUCT_LIMIT + 1);
});

test('pro plan store user can see visit statistics', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Pro Visitas',
        'slug' => 'tienda-pro-visitas',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PRO,
        'views_count' => 42,
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->get(route('admin.store.visits'))
        ->assertOk()
        ->assertSee('Visitas de tu tienda')
        ->assertSee('42')
        ->assertSee('Pro');
});

test('basic plan store user cannot see visit statistics', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Basica Visitas',
        'slug' => 'tienda-basica-visitas',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_BASIC,
        'views_count' => 42,
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->get(route('admin.store.visits'))
        ->assertForbidden();
});

test('cart rejects products from an expired owner account', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDays(10),
        'active_ends_at' => now()->subDay(),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda vencida',
        'slug' => 'tienda-vencida',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Producto vencido',
        'price' => 25000,
    ]);

    $this->post('/cart/add/' . $product->id)
        ->assertSessionHas('error', 'Esta tienda no esta disponible para recibir pedidos.');

    expect(session('cart'))->toBeNull();
});

test('checkout rejects an old cart when owner account expires', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDays(10),
        'active_ends_at' => now()->subDay(),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda vieja',
        'slug' => 'tienda-vieja',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Producto viejo',
        'price' => 35000,
    ]);

    $cart = [
        (string) $product->id => [
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'store_id' => $store->id,
        ],
    ];

    $this->withSession(['cart' => $cart])
        ->post('/cart/whatsapp', [
            'name' => 'Cliente',
            'last_name' => 'Prueba',
            'phone' => '3001112233',
            'address' => 'Calle 1',
            'neighborhood' => 'Chapinero',
            'city' => 'Bogota',
            'document' => '123456',
        ])
        ->assertRedirect(route('cart.index', ['store' => $store->slug]))
        ->assertSessionHas('error', 'Esta tienda no esta disponible para recibir pedidos.');

    $this->assertDatabaseCount('orders', 0);
});

test('technology storefront renders variant selectors before adding to cart', function () {
    $user = User::factory()->create();

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tech Store',
        'slug' => 'tech-store',
        'whatsapp' => '573001112233',
        'business_type' => 'technology',
        'is_active' => true,
    ]);

    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Audifonos',
        'price' => 99000,
        'sizes' => ['Unica'],
        'colors' => ['Negro'],
    ]);

    $this->get('/tech-store/productos/' . $product->publicRouteKey())
        ->assertOk()
        ->assertSee('name="size"', false)
        ->assertSee('name="color"', false);
});

test('restaurant storefront renders as a menu', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Bistro Menu',
        'slug' => 'bistro-menu',
        'whatsapp' => '573001112233',
        'business_type' => 'restaurant',
        'is_active' => true,
    ]);
    $store->ensureCategoryRecords();
    $category = $store->categories()->where('name', 'Entradas')->first();

    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Croquetas de la casa',
        'category' => $category->name,
        'price' => 28000,
        'description' => 'Crujientes, con salsa cremosa y hierbas frescas.',
    ]);

    $this->get('/bistro-menu')
        ->assertOk()
        ->assertSee('restaurant-menu', false)
        ->assertSee('Croquetas de la casa')
        ->assertSee('Crujientes, con salsa cremosa y hierbas frescas.')
        ->assertSee('Pedir')
        ->assertSee('Carta completa')
        ->assertDontSee('restaurant-product-card', false);

    $this->get('/bistro-menu/productos/' . $product->publicRouteKey())
        ->assertOk()
        ->assertSee('Detalle del plato')
        ->assertSee('Sobre este plato')
        ->assertSee('Agregar al pedido')
        ->assertSee('Pedir por WhatsApp')
        ->assertDontSee('Comprar por WhatsApp');
});

test('reservation stores use service and reservation language', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Agenda Spa',
        'slug' => 'agenda-spa',
        'business_type' => 'reservations',
        'whatsapp' => '573001112233',
        'business_hours' => 'Lunes a viernes 9:00 AM - 5:00 PM',
        'is_active' => true,
        'show_hero_products_action' => true,
    ]);

    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Masaje relajante',
        'price' => 80000,
        'category' => 'Servicios',
    ]);

    $this->get('/agenda-spa')
        ->assertOk()
        ->assertSee('Ver todos los servicios')
        ->assertSee('Servicios');

    $this->get('/agenda-spa/productos')
        ->assertOk()
        ->assertSee('Servicios')
        ->assertSee('1 servicios')
        ->assertSee('solicita tu reserva por WhatsApp');

    $this->get(route('store.product.show', [
        'slug' => $store->slug,
        'product' => $product->publicRouteKey(),
    ]))
        ->assertOk()
        ->assertSee('Vista previa del servicio')
        ->assertSee('Agregar a la reserva')
        ->assertSee('Reservar por WhatsApp');
});

test('reservation checkout asks for date and time and sends a reservation whatsapp message', function () {
    $reservationDate = now()->addWeek()->toDateString();
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Agenda Medica',
        'slug' => 'agenda-medica',
        'business_type' => 'reservations',
        'whatsapp' => '573001112233',
        'business_hours' => 'Lunes a viernes 8:00 AM - 4:00 PM',
        'is_active' => true,
    ]);

    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Consulta inicial',
        'price' => 120000,
        'category' => 'Consultas',
    ]);

    $this->post('/cart/add/' . $product->id, ['quantity' => 1])
        ->assertRedirect();

    $this->get(route('cart.index', ['store' => $store->slug]))
        ->assertOk()
        ->assertSee('Datos de la reserva')
        ->assertSee('Horario de atencion')
        ->assertSee('name="reservation_date"', false)
        ->assertSee('name="reservation_time"', false);

    $this->post(route('cart.whatsapp', ['store' => $store->slug]), [
        'name' => 'Cliente',
        'last_name' => 'Reserva',
        'phone' => '3001234567',
        'address' => 'Consulta online',
        'neighborhood' => 'Virtual',
        'city' => 'Bogota',
        'document' => '123456',
        'reservation_date' => $reservationDate,
        'reservation_time' => '14:30',
        'notes' => 'Prefiere atencion virtual',
    ])->assertRedirectContains('https://wa.me/573001112233');

    $order = Order::where('store_id', $store->id)->latest('id')->firstOrFail();

    expect($order->reservation_date->toDateString())->toBe($reservationDate);
    expect($order->reservation_time)->toBe('14:30');

    $message = app(\App\Services\WhatsAppOrderMessageBuilder::class)->message($order->load(['items', 'store']));

    expect($message)->toContain('Nueva reserva');
    expect($message)->toContain('Fecha deseada: ' . $reservationDate);
    expect($message)->toContain('Hora deseada: 14:30');
    expect($message)->toContain('Horario de atencion: Lunes a viernes 8:00 AM - 4:00 PM');
    expect($message)->toContain('Servicio: Consulta inicial x1');
});

test('reservation checkout requires date and time even without store query parameter', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Agenda Sin Query',
        'slug' => 'agenda-sin-query',
        'business_type' => 'reservations',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Servicio sin query',
        'price' => 90000,
    ]);

    $this->post('/cart/add/' . $product->id, ['quantity' => 1])
        ->assertRedirect();

    $this->post(route('cart.whatsapp'), [
        'name' => 'Cliente',
        'last_name' => 'Reserva',
        'phone' => '3001234567',
        'address' => 'Consulta online',
        'neighborhood' => 'Virtual',
        'city' => 'Bogota',
        'document' => '123456',
    ])->assertSessionHasErrors(['reservation_date', 'reservation_time']);

    $this->assertDatabaseMissing('orders', [
        'store_id' => $store->id,
        'customer_name' => 'Cliente Reserva',
    ]);
});

test('reservation checkout rejects past dates and invalid times', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Agenda Valida',
        'slug' => 'agenda-valida',
        'business_type' => 'reservations',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Servicio validado',
        'price' => 90000,
    ]);

    $this->post('/cart/add/' . $product->id, ['quantity' => 1])
        ->assertRedirect();

    $this->post(route('cart.whatsapp', ['store' => $store->slug]), [
        'name' => 'Cliente',
        'last_name' => 'Reserva',
        'phone' => '3001234567',
        'address' => 'Consulta online',
        'neighborhood' => 'Virtual',
        'city' => 'Bogota',
        'document' => '123456',
        'reservation_date' => now()->subDay()->toDateString(),
        'reservation_time' => 'cuando pueda',
    ])->assertSessionHasErrors(['reservation_date', 'reservation_time']);

    $this->assertDatabaseMissing('orders', [
        'store_id' => $store->id,
        'customer_name' => 'Cliente Reserva',
    ]);
});

test('reservation checkout rejects dates and times outside the store schedule', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Agenda Controlada',
        'slug' => 'agenda-controlada',
        'business_type' => 'reservations',
        'whatsapp' => '573001112233',
        'reservation_available_days' => ['monday'],
        'reservation_time_start' => '09:00',
        'reservation_time_end' => '17:00',
        'is_active' => true,
    ]);

    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Consulta con agenda',
        'price' => 90000,
    ]);

    $this->post('/cart/add/' . $product->id, ['quantity' => 1])
        ->assertRedirect();

    $this->get(route('cart.index', ['store' => $store->slug]))
        ->assertOk()
        ->assertSee('Dias disponibles: Lunes')
        ->assertSee('Horario de reservas: 09:00 - 17:00');

    $unavailableDate = now()->addDay();

    while ($unavailableDate->isMonday()) {
        $unavailableDate->addDay();
    }

    $this->post(route('cart.whatsapp', ['store' => $store->slug]), [
        'name' => 'Cliente',
        'last_name' => 'Reserva',
        'phone' => '3001234567',
        'address' => 'Consulta online',
        'neighborhood' => 'Virtual',
        'city' => 'Bogota',
        'document' => '123456',
        'reservation_date' => $unavailableDate->toDateString(),
        'reservation_time' => '18:00',
    ])
        ->assertRedirect(route('cart.index', ['store' => $store->slug]))
        ->assertSessionHas('error', 'La fecha u hora seleccionada no esta dentro de la agenda disponible.');

    $this->assertDatabaseMissing('orders', [
        'store_id' => $store->id,
        'customer_name' => 'Cliente Reserva',
    ]);
});

test('non reservation products respect stock and are marked sold out after checkout', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda Stock',
        'slug' => 'tienda-stock',
        'business_type' => 'store',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Producto limitado',
        'price' => 45000,
        'stock_quantity' => 2,
    ]);

    $this->get(route('store.product.show', [
        'slug' => $store->slug,
        'product' => $product->publicRouteKey(),
    ]))
        ->assertOk()
        ->assertSee('2 disponibles');

    $this->post('/cart/add/' . $product->id, ['quantity' => 2])
        ->assertRedirect()
        ->assertSessionMissing('error');

    $this->post('/cart/add/' . $product->id, ['quantity' => 1])
        ->assertSessionHas('error', 'Solo quedan 2 unidades disponibles.');

    $this->post(route('cart.whatsapp', ['store' => $store->slug]), [
        'name' => 'Cliente',
        'last_name' => 'Stock',
        'phone' => '3001234567',
        'address' => 'Calle 10',
        'neighborhood' => 'Cedritos',
        'city' => 'Bogota',
        'document' => '123456',
    ])->assertRedirectContains('https://wa.me/573001112233');

    expect($product->refresh()->stock_quantity)->toBe(0);
    expect($product->is_sold_out)->toBeTrue();
});

test('brand color must be a hex value and is normalized', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $storeUser = User::factory()->create();

    $this->actingAs($admin)
        ->post('/admin/stores', [
            'user_id' => $storeUser->id,
            'name' => 'Color Store',
            'business_type' => 'store',
            'slug' => 'color-store',
            'whatsapp' => '573001112233',
            'brand_color' => 'abc',
        ])
        ->assertRedirect('/admin/stores');

    expect(Store::where('slug', 'color-store')->first()->brand_color)->toBe('#aabbcc');

    $badColorUser = User::factory()->create();

    $this->actingAs($admin)
        ->post('/admin/stores', [
            'user_id' => $badColorUser->id,
            'name' => 'Bad Color Store',
            'business_type' => 'store',
            'slug' => 'bad-color-store',
            'whatsapp' => '573001112233',
            'brand_color' => '#fff;background:red',
        ])
        ->assertSessionHasErrors('brand_color');
});

test('store menu text contrast follows the automatic branded menu background', function () {
    $storeUser = User::factory()->create();

    Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Verde',
        'slug' => 'tienda-verde',
        'whatsapp' => '573001112233',
        'business_type' => 'store',
        'is_active' => true,
        'brand_color' => '#008a29',
        'background_color' => '#111827',
    ]);

    $this->get('/tienda-verde')
        ->assertOk()
        ->assertSee('--store-text: #ffffff', false)
        ->assertSee('--store-nav-text: #111111', false);
});

test('store responsive product columns can be configured from the panel', function () {
    $storeUser = User::factory()->create();

    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Columnas',
        'slug' => 'tienda-columnas',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->post('/admin/store-settings', [
            'name' => $store->name,
            'business_type' => 'store',
            'whatsapp' => $store->whatsapp,
            'responsive_product_columns' => 1,
            'show_hero_products_action' => 1,
        ])
        ->assertRedirect('/admin/store-settings');

    expect($store->refresh()->responsive_product_columns)->toBe(1);

    $this->get('/tienda-columnas')
        ->assertOk()
        ->assertSee('--responsive-product-columns: 1', false);

    $this->actingAs($storeUser)
        ->post('/admin/store-settings', [
            'name' => $store->name,
            'business_type' => 'store',
            'whatsapp' => $store->whatsapp,
            'responsive_product_columns' => 4,
        ])
        ->assertSessionHasErrors('responsive_product_columns');
});

test('hero products action is disabled by default and can be enabled from the panel', function () {
    $storeUser = User::factory()->create();

    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Hero',
        'slug' => 'tienda-hero',
        'whatsapp' => '573001112233',
        'shop_copy' => 'Texto visible cuando el hero esta activo.',
        'is_active' => true,
    ]);

    $this->get('/tienda-hero')
        ->assertOk()
        ->assertDontSee('store-hero-products-action', false)
        ->assertDontSee('<p class="store-hero-short-copy">Texto visible cuando el hero esta activo.</p>', false);

    $this->actingAs($storeUser)
        ->post('/admin/store-settings', [
            'name' => $store->name,
            'business_type' => 'store',
            'whatsapp' => $store->whatsapp,
            'responsive_product_columns' => 2,
            'show_hero_products_action' => 1,
        ])
        ->assertRedirect('/admin/store-settings');

    expect($store->refresh()->show_hero_products_action)->toBeTrue();

    $this->get('/tienda-hero')
        ->assertOk()
        ->assertSee('store-hero-products-action', false)
        ->assertSee('Texto visible cuando el hero esta activo.');
});

test('admin dashboard shows the latest ten updates and removes older ones', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $updates = app(AdminUpdateService::class);

    foreach (range(1, 12) as $index) {
        $updates->record('Actualizacion ' . $index, 'Detalle ' . $index, 'sistema');
    }

    expect(AdminUpdate::count())->toBe(10);

    $this->assertDatabaseMissing('admin_updates', ['title' => 'Actualizacion 1']);
    $this->assertDatabaseMissing('admin_updates', ['title' => 'Actualizacion 2']);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Nuevas actualizaciones')
        ->assertSee('Actualizacion 12')
        ->assertDontSee('Actualizacion 1</strong>', false);
});

test('dashboard total sales subtracts returned orders', function () {
    $storeUser = User::factory()->create(['role' => 'store']);
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda ventas netas',
        'slug' => 'tienda-ventas-netas',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    foreach ([
        ['status' => 'pagado', 'total' => 100000],
        ['status' => 'enviado', 'total' => 50000],
        ['status' => 'devuelto', 'total' => 30000],
        ['status' => 'pendiente', 'total' => 20000],
    ] as $orderData) {
        Order::create([
            'customer_name' => 'Cliente Venta',
            'customer_phone' => '3001112233',
            'customer_address' => 'Calle 1',
            'customer_city' => 'Bogota',
            'customer_document' => '123456',
            'status' => $orderData['status'],
            'total' => $orderData['total'],
            'store_id' => $store->id,
        ]);
    }

    $this->actingAs($storeUser)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('$ 120.000');
});

test('validation messages are shown in spanish', function () {
    $this->post('/login', [
        'email' => 'correo-mal-escrito',
        'password' => '',
    ])->assertSessionHasErrors(['email', 'password']);

    $errors = session('errors')->getBag('default');

    expect($errors->first('email'))->toBe('El campo correo electronico debe ser una direccion de correo valida.');
    expect($errors->first('password'))->toBe('El campo contrasena es obligatorio.');
});

test('admin cannot create a second store for the same user', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $storeUser = User::factory()->create();

    Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Primera tienda',
        'slug' => 'primera-tienda',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post('/admin/stores', [
            'user_id' => $storeUser->id,
            'name' => 'Segunda tienda',
            'business_type' => 'store',
            'slug' => 'segunda-tienda',
            'whatsapp' => '573001112233',
        ])
        ->assertSessionHasErrors('user_id');
});

test('deleting a store removes its products and banners from the database', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => 'admin']);
    $storeUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda con archivos',
        'slug' => 'tienda-con-archivos',
        'whatsapp' => '573001112233',
        'cover_image' => 'stores/cover.webp',
        'logo_image' => 'stores/logo.webp',
        'is_active' => true,
    ]);

    Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto con imagen',
        'price' => 10000,
        'image' => 'products/product.webp',
        'images' => ['products/product-extra.webp'],
    ]);

    StoreBanner::create([
        'store_id' => $store->id,
        'title' => 'Banner unico',
        'image' => 'banners/banner.webp',
    ]);

    Storage::disk('public')->put('products/product.webp', 'fake');
    Storage::disk('public')->put('products/product-extra.webp', 'fake');

    $this->actingAs($admin)
        ->delete(route('admin.stores.destroy', $store))
        ->assertRedirect('/admin/stores');

    $this->assertDatabaseMissing('stores', ['id' => $store->id]);
    $this->assertDatabaseMissing('products', ['store_id' => $store->id]);
    $this->assertDatabaseMissing('store_banners', ['store_id' => $store->id]);
    Storage::disk('public')->assertMissing('products/product.webp');
    Storage::disk('public')->assertMissing('products/product-extra.webp');
});

test('deleting a user does not remove shared global banner files used by another store', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => 'admin']);
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    $firstStore = Store::create([
        'user_id' => $firstUser->id,
        'name' => 'Tienda uno',
        'slug' => 'tienda-uno',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $secondStore = Store::create([
        'user_id' => $secondUser->id,
        'name' => 'Tienda dos',
        'slug' => 'tienda-dos',
        'whatsapp' => '573001112234',
        'is_active' => true,
    ]);

    foreach ([$firstStore, $secondStore] as $store) {
        StoreBanner::create([
            'store_id' => $store->id,
            'title' => 'Banner global',
            'image' => 'banners/global.webp',
            'applies_to_all' => true,
            'group_token' => 'shared-token',
        ]);
    }

    Storage::disk('public')->put('banners/global.webp', 'fake');

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $firstUser))
        ->assertRedirect('/admin/users');

    Storage::disk('public')->assertExists('banners/global.webp');
    $this->assertDatabaseHas('store_banners', [
        'store_id' => $secondStore->id,
        'image' => 'banners/global.webp',
    ]);
});

test('deleting a sold product keeps the order item history', function () {
    $storeUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda historial',
        'slug' => 'tienda-historial',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto vendido',
        'price' => 12000,
    ]);

    $order = Order::create([
        'customer_name' => 'Cliente Prueba',
        'customer_phone' => '3001112233',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pagado',
        'total' => 12000,
        'store_id' => $store->id,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 1,
        'price' => 12000,
    ]);

    $this->actingAs($storeUser)
        ->delete(route('admin.products.destroy', $product))
        ->assertRedirect();

    $this->assertDatabaseHas('orders', ['id' => $order->id]);
    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => null,
        'product_name' => 'Producto vendido',
        'quantity' => 1,
    ]);

    $this->actingAs($storeUser)
        ->get('/admin/orders')
        ->assertOk()
        ->assertSee('Producto vendido');
});

test('checkout rejects a cart item whose product was deleted without creating an order', function () {
    $storeUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda stale',
        'slug' => 'tienda-stale',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto borrado',
        'price' => 14000,
    ]);

    $cart = [
        (string) $product->id => [
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'store_id' => $store->id,
        ],
    ];

    $product->delete();

    $this->withSession(['cart' => $cart])
        ->post('/cart/whatsapp', [
            'name' => 'Cliente',
            'last_name' => 'Prueba',
            'phone' => '3001112233',
            'address' => 'Calle 1',
            'neighborhood' => 'Chapinero',
            'city' => 'Bogota',
            'document' => '123456',
        ])
        ->assertRedirect(route('cart.index', ['store' => $store->slug]))
        ->assertSessionHas('error', 'Uno de los productos del carrito ya no esta disponible. Eliminalo e intenta de nuevo.');

    $this->assertDatabaseCount('orders', 0);
});

test('admin cannot assign a store to a non store user', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $otherAdmin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post('/admin/stores', [
            'user_id' => $otherAdmin->id,
            'name' => 'Tienda invalida',
            'business_type' => 'store',
            'slug' => 'tienda-invalida',
            'whatsapp' => '573001112233',
        ])
        ->assertSessionHasErrors('user_id');
});

test('admin can create another admin user from the panel', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post('/admin/users', [
            'name' => 'Admin Nuevo',
            'email' => 'admin-nuevo@example.com',
            'role' => 'admin',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect('/admin/users');

    $this->assertDatabaseHas('users', [
        'email' => 'admin-nuevo@example.com',
        'role' => 'admin',
        'is_active' => true,
        'active_starts_at' => null,
        'active_duration_days' => null,
        'active_ends_at' => null,
    ]);

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertOk()
        ->assertSee('Admin Nuevo')
        ->assertSee('Administrador');
});

test('admin can extend a store user access from the current end date', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create([
        'role' => 'store',
        'active_starts_at' => now()->subDays(10)->toDateString(),
        'active_duration_days' => 40,
        'active_ends_at' => now()->addDays(30)->toDateString(),
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.users.extend', $user), [
            'extend_days' => 15,
        ])
        ->assertRedirect('/admin/users');

    $user->refresh();

    expect($user->active_ends_at->toDateString())->toBe(now()->addDays(45)->toDateString())
        ->and($user->is_active)->toBeTrue()
        ->and($user->active_duration_days)->toBe(55);
});

test('admin can extend an expired store user access from today and reactivate the store', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create([
        'role' => 'store',
        'is_active' => false,
        'active_starts_at' => now()->subDays(20)->toDateString(),
        'active_duration_days' => 10,
        'active_ends_at' => now()->subDays(10)->toDateString(),
    ]);
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda renovada',
        'slug' => 'tienda-renovada',
        'whatsapp' => '573001112233',
        'is_active' => false,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.users.extend', $user), [
            'extend_days' => 30,
        ])
        ->assertRedirect('/admin/users');

    $user->refresh();
    $store->refresh();

    expect($user->active_ends_at->toDateString())->toBe(now()->addDays(30)->toDateString())
        ->and($user->is_active)->toBeTrue()
        ->and($store->is_active)->toBeTrue();
});

test('legacy invalid brand colors are not printed in public inline styles', function () {
    $storeUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda color viejo',
        'slug' => 'tienda-color-viejo',
        'whatsapp' => '573001112233',
        'brand_color' => '#fff;background:red',
        'is_active' => true,
    ]);

    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto color',
        'price' => 15000,
    ]);

    $this->get('/tienda-color-viejo/productos/' . $product->id)
        ->assertOk()
        ->assertSee('--brand-color: #111111', false)
        ->assertDontSee('#fff;background:red', false);

    $cart = [
        (string) $product->id => [
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'store_id' => $store->id,
        ],
    ];

    $this->withSession(['cart' => $cart])
        ->get('/cart')
        ->assertOk()
        ->assertSee('--accent: #111111', false)
        ->assertDontSee('#fff;background:red', false);
});

test('admin can see orders from all stores', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $storeUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda visible admin',
        'slug' => 'tienda-visible-admin',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    Order::create([
        'customer_name' => 'Cliente Global',
        'customer_phone' => '3001112233',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pendiente',
        'total' => 12000,
        'store_id' => $store->id,
    ]);
    Order::create([
        'customer_name' => 'Cliente Pagado',
        'customer_phone' => '3001112234',
        'customer_address' => 'Calle 2',
        'customer_city' => 'Medellin',
        'customer_document' => '654321',
        'status' => 'pagado',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_APPROVED,
        'total' => 18000,
        'store_id' => $store->id,
    ]);
    Order::create([
        'customer_name' => 'Cliente Rechazado',
        'customer_phone' => '3001112235',
        'customer_address' => 'Calle 3',
        'customer_city' => 'Cali',
        'customer_document' => '777777',
        'status' => 'pendiente',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_REJECTED,
        'total' => 19000,
        'store_id' => $store->id,
    ]);
    Order::create([
        'customer_name' => 'Cliente Cancelado',
        'customer_phone' => '3001112236',
        'customer_address' => 'Calle 4',
        'customer_city' => 'Barranquilla',
        'customer_document' => '888888',
        'status' => 'pendiente',
        'payment_method' => Order::PAYMENT_METHOD_MERCADOPAGO,
        'payment_provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
        'payment_status' => Order::PAYMENT_STATUS_CANCELLED,
        'total' => 20000,
        'store_id' => $store->id,
    ]);
    Order::create([
        'customer_name' => 'Cliente Devuelto',
        'customer_phone' => '3001112237',
        'customer_address' => 'Calle 5',
        'customer_city' => 'Pereira',
        'customer_document' => '999999',
        'status' => 'devuelto',
        'total' => 21000,
        'store_id' => $store->id,
    ]);

    $this->actingAs($admin)
        ->get('/admin/orders')
        ->assertOk()
        ->assertSee('Filtrar por estado')
        ->assertSee('name="status"', false)
        ->assertSee('Mostrando 5 de 5 pedidos')
        ->assertDontSee('No hay pedidos con ese estado.')
        ->assertSee('Cliente Global')
        ->assertSee('Cliente Pagado')
        ->assertSee('Cliente Rechazado')
        ->assertSee('Cliente Cancelado')
        ->assertSee('Cliente Devuelto')
        ->assertSee('Metodo de pago')
        ->assertSee('Estado de pago')
        ->assertSee('WhatsApp')
        ->assertSee('Mercado Pago')
        ->assertSee('Pendiente')
        ->assertSee('Devuelto')
        ->assertSee('Aprobado')
        ->assertSee('Rechazado')
        ->assertSee('Cancelado')
        ->assertSee('Tienda visible admin');

    $this->actingAs($admin)
        ->get('/admin/orders?status=pagado')
        ->assertOk()
        ->assertSee('Mostrando 1 de 5 pedidos')
        ->assertSee('Cliente Pagado')
        ->assertDontSee('Cliente Global')
        ->assertSee('value="pagado" selected', false);

    $this->actingAs($admin)
        ->get('/admin/orders?status=devuelto')
        ->assertOk()
        ->assertSee('Mostrando 1 de 5 pedidos')
        ->assertSee('Cliente Devuelto')
        ->assertDontSee('Cliente Pagado')
        ->assertSee('value="devuelto" selected', false);

    $this->actingAs($admin)
        ->get('/admin/orders?status=enviado')
        ->assertOk()
        ->assertSee('Mostrando 0 de 5 pedidos')
        ->assertSee('No hay pedidos con ese estado.')
        ->assertDontSee('Cliente Global')
        ->assertDontSee('Cliente Pagado');
});

test('admin can update order status from global orders list', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $storeUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda estado admin',
        'slug' => 'tienda-estado-admin',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $order = Order::create([
        'customer_name' => 'Cliente Estado',
        'customer_phone' => '3001112233',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pagado',
        'total' => 12000,
        'store_id' => $store->id,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.orders.status', $order), [
            'status' => 'devuelto',
        ])
        ->assertRedirect('/admin/orders');

    expect($order->refresh()->status)->toBe('devuelto');
});

test('pending orders cannot be marked as returned', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $storeUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda devuelto pendiente',
        'slug' => 'tienda-devuelto-pendiente',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $order = Order::create([
        'customer_name' => 'Cliente Pendiente',
        'customer_phone' => '3001112233',
        'customer_address' => 'Calle 1',
        'customer_city' => 'Bogota',
        'customer_document' => '123456',
        'status' => 'pendiente',
        'total' => 12000,
        'store_id' => $store->id,
    ]);

    $this->actingAs($admin)
        ->from('/admin/orders')
        ->patch(route('admin.orders.status', $order), [
            'status' => 'devuelto',
        ])
        ->assertRedirect('/admin/orders')
        ->assertSessionHasErrors('status');

    expect($order->refresh()->status)->toBe('pendiente');
});

test('store user can delete an order from their panel', function () {
    $storeUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda elimina pedido',
        'slug' => 'tienda-elimina-pedido',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $order = Order::create([
        'store_id' => $store->id,
        'customer_name' => 'Cliente Eliminar',
        'customer_phone' => '3001234567',
        'status' => 'pendiente',
        'total' => 45000,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_name' => 'Producto pedido',
        'quantity' => 1,
        'price' => 45000,
    ]);

    $this->actingAs($storeUser)
        ->get('/admin/orders')
        ->assertOk()
        ->assertSee(route('admin.orders.destroy', $order), false)
        ->assertSee('Eliminar pedido');

    $this->actingAs($storeUser)
        ->delete(route('admin.orders.destroy', $order))
        ->assertRedirect('/admin/orders')
        ->assertSessionHas('success', 'Pedido eliminado.');

    $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    $this->assertDatabaseMissing('order_items', ['order_id' => $order->id]);
});

test('store user cannot delete an order from another store', function () {
    $storeUser = User::factory()->create();
    $otherStoreUser = User::factory()->create();

    Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda propia',
        'slug' => 'tienda-propia',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $otherStore = Store::create([
        'user_id' => $otherStoreUser->id,
        'name' => 'Tienda ajena',
        'slug' => 'tienda-ajena',
        'whatsapp' => '573001112244',
        'is_active' => true,
    ]);

    $order = Order::create([
        'store_id' => $otherStore->id,
        'customer_name' => 'Cliente Ajeno',
        'customer_phone' => '3001234567',
        'status' => 'pendiente',
        'total' => 45000,
    ]);

    $this->actingAs($storeUser)
        ->delete(route('admin.orders.destroy', $order))
        ->assertForbidden();

    $this->assertDatabaseHas('orders', ['id' => $order->id]);
});

test('admin can create edit and delete products for any store', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $storeUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Productos Admin',
        'slug' => 'tienda-productos-admin',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PREMIUM,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get('/admin/products')
        ->assertOk()
        ->assertSee('Tienda Productos Admin')
        ->assertSee('Ver productos');

    $this->actingAs($admin)
        ->post('/admin/products', [
            'store_id' => $store->id,
            'name' => 'Producto desde Admin',
            'category' => 'Admin',
            'price' => 75000,
            'has_offer' => '1',
            'offer_original_price' => 90000,
            'custom_badges' => 'Nuevo, Mas vendido, Edicion limitada, Ignorada',
            'description' => 'Creado desde el administrador.',
        ])
        ->assertRedirect('/admin/products');

    $product = Product::where('store_id', $store->id)
        ->where('name', 'Producto desde Admin')
        ->firstOrFail();

    expect($product->user_id)->toBe($storeUser->id);
    expect($product->has_offer)->toBeTrue();
    expect((float) $product->offer_original_price)->toBe(90000.0);
    expect($product->custom_badges)->toBe(['Nuevo', 'Mas vendido', 'Edicion limitada']);

    $this->actingAs($admin)
        ->get(route('admin.stores.products.index', $store))
        ->assertOk()
        ->assertSee('Producto desde Admin')
        ->assertSee('Volver a tiendas');

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), [
            'store_id' => $store->id,
            'name' => 'Producto editado por Admin',
            'category' => 'Admin',
            'price' => 82000,
            'has_offer' => '0',
            'offer_original_price' => 95000,
            'custom_badges' => 'Ultimas unidades',
            'description' => 'Editado desde el administrador.',
        ])
        ->assertRedirect('/admin/products');

    expect($product->refresh()->name)->toBe('Producto editado por Admin');
    expect((float) $product->price)->toBe(82000.0);
    expect($product->has_offer)->toBeFalse();
    expect($product->offer_original_price)->toBeNull();
    expect($product->custom_badges)->toBe(['Ultimas unidades']);

    $this->actingAs($admin)
        ->delete(route('admin.products.destroy', $product))
        ->assertRedirect();

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});

test('pro stores cannot see or force product offer fields', function () {
    $storeUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Pro Sin Ofertas',
        'slug' => 'tienda-pro-sin-ofertas',
        'whatsapp' => '573001112233',
        'plan' => Store::PLAN_PRO,
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->get('/admin/products/create')
        ->assertOk()
        ->assertDontSee('Mostrar etiqueta de oferta')
        ->assertDontSee('Precio antes de oferta')
        ->assertDontSee('Etiquetas personalizadas');

    $this->actingAs($storeUser)
        ->post('/admin/products', [
            'name' => 'Producto Pro Forzado',
            'price' => 50000,
            'has_offer' => '1',
            'offer_original_price' => 70000,
            'custom_badges' => 'Nuevo, Premium',
        ])
        ->assertRedirect('/admin/products');

    $product = Product::where('store_id', $store->id)
        ->where('name', 'Producto Pro Forzado')
        ->firstOrFail();

    expect($product->has_offer)->toBeFalse();
    expect($product->offer_original_price)->toBeNull();
    expect($product->custom_badges)->toBe([]);

    $this->actingAs($storeUser)
        ->get(route('admin.products.edit', $product))
        ->assertOk()
        ->assertDontSee('Mostrar etiqueta de oferta')
        ->assertDontSee('Precio antes de oferta')
        ->assertDontSee('Etiquetas personalizadas');
});

test('admin can browse stores before managing categories for one store', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $storeUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Categorias Admin',
        'slug' => 'tienda-categorias-admin',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get('/admin/categories')
        ->assertOk()
        ->assertSee('Tienda Categorias Admin')
        ->assertSee('Ver categorias');

    $this->actingAs($admin)
        ->get(route('admin.stores.categories.index', $store))
        ->assertOk()
        ->assertSee('Categorias de esta tienda')
        ->assertSee('Agregar categoria');

    $this->actingAs($admin)
        ->post(route('admin.categories.store'), [
            'store_id' => $store->id,
            'name' => 'Admin Especial',
            'slug' => 'admin-especial',
            'description' => 'Categoria creada por admin.',
            'sort_order' => 10,
            'is_active' => 1,
        ])
        ->assertRedirect(route('admin.stores.categories.index', $store));

    $category = StoreCategory::where('store_id', $store->id)
        ->where('name', 'Admin Especial')
        ->firstOrFail();

    $this->actingAs($admin)
        ->put(route('admin.categories.update', $category), [
            'name' => 'Admin Editada',
            'slug' => 'admin-editada',
            'description' => 'Categoria editada por admin.',
            'sort_order' => 20,
            'is_active' => 1,
        ])
        ->assertRedirect(route('admin.stores.categories.index', $store));

    expect($category->refresh()->name)->toBe('Admin Editada');

    $this->actingAs($admin)
        ->delete(route('admin.categories.destroy', $category))
        ->assertRedirect(route('admin.stores.categories.index', $store));

    $this->assertDatabaseMissing('store_categories', ['id' => $category->id]);
});

test('admin can view store visits from the stores menu', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $storeUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda con visitas',
        'slug' => 'tienda-con-visitas',
        'whatsapp' => '573001112233',
        'is_active' => true,
        'views_count' => 99,
    ]);
    Store::create([
        'user_id' => User::factory()->create()->id,
        'name' => 'Tienda sin visitas',
        'slug' => 'tienda-sin-visitas',
        'whatsapp' => '573001112233',
        'is_active' => true,
        'views_count' => 0,
    ]);

    foreach (range(1, 11) as $index) {
        Store::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Tienda visita extra ' . $index,
            'slug' => 'tienda-visita-extra-' . $index,
            'whatsapp' => '573001112233',
            'is_active' => true,
            'views_count' => 20 + $index,
        ]);
    }

    $this->actingAs($admin)
        ->get('/admin/stores')
        ->assertOk()
        ->assertSee(route('admin.stores.visits'), false);

    $this->actingAs($admin)
        ->get(route('admin.stores.visits'))
        ->assertOk()
        ->assertSee('Visitas por tienda')
        ->assertSee('Tienda con visitas')
        ->assertSee('/' . $store->slug)
        ->assertSee('99')
        ->assertDontSee('Tienda sin visitas')
        ->assertDontSee('Tienda visita extra 1</strong>', false)
        ->assertDontSee('pagination.previous')
        ->assertDontSee('pagination.next')
        ->assertSee('pagination', false);
});

test('product social preview includes product name description and image', function () {
    Storage::fake('public');

    $storeUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda SEO',
        'slug' => 'tienda-seo',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    Storage::disk('public')->put('products/camisa.webp', 'fake');

    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Camisa Azul',
        'description' => 'Camisa fresca para clima calido.',
        'price' => 49000,
        'image' => 'products/camisa.webp',
    ]);

    $this->get('/tienda-seo/productos/' . $product->id)
        ->assertOk()
        ->assertSee('<meta property="og:title" content="Camisa Azul | Tienda SEO">', false)
        ->assertSee('<meta property="og:description" content="Camisa fresca para clima calido.">', false)
        ->assertSee('<meta property="og:image" content="' . config('app.url') . '/storage/products/camisa.webp">', false)
        ->assertSee('<meta name="twitter:title" content="Camisa Azul | Tienda SEO">', false);
});

test('storefront product links use slug while old id urls still work', function () {
    $storeUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Slug',
        'slug' => 'tienda-slug',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Zapato Seguro',
        'description' => 'Producto con URL segura.',
        'price' => 99000,
    ]);

    expect($product->slug)->toStartWith('zapato-seguro-');
    expect($product->slug)->not->toBe((string) $product->id);

    $this->get('/tienda-slug')
        ->assertOk()
        ->assertSee('/tienda-slug/productos/' . $product->slug, false)
        ->assertDontSee('/tienda-slug/productos/' . $product->id . '"', false);

    $this->get('/tienda-slug/productos/' . $product->slug)
        ->assertOk()
        ->assertSee('Zapato Seguro');

    $this->get('/tienda-slug/productos/' . $product->id)
        ->assertOk()
        ->assertSee('Zapato Seguro')
        ->assertSee('<link rel="canonical" href="' . config('app.url') . '/tienda-slug/productos/' . $product->slug . '">', false);
});

test('store home meta title uses cover short copy', function () {
    $storeUser = User::factory()->create();

    Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Copy',
        'slug' => 'tienda-copy',
        'whatsapp' => '573001112233',
        'shop_copy' => 'Compra ropa urbana y pide por WhatsApp en minutos.',
        'is_active' => true,
    ]);

    $this->get('/tienda-copy')
        ->assertOk()
        ->assertSee('Compra ropa urbana y pide por WhatsApp en minutos.')
        ->assertSee('<title>Tienda Copy | Compra ropa urbana y pide por WhatsApp en minutos.</title>', false)
        ->assertSee('<meta property="og:title" content="Tienda Copy | Compra ropa urbana y pide por WhatsApp en minutos.">', false);
});

test('store home uses cover image for hero and social preview and logo as favicon', function () {
    Storage::fake('public');

    $storeUser = User::factory()->create();

    Storage::disk('public')->put('stores/cover.webp', 'fake-cover');
    Storage::disk('public')->put('stores/logo.webp', 'fake-logo');

    Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Visual',
        'slug' => 'tienda-visual',
        'whatsapp' => '573001112233',
        'cover_image' => 'stores/cover.webp',
        'logo_image' => 'stores/logo.webp',
        'is_active' => true,
    ]);

    $this->get('/tienda-visual')
        ->assertOk()
        ->assertSee('<img src="' . asset('storage/stores/cover.webp') . '" alt="Tienda Visual"', false)
        ->assertSee('<meta property="og:image" content="' . config('app.url') . '/storage/stores/cover.webp">', false)
        ->assertSee('<meta property="og:image:alt" content="Portada de Tienda Visual">', false)
        ->assertSee('<link rel="icon" type="image/svg+xml" sizes="any" href="' . route('store.favicon', 'tienda-visual') . '">', false);

    $this->get('/tienda-visual/favicon.svg')
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8')
        ->assertSee('<svg', false)
        ->assertSee('base64,' . base64_encode('fake-logo'), false);
});

test('store user can add material to a product and customers can see it', function () {
    $storeUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Material',
        'slug' => 'tienda-material',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->post('/admin/products', [
            'name' => 'Chaqueta Denim',
            'category' => 'Chaquetas',
            'material' => 'Denim 100% algodon',
            'price' => 139000,
            'description' => 'Chaqueta resistente para uso diario.',
        ])
        ->assertRedirect('/admin/products');

    $product = Product::where('store_id', $store->id)->firstOrFail();

    expect($product->material)->toBe('Denim 100% algodon');

    $this->actingAs($storeUser)
        ->get('/admin/products')
        ->assertOk()
        ->assertSee('Denim 100% algodon');

    $this->get('/tienda-material/productos/' . $product->publicRouteKey())
        ->assertOk()
        ->assertSee('Material')
        ->assertSee('Denim 100% algodon');
});

test('store user can add multiple product images and customers see a carousel', function () {
    Storage::fake('public');

    $storeUser = User::factory()->create();
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=');

    Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Galeria',
        'slug' => 'tienda-galeria',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $this->actingAs($storeUser)
        ->post('/admin/products', [
            'name' => 'Bolso Galeria',
            'price' => 89000,
            'image' => UploadedFile::fake()->createWithContent('principal.png', $png),
            'images' => [
                UploadedFile::fake()->createWithContent('detalle-uno.png', $png),
                UploadedFile::fake()->createWithContent('detalle-dos.png', $png),
            ],
        ])
        ->assertRedirect('/admin/products');

    $product = Product::where('name', 'Bolso Galeria')->firstOrFail();

    expect($product->images)->toHaveCount(2);
    Storage::disk('public')->assertExists($product->image);

    foreach ($product->images as $image) {
        Storage::disk('public')->assertExists($image);
    }

    $this->get('/tienda-galeria/productos/' . $product->publicRouteKey())
        ->assertOk()
        ->assertSee('data-product-carousel', false)
        ->assertSee('data-carousel-thumb="2"', false);
});

test('store user can remove an extra product image from the gallery', function () {
    Storage::fake('public');

    $storeUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Quitar Imagen',
        'slug' => 'tienda-quitar-imagen',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    Storage::disk('public')->put('products/main.webp', 'fake');
    Storage::disk('public')->put('products/extra-a.webp', 'fake');
    Storage::disk('public')->put('products/extra-b.webp', 'fake');

    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto Galeria',
        'price' => 49000,
        'image' => 'products/main.webp',
        'images' => ['products/extra-a.webp', 'products/extra-b.webp'],
    ]);

    $this->actingAs($storeUser)
        ->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'price' => $product->price,
            'remove_images' => ['products/extra-a.webp'],
        ])
        ->assertRedirect('/admin/products');

    expect($product->refresh()->images)->toBe(['products/extra-b.webp']);
    Storage::disk('public')->assertMissing('products/extra-a.webp');
    Storage::disk('public')->assertExists('products/extra-b.webp');
});

test('store user cannot remove product images outside the product gallery', function () {
    Storage::fake('public');

    $storeUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Imagen Segura',
        'slug' => 'tienda-imagen-segura',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    Storage::disk('public')->put('products/main.webp', 'fake');
    Storage::disk('public')->put('products/gallery.webp', 'fake');
    Storage::disk('public')->put('stores/cover.webp', 'fake-cover');

    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto Seguro',
        'price' => 59000,
        'image' => 'products/main.webp',
        'images' => ['products/gallery.webp'],
    ]);

    $this->actingAs($storeUser)
        ->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'price' => $product->price,
            'remove_images' => ['stores/cover.webp'],
        ])
        ->assertRedirect('/admin/products');

    expect($product->refresh()->images)->toBe(['products/gallery.webp']);
    Storage::disk('public')->assertExists('stores/cover.webp');
    Storage::disk('public')->assertExists('products/gallery.webp');
});

test('admin cannot use reserved store slugs and entered slugs are normalized', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    foreach (['cart', 'forgot-password', 'reset-password', 'verify-email', 'confirm-password', 'email', 'productos', 'nosotros', 'categorias'] as $reservedSlug) {
        $reservedSlugUser = User::factory()->create();

        $this->actingAs($admin)
            ->post('/admin/stores', [
                'user_id' => $reservedSlugUser->id,
                'name' => 'Slug Reservado ' . $reservedSlug,
                'business_type' => 'store',
                'slug' => $reservedSlug,
                'whatsapp' => '573001112233',
            ])
            ->assertSessionHasErrors('slug');
    }

    $normalSlugUser = User::factory()->create();

    $this->actingAs($admin)
        ->post('/admin/stores', [
            'user_id' => $normalSlugUser->id,
            'name' => 'Mi Tienda Bonita',
            'business_type' => 'store',
            'slug' => 'Mi Tienda Bonita',
            'whatsapp' => '573001112233',
        ])
        ->assertRedirect('/admin/stores');

    $this->assertDatabaseHas('stores', [
        'user_id' => $normalSlugUser->id,
        'slug' => 'mi-tienda-bonita',
    ]);
});

test('public product route key regenerates missing slugs instead of exposing ids', function () {
    $storeUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Missing Slug',
        'slug' => 'tienda-missing-slug',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto Importado',
        'price' => 30000,
    ]);

    Product::whereKey($product->id)->update(['slug' => null]);
    $product = Product::findOrFail($product->id);

    $routeKey = $product->publicRouteKey();

    expect($routeKey)->toStartWith('producto-importado-');
    expect($routeKey)->not->toBe((string) $product->id);
    expect($product->refresh()->slug)->toBe($routeKey);
});

test('offer badge is shown only on premium stores', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $proStore = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda Pro Etiqueta',
        'slug' => 'tienda-pro-etiqueta',
        'plan' => Store::PLAN_PRO,
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $premiumStore = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda Premium Etiqueta',
        'slug' => 'tienda-premium-etiqueta',
        'plan' => Store::PLAN_PREMIUM,
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    Product::create([
        'user_id' => $user->id,
        'store_id' => $proStore->id,
        'name' => 'Producto destacado pro',
        'price' => 45000,
        'has_offer' => true,
        'offer_original_price' => 60000,
        'custom_badges' => ['Nuevo'],
    ]);

    Product::create([
        'user_id' => $user->id,
        'store_id' => $premiumStore->id,
        'name' => 'Producto destacado premium',
        'price' => 45000,
        'has_offer' => true,
        'offer_original_price' => 60000,
        'custom_badges' => ['Nuevo', 'Mas vendido'],
    ]);

    Product::create([
        'user_id' => $user->id,
        'store_id' => $premiumStore->id,
        'name' => 'Producto sin etiqueta premium',
        'price' => 55000,
        'has_offer' => false,
        'offer_original_price' => null,
    ]);

    $basicStore = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda Basic Etiqueta',
        'slug' => 'tienda-basic-etiqueta',
        'plan' => Store::PLAN_BASIC,
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    Product::create([
        'user_id' => $user->id,
        'store_id' => $basicStore->id,
        'name' => 'Producto destacado basic',
        'price' => 45000,
        'has_offer' => true,
        'offer_original_price' => 60000,
        'custom_badges' => ['Nuevo'],
    ]);

    $this->get('/tienda-pro-etiqueta')
        ->assertOk()
        ->assertDontSee('product-offer-badge', false)
        ->assertDontSee('Oferta')
        ->assertDontSee('Nuevo');

    $this->get('/tienda-basic-etiqueta')
        ->assertOk()
        ->assertDontSee('product-offer-badge', false)
        ->assertDontSee('Oferta')
        ->assertDontSee('Nuevo');

    $premiumResponse = $this->get('/tienda-premium-etiqueta')
        ->assertOk()
        ->assertSee('product-offer-badge', false)
        ->assertSee('Oferta')
        ->assertSee('Nuevo')
        ->assertSee('Mas vendido')
        ->assertSee('$60.000')
        ->assertSee('$45.000')
        ->assertSee('Producto sin etiqueta premium');

    expect(substr_count($premiumResponse->getContent(), 'product-offer-badge'))->toBe(3);
});

test('offer menu and page are available only for premium stores with offer products', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $proStore = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda Pro Menu Oferta',
        'slug' => 'tienda-pro-menu-oferta',
        'plan' => Store::PLAN_PRO,
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    Product::create([
        'user_id' => $user->id,
        'store_id' => $proStore->id,
        'name' => 'Oferta forzada pro',
        'price' => 45000,
        'has_offer' => true,
        'offer_original_price' => 60000,
    ]);

    $premiumStore = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda Premium Menu Oferta',
        'slug' => 'tienda-premium-menu-oferta',
        'plan' => Store::PLAN_PREMIUM,
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    Product::create([
        'user_id' => $user->id,
        'store_id' => $premiumStore->id,
        'name' => 'Audifonos en oferta',
        'price' => 45000,
        'has_offer' => true,
        'offer_original_price' => 60000,
    ]);

    Product::create([
        'user_id' => $user->id,
        'store_id' => $premiumStore->id,
        'name' => 'Audifonos precio normal',
        'price' => 55000,
        'has_offer' => false,
        'offer_original_price' => null,
    ]);

    $this->get('/tienda-pro-menu-oferta')
        ->assertOk()
        ->assertDontSee('nav-offer-link', false)
        ->assertDontSee('Ofertas');

    $this->get('/tienda-pro-menu-oferta/ofertas')
        ->assertNotFound();

    $this->get('/tienda-premium-menu-oferta')
        ->assertOk()
        ->assertSee('nav-offer-link', false)
        ->assertSee('/tienda-premium-menu-oferta/ofertas', false)
        ->assertSee('Ofertas');

    $this->get('/tienda-premium-menu-oferta/ofertas')
        ->assertOk()
        ->assertSee('Audifonos en oferta')
        ->assertDontSee('Audifonos precio normal')
        ->assertSee('product-offer-badge', false)
        ->assertSee('$60.000')
        ->assertSee('$45.000');
});

test('store home groups products by three categories and category pages show the full list', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda Categorias',
        'slug' => 'tienda-categorias',
        'whatsapp' => '573001112233',
        'is_active' => true,
        'show_hero_products_action' => true,
    ]);

    foreach (['Audio', 'Computo', 'Gaming', 'Accesorios'] as $index => $name) {
        StoreCategory::create([
            'store_id' => $store->id,
            'name' => $name,
            'slug' => strtolower($name),
            'description' => 'Descripcion de ' . $name,
            'is_active' => true,
            'sort_order' => $index + 1,
        ]);

        foreach (range(1, 5) as $productIndex) {
            Product::create([
                'user_id' => $user->id,
                'store_id' => $store->id,
                'name' => $name . ' Producto ' . $productIndex,
                'category' => $name,
                'price' => 10000 + $productIndex,
            ]);
        }
    }

    Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Producto sin categoria',
        'price' => 25000,
    ]);

    $this->get('/tienda-categorias')
        ->assertOk()
        ->assertSee('id="categoria-audio"', false)
        ->assertSee('id="categoria-computo"', false)
        ->assertSee('id="categoria-gaming"', false)
        ->assertDontSee('id="categoria-accesorios"', false)
        ->assertSee('Audio Producto 4')
        ->assertDontSee('Audio Producto 5')
        ->assertSee('Producto sin categoria')
        ->assertSee('/tienda-categorias/productos', false)
        ->assertSee('Ver todos los productos')
        ->assertSee('/tienda-categorias/categorias/accesorios', false);

    $this->get('/tienda-categorias/productos')
        ->assertOk()
        ->assertSee('Audio Producto 5')
        ->assertSee('Accesorios Producto 5')
        ->assertSee('Producto sin categoria')
        ->assertSee('<title>Catalogo | Tienda Categorias</title>', false);

    $this->get('/tienda-categorias/categorias/audio')
        ->assertOk()
        ->assertSee('Audio Producto 5')
        ->assertSee('<title>Audio | Tienda Categorias</title>', false);

    $product = Product::where('store_id', $store->id)
        ->where('category', 'Audio')
        ->firstOrFail();

    $this->get(route('store.product.show', [
        'slug' => $store->slug,
        'product' => $product->publicRouteKey(),
    ]))
        ->assertOk()
        ->assertSee('Categorias')
        ->assertSee('/tienda-categorias/categorias/audio', false)
        ->assertDontSee('href="#destacado"', false)
        ->assertDontSee('href="#novedades"', false);
});

test('category sort order places priority positions before normal categories', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda Orden Categorias',
        'slug' => 'tienda-orden-categorias',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    foreach ([
        ['Normal Categoria', 0],
        ['Primera Categoria', 10],
        ['Final Categoria', 100],
        ['Quinta Categoria', 50],
    ] as [$name, $sortOrder]) {
        StoreCategory::create([
            'store_id' => $store->id,
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);
    }

    $this->actingAs($user)
        ->get('/admin/categories')
        ->assertOk()
        ->assertSeeInOrder([
            'Primera Categoria',
            'Quinta Categoria',
            'Normal Categoria',
            'Final Categoria',
        ]);
});

test('customers can search products in the full catalog', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda Busqueda',
        'slug' => 'tienda-busqueda',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Camisa Premium',
        'price' => 45000,
        'description' => 'Tela azul para todos los dias.',
    ]);

    Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Zapatos Negros',
        'price' => 90000,
    ]);

    foreach (range(1, 19) as $index) {
        Product::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'name' => 'Producto Extra ' . $index,
            'price' => 10000 + $index,
        ]);
    }

    $this->get('/tienda-busqueda/productos?q=azul')
        ->assertOk()
        ->assertSee('No encontramos productos para esa busqueda.')
        ->assertDontSee('Camisa Premium')
        ->assertDontSee('Zapatos Negros');

    $this->get('/tienda-busqueda/productos?q=premium')
        ->assertOk()
        ->assertSee('Camisa Premium')
        ->assertDontSee('Zapatos Negros');
});

test('customers can search products inside a category', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda Categoria Busqueda',
        'slug' => 'tienda-categoria-busqueda',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    StoreCategory::create([
        'store_id' => $store->id,
        'name' => 'Ropa',
        'slug' => 'ropa',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Vestido Floral',
        'price' => 70000,
        'category' => 'Ropa',
    ]);

    Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Chaqueta Lisa',
        'price' => 120000,
        'category' => 'Ropa',
    ]);

    foreach (range(1, 19) as $index) {
        Product::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'name' => 'Ropa Extra ' . $index,
            'price' => 10000 + $index,
            'category' => 'Ropa',
        ]);
    }

    $this->get('/tienda-categoria-busqueda/categorias/ropa?q=floral')
        ->assertOk()
        ->assertSee('Vestido Floral')
        ->assertDontSee('Chaqueta Lisa');
});

test('product search is hidden and ignored until the store has more than twenty products', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda Sin Busqueda',
        'slug' => 'tienda-sin-busqueda',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Producto Especial',
        'price' => 30000,
    ]);

    foreach (range(1, 19) as $index) {
        Product::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'name' => 'Producto Comun ' . $index,
            'price' => 10000 + $index,
        ]);
    }

    $this->get('/tienda-sin-busqueda/productos?q=especial')
        ->assertOk()
        ->assertDontSee('Buscar productos')
        ->assertDontSee('Resultados para "especial".', false)
        ->assertSee('Producto Especial')
        ->assertSee('Producto Comun 1');
});

test('category pages use compact storefront pagination after eight products', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda Paginacion',
        'slug' => 'tienda-paginacion',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    StoreCategory::create([
        'store_id' => $store->id,
        'name' => 'Audio',
        'slug' => 'audio',
        'is_active' => true,
    ]);

    foreach (range(1, 9) as $index) {
        Product::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'name' => 'Audio Producto ' . $index,
            'category' => 'Audio',
            'price' => 10000 + $index,
        ]);
    }

    $this->get('/tienda-paginacion/categorias/audio')
        ->assertOk()
        ->assertSee('store-pagination-nav', false)
        ->assertSee('store-pagination-link--control', false)
        ->assertSee('Siguiente');
});

test('admin panel routes use admin tokens instead of numeric ids', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $storeUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda segura',
        'slug' => 'tienda-segura',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $product = Product::create([
        'user_id' => $storeUser->id,
        'store_id' => $store->id,
        'name' => 'Producto seguro',
        'price' => 50000,
    ]);

    expect(route('admin.products.edit', $product))
        ->toContain($product->admin_token)
        ->not->toContain('/' . $product->id . '/');

    $this->actingAs($storeUser)
        ->get('/admin/products/' . $product->id . '/edit')
        ->assertNotFound();

    $this->actingAs($storeUser)
        ->get(route('admin.products.edit', $product))
        ->assertOk();

    expect(route('admin.stores.edit', $store))
        ->toContain($store->admin_token)
        ->not->toContain('/' . $store->id . '/');

    $this->actingAs($admin)
        ->get('/admin/stores/' . $store->id . '/edit')
        ->assertNotFound();
});

test('cart items are isolated by store when customers switch storefronts', function () {
    $userA = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $userB = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $storeA = Store::create([
        'user_id' => $userA->id,
        'name' => 'Tienda A',
        'slug' => 'tienda-a',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    $storeB = Store::create([
        'user_id' => $userB->id,
        'name' => 'Tienda B',
        'slug' => 'tienda-b',
        'whatsapp' => '573001112244',
        'is_active' => true,
    ]);

    $productA = Product::create([
        'user_id' => $userA->id,
        'store_id' => $storeA->id,
        'name' => 'Producto tienda A',
        'price' => 10000,
    ]);
    $productB = Product::create([
        'user_id' => $userB->id,
        'store_id' => $storeB->id,
        'name' => 'Producto tienda B',
        'price' => 20000,
    ]);

    $this->post(route('cart.add', $productA->id))->assertRedirect();

    $this->get(route('cart.index', ['store' => $storeA->slug]))
        ->assertOk()
        ->assertSee('Producto tienda A')
        ->assertDontSee('Producto tienda B');

    $this->get(route('cart.index', ['store' => $storeB->slug]))
        ->assertOk()
        ->assertSee('Tu carrito esta vacio')
        ->assertDontSee('Producto tienda A');

    $this->post(route('cart.add', $productB->id))->assertRedirect();

    $this->get(route('cart.index', ['store' => $storeA->slug]))
        ->assertOk()
        ->assertSee('Producto tienda A')
        ->assertDontSee('Producto tienda B');

    $this->get(route('cart.index', ['store' => $storeB->slug]))
        ->assertOk()
        ->assertSee('Producto tienda B')
        ->assertDontSee('Producto tienda A');
});

test('checkout clears the store cart without reviving the legacy cart', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda Checkout',
        'slug' => 'tienda-checkout',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Producto checkout',
        'price' => 30000,
    ]);

    $this->post(route('cart.add', $product->id))->assertRedirect();

    $this->get(route('cart.index', ['store' => $store->slug]))
        ->assertOk()
        ->assertSee('name="neighborhood"', false)
        ->assertSee('placeholder="Barrio"', false)
        ->assertSee('required', false);

    $this->post(route('cart.whatsapp', ['store' => $store->slug]), [
        'name' => 'Cliente',
        'last_name' => 'Prueba',
        'phone' => '3001234567',
        'address' => 'Calle 1',
        'neighborhood' => 'San Fernando',
        'city' => 'Bogota',
        'document' => '123456',
    ])->assertRedirectContains('https://wa.me/573001112233');

    $this->assertDatabaseHas('orders', [
        'store_id' => $store->id,
        'total' => 30000,
    ]);

    $order = Order::where('store_id', $store->id)->latest('id')->first();

    expect($order->customer_neighborhood)->toBe('San Fernando');
    expect(app(\App\Services\WhatsAppOrderMessageBuilder::class)->message($order->load(['items', 'store'])))
        ->toContain('Barrio: San Fernando');

    $this->assertDatabaseHas('admin_updates', [
        'title' => 'Pedido nuevo',
        'body' => 'Pedido #' . $order->id . ' en Tienda Checkout por Cliente Prueba',
        'type' => 'pedido',
        'url' => '/admin/orders',
    ]);

    expect(session()->has('cart'))->toBeFalse();
    expect(session()->has('carts.' . $store->id))->toBeFalse();

    $this->get(route('cart.index', ['store' => $store->slug]))
        ->assertOk()
        ->assertSee('Tu carrito esta vacio')
        ->assertDontSee('Producto checkout');

    $this->actingAs($user)
        ->get('/admin/orders')
        ->assertOk()
        ->assertSee('Barrio')
        ->assertSee('San Fernando');
});

test('technology store checkout uses the minimal storefront shell', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tech Checkout',
        'slug' => 'tech-checkout',
        'business_type' => 'technology',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Audifonos Tech',
        'price' => 90000,
    ]);

    $this->post(route('cart.add', $product->id))->assertRedirect();

    $this->get(route('cart.index', ['store' => $store->slug]))
        ->assertOk()
        ->assertSee('minimal-shop-header', false)
        ->assertSee('tech-checkout-shell', false)
        ->assertSee('Resumen del pedido')
        ->assertSee('minimal-shop-footer', false)
        ->assertSee('Audifonos Tech');
});

test('checkout applies selected shipping method cost to orders and whatsapp message', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda Envios',
        'slug' => 'tienda-envios',
        'whatsapp' => '573001112233',
        'shipping_methods' => [
            ['name' => 'Domicilio local', 'cost' => 8000],
            ['name' => 'Recoger en tienda', 'cost' => 0],
        ],
        'is_active' => true,
    ]);

    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Producto con envio',
        'price' => 30000,
    ]);

    $this->post(route('cart.add', $product->id))->assertRedirect();

    $this->get(route('cart.index', ['store' => $store->slug]))
        ->assertOk()
        ->assertSee('Metodo de envio')
        ->assertSee('Domicilio local')
        ->assertSee('$ 8.000')
        ->assertSee('Recoger en tienda')
        ->assertSee('Gratis');

    $this->post(route('cart.whatsapp', ['store' => $store->slug]), [
        'name' => 'Cliente',
        'last_name' => 'Envio',
        'phone' => '3001234567',
        'address' => 'Calle 1',
        'neighborhood' => 'San Fernando',
        'city' => 'Bogota',
        'document' => '123456',
    ])->assertSessionHasErrors('shipping_method');

    $this->post(route('cart.whatsapp', ['store' => $store->slug]), [
        'name' => 'Cliente',
        'last_name' => 'Envio',
        'phone' => '3001234567',
        'address' => 'Calle 1',
        'neighborhood' => 'San Fernando',
        'city' => 'Bogota',
        'document' => '123456',
        'shipping_method' => '0',
    ])->assertRedirectContains('https://wa.me/573001112233');

    $order = Order::where('store_id', $store->id)->latest('id')->firstOrFail();

    expect($order->shipping_method)->toBe('Domicilio local')
        ->and((float) $order->shipping_cost)->toBe(8000.0)
        ->and((float) $order->total)->toBe(38000.0);

    expect(app(\App\Services\WhatsAppOrderMessageBuilder::class)->message($order->load(['items', 'store'])))
        ->toContain('Envio: Domicilio local ($8.000)')
        ->toContain('Total: $38.000');
});

test('shipping methods are only available on pro and premium plans', function () {
    $basicUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $basicStore = Store::create([
        'user_id' => $basicUser->id,
        'name' => 'Tienda Basica Envios',
        'slug' => 'tienda-basica-envios',
        'plan' => Store::PLAN_BASIC,
        'whatsapp' => '573001112233',
        'shipping_methods' => [
            ['name' => 'Domicilio bloqueado', 'cost' => 12000],
        ],
        'is_active' => true,
    ]);

    $basicProduct = Product::create([
        'user_id' => $basicUser->id,
        'store_id' => $basicStore->id,
        'name' => 'Producto basico',
        'price' => 30000,
    ]);

    $this->actingAs($basicUser)
        ->get('/admin/store-settings')
        ->assertOk()
        ->assertSee('Actualiza a Pro o Premium')
        ->assertDontSee('name="shipping_methods[0][name]"', false);

    $this->post(route('cart.add', $basicProduct->id))->assertRedirect();

    $this->get(route('cart.index', ['store' => $basicStore->slug]))
        ->assertOk()
        ->assertDontSee('Metodo de envio')
        ->assertDontSee('Domicilio bloqueado');

    $this->post(route('cart.whatsapp', ['store' => $basicStore->slug]), [
        'name' => 'Cliente',
        'last_name' => 'Basico',
        'phone' => '3001234567',
        'address' => 'Calle 1',
        'neighborhood' => 'San Fernando',
        'city' => 'Bogota',
        'document' => '123456',
    ])->assertRedirectContains('https://wa.me/573001112233');

    $basicOrder = Order::where('store_id', $basicStore->id)->latest('id')->firstOrFail();

    expect($basicOrder->shipping_method)->toBeNull()
        ->and((float) $basicOrder->shipping_cost)->toBe(0.0)
        ->and((float) $basicOrder->total)->toBe(30000.0);

    $proUser = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    Store::create([
        'user_id' => $proUser->id,
        'name' => 'Tienda Pro Envios',
        'slug' => 'tienda-pro-envios',
        'plan' => Store::PLAN_PRO,
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $this->actingAs($proUser)
        ->get('/admin/store-settings')
        ->assertOk()
        ->assertSee('name="shipping_methods[0][name]"', false);
});

test('local delivery pricing uses city before manual shipping methods', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda Zonas',
        'slug' => 'tienda-zonas',
        'plan' => Store::PLAN_PRO,
        'whatsapp' => '573001112233',
        'local_delivery_area' => 'Bogota',
        'local_delivery_cost' => 5000,
        'outside_delivery_cost' => 12000,
        'shipping_methods' => [
            ['name' => 'Metodo manual ignorado', 'cost' => 30000],
        ],
        'is_active' => true,
    ]);

    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Producto por zona',
        'price' => 30000,
    ]);

    $this->post(route('cart.add', $product->id))->assertRedirect();

    $this->get(route('cart.index', ['store' => $store->slug]))
        ->assertOk()
        ->assertSee('Envio por ciudad')
        ->assertSee('data-local-delivery-enabled="1"', false)
        ->assertDontSee('Metodo manual ignorado');

    $this->post(route('cart.whatsapp', ['store' => $store->slug]), [
        'name' => 'Cliente',
        'last_name' => 'Local',
        'phone' => '3001234567',
        'address' => 'Calle 1',
        'neighborhood' => 'Cedritos',
        'city' => 'bogota',
        'document' => '123456',
    ])->assertRedirectContains('https://wa.me/573001112233');

    $localOrder = Order::where('store_id', $store->id)->latest('id')->firstOrFail();

    expect($localOrder->shipping_method)->toBe('Envio local: Bogota')
        ->and((float) $localOrder->shipping_cost)->toBe(5000.0)
        ->and((float) $localOrder->total)->toBe(35000.0);

    $this->post(route('cart.add', $product->id))->assertRedirect();

    $this->post(route('cart.whatsapp', ['store' => $store->slug]), [
        'name' => 'Cliente',
        'last_name' => 'Fuera',
        'phone' => '3001234567',
        'address' => 'Calle 2',
        'neighborhood' => 'Cedritos',
        'city' => 'Cali',
        'document' => '789123',
    ])->assertRedirectContains('https://wa.me/573001112233');

    $outsideOrder = Order::where('store_id', $store->id)->latest('id')->firstOrFail();

    expect($outsideOrder->shipping_method)->toBe('Envio fuera de Bogota')
        ->and((float) $outsideOrder->shipping_cost)->toBe(12000.0)
        ->and((float) $outsideOrder->total)->toBe(42000.0);
});

test('checkout can require official colombia city selections', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    ColombiaLocation::create([
        'department_code' => '11',
        'department_name' => 'Bogota D.C.',
        'city_code' => '11001',
        'city_name' => 'Bogota',
        'normalized_city_name' => ColombiaLocation::normalizeName('Bogota'),
    ]);

    ColombiaLocation::create([
        'department_code' => '76',
        'department_name' => 'Valle Del Cauca',
        'city_code' => '76001',
        'city_name' => 'Cali',
        'normalized_city_name' => ColombiaLocation::normalizeName('Cali'),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda Ciudades',
        'slug' => 'tienda-ciudades',
        'plan' => Store::PLAN_PRO,
        'whatsapp' => '573001112233',
        'local_delivery_area' => 'Bogota',
        'local_delivery_city_code' => '11001',
        'local_delivery_cost' => 5000,
        'outside_delivery_cost' => 12000,
        'is_active' => true,
    ]);

    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Producto ciudad oficial',
        'price' => 30000,
    ]);

    $this->post(route('cart.add', $product->id))->assertRedirect();

    $this->get(route('cart.index', ['store' => $store->slug]))
        ->assertOk()
        ->assertSee('name="department_code"', false)
        ->assertSee('name="city_code"', false)
        ->assertSee('data-city-select data-city-input disabled', false)
        ->assertSee('Bogota')
        ->assertSee('Cali');

    $this->post(route('cart.whatsapp', ['store' => $store->slug]), [
        'name' => 'Cliente',
        'last_name' => 'Invalido',
        'phone' => '3001234567',
        'address' => 'Calle 1',
        'neighborhood' => 'Cedritos',
        'department_code' => '11',
        'city_code' => '76001',
        'document' => '123456',
    ])->assertSessionHasErrors('city_code');

    $this->post(route('cart.whatsapp', ['store' => $store->slug]), [
        'name' => 'Cliente',
        'last_name' => 'Oficial',
        'phone' => '3001234567',
        'address' => 'Calle 1',
        'neighborhood' => 'Cedritos',
        'department_code' => '11',
        'city_code' => '11001',
        'document' => '123456',
    ])->assertRedirectContains('https://wa.me/573001112233');

    $order = Order::where('store_id', $store->id)->latest('id')->firstOrFail();

    expect($order->customer_city)->toBe('Bogota')
        ->and($order->notes)->toContain('Provincia/Estado: Bogota D.C.')
        ->and($order->shipping_method)->toBe('Envio local: Bogota')
        ->and((float) $order->shipping_cost)->toBe(5000.0);
});

test('local delivery pricing uses official city code when city names are duplicated', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    ColombiaLocation::create([
        'department_code' => '52',
        'department_name' => 'Narino',
        'city_code' => '52399',
        'city_name' => 'La Union',
        'normalized_city_name' => ColombiaLocation::normalizeName('La Union'),
    ]);

    ColombiaLocation::create([
        'department_code' => '76',
        'department_name' => 'Valle Del Cauca',
        'city_code' => '76400',
        'city_name' => 'La Union',
        'normalized_city_name' => ColombiaLocation::normalizeName('La Union'),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda Ciudad Duplicada',
        'slug' => 'tienda-ciudad-duplicada',
        'plan' => Store::PLAN_PRO,
        'whatsapp' => '573001112233',
        'local_delivery_area' => 'La Union',
        'local_delivery_city_code' => '52399',
        'local_delivery_cost' => 4000,
        'outside_delivery_cost' => 11000,
        'is_active' => true,
    ]);

    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Producto ciudad repetida',
        'price' => 30000,
    ]);

    $this->post(route('cart.add', $product->id))->assertRedirect();

    $this->post(route('cart.whatsapp', ['store' => $store->slug]), [
        'name' => 'Cliente',
        'last_name' => 'Fuera',
        'phone' => '3001234567',
        'address' => 'Calle 2',
        'neighborhood' => 'Centro',
        'department_code' => '76',
        'city_code' => '76400',
        'document' => '789123',
    ])->assertRedirectContains('https://wa.me/573001112233');

    $order = Order::where('store_id', $store->id)->latest('id')->firstOrFail();

    expect($order->customer_city)->toBe('La Union')
        ->and($order->notes)->toContain('Provincia/Estado: Valle Del Cauca')
        ->and($order->shipping_method)->toBe('Envio fuera de La Union')
        ->and((float) $order->shipping_cost)->toBe(11000.0)
        ->and((float) $order->total)->toBe(41000.0);
});

test('product reviews are available on pro and premium stores', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda Reviews Pro',
        'slug' => 'tienda-reviews-pro',
        'plan' => Store::PLAN_PRO,
        'business_type' => 'technology',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Producto con reviews',
        'price' => 120000,
    ]);

    $this->post(route('product.reviews.store', $product), [
        'name' => 'Cliente feliz',
        'rating' => 5,
        'comment' => 'Muy buen producto.',
    ])->assertRedirect();

    $review = ProductReview::where('product_id', $product->id)->firstOrFail();

    $this->assertDatabaseHas('product_reviews', [
        'store_id' => $store->id,
        'product_id' => $product->id,
        'name' => 'Cliente feliz',
        'rating' => 5,
        'comment' => 'Muy buen producto.',
        'is_approved' => false,
    ]);

    $this->get(route('store.product.show', [
        'slug' => $store->slug,
        'product' => $product->fresh()->publicRouteKey(),
    ]))
        ->assertOk()
        ->assertDontSee('Resenas (0)')
        ->assertDontSee('Aun no hay resenas')
        ->assertDontSee('Cliente feliz')
        ->assertDontSee('Muy buen producto.')
        ->assertSee('Publicar resena');

    $this->actingAs($user)
        ->get(route('admin.products.edit', $product))
        ->assertOk()
        ->assertSee('Resenas del producto')
        ->assertSee('Pendiente')
        ->assertSee('Cliente feliz')
        ->assertSee('Aprobar');

    $this->actingAs($user)
        ->patch(route('admin.product-reviews.approve', $review))
        ->assertRedirect();

    expect($review->fresh()->is_approved)->toBeTrue();

    $this->get(route('store.product.show', [
        'slug' => $store->slug,
        'product' => $product->fresh()->publicRouteKey(),
    ]))
        ->assertOk()
        ->assertSee('Resenas (1)')
        ->assertSee('Cliente feliz')
        ->assertSee('Muy buen producto.');

    $premiumStore = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda Reviews Premium',
        'slug' => 'tienda-reviews-premium',
        'plan' => Store::PLAN_PREMIUM,
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    expect($premiumStore->allowsProductReviews())->toBeTrue();
});

test('product reviews are blocked on basic stores', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda Reviews Basic',
        'slug' => 'tienda-reviews-basic',
        'plan' => Store::PLAN_BASIC,
        'business_type' => 'technology',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Producto sin reviews',
        'price' => 120000,
    ]);

    $this->post(route('product.reviews.store', $product), [
        'name' => 'Cliente',
        'rating' => 5,
        'comment' => 'No debe entrar.',
    ])->assertNotFound();

    expect(ProductReview::where('product_id', $product->id)->exists())->toBeFalse();

    $this->get(route('store.product.show', [
        'slug' => $store->slug,
        'product' => $product->fresh()->publicRouteKey(),
    ]))
        ->assertOk()
        ->assertDontSee('Publicar resena')
        ->assertDontSee('Resenas (');
});

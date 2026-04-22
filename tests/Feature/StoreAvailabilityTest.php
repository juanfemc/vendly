<?php

use App\Models\Store;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StoreBanner;
use App\Models\User;
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
        'is_active' => true,
    ]);

    $this->get('/tienda-activa')->assertOk();
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
            'city' => 'Bogota',
            'document' => '123456',
        ])
        ->assertRedirect('/cart')
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

    Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Audifonos',
        'price' => 99000,
        'sizes' => ['Unica'],
        'colors' => ['Negro'],
    ]);

    $this->get('/tech-store')
        ->assertOk()
        ->assertSee('name="size"', false)
        ->assertSee('name="color"', false);
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

    expect(Store::where('slug', 'color-store')->first()->brand_color)->toBe('#abc');

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
    ]);

    StoreBanner::create([
        'store_id' => $store->id,
        'title' => 'Banner unico',
        'image' => 'banners/banner.webp',
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.stores.destroy', $store))
        ->assertRedirect('/admin/stores');

    $this->assertDatabaseMissing('stores', ['id' => $store->id]);
    $this->assertDatabaseMissing('products', ['store_id' => $store->id]);
    $this->assertDatabaseMissing('store_banners', ['store_id' => $store->id]);
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
        'status' => 'pendiente',
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
        ->delete('/admin/products/' . $product->id)
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
            'city' => 'Bogota',
            'document' => '123456',
        ])
        ->assertRedirect('/cart')
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

    $this->actingAs($admin)
        ->get('/admin/orders')
        ->assertOk()
        ->assertSee('Cliente Global')
        ->assertSee('Tienda visible admin');
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
        'status' => 'pendiente',
        'total' => 12000,
        'store_id' => $store->id,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.orders.status', $order->id), [
            'status' => 'pagado',
        ])
        ->assertRedirect('/admin/orders');

    expect($order->refresh()->status)->toBe('pagado');
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
        ->assertSee('<title>Compra ropa urbana y pide por WhatsApp en minutos.</title>', false)
        ->assertSee('<meta property="og:title" content="Compra ropa urbana y pide por WhatsApp en minutos.">', false);
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
        ->assertSee('<link rel="icon" href="' . asset('storage/stores/logo.webp') . '">', false);
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

test('admin cannot use reserved store slugs and entered slugs are normalized', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    foreach (['cart', 'forgot-password', 'reset-password', 'verify-email', 'confirm-password', 'email'] as $reservedSlug) {
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

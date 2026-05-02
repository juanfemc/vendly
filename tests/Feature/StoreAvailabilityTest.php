<?php

use App\Models\Store;
use App\Models\AdminUpdate;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StoreBanner;
use App\Models\StoreCategory;
use App\Models\User;
use App\Services\AdminUpdateService;
use Illuminate\Http\UploadedFile;
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
        ->patch(route('admin.orders.status', $order), [
            'status' => 'pagado',
        ])
        ->assertRedirect('/admin/orders');

    expect($order->refresh()->status)->toBe('pagado');
});

test('admin can create edit and delete products for any store', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $storeUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $storeUser->id,
        'name' => 'Tienda Productos Admin',
        'slug' => 'tienda-productos-admin',
        'whatsapp' => '573001112233',
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
            'description' => 'Creado desde el administrador.',
        ])
        ->assertRedirect('/admin/products');

    $product = Product::where('store_id', $store->id)
        ->where('name', 'Producto desde Admin')
        ->firstOrFail();

    expect($product->user_id)->toBe($storeUser->id);

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
            'description' => 'Editado desde el administrador.',
        ])
        ->assertRedirect('/admin/products');

    expect($product->refresh()->name)->toBe('Producto editado por Admin');
    expect((float) $product->price)->toBe(82000.0);

    $this->actingAs($admin)
        ->delete(route('admin.products.destroy', $product))
        ->assertRedirect();

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
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

    $this->post(route('cart.whatsapp', ['store' => $store->slug]), [
        'name' => 'Cliente',
        'last_name' => 'Prueba',
        'phone' => '3001234567',
        'address' => 'Calle 1',
        'city' => 'Bogota',
        'document' => '123456',
    ])->assertRedirectContains('https://wa.me/573001112233');

    $this->assertDatabaseHas('orders', [
        'store_id' => $store->id,
        'total' => 30000,
    ]);

    expect(session()->has('cart'))->toBeFalse();
    expect(session()->has('carts.' . $store->id))->toBeFalse();

    $this->get(route('cart.index', ['store' => $store->slug]))
        ->assertOk()
        ->assertSee('Tu carrito esta vacio')
        ->assertDontSee('Producto checkout');
});

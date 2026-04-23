<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminBannerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StoreCategoryController;

/*
|--------------------------------------------------------------------------
| TIENDA
|--------------------------------------------------------------------------
*/


Route::post('/cart/add/{id}', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/buy-now/{id}', [CartController::class, 'buyNow'])->name('cart.buy_now');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::patch('/cart/item/{id}', [CartController::class, 'updateItem'])->name('cart.item.update');
Route::delete('/cart/item/{id}', [CartController::class, 'removeItem'])->name('cart.item.remove');
Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
Route::post('/cart/whatsapp', [CartController::class, 'whatsappFromCart'])->name('cart.whatsapp');



/*
|--------------------------------------------------------------------------
| ADMIN (PROTEGIDO)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/admin/products', [ProductController::class, 'index'])->name('admin.products.index');
    Route::get('/admin/products/create', [ProductController::class, 'create'])->name('admin.products.create');
    Route::post('/admin/products', [ProductController::class, 'store'])->name('admin.products.store');
    Route::get('/admin/products/{product}/edit', [ProductController::class, 'edit'])->name('admin.products.edit');
    Route::put('/admin/products/{product}', [ProductController::class, 'update'])->name('admin.products.update');
    Route::delete('/admin/products/{product}', [ProductController::class, 'destroy'])->name('admin.products.destroy');

    Route::get('/admin/orders', [OrderController::class, 'index']);
    Route::patch('/admin/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('admin.orders.status');
    Route::get('/admin/store-settings', [StoreController::class, 'settings']);
    Route::post('/admin/store-settings', [StoreController::class, 'updateSettings']);
    Route::get('/admin/categories', [StoreCategoryController::class, 'index'])->name('admin.categories.index');
    Route::post('/admin/categories', [StoreCategoryController::class, 'store'])->name('admin.categories.store');
    Route::get('/admin/categories/{category}/edit', [StoreCategoryController::class, 'edit'])->name('admin.categories.edit');
    Route::put('/admin/categories/{category}', [StoreCategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('/admin/categories/{category}', [StoreCategoryController::class, 'destroy'])->name('admin.categories.destroy');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/users', [AdminUserController::class, 'index']);
    Route::get('/admin/users/create', [AdminUserController::class, 'create']);
    Route::post('/admin/users', [AdminUserController::class, 'store']);
    Route::get('/admin/users/{user}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/admin/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
    Route::patch('/admin/users/{user}/toggle', [AdminUserController::class, 'toggleActive'])->name('admin.users.toggle');
    Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');

    Route::get('/admin/banners', [AdminBannerController::class, 'index']);
    Route::get('/admin/banners/create', [AdminBannerController::class, 'create']);
    Route::post('/admin/banners', [AdminBannerController::class, 'store']);
    Route::patch('/admin/banners/{banner}/toggle', [AdminBannerController::class, 'toggle'])->name('admin.banners.toggle');
    Route::delete('/admin/banners/{banner}', [AdminBannerController::class, 'destroy'])->name('admin.banners.destroy');

    Route::get('/admin/stores', [StoreController::class, 'index']);
    Route::get('/admin/stores/create', [StoreController::class, 'create']);
    Route::post('/admin/stores', [StoreController::class, 'store']);
    Route::get('/admin/stores/{store}/edit', [StoreController::class, 'edit'])->name('admin.stores.edit');
    Route::put('/admin/stores/{store}', [StoreController::class, 'update'])->name('admin.stores.update');
    Route::delete('/admin/stores/{store}', [StoreController::class, 'destroy'])->name('admin.stores.destroy');
});


/*
|--------------------------------------------------------------------------
| AUTH (ESTO NO SE TOCA 🔥)
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('landing');
});
require __DIR__.'/auth.php';
Route::get('/{slug}/categorias/{category}', [ProductController::class, 'category'])->name('store.category.show');
Route::get('/{slug}/productos/{product}', [ProductController::class, 'show'])->name('store.product.show');
Route::get('/{slug}', [ProductController::class, 'storeBySlug'])->name('store.show');

<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use App\Models\Store;
use App\Models\LandingTestimonial;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\AiContentController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StoreTemplateController;
use App\Http\Controllers\StoreFaviconController;
use App\Http\Controllers\StoreOnboardingController;
use App\Http\Controllers\TrialSignupController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminBannerController;
use App\Http\Controllers\LandingTestimonialController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentSettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StoreCategoryController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\WhatsAppInboxController;
use App\Services\StoreSubdomainService;

/*
|--------------------------------------------------------------------------
| TIENDA
|--------------------------------------------------------------------------
*/


Route::post('/cart/add/{id}', [CartController::class, 'add'])->middleware('throttle:30,1')->name('cart.add');
Route::post('/cart/buy-now/{id}', [CartController::class, 'buyNow'])->middleware('throttle:20,1')->name('cart.buy_now');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::patch('/cart/item/{id}', [CartController::class, 'updateItem'])->name('cart.item.update');
Route::delete('/cart/item/{id}', [CartController::class, 'removeItem'])->name('cart.item.remove');
Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
Route::post('/cart/whatsapp', [CartController::class, 'whatsappFromCart'])->middleware('throttle:10,1')->name('cart.whatsapp');
Route::post('/cart/mercadopago', [CartController::class, 'mercadoPagoFromCart'])->middleware('throttle:10,1')->name('cart.mercadopago');
Route::post('/productos/{product}/reviews', [ProductReviewController::class, 'store'])
    ->middleware('throttle:8,1')
    ->name('product.reviews.store');
Route::get('/cart/mercadopago/{order}/{result}', [CartController::class, 'mercadoPagoReturn'])
    ->whereIn('result', ['success', 'failure', 'pending'])
    ->whereUuid('order')
    ->name('cart.mercadopago.return');
Route::post('/webhooks/mercadopago', [CartController::class, 'mercadoPagoWebhook'])
    ->middleware('throttle:120,1')
    ->name('cart.mercadopago.webhook');
Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])
    ->middleware('throttle:30,1')
    ->name('whatsapp.webhook.verify');
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'receive'])
    ->middleware('throttle:120,1')
    ->name('whatsapp.webhook.receive');



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
    Route::post('/admin/ai/content', [AiContentController::class, 'generate'])->middleware('throttle:30,1')->name('admin.ai.content');
    Route::post('/admin/ai/images', [AiContentController::class, 'generateImage'])->middleware('throttle:10,1')->name('admin.ai.images');
    Route::get('/admin/products/create', [ProductController::class, 'create'])->name('admin.products.create');
    Route::post('/admin/products', [ProductController::class, 'store'])->name('admin.products.store');
    Route::get('/admin/products/{product}/edit', [ProductController::class, 'edit'])->name('admin.products.edit');
    Route::put('/admin/products/{product}', [ProductController::class, 'update'])->name('admin.products.update');
    Route::delete('/admin/products/{product}', [ProductController::class, 'destroy'])->name('admin.products.destroy');
    Route::patch('/admin/product-reviews/{review}/approve', [ProductReviewController::class, 'approve'])->name('admin.product-reviews.approve');
    Route::delete('/admin/product-reviews/{review}', [ProductReviewController::class, 'destroy'])->name('admin.product-reviews.destroy');

    Route::get('/admin/orders', [OrderController::class, 'index']);
    Route::patch('/admin/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('admin.orders.status');
    Route::delete('/admin/orders/{order}', [OrderController::class, 'destroy'])->name('admin.orders.destroy');
    Route::get('/admin/whatsapp', [WhatsAppInboxController::class, 'index'])->name('admin.whatsapp.index');
    Route::post('/admin/whatsapp/{conversation}/send', [WhatsAppInboxController::class, 'send'])
        ->middleware('throttle:20,1')
        ->name('admin.whatsapp.send');
    Route::get('/admin/store-settings', [StoreController::class, 'settings']);
    Route::post('/admin/store-settings', [StoreController::class, 'updateSettings']);
    Route::get('/admin/onboarding', [StoreOnboardingController::class, 'edit'])->name('admin.store.onboarding');
    Route::post('/admin/onboarding', [StoreOnboardingController::class, 'update'])->name('admin.store.onboarding.update');
    Route::get('/admin/templates', [StoreTemplateController::class, 'index'])->name('admin.templates.index');
    Route::post('/admin/templates/{template}', [StoreTemplateController::class, 'apply'])->name('admin.templates.apply');
    Route::get('/admin/payments', [PaymentSettingsController::class, 'index'])->name('admin.payments.index');
    Route::get('/admin/payments/mercadopago/connect', [PaymentSettingsController::class, 'connectMercadoPago'])->name('admin.payments.mercadopago.connect');
    Route::get('/admin/payments/mercadopago/callback', [PaymentSettingsController::class, 'mercadoPagoCallback'])->name('admin.payments.mercadopago.callback');
    Route::get('/admin/store-visits', [StoreController::class, 'visits'])->name('admin.store.visits');
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
    Route::patch('/admin/users/{user}/extend', [AdminUserController::class, 'extendAccess'])->name('admin.users.extend');
    Route::patch('/admin/users/{user}/toggle', [AdminUserController::class, 'toggleActive'])->name('admin.users.toggle');
    Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');

    Route::get('/admin/banners', [AdminBannerController::class, 'index']);
    Route::get('/admin/banners/create', [AdminBannerController::class, 'create']);
    Route::post('/admin/banners', [AdminBannerController::class, 'store']);
    Route::get('/admin/banners/{banner}/edit', [AdminBannerController::class, 'edit'])->name('admin.banners.edit');
    Route::put('/admin/banners/{banner}', [AdminBannerController::class, 'update'])->name('admin.banners.update');
    Route::patch('/admin/banners/{banner}/toggle', [AdminBannerController::class, 'toggle'])->name('admin.banners.toggle');
    Route::delete('/admin/banners/{banner}', [AdminBannerController::class, 'destroy'])->name('admin.banners.destroy');

    Route::get('/admin/testimonials', [LandingTestimonialController::class, 'index'])->name('admin.testimonials.index');
    Route::get('/admin/testimonials/create', [LandingTestimonialController::class, 'create'])->name('admin.testimonials.create');
    Route::post('/admin/testimonials', [LandingTestimonialController::class, 'store'])->name('admin.testimonials.store');
    Route::get('/admin/testimonials/{testimonial}/edit', [LandingTestimonialController::class, 'edit'])->name('admin.testimonials.edit');
    Route::put('/admin/testimonials/{testimonial}', [LandingTestimonialController::class, 'update'])->name('admin.testimonials.update');
    Route::patch('/admin/testimonials/{testimonial}/toggle', [LandingTestimonialController::class, 'toggle'])->name('admin.testimonials.toggle');
    Route::delete('/admin/testimonials/{testimonial}', [LandingTestimonialController::class, 'destroy'])->name('admin.testimonials.destroy');

    Route::get('/admin/stores', [StoreController::class, 'index']);
    Route::get('/admin/stores/create-with-user', [StoreController::class, 'createWithUser'])->name('admin.stores.create-with-user');
    Route::post('/admin/stores/create-with-user', [StoreController::class, 'storeWithUser'])->name('admin.stores.store-with-user');
    Route::get('/admin/stores/create', [StoreController::class, 'create']);
    Route::post('/admin/stores', [StoreController::class, 'store']);
    Route::get('/admin/stores/visits', [StoreController::class, 'visits'])->name('admin.stores.visits');
    Route::get('/admin/stores/{store}/products', [ProductController::class, 'index'])->name('admin.stores.products.index');
    Route::get('/admin/stores/{store}/categories', [StoreCategoryController::class, 'index'])->name('admin.stores.categories.index');
    Route::get('/admin/stores/{store}/edit', [StoreController::class, 'edit'])->name('admin.stores.edit');
    Route::put('/admin/stores/{store}', [StoreController::class, 'update'])->name('admin.stores.update');
    Route::post('/admin/stores/{store}/ai-credits', [StoreController::class, 'addAiCredits'])->name('admin.stores.ai-credits.store');
    Route::patch('/admin/stores/{store}/subscription', [StoreController::class, 'activateSubscription'])->name('admin.stores.subscription.activate');
    Route::delete('/admin/stores/{store}', [StoreController::class, 'destroy'])->name('admin.stores.destroy');
});


/*
|--------------------------------------------------------------------------
| AUTH (ESTO NO SE TOCA 🔥)
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    $subdomains = app(StoreSubdomainService::class);

    if ($subdomains->subdomainFromRequest(request()) || $subdomains->publicStoreFromRequest(request())) {
        return app(ProductController::class)->storeBySubdomain(request(), $subdomains);
    }

    $portfolioStores = Schema::hasColumn('stores', 'views_count')
        ? Store::publiclyAvailable()
            ->where('views_count', '>', 0)
            ->orderByDesc('views_count')
            ->orderBy('name')
            ->take(3)
            ->get()
        : collect();

    $proofStores = Schema::hasColumn('stores', 'views_count')
        ? Store::publiclyAvailable()
            ->whereNotNull('logo_image')
            ->where('logo_image', '!=', '')
            ->orderByDesc('views_count')
            ->orderBy('name')
            ->take(6)
            ->get()
        : collect();

    $testimonials = Schema::hasTable('landing_testimonials')
        ? LandingTestimonial::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
        : collect();

    return view('landing', compact('portfolioStores', 'proofStores', 'testimonials'));
});
require __DIR__.'/auth.php';

Route::middleware('guest')->group(function () {
    Route::get('/crear-tienda-gratis', [TrialSignupController::class, 'create'])->name('trial-signup.create');
    Route::post('/crear-tienda-gratis', [TrialSignupController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('trial-signup.store');
    Route::post('/crear-tienda-gratis/verificar-whatsapp', [TrialSignupController::class, 'sendVerificationCode'])
        ->middleware('throttle:5,1')
        ->name('trial-signup.whatsapp.verify');
});

Route::get('/categorias/{category}', [ProductController::class, 'categoryBySubdomain'])->name('subdomain.store.category.show');
Route::get('/nosotros', [ProductController::class, 'aboutBySubdomain'])->name('subdomain.store.about');
Route::get('/ofertas', [ProductController::class, 'offersBySubdomain'])->name('subdomain.store.offers.index');
Route::get('/productos', [ProductController::class, 'allProductsBySubdomain'])->name('subdomain.store.products.index');
Route::get('/productos/{product}', [ProductController::class, 'showBySubdomain'])->name('subdomain.store.product.show');
Route::get('/favicon.svg', [StoreFaviconController::class, 'current'])->name('store.favicon.current');
Route::get('/{slug}/favicon.svg', [StoreFaviconController::class, 'show'])->name('store.favicon');
Route::get('/{slug}/categorias/{category}', [ProductController::class, 'category'])->name('store.category.show');
Route::get('/{slug}/nosotros', [ProductController::class, 'about'])->name('store.about');
Route::get('/{slug}/ofertas', [ProductController::class, 'offers'])->name('store.offers.index');
Route::get('/{slug}/productos', [ProductController::class, 'allProducts'])->name('store.products.index');
Route::get('/{slug}/productos/{product}', [ProductController::class, 'show'])->name('store.product.show');
Route::get('/{slug}', [ProductController::class, 'storeBySlug'])->name('store.show');

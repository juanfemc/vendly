<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\AdminUpdate;
use App\Models\Order;
use App\Models\Store;
use App\Models\StoreBanner;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $hasVisitsColumn = Schema::hasColumn('stores', 'views_count');

        if ($user?->isAdmin()) {
            $storeUsersCount = User::where('role', 'store')->count();
            $storesCount = Store::count();
            $storeUsers = User::where('role', 'store')->latest()->get();
            $expiringStores = Store::with('user')
                ->subscriptionsEndingWithin(3)
                ->latest()
                ->take(8)
                ->get();
            $expiredStores = Store::with('user')
                ->expiredSubscriptions()
                ->latest()
                ->take(8)
                ->get();
            $expiringUsers = User::where('role', 'store')
                ->where('is_active', true)
                ->whereDate('active_ends_at', '>=', now()->toDateString())
                ->whereDate('active_ends_at', '<=', now()->addDays(7)->toDateString())
                ->orderBy('active_ends_at')
                ->get();
            $totalSales = (float) Order::whereIn('status', ['pagado', 'enviado'])->sum('total')
                - (float) Order::where('status', 'devuelto')->sum('total');
            $totalVisits = $hasVisitsColumn ? (int) Store::sum('views_count') : 0;
            $adminUpdates = Schema::hasTable('admin_updates')
                ? AdminUpdate::orderByDesc('id')->take(10)->get()
                : collect();

            return view('dashboard', compact(
                'storeUsersCount',
                'storesCount',
                'storeUsers',
                'expiringStores',
                'expiredStores',
                'expiringUsers',
                'totalSales',
                'totalVisits',
                'adminUpdates'
            ));
        }

        $store = $user?->store ?? $user?->stores()->first();
        $products = $store
            ? Product::where('store_id', $store->id)->latest()->take(6)->get()
            : collect();
        $productsCount = $store
            ? Product::where('store_id', $store->id)->count()
            : 0;
        $ordersCount = $store
            ? $store->orders()->count()
            : 0;
        $paidOrdersCount = $store
            ? $store->orders()->where('status', 'pagado')->count()
            : 0;
        $shippedOrdersCount = $store
            ? $store->orders()->where('status', 'enviado')->count()
            : 0;
        $totalSales = $store
            ? (float) $store->orders()->whereIn('status', ['pagado', 'enviado'])->sum('total')
                - (float) $store->orders()->where('status', 'devuelto')->sum('total')
            : 0;
        $totalVisits = $store && $hasVisitsColumn ? (int) $store->views_count : 0;
        $onboardingProgress = $store ? $store->onboardingProgress() : 0;
        $onboardingChecklist = $store ? $store->onboardingChecklist() : [];
        $needsOnboarding = $store ? $store->needsOnboarding() : false;
        $subscriptionExpired = $store ? $store->subscriptionExpired() : false;
        $subscriptionEndsSoon = $store ? $store->subscriptionEndsSoon() : false;
        $subscriptionRemainingLabel = $store ? $store->subscriptionRemainingLabel() : null;
        $accountExpiresSoon = $user?->active_ends_at
            && $user->is_active
            && $user->active_ends_at->toDateString() >= now()->toDateString()
            && $user->active_ends_at->toDateString() <= now()->addDays(7)->toDateString();

        $banners = $store
            ? StoreBanner::where('store_id', $store->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->latest()
                ->get()
            : collect();

        return view('dashboard', compact(
            'store',
            'products',
            'banners',
            'productsCount',
            'ordersCount',
            'paidOrdersCount',
            'shippedOrdersCount',
            'totalSales',
            'totalVisits',
            'onboardingProgress',
            'onboardingChecklist',
            'needsOnboarding',
            'subscriptionExpired',
            'subscriptionEndsSoon',
            'subscriptionRemainingLabel',
            'accountExpiresSoon'
        ));
    }
}

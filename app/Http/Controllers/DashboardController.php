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

        if ($user?->isAdmin()) {
            $storeUsersCount = User::where('role', 'store')->count();
            $storesCount = Store::count();
            $storeUsers = User::where('role', 'store')->latest()->get();
            $expiringUsers = User::where('role', 'store')
                ->where('is_active', true)
                ->whereDate('active_ends_at', '>=', now()->toDateString())
                ->whereDate('active_ends_at', '<=', now()->addDays(7)->toDateString())
                ->orderBy('active_ends_at')
                ->get();
            $totalSales = (float) Order::whereIn('status', ['pagado', 'enviado'])->sum('total');
            $totalVisits = (int) Store::sum('views_count');
            $adminUpdates = Schema::hasTable('admin_updates')
                ? AdminUpdate::orderByDesc('id')->take(10)->get()
                : collect();

            return view('dashboard', compact('storeUsersCount', 'storesCount', 'storeUsers', 'expiringUsers', 'totalSales', 'totalVisits', 'adminUpdates'));
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
            : 0;
        $totalVisits = $store ? (int) $store->views_count : 0;
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
            'accountExpiresSoon'
        ));
    }
}

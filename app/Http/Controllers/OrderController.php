<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Services\AdminUpdateService;

class OrderController extends Controller
{
    public function __construct(private AdminUpdateService $adminUpdateService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $user = auth()->user();
        $statusOptions = Order::statusOptions();
        $selectedStatus = $request->query('status');
        $selectedStatus = array_key_exists((string) $selectedStatus, $statusOptions)
            ? (string) $selectedStatus
            : null;

        if ($user?->isAdmin()) {
            $ordersQuery = Order::with(['items.product', 'store']);
            $totalOrders = (clone $ordersQuery)->count();
            $orders = $ordersQuery
                ->when($selectedStatus, fn ($query) => $query->where('status', $selectedStatus))
                ->latest()
                ->get();

            return view('admin.orders.index', compact('orders', 'statusOptions', 'selectedStatus', 'totalOrders'));
        }

        $store = $user?->store ?? $user?->stores()->first();

        if (! $store) {
            return view('admin.orders.index', [
                'orders' => collect(),
                'statusOptions' => $statusOptions,
                'selectedStatus' => $selectedStatus,
                'totalOrders' => 0,
            ]);
        }

        $ordersQuery = Order::with('items.product')->where('store_id', $store->id);
        $totalOrders = (clone $ordersQuery)->count();
        $orders = $ordersQuery
            ->when($selectedStatus, fn ($query) => $query->where('status', $selectedStatus))
            ->latest()
            ->get();

        return view('admin.orders.index', compact('orders', 'statusOptions', 'selectedStatus', 'totalOrders'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys(Order::statusOptions()))],
        ]);

        if (! $user?->isAdmin()) {
            $store = $user?->store ?? $user?->stores()->first();

            abort_if(! $store, 404);
            abort_unless((int) $order->store_id === (int) $store->id, 404);
        }

        $this->authorize('update', $order);
        $order->update([
            'status' => $validated['status'],
        ]);

        $this->adminUpdateService->record(
            'Pedido actualizado',
            'Pedido #' . $order->id . ' ahora esta ' . Order::statusOptions()[$validated['status']],
            'pedido',
            '/admin/orders'
        );

        return redirect('/admin/orders')->with('success', 'Estado del pedido actualizado.');
    }

    public function destroy(Order $order)
    {
        $this->authorize('delete', $order);

        $orderId = $order->id;
        $storeName = $order->store?->name;

        $order->delete();

        $this->adminUpdateService->record(
            'Pedido eliminado',
            'Pedido #' . $orderId . ($storeName ? ' en ' . $storeName : ''),
            'pedido',
            '/admin/orders'
        );

        return redirect('/admin/orders')->with('success', 'Pedido eliminado.');
    }
}

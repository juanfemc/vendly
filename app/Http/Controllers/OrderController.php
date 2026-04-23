<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;

class OrderController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Order::class);

        $user = auth()->user();

        if ($user?->isAdmin()) {
            $orders = Order::with(['items.product', 'store'])->latest()->get();
            $statusOptions = Order::statusOptions();

            return view('admin.orders.index', compact('orders', 'statusOptions'));
        }

        $store = $user?->store ?? $user?->stores()->first();

        if (! $store) {
            return view('admin.orders.index', ['orders' => collect()]);
        }

        $orders = Order::with('items.product')->where('store_id', $store->id)->latest()->get();

        $statusOptions = Order::statusOptions();

        return view('admin.orders.index', compact('orders', 'statusOptions'));
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

        return redirect('/admin/orders')->with('success', 'Estado del pedido actualizado.');
    }
}

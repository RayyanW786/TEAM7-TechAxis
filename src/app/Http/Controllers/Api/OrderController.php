<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends ApiController
{
    public function index(Request $request)
    {
        $user = $request->user();

        $orders = Order::query()
            ->with(['items', 'shipments', 'discountRedemptions.discountCode'])
            ->when(!$user || $user->role !== UserRole::Admin, fn($qb) => $qb->where('user_id', $user?->id))
            ->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', 20));

        return response()->json($orders);
    }

    public function show(Request $request, Order $order)
    {
        $user = $request->user();

        if (!$user || ($user->role !== UserRole::Admin && (int) $order->user_id !== (int) $user->id)) {
            abort(404);
        }

        $order->load(['items', 'shipments', 'billingAddress', 'shippingAddress', 'discountRedemptions.discountCode']);

        return response()->json($order);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_map(fn($c) => $c->value, OrderStatus::cases()))],
        ]);

        $order->update([
            'status' => $data['status'],
        ]);

    
        return back()->with('success', 'Order status updated successfully.');
    }
}

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
            ->with([
                'items',
                'shipments',
                'discountRedemptions.discountCode',
                'user:id,name,email',
                'shippingAddress:id,user_id,label,recipient_name,line1,city,postal_code',
                'billingAddress:id,user_id,label,recipient_name,line1,city,postal_code',
            ])
            ->when(!$user || $user->role !== UserRole::Admin, fn($qb) => $qb->where('user_id', $user?->id))
            ->when($user && $user->role === UserRole::Admin && $request->filled('status'), fn ($qb) => $qb->where('status', $request->query('status')))
            ->when($user && $user->role === UserRole::Admin && $request->filled('user_id'), fn ($qb) => $qb->where('user_id', (int) $request->query('user_id')))
            ->when($user && $user->role === UserRole::Admin && $request->filled('date_from'), fn ($qb) => $qb->whereDate('created_at', '>=', $request->query('date_from')))
            ->when($user && $user->role === UserRole::Admin && $request->filled('date_to'), fn ($qb) => $qb->whereDate('created_at', '<=', $request->query('date_to')))
            ->when($user && $user->role === UserRole::Admin && $request->filled('q'), function ($qb) use ($request) {
                $q = trim((string) $request->query('q'));
                $like = '%' . $q . '%';

                $qb->where(function ($inner) use ($q, $like) {
                    $inner->whereHas('user', fn ($userQb) => $userQb->where('name', 'ilike', $like)->orWhere('email', 'ilike', $like))
                        ->orWhereHas('items', fn ($itemQb) => $itemQb->where('name_snapshot', 'ilike', $like)->orWhere('sku_snapshot', 'ilike', $like));

                    if (is_numeric($q)) {
                        $inner->orWhere('id', (int) $q)->orWhere('user_id', (int) $q);
                    }
                });
            })
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

        $order->load([
            'user',
            'items.product',
            'items.variant',
            'shipments',
            'billingAddress',
            'shippingAddress',
            'discountRedemptions.discountCode',
        ]);

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

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($order->fresh(['user', 'shipments', 'items']));
        }

        return back()->with('success', 'Order status updated successfully.');
    }
}

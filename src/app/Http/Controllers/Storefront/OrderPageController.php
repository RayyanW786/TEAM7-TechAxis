<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderPageController extends Controller
{
    public function show(Request $request, Order $order)
    {
        $user = $request->user();
        abort_unless($user && (int) $order->user?->id === (int) $user->id, 404);

        $order->load(['items.product', 'items.variant']);

        return view('storefront.orders.show', [
            'order' => $order,
        ]);
    }
}

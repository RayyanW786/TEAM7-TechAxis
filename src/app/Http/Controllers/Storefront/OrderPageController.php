<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductReview;
use Illuminate\Http\Request;

class OrderPageController extends Controller
{
    public function show(Request $request, Order $order)
    {
        $user = $request->user();
        abort_unless(
            $user && ($user->isAdmin() || (int) $order->user_id === (int) $user->id),
            404
        );

        $order->load([
            'items.product',
            'items.variant',
            'billingAddress',
            'shippingAddress',
        ]);

        $reviewedProductIds = ProductReview::query()
            ->where('user_id', $user->id)
            ->whereIn('product_id', $order->items->pluck('product_id')->filter()->unique()->all())
            ->pluck('id', 'product_id')
            ->map(fn ($reviewId) => (int) $reviewId)
            ->all();

        return view('storefront.orders.show', [
            'order' => $order,
            'reviewedProductIds' => $reviewedProductIds,
        ]);
    }
}

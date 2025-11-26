<?php

namespace App\Http\Controllers\Api;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ServiceReview;
use Illuminate\Http\Request;

class ReviewController extends ApiController
{
    public function productIndex(Product $product)
    {
        return response()->json(
            ProductReview::query()
                ->where('product_id', $product->id)
                ->with('user:id,name')
                ->orderByDesc('created_at')
                ->paginate(20)
        );
    }

    public function upsertProduct(Request $request, Product $product)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'order_item_id' => ['nullable', 'integer', 'exists:order_items,id'],
        ]);

        $orderItemId = $data['order_item_id'] ?? null;

        if ($orderItemId) {
            $ok = OrderItem::query()
                ->where('id', $orderItemId)
                ->where('product_id', $product->id)
                ->whereHas('order', fn($qb) => $qb->where('user_id', $user->id))
                ->exists();

            abort_unless($ok, 422, 'order_item_id is not a verified purchase for this product.');
        }

        $review = ProductReview::query()->updateOrCreate(
            ['product_id' => $product->id, 'user_id' => $user->id],
            [
                'order_item_id' => $orderItemId,
                'rating' => $data['rating'],
                'title' => $data['title'] ?? null,
                'body' => $data['body'] ?? null,
            ]
        );

        return response()->json($review->fresh(), 201);
    }

    public function upsertService(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string'],
        ]);

        $review = ServiceReview::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['rating' => $data['rating'], 'comment' => $data['comment'] ?? null]
        );

        return response()->json($review->fresh(), 201);
    }
}

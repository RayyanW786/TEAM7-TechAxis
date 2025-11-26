<?php

namespace App\Http\Controllers\Api;

use App\Models\ProductVariant;
use App\Models\ShoppingCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class CartController extends ApiController
{
    public function show(Request $request)
    {
        $cart = $this->resolveCart($request);

        $cart->load(['items.product', 'items.variant']);

        return response()->json($cart);
    }

    public function addItem(Request $request)
    {
        $cart = $this->resolveCart($request);

        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        if (!empty($data['variant_id'])) {
            $ok = ProductVariant::query()
                ->where('id', $data['variant_id'])
                ->where('product_id', $data['product_id'])
                ->exists();

            abort_unless($ok, 422, 'Variant does not belong to the product.');
        }

        $cart->addItem((int) $data['product_id'], $data['variant_id'] ? (int) $data['variant_id'] : null, (int) $data['quantity']);

        $cart->load(['items.product', 'items.variant']);

        return response()->json($cart);
    }

    public function setItemQuantity(Request $request, int $cartItemId)
    {
        $cart = $this->resolveCart($request);

        $data = $request->validate([
            'quantity' => ['required', 'integer'],
        ]);

        $cart->updateItemQuantity($cartItemId, (int) $data['quantity']);

        $cart->load(['items.product', 'items.variant']);

        return response()->json($cart);
    }

    public function removeItem(Request $request)
    {
        $cart = $this->resolveCart($request);

        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
        ]);

        $cart->removeItem((int) $data['product_id'], $data['variant_id'] ? (int) $data['variant_id'] : null);

        $cart->load(['items.product', 'items.variant']);

        return response()->json($cart);
    }

    public function checkout(Request $request)
    {
        $cart = $this->resolveCart($request);

        $data = $request->validate([
            'billing_address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'shipping_address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'discount_code' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $order = DB::transaction(function () use ($request, $cart, $data) {
                $order = $cart->checkout(
                    $request->user()?->id,
                    $data['billing_address_id'] ?? null,
                    $data['shipping_address_id'] ?? null,
                    $data['discount_code'] ?? null
                );

                if (!empty($data['notes'])) {
                    $order->update(['notes' => $data['notes']]);
                }

                return $order;
            });

            return response()->json($order, 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Checkout failed.',
                'detail' => $e->getMessage(),
            ], 422);
        }
    }

    protected function resolveCart(Request $request): ShoppingCart
    {
        $user = $request->user();

        if ($user) {
            return ShoppingCart::firstOrCreate(['user_id' => $user->id]);
        }

        $cartId = $request->header('X-Cart-Id') ?? $request->query('cart_id');

        if ($cartId) {
            $cart = ShoppingCart::query()->find($cartId);

            if ($cart) {
                return $cart;
            }
        }

        return ShoppingCart::create(['user_id' => null]);
    }
}

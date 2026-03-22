<?php

namespace App\Http\Controllers\Api;

use App\Models\Address;
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
            'quantity' => ['required', 'integer', 'min:1'],
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

        $user = $request->user();
        abort_unless($user, 401);

        $data = $request->validate([
            'billing_address_id' => ['required', 'integer', 'exists:addresses,id'],
            'shipping_address_id' => ['required', 'integer', 'exists:addresses,id'],
            'discount_code' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $billingOwns = Address::query()
            ->whereKey((int) $data['billing_address_id'])
            ->where('user_id', $user->id)
            ->exists();
        abort_unless($billingOwns, 404);

        $shippingOwns = Address::query()
            ->whereKey((int) $data['shipping_address_id'])
            ->where('user_id', $user->id)
            ->exists();
        abort_unless($shippingOwns, 404);

        try {
            $order = DB::transaction(function () use ($user, $cart, $data) {
                $order = $cart->checkout(
                    $user->id,
                    (int) $data['billing_address_id'],
                    (int) $data['shipping_address_id'],
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

    public function previewDiscount(Request $request)
    {
        $cart = $this->resolveCart($request);

        $user = $request->user();
        abort_unless($user, 401);

        $data = $request->validate([
            'discount_code' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $preview = $cart->previewDiscount($user->id, $data['discount_code'] ?? null);

            return response()->json([
                'subtotal_amount' => (float) ($preview->subtotal_amount ?? 0),
                'discount_total' => (float) ($preview->discount_total ?? 0),
                'total_amount' => (float) ($preview->total_amount ?? 0),
                'applied_code' => $preview->applied_code,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage() ?: 'Could not validate discount code.',
            ], 422);
        }
    }

    protected function resolveCart(Request $request): ShoppingCart
    {
        $session = $request->session();
        $user = $request->user();

        if ($user) {
            // if a guest cart exists in session and the user has no cart yet, "claim" it
            $guestCartId = $session->pull('cart_id');
            
            if ($guestCartId) {
                $guestCart = ShoppingCart::query()
                    ->whereKey((int) $guestCartId)
                    ->whereNull('user_id')
                    ->first();

                if ($guestCart) {
                    $existing = ShoppingCart::query()->where('user_id', $user->id)->first();

                    if (!$existing) {
                        $guestCart->user_id = $user->id;
                        $guestCart->save();

                        return $guestCart;
                    }
                }
            }
            
            return ShoppingCart::firstOrCreate(['user_id' => $user->id]);
        }

        $cartId = $session->get('cart_id');

        if ($cartId) {
            $cart = ShoppingCart::query()
                ->whereKey((int) $cartId)
                ->whereNULL('user_id')
                ->find($cartId);

            if ($cart) {
                return $cart;
            }

            $session->forget('cart_id');
        }
        
        $cart = ShoppingCart::create(['user_id' => null]);
        $session->put('cart_id', $cart->id);

        return $cart;
    }
}

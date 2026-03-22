<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ShoppingCart extends Model
{
    use HasFactory;

    protected $table = 'shopping_carts';

    protected $fillable = [
        'user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): mixed
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): mixed
    {
        return $this->hasMany(CartItem::class, 'cart_id')->orderBy('id');
    }

    public function addItem(int $productId, ?int $variantId, int $quantity, ?string $unitPrice = null): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        $resolvedUnitPrice = $unitPrice ?? $this->resolveUnitPrice($productId, $variantId);

        DB::statement('select fn_add_to_cart (?, ?, ?, ?, ?)', [
            $this->id,
            $productId,
            $variantId,
            $quantity,
            $resolvedUnitPrice,
        ]);

        $this->refresh();
    }

    public function updateItemQuantity(int $cartItemId, int $quantity): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        DB::statement('select fn_update_cart_item_quantity(?, ?)', [
            $cartItemId,
            $quantity,
        ]);

        $this->refresh();
    }

    public function removeItem(int $productId, ?int $variantId): void
    {
        DB::statement('select fn_remove_from_cart(?, ?, ?)', [$this->id, $productId, $variantId]);
        $this->refresh();
    }

    public function checkout(
        ?int $userId,
        ?int $billingAddressId,
        ?int $shippingAddressId,
        ?string $discountCode = null
    ): Order {
        $row = DB::selectOne(
            'select fn_checkout_cart(?, ?, ?, ?, ?) as order_id',
            [$this->id, $userId, $billingAddressId, $shippingAddressId, $discountCode]
        );

        return Order::query()
            ->with(['items', 'shipments', 'discountRedemptions.discountCode'])
            ->findOrFail((int) $row->order_id);
    }

    public function previewDiscount(?int $userId, ?string $discountCode): object
    {
        return DB::selectOne(
            'select * from fn_preview_cart_discount(?, ?, ?)',
            [$this->id, $userId, $discountCode]
        );
    }

    protected function resolveUnitPrice(int $productId, ?int $variantId): string
    {
        if ($variantId) {
            return (string) DB::table('product_variants')
                ->where('id', $variantId)
                ->value('price');
        }

        return (string) DB::table('products')
            ->where('id', $productId)
            ->value('price');
    }
}

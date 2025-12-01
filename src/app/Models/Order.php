<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'status',
        'billing_address_id',
        'shipping_address_id',
        'notes',
        'placed_at',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'total_amount' => 'decimal:2',
        'subtotal_amount' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'placed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function billingAddress()
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id')->orderBy('id');
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class, 'order_id')->orderBy('id');
    }

    public function discountRedemptions()
    {
        return $this->hasMany(DiscountRedemption::class, 'order_id');
    }
}

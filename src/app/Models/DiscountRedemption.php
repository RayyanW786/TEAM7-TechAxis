<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountRedemption extends Model
{
    use HasFactory;

    protected $table = 'discount_redemptions';

    public $timestamps = false;

    protected $fillable = [
        'discount_code_id',
        'order_id',
        'user_id',
        'amount_applied',
    ];

    protected $casts = [
        'amount_applied' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function discountCode()
    {
        return $this->belongsTo(DiscountCode::class, 'discount_code_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountCode extends Model
{
    use HasFactory;

    protected $table = 'discount_codes';

    protected $fillable = [
        'code',
        'type',
        'amount',
        'max_uses',
        'max_uses_per_user',
        'min_order_total',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'type' => DiscountType::class,
        'amount' => 'decimal:2',
        'min_order_total' => 'decimal:2',
        'max_uses' => 'integer',
        'max_uses_per_user' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function redemptions()
    {
        return $this->hasMany(DiscountRedemption::class, 'discount_code_id');
    }
}

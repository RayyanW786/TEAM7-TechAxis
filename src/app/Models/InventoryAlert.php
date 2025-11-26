<?php

namespace App\Models;

use App\Enums\InventoryAlertType;
use App\Enums\InventoryItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryAlert extends Model
{
    use HasFactory;

    protected $table = 'inventory_alerts';

    public $timestamps = false;

    protected $fillable = [
        'item_type',
        'product_id',
        'variant_id',
        'previous_qty',
        'new_qty',
        'alert_type',
    ];

    protected $casts = [
        'item_type' => InventoryItemType::class,
        'alert_type' => InventoryAlertType::class,
        'previous_qty' => 'integer',
        'new_qty' => 'integer',
        'created_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductVariantOptionValue extends Pivot
{
    protected $table = 'product_variant_option_values';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'variant_id',
        'option_value_id',
    ];

    protected $casts = [
        'variant_id' => 'integer',
        'option_value_id' => 'integer',
    ];
}

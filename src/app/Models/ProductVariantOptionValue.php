<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariantOptionValue extends Model
{
    use HasFactory;

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

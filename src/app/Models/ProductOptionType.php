<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductOptionType extends Pivot
{

    protected $table = 'product_option_types';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'option_type_id',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'option_type_id' => 'integer',
    ];
}

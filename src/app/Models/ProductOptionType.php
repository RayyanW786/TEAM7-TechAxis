<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOptionType extends Model
{
    use HasFactory;

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

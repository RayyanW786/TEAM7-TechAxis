<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptionValue extends Model
{
    use HasFactory;

    protected $table = 'option_values';

    public $timestamps = false;

    protected $fillable = [
        'option_type_id',
        'value',
    ];

    public function optionType()
    {
        return $this->belongsTo(OptionType::class, 'option_type_id');
    }

    public function variants()
    {
        return $this->belongsToMany(ProductVariant::class, 'product_variant_option_values', 'option_value_id', 'variant_id');
    }
}

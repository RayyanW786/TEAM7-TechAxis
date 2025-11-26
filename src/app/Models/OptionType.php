<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptionType extends Model
{
    use HasFactory;

    protected $table = 'option_types';

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function values()
    {
        return $this->hasMany(OptionValue::class, 'option_type_id')->orderBy('value');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_option_types', 'option_type_id', 'product_id');
    }
}

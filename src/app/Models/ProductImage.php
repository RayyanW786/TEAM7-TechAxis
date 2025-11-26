<?php

namespace App\Models;

use App\Models\Traits\FormatsPgArrays;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductImage extends Model
{
    use HasFactory, FormatsPgArrays;

    protected $table = 'product_images';

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'url',
        'alt_text',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'created_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public static function setAsPrimary(int $productId, int $imageId): void
    {
        DB::statement('select fn_set_primary_product_image(?, ?)', [$productId, $imageId]);
    }

    public static function reorder(int $productId, array $imageIds): void
    {
        DB::statement('select fn_reorder_product_images(?, ?::bigint[])', [
            $productId,
            self::pgBigintArray($imageIds),
        ]);
    }
}

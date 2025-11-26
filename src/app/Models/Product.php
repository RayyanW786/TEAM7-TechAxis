<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'sku',
        'status',
        'summary',
        'description',
        'price',
        'stock_quantity',
        'has_variants',
        'low_stock_threshold',
    ];

    protected $casts = [
        'status' => ProductStatus::class,
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'has_variants' => 'boolean',
        'low_stock_threshold' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id')
            ->orderBy('price')
            ->orderBy('id');
    }

    public function optionTypes()
    {
        return $this->belongsToMany(OptionType::class, 'product_option_types', 'product_id', 'option_type_id');
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class, 'product_id')->orderByDesc('created_at');
    }

    public function scopeActive($query)
    {
        return $query->where('status', ProductStatus::Active);
    }

    public function lowestVariantPrice(): ?string
    {
        return $this->variants()->min('price');
    }

    public function effectivePrice(): string
    {
        if (!$this->has_variants) {
            return (string) $this->price;
        }

        return (string) ($this->lowestVariantPrice() ?? $this->price);
    }

    public static function searchUsingPgFn(
        string $queryText,
        ?string $categorySlugsPgArray = null,
        ?string $minPrice = null,
        ?string $maxPrice = null,
        int $limit = 20,
        int $offset = 0
    ): Collection {
        $rows = DB::select(
            'select * from fn_search_products(?, ?::text[], ?, ?, ?, ?)',
            [$queryText, $categorySlugsPgArray, $minPrice, $maxPrice, $limit, $offset]
        );

        return collect($rows);
    }

    public function imagesUsingPgFn(): Collection
    {
        return collect(DB::select('select * from fn_get_product_images(?)', [$this->id]));
    }
}

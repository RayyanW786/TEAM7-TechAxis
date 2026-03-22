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

    public function totalAvailableStock(): int
    {
        if (! $this->has_variants) {
            return (int) ($this->stock_quantity ?? 0);
        }

        if ($this->relationLoaded('variants')) {
            return (int) $this->variants->sum(fn ($variant) => max(0, (int) ($variant->stock_quantity ?? 0)));
        }

        return (int) $this->variants()->sum('stock_quantity');
    }

    public function stockState(): string
    {
        if (! $this->has_variants) {
            $stock = (int) ($this->stock_quantity ?? 0);
            $threshold = (int) ($this->low_stock_threshold ?? 0);

            if ($stock <= 0) {
                return 'out_of_stock';
            }

            if ($stock <= $threshold) {
                return 'low_stock';
            }

            return 'in_stock';
        }

        $variants = $this->relationLoaded('variants')
            ? $this->variants
            : $this->variants()->get(['stock_quantity', 'low_stock_threshold']);

        $totalStock = (int) $variants->sum(fn ($variant) => max(0, (int) ($variant->stock_quantity ?? 0)));

        if ($totalStock <= 0) {
            return 'out_of_stock';
        }

        $hasLowVariant = $variants->contains(function ($variant) {
            $stock = (int) ($variant->stock_quantity ?? 0);
            $threshold = (int) ($variant->low_stock_threshold ?? 0);

            return $stock > 0 && $stock <= $threshold;
        });

        return $hasLowVariant ? 'low_stock' : 'in_stock';
    }

    public function stockLabel(): string
    {
        return match ($this->stockState()) {
            'out_of_stock' => 'Out of stock',
            'low_stock' => 'Low stock',
            default => 'In stock',
        };
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

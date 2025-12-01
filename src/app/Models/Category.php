<?php

namespace App\Models;

use App\Models\Traits\FormatsPgArrays;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Category extends Model
{
    use HasFactory, FormatsPgArrays;

    protected $table = 'categories';

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public static function reorderSiblings(?int $parentId, array $orderedCategoryIds): void
    {
        DB::statement('select fn_reorder_category_siblings(?, ?::bigint[])', [
            $parentId,
            self::pgBigintArray($orderedCategoryIds),
        ]);
    }
}

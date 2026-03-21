<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends ApiController
{
    public function index(Request $request)
    {
        $searchQuery = trim((string) $request->query('q', ''));

        $categorySlugsParam = $request->query('category_slugs');
        $categorySlugs = is_array($categorySlugsParam)
            ? $categorySlugsParam
            : (is_string($categorySlugsParam) && $categorySlugsParam !== '' ? [$categorySlugsParam] : null);

        $minPrice = $request->filled('min_price') ? (string) $request->query('min_price') : null;
        $maxPrice = $request->filled('max_price') ? (string) $request->query('max_price') : null;

        $limit = max(1, min(100, (int) $request->query('limit', 20)));
        $offset = max(0, (int) $request->query('offset', 0));

        $usingPgSearch = $searchQuery !== ''
            || ($categorySlugs && count($categorySlugs) > 0)
            || $minPrice !== null
            || $maxPrice !== null;

        if ($usingPgSearch) {
            $rows = DB::select(
                'select * from fn_search_products(?, ?::text[], ?, ?, ?, ?)',
                [
                    $searchQuery === '' ? null : $searchQuery,
                    $this->pgTextArray($categorySlugs),
                    $minPrice,
                    $maxPrice,
                    $limit,
                    $offset,
                ]
            );

            return response()->json([
                'query' => $searchQuery,
                'filters' => [
                    'category_slugs' => $categorySlugs,
                    'min_price' => $minPrice,
                    'max_price' => $maxPrice,
                ],
                'items' => $rows,
                'limit' => $limit,
                'offset' => $offset,
            ]);
        }

        $user = $request->user();

        $products = Product::query()
            ->with(['category', 'brand'])
            ->when(!$user || $user->role !== UserRole::Admin, fn($qb) => $qb->where('status', ProductStatus::Active))
            ->when($request->filled('status') && $user && $user->role === UserRole::Admin, fn($qb) => $qb->where('status', $request->query('status')))
            ->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', 20));

        return response()->json($products);
    }

    public function featured(Request $request)
    {
        $limit = max(1, min(12, (int) $request->query('limit', 4)));

        $products = Product::query()
            ->active()
            ->with(['images' => fn($q) => $q->where('sort_order', 0)])
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        return response()->json([
            'items' => $products->map(fn(Product $product) => [
                'id' => $product->id,
                'slug' => $product->slug,
                'name' => $product->name,
                'summary' => $product->summary,
                'effective_price' => $product->effectivePrice(),
                'primary_image_url' => optional($product->images->first())->url,
                'primary_image_alt' => optional($product->images->first())->alt_text ?? $product->name,
            ])->values(),
            'limit' => $limit,
        ]);
    }


    public function show(Product $product)
    {
        $product->load(['category', 'brand', 'images', 'variants', 'optionTypes.values'])
            ->loadCount('reviews')
            ->loadAvg('reviews', 'rating');

        return response()->json([
            'product' => $product,
            'effective_price' => $product->effectivePrice(),
        ]);
    }

    public function store(Request $request)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'sku' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_map(fn($c) => $c->value, ProductStatus::cases()))],
            'summary' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'has_variants' => ['nullable', 'boolean'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
        ]);

        $product = Product::create($data);

        return response()->json($product->fresh(), 201);
    }

    public function update(Request $request, Product $product)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'sku' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_map(fn($c) => $c->value, ProductStatus::cases()))],
            'summary' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'has_variants' => ['nullable', 'boolean'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
        ]);

        $product->fill($data)->save();

        return response()->json($product->fresh(['category', 'brand']));
    }

    public function destroy(Request $request, Product $product)
    {
        $this->requireAdmin($request);

        $product->delete();

        return response()->json(['ok' => true]);
    }

    public function upsertVariant(Request $request, Product $product)
    {
        $this->requireAdmin($request);
        
        $variantId = $request->input('variant_id');

        $variant = null;

        if ($variantId) {
            $variant = ProductVariant::query()
                ->where('id', $variantId)
                ->where('product_id', $product->id)
                ->firstOrFail();
        }

        $skuUniqueRule = Rule::unique('product_variants', 'sku');
        if ($variant) {
            $skuUniqueRule = $skuUniqueRule->ignore($variant->id);
        }

        $data = $request->validate([
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'sku' => ['required', 'string', 'max:255', $skuUniqueRule],
            'title' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
        ]);

        $created = false;

        if (! $variant) {
            $variant = new ProductVariant();
            $variant->product_id = $product->id;
            $created = true;
        }

        $variant->fill([
            'sku' => $data['sku'],
            'title' => $data['title'] ?? null,
            'price' => $data['price'],
            'stock_quantity' => $data['stock_quantity'] ?? 0,
            'low_stock_threshold' => $data['low_stock_threshold'] ?? 5,
        ])->save();
        
        if (! $product->has_variants) {
            $product->update(['has_variants' => true]);
        }

        return response()->json($variant->fresh(), $created ? 201 : 200);
    }

    public function destroyVariant(Request $request, Product $product, int $variantId)
    {
        $this->requireAdmin($request);

        $variant = ProductVariant::query()
            ->where('id', $variantId)
            ->where('product_id', $product->id)
            ->firstOrFail();

        $variant->delete();
        
        if (! ProductVariant::query()->where('product_id', $product->id)->exists()) {
            $product->update(['has_variants' => false]);
        }

        return response()->json(['ok' => true]);
    }
}

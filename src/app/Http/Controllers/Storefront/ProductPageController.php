<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

class ProductPageController extends Controller
{
    public function index(Request $request)
    {
        $searchQuery = trim((string) $request->query('q', ''));
        $selectedCategorySlug = trim((string) $request->query('category', ''));
        $priceMode = $request->query('price_mode') === 'slider' ? 'slider' : 'values';

        $minPrice = $request->filled('min_price') ? (string) $request->query('min_price') : null;
        $maxPrice = $request->filled('max_price') ? (string) $request->query('max_price') : null;

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
        $priceBounds = $this->priceBoundsForCategory($selectedCategorySlug !== '' ? $selectedCategorySlug : null);

        $perPage = 12;

        $usingPgSearch = $searchQuery !== ''
            || $selectedCategorySlug !== ''
            || $minPrice !== null
            || $maxPrice !== null;

        // No filters/search -> Keep fast normal pagination.
        if (! $usingPgSearch) {
            $products = Product::query()
                ->where('status', ProductStatus::Active)
                ->with([
                    'images' => fn ($qb) => $qb->orderBy('sort_order')->orderBy('id'),
                    'variants:id,product_id,stock_quantity,low_stock_threshold',
                ])
                ->orderByDesc('created_at')
                ->paginate($perPage)
                ->withQueryString();

            return view('storefront.products.index', [
                'products' => $products,
                'categories' => $categories,
                'searchQuery' => $searchQuery,
                'selectedCategorySlug' => $selectedCategorySlug,
                'minPrice' => $minPrice,
                'maxPrice' => $maxPrice,
                'priceMode' => $priceMode,
                'priceBounds' => $priceBounds,
            ]);
        }

        // PG function pagination
        $currentPage = max(1, (int) $request->query('page', 1));
        $offset = ($currentPage - 1) * $perPage;
        $limit = $perPage + 1;

        $categorySlugs = $selectedCategorySlug !== '' ? [$selectedCategorySlug] : null;

        $rows = collect(DB::select(
            'select * from fn_search_products(?, ?::text[], ?, ?, ?, ?)',
            [
                $searchQuery === '' ? null : $searchQuery,
                $this->pgTextArray($categorySlugs),
                $minPrice,
                $maxPrice,
                $limit,
                $offset,
            ]
        ));

        $productIds = $rows->pluck('product_id')->map(fn ($id) => (int) $id)->all();

        $productsById = Product::query()
            ->whereIn('id', $productIds)
            ->where('status', ProductStatus::Active)
            ->with([
                'images' => fn ($qb) => $qb->orderBy('sort_order')->orderBy('id'),
                'variants:id,product_id,stock_quantity,low_stock_threshold',
            ])
            ->get()
            ->keyBy('id');

        $orderedProducts = [];
        foreach ($productIds as $productId) {
            $product = $productsById->get($productId);
            if (! $product) {
                continue;
            }

            $row = $rows->firstWhere('product_id', $productId);
            $product->listing_price = $row->product_price ?? null;

            $orderedProducts[] = $product;
        }

        $products = new Paginator($orderedProducts, $perPage, $currentPage, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
        $products->withQueryString();

        return view('storefront.products.index', [
            'products' => $products,
            'categories' => $categories,
            'searchQuery' => $searchQuery,
            'selectedCategorySlug' => $selectedCategorySlug,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'priceMode' => $priceMode,
            'priceBounds' => $priceBounds,
        ]);
    }

    public function show(Product $product)
    {
        abort_unless($product->status === ProductStatus::Active, 404);

        $product->load([
            'images' => fn ($qb) => $qb->orderBy('sort_order')->orderBy('id'),
            'variants' => fn ($qb) => $qb->orderBy('price')->orderBy('id'),
        ]);

        return view('storefront.products.show', [
            'product' => $product,
            'effectivePrice' => $product->effectivePrice(),
        ]);
    }

    private function pgTextArray(?array $values): ?string
    {
        if (! $values || count($values) === 0) {
            return null;
        }

        $unique = [];
        foreach ($values as $value) {
            $slug = strtolower(trim((string) $value));
            if ($slug !== '') $unique[$slug] = true;
        }

        return '{' . implode(',', array_keys($unique)) . '}';
    }

    private function priceBoundsForCategory(?string $categorySlug): array
    {
        $status = ProductStatus::Active->value;
        $categorySql = $categorySlug !== null ? ' and c.slug = ?' : '';
        $params = [$status];

        if ($categorySlug !== null) {
            $params[] = $categorySlug;
        }

        $params[] = $status;

        if ($categorySlug !== null) {
            $params[] = $categorySlug;
        }

        $sql = <<<SQL
select
  coalesce(min(price_value), 0) as min_price,
  coalesce(max(price_value), 1000) as max_price
from (
  select p.price as price_value
  from products p
  join categories c on c.id = p.category_id
  where p.status = ?
    and p.has_variants = false
    {$categorySql}

  union all

  select v.price as price_value
  from product_variants v
  join products p on p.id = v.product_id
  join categories c on c.id = p.category_id
  where p.status = ?
    and p.has_variants = true
    {$categorySql}
) priced
SQL;

        $row = DB::selectOne($sql, $params);

        $min = isset($row->min_price) ? (float) $row->min_price : 0.0;
        $max = isset($row->max_price) ? (float) $row->max_price : 1000.0;

        if ($max < $min) {
            $max = $min;
        }

        return [
            'min' => floor($min),
            'max' => ceil($max),
        ];
    }
}

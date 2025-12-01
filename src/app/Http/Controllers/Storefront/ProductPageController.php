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

        $minPrice = $request->filled('min_price') ? (string) $request->query('min_price') : null;
        $maxPrice = $request->filled('max_price') ? (string) $request->query('max_price') : null;

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $perPage = 12;

        $usingPgSearch = $searchQuery !== ''
            || $selectedCategorySlug !== ''
            || $minPrice !== null
            || $maxPrice !== null;

        // No filters/search -> Keep fast normal pagination.
        if (! $usingPgSearch) {
            $products = Product::query()
                ->where('status', ProductStatus::Active)
                ->with(['images' => fn ($qb) => $qb->orderBy('sort_order')->orderBy('id')])
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
            ->with(['images' => fn ($qb) => $qb->orderBy('sort_order')->orderBy('id')])
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
}

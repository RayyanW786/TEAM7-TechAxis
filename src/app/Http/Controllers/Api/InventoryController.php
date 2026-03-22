<?php

namespace App\Http\Controllers\Api;

use App\Models\InventoryAlert;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends ApiController
{
    public function summary(Request $request)
    {
        $this->requireAdmin($request);

        $totals = DB::selectOne(
            <<<SQL
            select
              count(*) filter (where p.status = 'active') as active_products,
              count(*) filter (
                where p.status = 'active'
                  and (
                    (p.has_variants = false and p.stock_quantity <= 0)
                    or
                    (p.has_variants = true and coalesce(v.variant_stock_total, 0) <= 0)
                  )
              ) as out_of_stock_count,
              count(*) filter (
                where p.status = 'active'
                  and (
                    (p.has_variants = false and p.stock_quantity > 0 and p.stock_quantity <= p.low_stock_threshold)
                    or
                    (p.has_variants = true and coalesce(v.low_variant_count, 0) > 0 and coalesce(v.variant_stock_total, 0) > 0)
                  )
              ) as low_stock_count
            from products p
            left join (
              select
                product_id,
                sum(stock_quantity) as variant_stock_total,
                count(*) filter (where stock_quantity > 0 and stock_quantity <= low_stock_threshold) as low_variant_count
              from product_variants
              group by product_id
            ) v on v.product_id = p.id
            SQL
        );

        $recentTransactions = InventoryTransaction::query()->count();
        $recentAlerts = InventoryAlert::query()->count();

        return response()->json([
            'active_products' => (int) ($totals->active_products ?? 0),
            'out_of_stock_count' => (int) ($totals->out_of_stock_count ?? 0),
            'low_stock_count' => (int) ($totals->low_stock_count ?? 0),
            'transaction_count' => $recentTransactions,
            'alert_count' => $recentAlerts,
        ]);
    }

    public function stockIndex(Request $request)
    {
        $this->requireAdmin($request);

        $q = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->with(['brand:id,name', 'category:id,name', 'variants:id,product_id,title,sku,stock_quantity,low_stock_threshold'])
            ->when($q !== '', function ($qb) use ($q) {
                $like = '%' . $q . '%';
                $qb->where(function ($inner) use ($like) {
                    $inner
                        ->where('name', 'ilike', $like)
                        ->orWhere('slug', 'ilike', $like)
                        ->orWhere('sku', 'ilike', $like);
                });
            })
            ->orderBy('name')
            ->paginate((int) $request->query('per_page', 20));

        $products->getCollection()->transform(function (Product $product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'brand' => $product->brand?->name,
                'category' => $product->category?->name,
                'has_variants' => (bool) $product->has_variants,
                'stock_label' => $product->stockLabel(),
                'stock_state' => $product->stockState(),
                'total_stock' => $product->totalAvailableStock(),
                'low_stock_threshold' => (int) $product->low_stock_threshold,
                'variants' => $product->variants->map(fn ($variant) => [
                    'id' => $variant->id,
                    'title' => $variant->title,
                    'sku' => $variant->sku,
                    'stock_quantity' => (int) $variant->stock_quantity,
                    'low_stock_threshold' => (int) $variant->low_stock_threshold,
                ])->values(),
            ];
        });

        return response()->json($products);
    }

    public function restockPriorities(Request $request)
    {
        $this->requireAdmin($request);

        $rows = DB::select(
            <<<SQL
            select
              p.id,
              p.name,
              p.slug,
              p.has_variants,
              coalesce(case when p.has_variants then v.variant_stock_total else p.stock_quantity end, 0) as current_stock,
              coalesce(oi.recent_quantity, 0) as recent_quantity,
              case
                when (case when p.has_variants then coalesce(v.variant_stock_total, 0) else p.stock_quantity end) <= 0 then 2
                when (
                  (p.has_variants = false and p.stock_quantity <= p.low_stock_threshold)
                  or
                  (p.has_variants = true and coalesce(v.low_variant_count, 0) > 0)
                ) then 1
                else 0
              end as urgency_rank
            from products p
            left join (
              select
                product_id,
                sum(stock_quantity) as variant_stock_total,
                count(*) filter (where stock_quantity > 0 and stock_quantity <= low_stock_threshold) as low_variant_count
              from product_variants
              group by product_id
            ) v on v.product_id = p.id
            left join (
              select
                product_id,
                sum(quantity) as recent_quantity
              from order_items
              where created_at >= now() - interval '30 days'
              group by product_id
            ) oi on oi.product_id = p.id
            where p.status = 'active'
            order by urgency_rank desc, recent_quantity desc, p.name asc
            limit 12
            SQL
        );

        return response()->json(['items' => $rows]);
    }

    public function alerts(Request $request)
    {
        $this->requireAdmin($request);

        $alerts = InventoryAlert::query()
            ->with(['product:id,name,slug,has_variants,stock_quantity', 'variant:id,product_id,sku,title,stock_quantity'])
            ->where(function ($qb) {
                $qb->whereNotNull('variant_id')
                    ->orWhereHas('product', fn ($product) => $product->where('has_variants', false));
            })
            ->when($request->filled('alert_type'), fn ($qb) => $qb->where('alert_type', $request->query('alert_type')))
            ->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', 30));

        $alerts->getCollection()->transform(function (InventoryAlert $alert) {
            $product = $alert->product;
            $variant = $alert->variant;

            $currentQty = $variant
                ? (int) ($variant->stock_quantity ?? 0)
                : ($product?->has_variants ? $product->totalAvailableStock() : (int) ($product->stock_quantity ?? 0));

            return [
                'id' => $alert->id,
                'item_type' => $alert->item_type?->value,
                'previous_qty' => (int) ($alert->previous_qty ?? 0),
                'new_qty' => (int) ($alert->new_qty ?? 0),
                'current_qty' => $currentQty,
                'alert_type' => $alert->alert_type?->value,
                'created_at' => optional($alert->created_at)?->toIso8601String(),
                'product' => $product ? [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'has_variants' => (bool) $product->has_variants,
                ] : null,
                'variant' => $variant ? [
                    'id' => $variant->id,
                    'title' => $variant->title,
                    'sku' => $variant->sku,
                    'stock_quantity' => (int) ($variant->stock_quantity ?? 0),
                ] : null,
            ];
        });

        return response()->json($alerts);
    }

    public function transactions(Request $request)
    {
        $this->requireAdmin($request);

        $transactions = InventoryTransaction::query()
            ->with(['product:id,name,slug', 'variant:id,sku,title', 'order:id,status'])
            ->when($request->filled('reason'), fn ($qb) => $qb->where('reason', $request->query('reason')))
            ->when($request->filled('product_id'), fn ($qb) => $qb->where('product_id', (int) $request->query('product_id')))
            ->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', 30));

        return response()->json($transactions);
    }

    public function storeTransaction(Request $request)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity_change' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'max:255'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
        ]);

        $tx = DB::transaction(function () use ($data) {
            $quantityChange = (int) $data['quantity_change'];

            if (!empty($data['variant_id'])) {
                $variant = ProductVariant::query()
                    ->whereKey((int) $data['variant_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $variant->product_id !== (int) $data['product_id']) {
                    abort(422, 'The selected variant does not belong to the selected product.');
                }

                $newStock = (int) $variant->stock_quantity + $quantityChange;
                if ($newStock < 0) {
                    abort(422, 'This adjustment would make the variant stock negative.');
                }

                $variant->update([
                    'stock_quantity' => $newStock,
                ]);
            } else {
                $product = Product::query()
                    ->whereKey((int) $data['product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $newStock = (int) $product->stock_quantity + $quantityChange;
                if ($newStock < 0) {
                    abort(422, 'This adjustment would make the product stock negative.');
                }

                $product->update([
                    'stock_quantity' => $newStock,
                ]);
            }

            return InventoryTransaction::create($data);
        });

        return response()->json($tx->fresh(['product:id,name,slug', 'variant:id,sku,title', 'order:id,status']), 201);
    }
}

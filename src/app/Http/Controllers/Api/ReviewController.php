<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ServiceReview;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReviewController extends ApiController
{
    public function productIndex(Request $request, Product $product)
    {
        $sort = $this->normalizeReviewSort((string) $request->query('sort', 'newest'));
        $perPage = max(1, min(12, (int) $request->query('per_page', 6)));
        $user = $request->user();

        $query = ProductReview::query()
            ->where('product_id', $product->id)
            ->with('user:id,name');

        $this->applyReviewSort($query, $sort);

        $reviews = $query->paginate($perPage);
        $currentUserReview = $user
            ? ProductReview::query()
                ->where('product_id', $product->id)
                ->where('user_id', $user->id)
                ->with('user:id,name')
                ->first()
            : null;

        return response()->json([
            'summary' => $this->productSummary($product->id),
            'sort' => $sort,
            'current_user_review' => $currentUserReview ? $this->serializeProductReview($currentUserReview) : null,
            'eligible_order_items' => $user ? $this->eligibleOrderItems($user->id, $product->id) : [],
            'reviews' => $this->paginatorPayload(
                $reviews,
                fn (ProductReview $review) => $this->serializeProductReview($review)
            ),
        ]);
    }

    public function serviceIndex(Request $request)
    {
        $perPage = max(1, min(12, (int) $request->query('per_page', 6)));
        $user = $request->user();

        $reviews = ServiceReview::query()
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $currentUserReview = $user
            ? ServiceReview::query()
                ->where('user_id', $user->id)
                ->with('user:id,name')
                ->first()
            : null;

        return response()->json([
            'summary' => $this->serviceSummary(),
            'current_user_review' => $currentUserReview ? $this->serializeServiceReview($currentUserReview) : null,
            'reviews' => $this->paginatorPayload(
                $reviews,
                fn (ServiceReview $review) => $this->serializeServiceReview($review)
            ),
        ]);
    }

    public function upsertProduct(Request $request, Product $product)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:5000'],
            'order_item_id' => ['required', 'integer', 'exists:order_items,id'],
        ]);

        $eligibleOrderItem = OrderItem::query()
            ->where('id', $data['order_item_id'])
            ->where('product_id', $product->id)
            ->whereHas('order', function (Builder $query) use ($user) {
                $query
                    ->where('user_id', $user->id)
                    ->where('status', OrderStatus::Completed->value);
            })
            ->first();

        abort_unless($eligibleOrderItem, 422, 'Only completed purchases can be reviewed for this product.');

        $review = ProductReview::query()->updateOrCreate(
            ['product_id' => $product->id, 'user_id' => $user->id],
            [
                'order_item_id' => $eligibleOrderItem->id,
                'rating' => $data['rating'],
                'title' => $data['title'] ?? null,
                'body' => $data['body'] ?? null,
            ]
        );

        $review->load('user:id,name');

        return response()->json([
            'review' => $this->serializeProductReview($review),
            'summary' => $this->productSummary($product->id),
            'eligible_order_items' => $this->eligibleOrderItems($user->id, $product->id),
        ], 201);
    }

    public function upsertService(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        $review = ServiceReview::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['rating' => $data['rating'], 'comment' => $data['comment'] ?? null]
        );

        $review->load('user:id,name');

        return response()->json([
            'review' => $this->serializeServiceReview($review),
            'summary' => $this->serviceSummary(),
        ], 201);
    }

    public function adminProductIndex(Request $request)
    {
        $this->requireAdmin($request);

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'rating' => $request->filled('rating') ? (int) $request->query('rating') : null,
            'verified' => trim((string) $request->query('verified', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
        ];

        $query = ProductReview::query()
            ->with(['user:id,name', 'product:id,name,slug']);

        $this->applyAdminProductFilters($query, $filters);

        $summary = (clone $query)
            ->selectRaw('COALESCE(ROUND(AVG(rating)::numeric, 2), 0.00) AS average_rating')
            ->selectRaw('COUNT(*) AS review_count')
            ->selectRaw('COUNT(order_item_id) AS verified_review_count')
            ->first();

        $reviews = $query
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'filters' => $filters,
            'summary' => [
                'average_rating' => isset($summary->average_rating) ? (float) $summary->average_rating : 0.0,
                'review_count' => isset($summary->review_count) ? (int) $summary->review_count : 0,
                'verified_review_count' => isset($summary->verified_review_count) ? (int) $summary->verified_review_count : 0,
            ],
            'reviews' => $this->paginatorPayload(
                $reviews,
                fn (ProductReview $review) => $this->serializeAdminProductReview($review)
            ),
        ]);
    }

    public function adminServiceIndex(Request $request)
    {
        $this->requireAdmin($request);

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'rating' => $request->filled('rating') ? (int) $request->query('rating') : null,
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
        ];

        $query = ServiceReview::query()
            ->with('user:id,name');

        $this->applyAdminServiceFilters($query, $filters);

        $summary = (clone $query)
            ->selectRaw('COALESCE(ROUND(AVG(rating)::numeric, 2), 0.00) AS average_rating')
            ->selectRaw('COUNT(*) AS review_count')
            ->first();

        $reviews = $query
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'filters' => $filters,
            'summary' => [
                'average_rating' => isset($summary->average_rating) ? (float) $summary->average_rating : 0.0,
                'review_count' => isset($summary->review_count) ? (int) $summary->review_count : 0,
            ],
            'reviews' => $this->paginatorPayload(
                $reviews,
                fn (ServiceReview $review) => $this->serializeAdminServiceReview($review)
            ),
        ]);
    }

    private function normalizeReviewSort(string $sort): string
    {
        return in_array($sort, ['newest', 'highest', 'lowest'], true) ? $sort : 'newest';
    }

    private function applyReviewSort(Builder $query, string $sort): void
    {
        match ($sort) {
            'highest' => $query->orderByDesc('rating')->orderByDesc('created_at'),
            'lowest' => $query->orderBy('rating')->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at'),
        };
    }

    private function applyAdminProductFilters(Builder $query, array $filters): void
    {
        if ($filters['q'] !== '') {
            $term = '%' . $filters['q'] . '%';
            $query->where(function (Builder $nested) use ($term) {
                $nested
                    ->where('title', 'ilike', $term)
                    ->orWhere('body', 'ilike', $term)
                    ->orWhereHas('product', fn (Builder $productQuery) => $productQuery->where('name', 'ilike', $term))
                    ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'ilike', $term));
            });
        }

        if ($filters['rating']) {
            $query->where('rating', $filters['rating']);
        }

        if ($filters['verified'] === 'verified') {
            $query->whereNotNull('order_item_id');
        } elseif ($filters['verified'] === 'unverified') {
            $query->whereNull('order_item_id');
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
    }

    private function applyAdminServiceFilters(Builder $query, array $filters): void
    {
        if ($filters['q'] !== '') {
            $term = '%' . $filters['q'] . '%';
            $query->where(function (Builder $nested) use ($term) {
                $nested
                    ->where('comment', 'ilike', $term)
                    ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'ilike', $term));
            });
        }

        if ($filters['rating']) {
            $query->where('rating', $filters['rating']);
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
    }

    private function productSummary(int $productId): array
    {
        try {
            $row = DB::selectOne('select * from fn_get_product_review_summary(?)', [$productId]);
        } catch (\Throwable $exception) {
            $row = DB::selectOne(
                'select
                    COALESCE(ROUND(AVG(rating)::numeric, 2), 0.00) AS average_rating,
                    COUNT(*) AS review_count,
                    COUNT(order_item_id) AS verified_review_count,
                    COUNT(*) FILTER (WHERE rating = 5) AS five_star_count,
                    COUNT(*) FILTER (WHERE rating = 4) AS four_star_count,
                    COUNT(*) FILTER (WHERE rating = 3) AS three_star_count,
                    COUNT(*) FILTER (WHERE rating = 2) AS two_star_count,
                    COUNT(*) FILTER (WHERE rating = 1) AS one_star_count
                 from product_reviews
                 where product_id = ?',
                [$productId]
            );
        }

        return [
            'average_rating' => isset($row->average_rating) ? (float) $row->average_rating : 0.0,
            'review_count' => isset($row->review_count) ? (int) $row->review_count : 0,
            'verified_review_count' => isset($row->verified_review_count) ? (int) $row->verified_review_count : 0,
            'star_breakdown' => [
                5 => isset($row->five_star_count) ? (int) $row->five_star_count : 0,
                4 => isset($row->four_star_count) ? (int) $row->four_star_count : 0,
                3 => isset($row->three_star_count) ? (int) $row->three_star_count : 0,
                2 => isset($row->two_star_count) ? (int) $row->two_star_count : 0,
                1 => isset($row->one_star_count) ? (int) $row->one_star_count : 0,
            ],
        ];
    }

    private function serviceSummary(): array
    {
        try {
            $row = DB::selectOne('select * from fn_get_service_review_summary()');
        } catch (\Throwable $exception) {
            $row = DB::selectOne(
                'select
                    COALESCE(ROUND(AVG(rating)::numeric, 2), 0.00) AS average_rating,
                    COUNT(*) AS review_count,
                    COUNT(*) FILTER (WHERE rating = 5) AS five_star_count,
                    COUNT(*) FILTER (WHERE rating = 4) AS four_star_count,
                    COUNT(*) FILTER (WHERE rating = 3) AS three_star_count,
                    COUNT(*) FILTER (WHERE rating = 2) AS two_star_count,
                    COUNT(*) FILTER (WHERE rating = 1) AS one_star_count
                 from service_reviews'
            );
        }

        return [
            'average_rating' => isset($row->average_rating) ? (float) $row->average_rating : 0.0,
            'review_count' => isset($row->review_count) ? (int) $row->review_count : 0,
            'star_breakdown' => [
                5 => isset($row->five_star_count) ? (int) $row->five_star_count : 0,
                4 => isset($row->four_star_count) ? (int) $row->four_star_count : 0,
                3 => isset($row->three_star_count) ? (int) $row->three_star_count : 0,
                2 => isset($row->two_star_count) ? (int) $row->two_star_count : 0,
                1 => isset($row->one_star_count) ? (int) $row->one_star_count : 0,
            ],
        ];
    }

    private function eligibleOrderItems(int $userId, int $productId): array
    {
        try {
            $rows = DB::select(
                'select * from fn_get_review_eligible_order_items(?, ?)',
                [$userId, $productId]
            );
        } catch (\Throwable $exception) {
            $rows = DB::select(
                'select
                    oi.id as order_item_id,
                    oi.order_id,
                    oi.variant_id,
                    pv.title as variant_title,
                    oi.quantity,
                    oi.unit_price,
                    COALESCE(o.updated_at, o.created_at) as order_completed_at
                 from order_items oi
                 join orders o on o.id = oi.order_id
                 left join product_variants pv on pv.id = oi.variant_id
                 where o.user_id = ?
                   and oi.product_id = ?
                   and o.status = ?
                 order by COALESCE(o.updated_at, o.created_at) desc, oi.id desc',
                [$userId, $productId, OrderStatus::Completed->value]
            );
        }

        return collect($rows)
            ->map(function ($row) {
                $completedAt = isset($row->order_completed_at) ? Carbon::parse($row->order_completed_at) : null;
                $variantTitle = trim((string) ($row->variant_title ?? ''));
                $label = 'Order #' . (int) $row->order_id;

                if ($variantTitle !== '') {
                    $label .= ' - ' . $variantTitle;
                }

                if ($completedAt) {
                    $label .= ' - completed ' . $completedAt->format('d M Y');
                }

                return [
                    'order_item_id' => (int) $row->order_item_id,
                    'order_id' => (int) $row->order_id,
                    'variant_id' => $row->variant_id ? (int) $row->variant_id : null,
                    'variant_title' => $variantTitle !== '' ? $variantTitle : null,
                    'quantity' => (int) $row->quantity,
                    'unit_price' => isset($row->unit_price) ? (float) $row->unit_price : 0.0,
                    'order_completed_at' => $completedAt?->toIso8601String(),
                    'label' => $label,
                ];
            })
            ->values()
            ->all();
    }

    private function paginatorPayload($paginator, callable $transform): array
    {
        return [
            'items' => $paginator->getCollection()->map($transform)->values()->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    private function serializeProductReview(ProductReview $review): array
    {
        return [
            'id' => (int) $review->id,
            'rating' => (int) $review->rating,
            'title' => $review->title,
            'body' => $review->body,
            'order_item_id' => $review->order_item_id ? (int) $review->order_item_id : null,
            'verified_purchase' => ! is_null($review->order_item_id),
            'user' => [
                'id' => $review->user?->id ? (int) $review->user->id : null,
                'name' => $review->user?->name ?? 'Anonymous customer',
            ],
            'created_at' => $review->created_at?->toIso8601String(),
            'created_at_label' => $review->created_at?->format('d M Y'),
            'updated_at' => $review->updated_at?->toIso8601String(),
        ];
    }

    private function serializeServiceReview(ServiceReview $review): array
    {
        return [
            'id' => (int) $review->id,
            'rating' => (int) $review->rating,
            'comment' => $review->comment,
            'user' => [
                'id' => $review->user?->id ? (int) $review->user->id : null,
                'name' => $review->user?->name ?? 'Anonymous customer',
            ],
            'created_at' => $review->created_at?->toIso8601String(),
            'created_at_label' => $review->created_at?->format('d M Y'),
        ];
    }

    private function serializeAdminProductReview(ProductReview $review): array
    {
        return [
            'id' => (int) $review->id,
            'product' => [
                'id' => $review->product?->id ? (int) $review->product->id : null,
                'name' => $review->product?->name ?? 'Unknown product',
                'slug' => $review->product?->slug,
            ],
            'user' => [
                'id' => $review->user?->id ? (int) $review->user->id : null,
                'name' => $review->user?->name ?? 'Unknown user',
            ],
            'rating' => (int) $review->rating,
            'title' => $review->title,
            'body' => $review->body,
            'verified_purchase' => ! is_null($review->order_item_id),
            'created_at' => $review->created_at?->toIso8601String(),
            'created_at_label' => $review->created_at?->format('d M Y H:i'),
        ];
    }

    private function serializeAdminServiceReview(ServiceReview $review): array
    {
        return [
            'id' => (int) $review->id,
            'user' => [
                'id' => $review->user?->id ? (int) $review->user->id : null,
                'name' => $review->user?->name ?? 'Unknown user',
            ],
            'rating' => (int) $review->rating,
            'comment' => $review->comment,
            'created_at' => $review->created_at?->toIso8601String(),
            'created_at_label' => $review->created_at?->format('d M Y H:i'),
        ];
    }
}

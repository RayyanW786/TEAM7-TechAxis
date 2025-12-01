<?php

namespace App\Http\Controllers\Api;

use App\Enums\DiscountType;
use App\Models\DiscountCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DiscountCodeController extends ApiController
{
    public function index(Request $request)
    {
        $this->requireAdmin($request);

        $q = trim((string) $request->query('q', ''));

        $codes = DiscountCode::query()
            ->when($q !== '', fn($qb) => $qb->where('code', 'ilike', '%' . $q . '%'))
            ->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', 20));

        return response()->json($codes);
    }

    public function show(Request $request, DiscountCode $discountCode)
    {
        $this->requireAdmin($request);

        return response()->json($discountCode);
    }

    public function store(Request $request)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_map(fn($c) => $c->value, DiscountType::cases()))],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'max_uses_per_user' => ['nullable', 'integer', 'min:1'],
            'min_order_total' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return response()->json(DiscountCode::create($data), 201);
    }

    public function update(Request $request, DiscountCode $discountCode)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'code' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', Rule::in(array_map(fn($c) => $c->value, DiscountType::cases()))],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'max_uses_per_user' => ['nullable', 'integer', 'min:1'],
            'min_order_total' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $discountCode->fill($data)->save();

        return response()->json($discountCode->fresh());
    }

    public function destroy(Request $request, DiscountCode $discountCode)
    {
        $this->requireAdmin($request);

        $discountCode->delete();

        return response()->json(['ok' => true]);
    }
}

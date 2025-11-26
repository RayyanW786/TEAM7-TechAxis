<?php

namespace App\Http\Controllers\Api;

use App\Models\InventoryAlert;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;

class InventoryController extends ApiController
{
    public function alerts(Request $request)
    {
        $this->requireAdmin($request);

        $alerts = InventoryAlert::query()
            ->with(['product:id,name,slug', 'variant:id,sku,title'])
            ->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', 30));

        return response()->json($alerts);
    }

    public function transactions(Request $request)
    {
        $this->requireAdmin($request);

        $transactions = InventoryTransaction::query()
            ->with(['product:id,name,slug', 'variant:id,sku,title', 'order:id,status'])
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

        $tx = InventoryTransaction::create($data);

        return response()->json($tx, 201);
    }
}

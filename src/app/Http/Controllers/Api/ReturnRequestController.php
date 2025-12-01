<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReturnRequestController extends ApiController
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $returns = ReturnRequest::query()
            ->when($user->role !== UserRole::Admin, fn($qb) => $qb->where('user_id', $user->id))
            ->orderByDesc('requested_at')
            ->paginate((int) $request->query('per_page', 20));

        return response()->json($returns);
    }

    public function requestReturn(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = $request->validate([
            'order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string'],
        ]);

        $row = DB::selectOne(
            'select fn_request_return(?, ?, ?, ?) as return_id',
            [
                (int) $data['order_item_id'],
                (int) $user->id,
                (int) $data['quantity'],
                $data['reason'] ?? null,
            ]
        );

        return response()->json([
            'return_id' => (int) $row->return_id,
        ], 201);
    }

    public function process(Request $request, int $returnId)
    {
        $user = $request->user();
        abort_unless($user, 401);
        abort_unless($user->role === UserRole::Admin, 403);

        $data = $request->validate([
            'approve' => ['required', 'boolean'],
            'approved_quantity' => ['nullable', 'integer', 'min:1'],
            'restock' => ['nullable', 'boolean'],
        ]);

        try {
            DB::transaction(function () use ($user, $returnId, $data) {
                DB::statement('select fn_process_return(?, ?, ?, ?, ?)', [
                    (int) $returnId,
                    (int) $user->id,
                    (bool) $data['approve'],
                    $data['approved_quantity'] ?? null,
                    (bool) ($data['restock'] ?? false),
                ]);
            });

            return response()->json(['ok' => true]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Return processing failed.',
                'detail' => $e->getMessage(),
            ], 422);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Enums\TicketKind;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\OrderItem;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupportTicketController extends ApiController
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $tickets = SupportTicket::query()
            ->with([
                'creator:id,name,email',
                'assignee:id,name,email',
                'product:id,name,slug',
                'variant:id,title,sku',
                'order:id,status,total_amount,placed_at',
                'orderItem:id,order_id,product_id,variant_id,quantity,unit_price,name_snapshot',
            ])
            ->when($user->role !== UserRole::Admin, fn ($qb) => $qb->where('created_by_user_id', $user->id))
            ->when($request->filled('status'), function ($qb) use ($request) {
                $status = (string) $request->query('status');

                if ($status === TicketStatus::Open->value) {
                    $qb->where('status', '!=', TicketStatus::Closed->value);
                    return;
                }

                $qb->where('status', $status);
            })
            ->when($request->filled('ticket_kind'), fn ($qb) => $qb->where('ticket_kind', $request->query('ticket_kind')))
            ->when($request->filled('assigned_to_user_id'), function ($qb) use ($request) {
                $assignedTo = $request->query('assigned_to_user_id');

                if ($assignedTo === 'unassigned') {
                    $qb->whereNull('assigned_to_user_id');
                    return;
                }

                $qb->where('assigned_to_user_id', (int) $assignedTo);
            })
            ->when($request->filled('created_from'), fn ($qb) => $qb->whereDate('created_at', '>=', $request->query('created_from')))
            ->when($request->filled('created_to'), fn ($qb) => $qb->whereDate('created_at', '<=', $request->query('created_to')))
            ->when($request->filled('q'), function ($qb) use ($request) {
                $q = trim((string) $request->query('q'));
                $ilike = '%' . $q . '%';

                $qb->where(function ($inner) use ($ilike, $q) {
                    $inner
                        ->where('subject', 'ilike', $ilike)
                        ->orWhereHas('creator', fn ($creator) => $creator->where('name', 'ilike', $ilike)->orWhere('email', 'ilike', $ilike))
                        ->orWhereHas('product', fn ($product) => $product->where('name', 'ilike', $ilike))
                        ->orWhereHas('order', fn ($order) => $order->where('id', (int) $q));
                });
            })
            ->orderByRaw("case when status = 'closed' then 1 else 0 end")
            ->orderByDesc('last_message_at')
            ->paginate((int) $request->query('per_page', 20));

        return response()->json($tickets);
    }

    public function show(Request $request, SupportTicket $ticket)
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($user->role !== UserRole::Admin && (int) $ticket->created_by_user_id !== (int) $user->id) {
            abort(404);
        }

        $ticket->load([
            'messages.sender',
            'creator:id,name,email',
            'assignee:id,name,email',
            'product:id,name,slug',
            'variant:id,title,sku',
            'order:id,status,total_amount,placed_at',
            'orderItem:id,order_id,product_id,variant_id,quantity,unit_price,line_total,name_snapshot,sku_snapshot',
        ]);

        return response()->json($ticket);
    }

    public function meta(Request $request)
    {
        $this->requireAdmin($request);

        return response()->json([
            'status_options' => array_map(fn ($status) => $status->value, TicketStatus::cases()),
            'kind_options' => array_map(fn ($kind) => $kind->value, TicketKind::cases()),
            'admin_users' => User::query()
                ->where('role', UserRole::Admin)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'ticket_kind' => ['nullable', Rule::in(array_map(fn ($kind) => $kind->value, TicketKind::cases()))],
            'order_item_id' => ['nullable', 'integer', 'exists:order_items,id'],
        ]);

        $ticketKind = TicketKind::from($data['ticket_kind'] ?? TicketKind::General->value);
        $context = $this->resolveOrderItemContext($request, $data['order_item_id'] ?? null, $ticketKind);

        $subject = trim((string) ($data['subject'] ?? ''));
        if ($subject === '') {
            $subject = $this->buildSubject($ticketKind, $context);
        }

        $message = trim((string) ($data['message'] ?? ''));
        if ($message === '') {
            $message = $this->buildAutomatedMessage($ticketKind, $context);
        }

        $ticket = SupportTicket::create([
            'created_by_user_id' => $user->id,
            'subject' => $subject,
            'status' => TicketStatus::Open,
            'ticket_kind' => $ticketKind,
            'order_id' => $context['order_id'] ?? null,
            'order_item_id' => $context['order_item_id'] ?? null,
            'product_id' => $context['product_id'] ?? null,
            'variant_id' => $context['variant_id'] ?? null,
        ]);

        SupportMessage::create([
            'ticket_id' => $ticket->id,
            'sender_user_id' => $user->id,
            'body' => $message,
            'is_internal' => false,
        ]);

        $ticket->update([
            'last_message_at' => now(),
            'status' => $user->role === UserRole::Admin ? TicketStatus::WaitingOnCustomer : TicketStatus::WaitingOnAdmin,
        ]);

        return response()->json($ticket->fresh()->load(['messages.sender', 'creator', 'product', 'order', 'orderItem']), 201);
    }

    public function addMessage(Request $request, SupportTicket $ticket)
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($user->role !== UserRole::Admin && (int) $ticket->created_by_user_id !== (int) $user->id) {
            abort(404);
        }

        $data = $request->validate([
            'body' => ['required', 'string'],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        $isInternal = (bool) ($data['is_internal'] ?? false);

        if ($isInternal) {
            $this->requireAdmin($request);
        }

        SupportMessage::create([
            'ticket_id' => $ticket->id,
            'sender_user_id' => $user->id,
            'body' => $data['body'],
            'is_internal' => $isInternal,
        ]);

        $ticket->update([
            'last_message_at' => now(),
            'status' => $user->role === UserRole::Admin ? TicketStatus::WaitingOnCustomer : TicketStatus::WaitingOnAdmin,
        ]);

        return response()->json($ticket->fresh()->load(['messages.sender', 'creator', 'assignee', 'product', 'order', 'orderItem']), 201);
    }

    public function close(Request $request, SupportTicket $ticket)
    {
        $this->requireAdmin($request);

        $ticket->update([
            'status' => TicketStatus::Closed,
            'closed_at' => now(),
            'closed_by_user_id' => $request->user()->id,
        ]);

        return response()->json($ticket->fresh());
    }

    public function reopen(Request $request, SupportTicket $ticket)
    {
        $this->requireAdmin($request);

        $ticket->update([
            'status' => TicketStatus::WaitingOnCustomer,
            'closed_at' => null,
            'closed_by_user_id' => null,
        ]);

        return response()->json($ticket->fresh());
    }

    public function assign(Request $request, SupportTicket $ticket)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $ticket->update([
            'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
        ]);

        return response()->json($ticket->fresh());
    }

    private function resolveOrderItemContext(Request $request, ?int $orderItemId, TicketKind $ticketKind): array
    {
        if (! $orderItemId) {
            abort_if($ticketKind !== TicketKind::General, 422, 'Order item context is required for this ticket.');
            return [];
        }

        $orderItem = OrderItem::query()
            ->with(['order', 'product', 'variant'])
            ->findOrFail($orderItemId);

        $user = $request->user();
        abort_unless($user, 401);

        if ($user->role !== UserRole::Admin && (int) $orderItem->order?->user_id !== (int) $user->id) {
            abort(404);
        }

        return [
            'order_id' => (int) $orderItem->order_id,
            'order_item_id' => (int) $orderItem->id,
            'product_id' => (int) $orderItem->product_id,
            'variant_id' => $orderItem->variant_id ? (int) $orderItem->variant_id : null,
            'product_name' => $orderItem->product?->name ?? $orderItem->name_snapshot,
            'variant_title' => $orderItem->variant?->title,
            'quantity' => (int) $orderItem->quantity,
            'unit_price' => (float) $orderItem->unit_price,
            'order_status' => $orderItem->order?->status?->value,
        ];
    }

    private function buildSubject(TicketKind $ticketKind, array $context): string
    {
        $productName = $context['product_name'] ?? 'Order item';
        $orderId = $context['order_id'] ?? null;

        return match ($ticketKind) {
            TicketKind::ProductSupport => $orderId
                ? "Product support for {$productName} (Order #{$orderId})"
                : "Product support for {$productName}",
            TicketKind::RefundRequest => $orderId
                ? "Refund request for {$productName} (Order #{$orderId})"
                : "Refund request for {$productName}",
            default => 'Support request',
        };
    }

    private function buildAutomatedMessage(TicketKind $ticketKind, array $context): string
    {
        if ($ticketKind === TicketKind::General || empty($context)) {
            return 'A new support ticket was created. The customer will add more detail shortly.';
        }

        $lines = [
            'This ticket was created from a purchased product.',
            'Product: ' . ($context['product_name'] ?? 'Unknown product'),
        ];

        if (! empty($context['variant_title'])) {
            $lines[] = 'Variant: ' . $context['variant_title'];
        }

        if (! empty($context['order_id'])) {
            $lines[] = 'Order: #' . $context['order_id'];
        }

        if (isset($context['quantity'])) {
            $lines[] = 'Quantity: ' . $context['quantity'];
        }

        if (isset($context['unit_price'])) {
            $lines[] = 'Unit price: GBP ' . number_format((float) $context['unit_price'], 2);
        }

        $lines[] = '';
        $lines[] = $ticketKind === TicketKind::RefundRequest
            ? 'The customer is requesting a refund for this item. Please review the order and respond with the next steps.'
            : 'The customer would like support with this purchased item. Please review the order and respond with the next steps.';

        return implode("\n", $lines);
    }
}

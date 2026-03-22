<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Enums\TicketKind;
use App\Enums\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupportTicketPageController extends Controller
{
    public function index()
    {
        $tickets = SupportTicket::query()
            ->where('created_by_user_id', Auth::id())
            ->orderByDesc('last_message_at')
            ->paginate(10);

        return view('contact', [
            'tickets' => $tickets,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body'    => ['required', 'string', 'max:5000'],
        ]);

        $ticket = DB::transaction(function () use ($data) {
            $ticket = SupportTicket::create([
                'created_by_user_id' => Auth::id(),
                'subject'            => $data['subject'],
                'status'             => TicketStatus::Open,
                'last_message_at'    => now(),
            ]);

            SupportMessage::create([
                'ticket_id'       => $ticket->id,
                'sender_user_id'  => Auth::id(),
                'body'            => $data['body'],
                'is_internal'     => false,
                'created_at'      => now(),
            ]);

            $ticket->update([
                'last_message_at' => now(),
                'status'          => TicketStatus::WaitingOnAdmin, // customer has sent message
            ]);

            return $ticket;
        });

        return redirect()->route('support.tickets.show', $ticket)->with('success', 'Ticket created.');
    }

    public function storeOrderItemTicket(Request $request, OrderItem $orderItem)
    {
        abort_if((int) $orderItem->order?->user_id !== (int) Auth::id(), 404);
        abort_if($orderItem->order?->status?->value === 'cancelled', 422, 'Cancelled orders cannot open tickets.');

        $kind = TicketKind::from($request->validate([
            'ticket_kind' => ['required', 'in:product_support,refund_request'],
        ])['ticket_kind']);

        $existing = SupportTicket::query()
            ->where('created_by_user_id', Auth::id())
            ->where('order_item_id', $orderItem->id)
            ->where('ticket_kind', $kind)
            ->where('status', '!=', TicketStatus::Closed)
            ->first();

        if ($existing) {
            return redirect()->route('support.tickets.show', $existing)
                ->with('success', 'An open ticket already exists for this request, so we took you there.');
        }

        $orderItem->loadMissing(['order', 'product', 'variant']);

        $subject = $kind === TicketKind::RefundRequest
            ? "Refund request for {$orderItem->product?->name} (Order #{$orderItem->order_id})"
            : "Product support for {$orderItem->product?->name} (Order #{$orderItem->order_id})";

        $bodyLines = [
            'This ticket was created from a purchased product.',
            'Product: ' . ($orderItem->product?->name ?? $orderItem->name_snapshot),
        ];

        if ($orderItem->variant?->title) {
            $bodyLines[] = 'Variant: ' . $orderItem->variant->title;
        }

        $bodyLines[] = 'Order: #' . $orderItem->order_id;
        $bodyLines[] = 'Quantity: ' . $orderItem->quantity;
        $bodyLines[] = 'Unit price: GBP ' . number_format((float) $orderItem->unit_price, 2);
        $bodyLines[] = '';
        $bodyLines[] = $kind === TicketKind::RefundRequest
            ? 'The customer is requesting a refund for this item. Please review the order and reply with the next steps.'
            : 'The customer is requesting support with this purchased item. Please review the order and reply with the next steps.';

        $ticket = DB::transaction(function () use ($subject, $bodyLines, $kind, $orderItem) {
            $ticket = SupportTicket::create([
                'created_by_user_id' => Auth::id(),
                'subject' => $subject,
                'status' => TicketStatus::Open,
                'ticket_kind' => $kind,
                'order_id' => $orderItem->order_id,
                'order_item_id' => $orderItem->id,
                'product_id' => $orderItem->product_id,
                'variant_id' => $orderItem->variant_id,
                'last_message_at' => now(),
            ]);

            SupportMessage::create([
                'ticket_id' => $ticket->id,
                'sender_user_id' => Auth::id(),
                'body' => implode("\n", $bodyLines),
                'is_internal' => false,
                'created_at' => now(),
            ]);

            $ticket->update([
                'last_message_at' => now(),
                'status' => TicketStatus::WaitingOnAdmin,
            ]);

            return $ticket;
        });

        return redirect()->route('support.tickets.show', $ticket)->with('success', 'Ticket created.');
    }

    public function show(SupportTicket $ticket)
    {
        $this->authorizeTicket($ticket);

        $limit = 25;

        // Load latest N, display oldest->newest
        $chunk = SupportMessage::query()
            ->where('ticket_id', $ticket->id)
            ->where('is_internal', false)
            ->orderByDesc('id')
            ->limit($limit)
            ->with('sender')
            ->get();

        $messages = $chunk->sortBy('id')->values();
        $oldestId = $messages->first()?->id;

        $hasMore = $oldestId
            ? SupportMessage::query()
                ->where('ticket_id', $ticket->id)
                ->where('is_internal', false)
                ->where('id', '<', $oldestId)
                ->exists()
            : false;

        return view('support.show', [
            'ticket'   => $ticket,
            'messages' => $messages,
            'oldestId' => $oldestId,
            'hasMore'  => $hasMore,
        ]);
    }

    public function storeMessage(Request $request, SupportTicket $ticket)
    {
        $this->authorizeTicket($ticket);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($ticket, $data) {
            SupportMessage::create([
                'ticket_id'      => $ticket->id,
                'sender_user_id' => Auth::id(),
                'body'           => $data['body'],
                'is_internal'    => false,
                'created_at'     => now(),
            ]);

            $ticket->update([
                'last_message_at' => now(),
                'status'          => TicketStatus::WaitingOnAdmin,
            ]);
        });

        return redirect()->route('support.tickets.show', $ticket)->with('success', 'Message sent.');
    }

    // Chunked message history: GET /support/tickets/{ticket}/messages?before_id=123&limit=25
    public function messages(Request $request, SupportTicket $ticket)
    {
        $this->authorizeTicket($ticket);

        $limit = max(1, min(50, (int) $request->query('limit', 25)));
        $beforeId = $request->query('before_id');

        $query = SupportMessage::query()
            ->where('ticket_id', $ticket->id)
            ->where('is_internal', false);

        if ($beforeId !== null && is_numeric($beforeId)) {
            $query->where('id', '<', (int) $beforeId);
        }

        $chunk = $query
            ->orderByDesc('id')
            ->limit($limit)
            ->with('sender')
            ->get();

        $items = $chunk->sortBy('id')->values();

        $oldestId = $items->first()?->id;

        $hasMore = $oldestId
            ? SupportMessage::query()
                ->where('ticket_id', $ticket->id)
                ->where('is_internal', false)
                ->where('id', '<', $oldestId)
                ->exists()
            : false;

        return response()->json([
            'items' => $items->map(function (SupportMessage $m) {
                return [
                    'id' => $m->id,
                    'body' => $m->body,
                    'created_at' => optional($m->created_at)->toIso8601String(),
                    'sender_name' => $m->sender?->name ?? 'User',
                    'is_me' => (int) $m->sender_user_id === (int) Auth::id(),
                ];
            })->values(),
            'oldest_id' => $oldestId,
            'has_more' => $hasMore,
        ]);
    }

    private function authorizeTicket(SupportTicket $ticket): void
    {
        abort_if((int) $ticket->created_by_user_id !== (int) Auth::id(), 404);
    }
}

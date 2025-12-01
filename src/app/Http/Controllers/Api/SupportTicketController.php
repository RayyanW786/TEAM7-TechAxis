<?php

namespace App\Http\Controllers\Api;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportTicketController extends ApiController
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $tickets = SupportTicket::query()
            ->when($user->role !== UserRole::Admin, fn($qb) => $qb->where('created_by_user_id', $user->id))
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

        $ticket->load('messages.sender');

        return response()->json($ticket);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        $ticket = SupportTicket::create([
            'created_by_user_id' => $user->id,
            'subject' => $data['subject'],
            'status' => TicketStatus::Open,
        ]);

        SupportMessage::create([
            'ticket_id' => $ticket->id,
            'sender_user_id' => $user->id,
            'body' => $data['message'],
            'is_internal' => false,
        ]);

        $ticket->update([
            'last_message_at' => now(),
            'status' => $user->role === UserRole::Admin ? TicketStatus::WaitingOnCustomer : TicketStatus::WaitingOnAdmin,
        ]);

        return response()->json($ticket->load('messages.sender'), 201);
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

        return response()->json($ticket->fresh()->load('messages.sender'), 201);
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
}

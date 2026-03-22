@extends('layouts.main')
@section('title', 'Tech Axis | Ticket')

@push('styles')
    <link href="{{ asset('css/support.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="support-page">
    <div class="support-header row">
        <a class="secondary-btn" href="{{ route('support.tickets.index') }}">← Back</a>

        <div class="ticket-headings">
            <h1 class="support-title">Ticket #{{ $ticket->id }}</h1>
            <div class="support-subtitle">{{ $ticket->subject }}</div>
        </div>

        <div class="ticket-pill">{{ str_replace('_', ' ', $ticket->status->value) }}</div>
    </div>

    @if(session('success'))
        <div class="support-alert success">{{ session('success') }}</div>
    @endif

    <div class="support-card">
        <div class="messages-wrap">
            <div class="messages"
                 id="messages"
                 data-messages-url="{{ route('support.tickets.messages.index', $ticket) }}"
                 data-oldest-id="{{ $oldestId ?? '' }}"
                 data-has-more="{{ $hasMore ? '1' : '0' }}">
                <div class="messages-loader" id="messagesLoader">Loading older messages…</div>

                @foreach($messages as $m)
                    <div class="msg {{ (int)$m->sender_user_id === (int)auth()->id() ? 'me' : 'them' }}"
                         data-id="{{ $m->id }}">
                        <div class="msg-meta">
                            {{ $m->sender?->name ?? 'User' }}
                            · {{ \Carbon\Carbon::parse($m->created_at)->format('d M Y H:i') }}
                        </div>
                        <div class="msg-body">{!! nl2br(e($m->body)) !!}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <form method="POST" action="{{ route('support.tickets.messages.store', $ticket) }}" class="message-form">
            @csrf
            <textarea class="form-textarea message-form__textarea" name="body" rows="3" maxlength="5000"
                      placeholder="Write a message…" required></textarea>
            <div class="message-form__actions">
                <button class="primary-btn message-form__button" type="submit">Send message</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/support-ticket.js') }}" defer></script>
@endpush

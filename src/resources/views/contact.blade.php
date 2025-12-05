@extends('layouts.main')
@section('title', 'Tech Axis | Support')

@push('styles')
    <link href="{{ asset('css/support.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="support-page">
    <div class="support-header">
        <h1 class="support-title">Support</h1>
        <p class="support-subtitle">Create a ticket and view your previous conversations.</p>
    </div>

    @if(session('success'))
        <div class="support-alert success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="support-alert error">
            <b>Please fix:</b>
            <ul>
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="support-grid">
        <section class="support-card">
            <div class="support-card-head">
                <h2>Your Tickets</h2>
            </div>

            @forelse($tickets as $t)
                <a class="ticket-row" href="{{ route('support.tickets.show', $t) }}">
                    <div class="ticket-row-top">
                        <div class="ticket-subject">{{ $t->subject }}</div>
                        <div class="ticket-status">{{ str_replace('_', ' ', $t->status->value) }}</div>
                    </div>
                    <div class="ticket-row-bottom">
                        Last updated: {{ \Carbon\Carbon::parse($t->last_message_at)->diffForHumans() }}
                    </div>
                </a>
            @empty
                <p class="muted">No tickets yet.</p>
            @endforelse

            <div class="support-pagination">
                {{ $tickets->links() }}
            </div>
        </section>

        <section class="support-card">
            <div class="support-card-head">
                <h2>Create New Ticket</h2>
            </div>

            <form method="POST" action="{{ route('support.tickets.store') }}" class="ticket-form">
                @csrf

                <label class="form-label">Subject</label>
                <input class="form-input" name="subject" type="text" maxlength="200"
                       value="{{ old('subject') }}" placeholder="e.g., Issue with my order" required>

                <label class="form-label">Message</label>
                <textarea class="form-textarea" name="body" rows="7" maxlength="5000"
                          placeholder="Tell us what's going on..." required>{{ old('body') }}</textarea>

                <button class="primary-btn" type="submit">Create Ticket</button>

                <p class="muted small">
                    Logged in as <b>{{ auth()->user()->name }}</b> ({{ auth()->user()->email }})
                </p>
            </form>
        </section>
    </div>
</div>
@endsection

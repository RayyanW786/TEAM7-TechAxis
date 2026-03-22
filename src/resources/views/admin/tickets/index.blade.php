@extends('layouts.main')

@section('title', 'Support Tickets | Admin')

@push('styles')
    <link href="{{ asset('css/admin/admin-shell.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="admin-shell" data-admin-tickets-root>
        <div class="admin-shell-header">
            <div>
                <span class="admin-shell-kicker">Tickets</span>
                <h1 class="admin-shell-title">Support inbox</h1>
                <p class="admin-shell-copy">Handle refund requests and product-support conversations with assignment, internal notes, and full order context.</p>
            </div>
            <div class="admin-shell-actions">
                <a href="{{ route('admin.dashboard') }}" class="admin-shell-button">Back to dashboard</a>
            </div>
        </div>

        <div data-admin-tickets-app></div>
    </div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/admin/tickets.js') }}"></script>
@endpush

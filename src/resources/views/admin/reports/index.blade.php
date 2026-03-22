@extends('layouts.main')

@section('title', 'Reports | Admin')

@push('styles')
    <link href="{{ asset('css/admin/admin-shell.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="admin-shell" data-admin-reports-root>
        <div class="admin-shell-header">
            <div>
                <span class="admin-shell-kicker">Reports</span>
                <h1 class="admin-shell-title">Operational reporting</h1>
                <p class="admin-shell-copy">Track live order, shipment, ticket, inventory, and revenue trends in a single production-ready reporting view.</p>
            </div>
            <div class="admin-shell-actions">
                <a href="{{ route('admin.dashboard') }}" class="admin-shell-button">Back to dashboard</a>
            </div>
        </div>

        <div data-admin-reports-app></div>
    </div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/admin/reports.js') }}"></script>
@endpush

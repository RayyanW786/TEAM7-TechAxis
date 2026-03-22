@extends('layouts.main')

@section('title', 'Orders | Admin')

@push('styles')
    <link href="{{ asset('css/admin/admin-shell.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="admin-shell" data-admin-orders-root>
        <div class="admin-shell-header">
            <div>
                <span class="admin-shell-kicker">Orders</span>
                <h1 class="admin-shell-title">Order processing</h1>
                <p class="admin-shell-copy">Search customer orders, inspect order detail, update statuses, and process shipments from one workflow.</p>
            </div>
            <div class="admin-shell-actions">
                <a href="{{ route('admin.dashboard') }}" class="admin-shell-button">Back to dashboard</a>
            </div>
        </div>

        <div data-admin-orders-app>
            <div class="admin-panel">
                <div class="admin-empty">Loading order processing tools...</div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin/admin_orders.js') }}"></script>
@endpush

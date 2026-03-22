@extends('layouts.main')

@section('title', 'Inventory | Admin')

@push('styles')
    <link href="{{ asset('css/admin/admin-shell.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="admin-shell" data-admin-inventory-root>
        <div class="admin-shell-header">
            <div>
                <span class="admin-shell-kicker">Inventory</span>
                <h1 class="admin-shell-title">Inventory dashboard</h1>
                <p class="admin-shell-copy">Monitor stock health, record incoming stock, review alert history, and act on the most urgent restock priorities.</p>
            </div>
            <div class="admin-shell-actions">
                <a href="{{ route('admin.dashboard') }}" class="admin-shell-button">Back to dashboard</a>
            </div>
        </div>

        <div data-admin-inventory-app></div>
    </div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/admin/inventory.js') }}"></script>
@endpush

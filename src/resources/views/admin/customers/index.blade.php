@extends('layouts.main')

@section('title', 'Customers | Admin')

@push('styles')
    <link href="{{ asset('css/admin/admin-shell.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="admin-shell" data-admin-customers-root>
        <div class="admin-shell-header">
            <div>
                <span class="admin-shell-kicker">Customers</span>
                <h1 class="admin-shell-title">Customer management</h1>
                <p class="admin-shell-copy">Search, create, update, and remove customer accounts while keeping profile, address, and recent order context visible.</p>
            </div>
            <div class="admin-shell-actions">
                <a href="{{ route('admin.dashboard') }}" class="admin-shell-button">Back to dashboard</a>
            </div>
        </div>

        <div data-admin-customers-app></div>
    </div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/admin/customers.js') }}"></script>
@endpush

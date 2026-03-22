@extends('layouts.main')

@section('title', 'Discount Codes | Admin')

@push('styles')
    <link href="{{ asset('css/admin/admin-shell.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="admin-shell" data-admin-discounts-root>
        <div class="admin-shell-header">
            <div>
                <span class="admin-shell-kicker">Discounts</span>
                <h1 class="admin-shell-title">Discount code management</h1>
                <p class="admin-shell-copy">Create, schedule, deactivate, and monitor order-wide promotions that customers can apply at checkout.</p>
            </div>
            <div class="admin-shell-actions">
                <a href="{{ route('admin.dashboard') }}" class="admin-shell-button">Back to dashboard</a>
            </div>
        </div>

        <div data-admin-discounts-app></div>
    </div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/admin/discounts.js') }}"></script>
@endpush

@extends('layouts.main')

@section('title', 'Products | Admin')

@push('styles')
    <link href="{{ asset('css/admin/admin-shell.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="admin-shell">
        <div class="admin-shell-header">
            <div>
                <span class="admin-shell-kicker">Products</span>
                <h1 class="admin-shell-title">Catalog management</h1>
                <p class="admin-shell-copy">Maintain product content, pricing, stock, and storefront-ready listings from the admin side.</p>
            </div>
            <div class="admin-shell-actions">
                <a href="{{ route('admin.products.create') }}" class="admin-shell-button admin-shell-button--primary">Add product</a>
                <a href="{{ route('admin.dashboard') }}" class="admin-shell-button">Back to dashboard</a>
            </div>
        </div>

        <section class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Product list</h2>
                    <p>Select a product to edit its content and stock.</p>
                </div>
            </div>
            <div class="admin-link-list" id="product-list"></div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin/index_products.js') }}"></script>
@endpush

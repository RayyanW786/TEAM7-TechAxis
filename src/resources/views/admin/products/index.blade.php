@extends('layouts.main')

@section('title', 'Products | Admin')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
<link href="{{ asset('css/admin/index_product.css') }}" rel="stylesheet">
@endpush

@section('content')

<div class="dashboard-container">
    <h1>Products</h1>
    <br>
    <a href="{{ route('admin.products.create') }}" class="add-btn">Add Product</a>

    <div class="admin-links" id="product-list"></div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/admin/index_products.js') }}"></script>
@endpush
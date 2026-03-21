@extends('layouts.main')

@section('title', 'Edit Product | Tech Axis')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
<link href="{{ asset('css/admin/edit_product.css') }}" rel="stylesheet">
@endpush

@section('content')

<div class="edit-product-container">
    <a href="{{ route('admin.products.index') }}" class="back-btn">
    ← Back to Products
    </a>
    <h1>Edit Product</h1>

    <div class="edit-product-form">

        <div class="form-group">
            <label>Product Name</label>
            <input type="text" id="name" value="{{ $product->name }}">
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea id="description">{{ $product->description }}</textarea>
        </div>

        <div class="form-group">
            <label>Price (£)</label>
            <input type="number" id="price" step="0.01" value="{{ $product->price }}">
        </div>

        <div class="form-group">
            <label>Stock</label>
            <input type="number" id="stock_quantity" value="{{ $product->stock_quantity }}">
        </div>

        <div class="actions">
            <button onclick="updateProduct()">Update Product</button>
            <button onclick="deleteProduct()" class="danger">Delete</button>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/admin/edit_product.js') }}"></script>
@endpush

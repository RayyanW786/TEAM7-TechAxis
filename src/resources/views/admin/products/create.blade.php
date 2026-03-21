@extends('layouts.main')

@section('title', 'Create Product | Tech Axis')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
<link href="{{ asset('css/admin/create_product.css') }}" rel="stylesheet">
@endpush

@section('content')

    <div class="edit-product-container">
        <h1>Create Product</h1>

        <a href="{{ route('admin.products.index') }}" class="back-btn">← Back</a>

        <div class="edit-product-form">

            <div class="form-group">
                <label>Name</label>
                <input type="text" id="name">
            </div>

            <div class="form-group">
                <label>Slug</label>
                <input type="text" id="slug">
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea id="description"></textarea>
            </div>

            <div class="form-group">
                <label>Summary</label>
                <textarea id="summary"></textarea>
            </div>

            <div class="form-group">
                <label>Price (£)</label>
                <input type="number" id="price">
            </div>

            <div class="form-group">
                <label>Stock</label>
                <input type="number" id="stock_quantity">
            </div>

            <div class="form-group">
                <label>Category</label>
                <select id="category_id">
                    <option value="">Select Category</option>
                </select>
            </div>

            <div class="form-group">
                <label>Image URL</label>
                <input type="text" id="image_url" placeholder="https://...">
            </div>

            <div class="form-group">
                <img id="image_preview" style="max-width: 200px; display: none; border-radius: 8px;">
            </div>

            <div class="actions">
                <button type="button" onclick="createProduct()">Create Product</button>
            </div>

        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/admin/create_product.js') }}"></script>
@endpush

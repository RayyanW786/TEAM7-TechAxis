@extends('layouts.storefront')

@section('title', 'Products')

@section('content')
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3 products-page-header">
    <div>
        <h1 class="h3 section-title mb-1">Products</h1>
        <div class="accent-rule"></div>
        <p class="text-muted mt-2 mb-0">Search and filter products by category and price.</p>
    </div>
</div>

<div class="card shadow-sm mb-4 products-filter-card">
    <div class="card-body">
        <form method="get" action="{{ route('products.index') }}" class="row g-3 align-items-end products-filter-form">
            <div class="col-12 col-lg-5">
                <label class="form-label">Search</label>
                <input
                    name="q"
                    value="{{ $searchQuery }}"
                    type="text"
                    class="form-control"
                    placeholder="Search products..."
                >
            </div>

            <div class="col-12 col-lg-3">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}" @selected($selectedCategorySlug === $category->slug)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-lg-2">
                <label class="form-label">Min price</label>
                <input name="min_price" value="{{ $minPrice }}" type="number" min="0" step="0.01" class="form-control" placeholder="0.00">
            </div>

            <div class="col-6 col-lg-2">
                <label class="form-label">Max price</label>
                <input name="max_price" value="{{ $maxPrice }}" type="number" min="0" step="0.01" class="form-control" placeholder="999.99">
            </div>

            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Apply filters</button>
                <a class="btn btn-outline-primary" href="{{ route('products.index') }}">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-4 products-grid">
    @forelse ($products as $product)
        @php
            $imageUrl = optional($product->images->first())->url;
            $displayPrice = $product->listing_price ?? $product->effectivePrice();
        @endphp

        <div class="col">
            <div class="card h-100 shadow-sm product-list-card">
                <a href="{{ route('products.show', $product) }}" class="text-decoration-none product-card-link d-block">
                    @if ($imageUrl)
                        <img src="{{ $imageUrl }}" class="product-card-img card-img-top" alt="{{ $product->name }}">
                    @else
                        <div class="product-card-img d-flex align-items-center justify-content-center text-muted">
                            No image
                        </div>
                    @endif

                    <div class="card-body product-card-body">
                        <h2 class="h6 mb-2 product-card-title line-clamp-2">{{ $product->name }}</h2>
                        <div class="fw-semibold mb-2">&pound;{{ number_format((float) $displayPrice, 2) }}</div>

                        <p class="text-muted small mb-0 line-clamp-2 product-card-summary">
                            {{ $product->summary ?: 'No Product summary found' }}
                        </p>
                    </div>
                </a>

                <div class="card-footer border-0 pt-0 pb-3 px-3">
                    <button
                        type="button"
                        class="btn btn-outline-primary btn-sm w-100 compare-toggle-button"
                        data-compare-toggle
                        data-product-id="{{ (int) $product->id }}"
                        data-product-slug="{{ $product->slug }}"
                        data-product-name="{{ $product->name }}"
                        data-product-price="{{ number_format((float) $displayPrice, 2, '.', '') }}"
                        data-product-summary="{{ $product->summary ?: '' }}"
                        data-product-url="{{ route('products.show', $product) }}"
                        data-product-image="{{ $imageUrl ?: '' }}"
                    >
                        Add to compare
                    </button>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info mb-0">No products found for your filters.</div>
        </div>
    @endforelse
</div>

@if (method_exists($products, 'links'))
    <div class="d-flex justify-content-center mt-4">
        {{ $products->links() }}
    </div>
@endif
@endsection

@push('scripts')
<script type="module" src="{{ asset('js/storefront/product-compare.js') }}"></script>
@endpush

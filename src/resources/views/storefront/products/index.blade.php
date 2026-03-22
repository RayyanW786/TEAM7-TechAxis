@extends('layouts.storefront')

@section('title', 'Products')

@section('content')
@php
    $priceFloor = max(0, (int) ($priceBounds['min'] ?? 0));
    $priceCeiling = max($priceFloor + 1, (int) ($priceBounds['max'] ?? 1000));
    $selectedMinPrice = $minPrice !== null ? max($priceFloor, min((float) $minPrice, $priceCeiling)) : $priceFloor;
    $selectedMaxPrice = $maxPrice !== null ? max($selectedMinPrice, min((float) $maxPrice, $priceCeiling)) : $priceCeiling;
@endphp

<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3 products-page-header">
    <div>
        <h1 class="h3 section-title mb-1">Products</h1>
        <div class="accent-rule"></div>
        <p class="text-muted mt-2 mb-0">Search normally, or use guided tools to build advanced queries and adjust price filters your way.</p>
    </div>
</div>

<div class="card shadow-sm mb-4 products-filter-card">
    <div class="card-body">
        <form method="get" action="{{ route('products.index') }}" class="row g-3 align-items-start products-filter-form">
            <div class="col-12 col-xl-6">
                <div class="filter-input-shell filter-input-shell--search">
                    <label class="form-label">Search</label>
                    <div class="search-input-row">
                        <input
                            id="productsSearchInput"
                        name="q"
                        value="{{ $searchQuery }}"
                        type="text"
                        class="form-control form-control-lg"
                        placeholder='Search products, e.g. "nintendo switch"'
                        autocomplete="off"
                    >
                        <button
                            type="button"
                            class="btn btn-outline-primary search-builder-trigger"
                            data-bs-toggle="modal"
                            data-bs-target="#advancedSearchBuilderModal"
                        >
                            Build search
                        </button>
                    </div>
                    <div class="form-text mt-2">
                        Search normally if you want, or use the builder for phrases, exclusions, and OR searches.
                    </div>
                    <div class="products-search-examples mt-3">
                        <div class="small text-muted mb-2">Quick examples</div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary search-example-chip" data-search-example='"nintendo switch"'>"nintendo switch"</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary search-example-chip" data-search-example='ps5 -controller'>ps5 -controller</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary search-example-chip" data-search-example='pc OR console'>pc OR console</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="filter-input-shell filter-input-shell--category">
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
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div
                    class="price-filter-panel"
                    data-price-filter
                    data-price-floor="{{ $priceFloor }}"
                    data-price-ceiling="{{ $priceCeiling }}"
                    data-selected-min="{{ $selectedMinPrice }}"
                    data-selected-max="{{ $selectedMaxPrice }}"
                >
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <label class="form-label mb-1">Price</label>
                            <div class="form-text m-0">Keep precise values by default, or switch to a slider for faster browsing.</div>
                        </div>

                        <div class="form-check form-switch price-mode-switch">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="priceModeToggle"
                                data-price-mode-toggle
                                @checked($priceMode === 'slider')
                            >
                            <label class="form-check-label small" for="priceModeToggle">Use slider instead</label>
                        </div>
                    </div>

                    <input type="hidden" name="price_mode" value="{{ $priceMode }}" data-price-mode-input>

                    <div class="row g-2" data-price-values-panel>
                        <div class="col-6">
                            <label class="form-label small">Min price</label>
                            <input
                                name="min_price"
                                value="{{ $minPrice }}"
                                type="number"
                                min="0"
                                step="0.01"
                                class="form-control"
                                placeholder="0.00"
                                data-price-input-min
                            >
                        </div>

                        <div class="col-6">
                            <label class="form-label small">Max price</label>
                            <input
                                name="max_price"
                                value="{{ $maxPrice }}"
                                type="number"
                                min="0"
                                step="0.01"
                                class="form-control"
                                placeholder="999.99"
                                data-price-input-max
                            >
                        </div>
                    </div>

                    <div class="price-slider-panel d-none" data-price-slider-panel>
                        <div class="price-slider-summary" data-price-slider-summary>
                            &pound;{{ number_format((float) $selectedMinPrice, 2) }} - &pound;{{ number_format((float) $selectedMaxPrice, 2) }}
                        </div>

                        <div class="dual-range-slider mt-3">
                            <div class="dual-range-slider__track" data-price-slider-track></div>
                            <input
                                type="range"
                                min="{{ $priceFloor }}"
                                max="{{ $priceCeiling }}"
                                step="1"
                                value="{{ (int) $selectedMinPrice }}"
                                class="form-range dual-range-slider__input"
                                data-price-slider-min
                                aria-label="Minimum price slider"
                            >
                            <input
                                type="range"
                                min="{{ $priceFloor }}"
                                max="{{ $priceCeiling }}"
                                step="1"
                                value="{{ (int) $selectedMaxPrice }}"
                                class="form-range dual-range-slider__input"
                                data-price-slider-max
                                aria-label="Maximum price slider"
                            >
                        </div>

                        <div class="d-flex justify-content-between small text-muted mt-2">
                            <span>&pound;{{ number_format((float) $priceFloor, 0) }}</span>
                            <span>&pound;{{ number_format((float) $priceCeiling, 0) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 d-flex gap-2 products-filter-actions">
                <button class="btn btn-primary" type="submit">Apply filters</button>
                <a class="btn btn-outline-primary" href="{{ route('products.index') }}">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="advancedSearchBuilderModal" tabindex="-1" aria-labelledby="advancedSearchBuilderLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 mb-1" id="advancedSearchBuilderLabel">Advanced Search Builder</h2>
                    <p class="text-muted small mb-0">Create an advanced search visually and we will generate the query for you.</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-secondary builder-helper-alert mb-4">
                    Separate multiple items with commas. The builder supports exact phrases, exclusions, and OR searches without requiring you to memorise the syntax.
                </div>

                <div class="row g-3" data-search-builder>
                    <div class="col-12 col-md-6">
                        <label for="builderIncludeTerms" class="form-label">Must include</label>
                        <input
                            id="builderIncludeTerms"
                            type="text"
                            class="form-control"
                            placeholder="e.g. ps5, headset"
                            data-builder-field="include"
                        >
                        <div class="form-text">Products should match these words or phrases.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="builderAnyTerms" class="form-label">Match any of these</label>
                        <input
                            id="builderAnyTerms"
                            type="text"
                            class="form-control"
                            placeholder="e.g. pc, console, switch"
                            data-builder-field="any"
                        >
                        <div class="form-text">Use this when either term is acceptable.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="builderExactPhrase" class="form-label">Exact phrase</label>
                        <input
                            id="builderExactPhrase"
                            type="text"
                            class="form-control"
                            placeholder="e.g. wireless gaming mouse"
                            data-builder-field="phrase"
                        >
                        <div class="form-text">This will be wrapped in quotation marks automatically.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="builderExcludeTerms" class="form-label">Exclude</label>
                        <input
                            id="builderExcludeTerms"
                            type="text"
                            class="form-control"
                            placeholder="e.g. controller, used"
                            data-builder-field="exclude"
                        >
                        <div class="form-text">Hide results containing these words or phrases.</div>
                    </div>
                </div>

                <div class="builder-preview-card mt-4">
                    <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                        <label class="form-label mb-0" for="builderPreviewOutput">Search preview</label>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-builder-copy-preview>Copy preview</button>
                    </div>
                    <textarea
                        id="builderPreviewOutput"
                        class="form-control builder-preview-output"
                        rows="3"
                        readonly
                        data-builder-preview
                    >{{ $searchQuery }}</textarea>
                    <div class="form-text mt-2">This preview is exactly what will be sent to the existing search box.</div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-secondary" data-builder-clear>Clear builder</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" data-builder-apply>Apply and search</button>
                </div>
            </div>
        </div>
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
            <div class="alert alert-info mb-0">
                <div class="fw-semibold mb-2">No products found for your current filters.</div>
                <div class="small text-muted">Try broadening the search, removing excluded terms, or opening the search builder for exact phrases and OR searches.</div>
            </div>
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
<script type="module" src="{{ asset('js/storefront/products-index.js') }}"></script>
@endpush

@extends('layouts.storefront')

@section('title', $product->name)

@section('content')
@php
    $summaryText = $product->summary ?: 'No Product summary found';
    $descriptionText = $product->description ?: 'No Product description found';
    $images = $product->images ?? collect();
    $primaryImageUrl = optional($images->first())->url;
@endphp

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 product-page-topbar">
    <a href="{{ route('products.index') }}" class="text-decoration-none">&larr; Back to products</a>

    <a href="{{ route('cart.show') }}" class="btn btn-outline-primary btn-sm">
        View cart
    </a>
</div>

<div class="row g-4 product-detail-grid">
    <div class="col-12 col-lg-6 product-media-column">
        <div class="card shadow-sm">
            <div class="card-body">
                @if ($images->count() > 0)
                    <div id="productCarousel" class="carousel slide" data-bs-ride="false">
                        <div class="carousel-inner">
                            @foreach ($images as $index => $img)
                                <div class="carousel-item @if($index === 0) active @endif">
                                    <img
                                        src="{{ $img->url }}"
                                        class="product-hero d-block w-100"
                                        alt="{{ $img->alt_text ?? $product->name }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#imageModal"
                                    >
                                </div>
                            @endforeach
                        </div>

                        @if ($images->count() > 1)
                            <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        @endif
                    </div>

                    @if ($images->count() > 1)
                        <div class="d-flex gap-2 flex-wrap mt-3 product-thumbs">
                            @foreach ($images as $index => $img)
                                <button
                                    type="button"
                                    class="thumb-btn"
                                    aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                                    data-bs-target="#productCarousel"
                                    data-bs-slide-to="{{ $index }}"
                                >
                                    <img src="{{ $img->url }}" class="thumb-img" alt="{{ $img->alt_text ?? $product->name }}">
                                </button>
                            @endforeach
                        </div>
                        <div class="form-text mt-2">Tip: click the main image to zoom.</div>
                    @endif
                @else
                    <div class="product-hero d-flex align-items-center justify-content-center text-muted">
                        No image
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6 product-info-column">
        <h1 class="h3 section-title mb-1">{{ $product->name }}</h1>
        <div class="accent-rule mb-3"></div>

        <div class="fs-4 fw-semibold mb-3" id="priceText">
            &pound;{{ number_format((float) $effectivePrice, 2) }}
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 mb-2">Summary</h2>
                <p class="text-muted mb-0">{{ $summaryText }}</p>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 mb-2">Description</h2>
                <p class="text-muted mb-0">{{ $descriptionText }}</p>
            </div>
        </div>

        <div id="messageBox" class="d-none" role="alert"></div>

        <div class="card shadow-sm purchase-card">
            <div class="card-body">
                @if ($product->has_variants && $product->variants->count())
                    <div class="mb-3">
                        <label class="form-label">Variant</label>
                        <select id="variantSelect" class="form-select">
                            <option value="">Select...</option>
                            @foreach ($product->variants as $variant)
                                <option value="{{ $variant->id }}" data-price="{{ $variant->price }}">
                                    {{ $variant->title ?: $variant->sku }} - &pound;{{ number_format((float) $variant->price, 2) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Variants may have different prices.</div>
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label">Quantity</label>
                    <input id="quantityInput" class="form-control" type="number" min="1" step="1" value="1">
                    <div class="form-text">Must be a whole number (1 or more).</div>
                </div>

                <div class="d-grid gap-2 product-action-buttons">
                    <button
                        id="addToCartButton"
                        class="btn btn-primary"
                        type="button"
                        data-product-id="{{ (int) $product->id }}"
                        data-requires-variant="{{ $product->has_variants ? '1' : '0' }}"
                    >
                        Add to cart
                    </button>

                    <button
                        type="button"
                        class="btn btn-outline-primary compare-toggle-button"
                        data-compare-toggle
                        data-product-id="{{ (int) $product->id }}"
                        data-product-slug="{{ $product->slug }}"
                        data-product-name="{{ $product->name }}"
                        data-product-price="{{ number_format((float) $effectivePrice, 2, '.', '') }}"
                        data-product-summary="{{ $summaryText }}"
                        data-product-url="{{ route('products.show', $product) }}"
                        data-product-image="{{ $primaryImageUrl ?: '' }}"
                    >
                        Add to compare
                    </button>
                </div>
                <div class="form-text mt-2">Select up to two products to compare.</div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h2 class="h6 mb-0">{{ $product->name }}</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-0">
                @if ($images->count() > 0)
                    <div id="productCarouselModal" class="carousel slide" data-bs-ride="false">
                        <div class="carousel-inner">
                            @foreach ($images as $index => $img)
                                <div class="carousel-item @if($index === 0) active @endif">
                                    <img src="{{ $img->url }}" class="d-block w-100 rounded" style="max-height: 75vh; object-fit: contain; background:#111;" alt="{{ $img->alt_text ?? $product->name }}">
                                </div>
                            @endforeach
                        </div>

                        @if ($images->count() > 1)
                            <button class="carousel-control-prev" type="button" data-bs-target="#productCarouselModal" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#productCarouselModal" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('js/storefront/products-show.js') }}"></script>
<script type="module" src="{{ asset('js/storefront/product-compare.js') }}"></script>
@endpush
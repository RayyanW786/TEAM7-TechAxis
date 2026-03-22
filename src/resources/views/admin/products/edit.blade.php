@extends('layouts.main')

@section('title', 'Edit Product | Admin')

@push('styles')
    <link href="{{ asset('css/admin/admin-shell.css') }}" rel="stylesheet">
    <style>
        .product-editor-shell {
            display: grid;
            gap: 1rem;
        }

        .product-editor-grid {
            display: grid;
            gap: 1rem;
        }

        .product-editor-section {
            display: grid;
            gap: 1rem;
            padding: 1.2rem;
            border: 1px solid var(--admin-border);
            border-radius: 24px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.035), rgba(255, 255, 255, 0.02));
        }

        .product-editor-section h2 {
            font-size: 1.08rem;
        }

        .product-editor-note {
            color: var(--admin-muted);
            font-size: 0.92rem;
            line-height: 1.6;
        }

        .product-field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.9rem;
        }

        .product-field {
            display: grid;
            gap: 0.42rem;
        }

        .product-field label {
            color: var(--admin-muted);
            font-size: 0.88rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .product-field input,
        .product-field select,
        .product-field textarea {
            width: 100%;
            min-height: 48px;
            padding: 0.8rem 0.95rem;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.11);
            background: #101010;
            color: #fff;
            transition: 0.18s ease;
        }

        .product-field textarea {
            min-height: 140px;
            resize: vertical;
        }

        .product-field input:focus,
        .product-field select:focus,
        .product-field textarea:focus {
            outline: none;
            border-color: rgba(220, 38, 38, 0.42);
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.12);
        }

        .product-field--full {
            grid-column: 1 / -1;
        }

        .product-repeat-list {
            display: grid;
            gap: 0.85rem;
        }

        .product-repeat-item {
            display: grid;
            gap: 0.85rem;
            padding: 1.15rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.04);
        }

        .product-repeat-item-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .product-repeat-item-head strong {
            font-size: 0.98rem;
        }

        .product-repeat-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.8fr) minmax(0, 1.4fr) minmax(160px, 0.7fr);
            gap: 0.75rem;
            align-items: end;
        }

        .product-repeat-grid--variants {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }

        .product-image-preview {
            grid-column: 1 / -1;
            display: grid;
            gap: 0.6rem;
        }

        .product-image-preview img {
            width: 100%;
            height: 220px;
            object-fit: contain;
            border-radius: 16px;
            border: 1px solid var(--admin-border);
            background: linear-gradient(180deg, #0f0f0f, #1a1a1a);
            padding: 0.75rem;
        }

        .product-actions-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
            justify-content: space-between;
        }

        .product-actions-row .admin-inline-form {
            width: auto;
        }

        .product-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            font-weight: 700;
            color: var(--admin-text);
            min-height: 48px;
            padding: 0.8rem 0.95rem;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.11);
            background: #101010;
            width: 100%;
            cursor: pointer;
            line-height: 1.35;
        }

        .product-toggle input[type="checkbox"] {
            width: 18px;
            min-width: 18px;
            height: 18px;
            min-height: 18px;
            margin: 0;
            padding: 0;
            accent-color: var(--admin-accent);
            border-radius: 4px;
            box-shadow: none;
            flex: 0 0 auto;
        }

        .product-empty-state {
            padding: 1rem;
            border-radius: 16px;
            border: 1px dashed rgba(255, 255, 255, 0.16);
            color: var(--admin-muted);
            background: rgba(255, 255, 255, 0.02);
        }

        .product-section-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
        }

        .product-small-button {
            min-height: 40px;
            padding: 0 0.9rem;
            border-radius: 999px;
            border: 1px solid var(--admin-border);
            background: var(--admin-surface-2);
            color: var(--admin-text);
            font-weight: 700;
        }

        .product-small-button:hover {
            border-color: rgba(220, 38, 38, 0.35);
            background: rgba(220, 38, 38, 0.12);
        }

        .product-repeat-item.is-removed {
            border-color: rgba(248, 113, 113, 0.36);
            background: rgba(248, 113, 113, 0.08);
        }

        @media (max-width: 980px) {
            .product-field-grid,
            .product-repeat-grid,
            .product-repeat-grid--variants {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 680px) {
            .product-field-grid,
            .product-repeat-grid,
            .product-repeat-grid--variants {
                grid-template-columns: 1fr;
            }

            .product-actions-row {
                align-items: stretch;
            }
        }
    </style>
@endpush

@section('content')
    <div
        class="admin-shell"
        data-product-form-root
        data-mode="edit"
        data-product-id="{{ $product->id }}"
        data-initial-category-id="{{ $product->category_id }}"
        data-initial-brand-id="{{ $product->brand_id }}"
    >
        <div class="admin-shell-header">
            <div>
                <span class="admin-shell-kicker">Products</span>
                <h1 class="admin-shell-title">Edit product</h1>
                <p class="admin-shell-copy">Update the product, manage variants, and keep the image gallery aligned with the storefront.</p>
                <div class="admin-inline-form" style="margin-top:0.9rem;">
                    <span class="admin-badge">Multiple images</span>
                    <span class="admin-badge">Variant support</span>
                    <span class="admin-badge">Stock thresholds</span>
                </div>
            </div>
            <div class="admin-shell-actions">
                <a href="{{ route('admin.products.index') }}" class="admin-shell-button">Back to products</a>
            </div>
        </div>

        <section class="admin-detail-card product-editor-shell">
            <div data-product-alert></div>

            <div class="product-editor-grid">
                <section class="product-editor-section">
                    <div class="admin-panel-head">
                        <div>
                            <h2>Core details</h2>
                            <p>Adjust the identity and content customers see on the storefront.</p>
                        </div>
                    </div>

                    <div class="product-field-grid">
                        <div class="product-field">
                            <label for="name">Name</label>
                            <input type="text" id="name" placeholder="Product name" required value="{{ $product->name }}">
                        </div>
                        <div class="product-field">
                            <label for="slug">Slug</label>
                            <input type="text" id="slug" placeholder="product-slug" required value="{{ $product->slug }}">
                        </div>
                        <div class="product-field">
                            <label for="sku">SKU</label>
                            <input type="text" id="sku" placeholder="Optional parent SKU" value="{{ $product->sku }}">
                        </div>
                        <div class="product-field">
                            <label for="status">Status</label>
                            <select id="status">
                                @foreach (['active', 'draft', 'archived'] as $status)
                                    <option value="{{ $status }}" @selected(($product->status?->value ?? $product->status) === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="product-field">
                            <label for="category_id">Category</label>
                            <select id="category_id" required>
                                <option value="">Loading categories...</option>
                            </select>
                        </div>
                        <div class="product-field">
                            <label for="brand_id">Brand</label>
                            <select id="brand_id">
                                <option value="">No brand</option>
                            </select>
                        </div>
                        <div class="product-field product-field--full">
                            <label for="summary">Summary</label>
                            <textarea id="summary" placeholder="Short customer-facing summary">{{ $product->summary }}</textarea>
                        </div>
                        <div class="product-field product-field--full">
                            <label for="description">Description</label>
                            <textarea id="description" placeholder="Full product description">{{ $product->description }}</textarea>
                        </div>
                    </div>
                </section>

                <section class="product-editor-section">
                    <div class="admin-panel-head">
                        <div>
                            <h2>Pricing and stock</h2>
                            <p>Keep pricing, stock, and the low-stock threshold in sync with your catalogue strategy.</p>
                        </div>
                    </div>

                    <div class="product-field-grid">
                        <div class="product-field">
                            <label for="price">Price</label>
                            <input type="number" id="price" min="0" step="0.01" placeholder="0.00" value="{{ $product->price }}" required>
                        </div>
                        <div class="product-field">
                            <label for="stock_quantity">Stock quantity</label>
                            <input type="number" id="stock_quantity" min="0" step="1" placeholder="0" value="{{ $product->stock_quantity }}">
                        </div>
                        <div class="product-field">
                            <label for="low_stock_threshold">Low stock threshold</label>
                            <input type="number" id="low_stock_threshold" min="0" step="1" value="{{ $product->low_stock_threshold }}">
                        </div>
                        <div class="product-field">
                            <label for="has_variants">Product type</label>
                            <label class="product-toggle" for="has_variants">
                                <input type="checkbox" id="has_variants" @checked($product->has_variants)>
                                Use variants for this product
                            </label>
                        </div>
                    </div>
                    <p class="product-editor-note">Variant stock replaces the simple stock value when variants are enabled. Use the row controls below to add or update variant options.</p>
                </section>

                <section class="product-editor-section">
                    <div class="admin-panel-head">
                        <div>
                            <h2>Images</h2>
                            <p>Manage the product gallery from one list. Rows are saved in display order.</p>
                        </div>
                        <div class="product-section-actions">
                            <button type="button" class="product-small-button" data-add-image>Add image row</button>
                        </div>
                    </div>
                    <div class="product-repeat-list" data-images-list></div>
                    <div class="product-empty-state" data-images-empty>Loading current images...</div>
                </section>

                <section class="product-editor-section">
                    <div class="admin-panel-head">
                        <div>
                            <h2>Variants</h2>
                            <p>Update existing variants and add new ones for colour, size, storage, or bundle choices.</p>
                        </div>
                        <div class="product-section-actions">
                            <button type="button" class="product-small-button" data-add-variant>Add variant row</button>
                        </div>
                    </div>
                    <div class="product-repeat-list" data-variants-list></div>
                    <div class="product-empty-state" data-variants-empty>Loading current variants...</div>
                </section>

                <div class="product-actions-row">
                    <p class="product-editor-note mb-0">Save the product first, then the image gallery and variants will be synchronised automatically.</p>
                    <div class="admin-inline-form">
                        <button type="button" class="admin-submit" data-save-product>Update product</button>
                        <button type="button" class="admin-shell-button" data-delete-product>Delete product</button>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/admin/edit_product.js') }}"></script>
@endpush

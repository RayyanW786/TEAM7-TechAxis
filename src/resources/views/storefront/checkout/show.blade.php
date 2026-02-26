@extends('layouts.storefront')

@section('title', 'Checkout')

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 checkout-page-topbar">
    <h1 class="h3 mb-0">Checkout</h1>
    <a class="btn btn-outline-secondary checkout-back-button" href="{{ route('cart.show') }}">Back to cart</a>
</div>

<div id="messageBox" class="d-none" role="alert"></div>

<div class="row g-4 checkout-layout">
    <div class="col-12 col-lg-7 checkout-main-column">
        <div class="card shadow-sm checkout-main-card">
            <div class="card-body">
                <h2 class="h5 mb-3">Delivery</h2>

                <div id="addressSelectBlock" class="d-none">
                    <div class="mb-3">
                        <label class="form-label">Shipping address</label>
                        <select id="shippingSelect" class="form-select"></select>
                    </div>

                    <div class="form-check mb-3">
                        <input id="billingSameCheckbox" class="form-check-input" type="checkbox" checked>
                        <label class="form-check-label" for="billingSameCheckbox">Billing same as shipping</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Billing address</label>
                        <select id="billingSelect" class="form-select"></select>
                    </div>
                </div>

                <div id="addressFormBlock" class="d-none">
                    <div class="alert alert-info">Add an address to continue.</div>

                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Label</label>
                            <input id="address_label" class="form-control" type="text" maxlength="50">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Recipient name</label>
                            <input id="address_recipient" class="form-control" type="text" maxlength="255">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Line 1 *</label>
                            <input id="address_line1" class="form-control" type="text" maxlength="255">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Line 2</label>
                            <input id="address_line2" class="form-control" type="text" maxlength="255">
                        </div>

                        <div class="col-6">
                            <label class="form-label">City *</label>
                            <input id="address_city" class="form-control" type="text" maxlength="255">
                        </div>

                        <div class="col-6">
                            <label class="form-label">Region</label>
                            <input id="address_region" class="form-control" type="text" maxlength="255">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Postal code *</label>
                            <input id="address_postal" class="form-control" type="text" maxlength="50">
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input id="address_default" class="form-check-input" type="checkbox" checked>
                                <label class="form-check-label" for="address_default">Set as default shipping</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <button id="saveAddressButton" class="btn btn-outline-primary" type="button">Save address</button>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <h2 class="h5 mb-3">Extras</h2>

                <div class="mb-3">
                    <label class="form-label">Discount code (optional)</label>
                    <input id="discountCodeInput" class="form-control" type="text" maxlength="100">
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes (optional)</label>
                    <textarea id="notesInput" class="form-control" rows="3"></textarea>
                </div>

                <button id="placeOrderButton" class="btn btn-success w-100" type="button">Place order</button>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5 checkout-summary-column">
        <div class="card shadow-sm checkout-summary-card">
            <div class="card-body">
                <h2 class="h5 mb-3">Summary</h2>
                <div id="summaryContainer" class="list-group mb-3"></div>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Total</span>
                    <span class="fs-5 fw-bold" id="totalText">&pound;0.00</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('js/storefront/checkout-show.js') }}"></script>
@endpush

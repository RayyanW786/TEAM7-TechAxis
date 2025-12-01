@extends('layouts.storefront')

@section('title', 'Cart')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h3 mb-0">Your Cart</h1>
    <a class="btn btn-primary" href="{{ route('checkout.show') }}">Checkout</a>

</div>

<div id="messageBox" class="d-none" role="alert"></div>

<div class="card shadow-sm">
    <div class="card-body">
        <div id="emptyState" class="alert alert-info d-none mb-0">Your cart is empty.</div>
        <div id="itemsContainer" class="list-group"></div>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Total</span>
        <span class="fs-5 fw-bold" id="totalText">£0.00</span>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('js/storefront/cart-show.js') }}"></script>
@endpush

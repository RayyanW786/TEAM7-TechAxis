@extends('layouts.storefront')

@section('title', 'Order #' . $order->id)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/order.css') }}">
@endpush

@section('content')
    @php
        $isCompletedOrder = $order->status === \App\Enums\OrderStatus::Completed;
        $total = 0;
        foreach ($order->items as $it) {
            $total += (float) $it->unit_price * (int) $it->quantity;
        }
    @endphp

    <div class="container">
        <div class="topbar">
            <a class="topbar-link" href="{{ route('products.index') }}">← Products</a>
            <h1 class="page-title">Order #{{ $order->id }}</h1>
            <a class="topbar-link" href="{{ route('cart.show') }}">Cart</a>
        </div>

        <div class="panel">
            <div class="status">Status: {{ $order->status }}</div>

            <div class="items">
                @foreach ($order->items as $item)
                    <div class="item">
                        <div class="item-name">
                            {{ $item->product?->name ?? 'Product' }}
                            @if ($item->variant?->title) • {{ $item->variant->title }} @endif
                        </div>
                        <div class="item-meta">
                            {{ $item->quantity }} × £{{ number_format((float) $item->unit_price, 2) }}
                        </div>
                        <div class="item-line">
                            £{{ number_format((float) $item->unit_price * (int) $item->quantity, 2) }}
                        </div>
                        @if ($isCompletedOrder && $item->product && !auth()->user()->isAdmin())
                            @php
                                $hasReview = isset($reviewedProductIds[$item->product->id]);
                                $reviewUrl = route('products.show', $item->product->slug) . '?write_review=1&order_item_id=' . $item->id;
                            @endphp
                            <div class="item-review-action">
                                <a href="{{ $reviewUrl }}" class="item-review-link">
                                    {{ $hasReview ? 'Edit review' : 'Review product' }}
                                </a>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <br>
            <div class="address">
                <h3>Shipping Address</h3>
                @if ($order->shippingAddress)
                    <p>{{ $order->shippingAddress->name }}</p>
                    <p>{{ $order->shippingAddress->line1 }}</p>
                    @if ($order->shippingAddress->line2)
                        <p>{{ $order->shippingAddress->line2 }}</p>
                    @endif
                    <p>{{ $order->shippingAddress->city }}, {{ $order->shippingAddress->postal_code }}</p>
                    <p>{{ $order->shippingAddress->country }}</p>
                @else
                    <p>No shipping address provided.</p>
                @endif
            </div>

            <div class="total">
                <span>Total</span>
                <strong>£{{ number_format($total, 2) }}</strong>
            </div>
            @auth
                @if(auth()->user()->isAdmin())
                    <form method="POST" action="/api/orders/{{ $order->id }}/status" class="status-form">
                        @csrf
                        @method('PATCH')
                        <label for="status">Update Status:</label>
                        <select name="status" id="status" onchange="this.form.submit()">
                            @foreach (\App\Enums\OrderStatus::cases() as $status)
                                <option value="{{ $status->value }}" {{ $order->status->value === $status->value ? 'selected' : '' }}>
                                    {{ ucfirst($status->value) }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
            @endauth
        </div>
    </div>
@endsection

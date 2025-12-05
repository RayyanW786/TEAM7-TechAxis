@extends('layouts.storefront')

@section('title', 'Order #' . $order->id)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/order.css') }}">
@endpush

@section('content')
@php
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
                </div>
            @endforeach
        </div>

        <div class="total">
            <span>Total</span>
            <strong>£{{ number_format($total, 2) }}</strong>
        </div>
    </div>
</div>
@endsection

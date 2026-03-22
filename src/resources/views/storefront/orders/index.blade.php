@extends('layouts.storefront')

@section('title', 'My Orders')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/order.css') }}">
@endpush

@section('content')
    <div class="container">
        <div class="topbar">
            <a class="topbar-link" href="{{ route('customer.dashboard') }}">Account</a>
            <h1 class="page-title">My orders</h1>
            <a class="topbar-link" href="{{ route('products.index') }}">Shop</a>
        </div>

        <div class="panel order-history-panel">
            <div class="status">Review your order history and open an order to see delivery details, support actions, and item reviews.</div>

            @if ($orders->count())
                <div class="order-history-list">
                    @foreach ($orders as $order)
                        <a href="{{ route('orders.show', $order) }}" class="order-history-card">
                            <div class="order-history-card__head">
                                <strong>Order #{{ $order->id }}</strong>
                                <span class="order-history-card__status">{{ ucfirst($order->status->value ?? (string) $order->status) }}</span>
                            </div>
                            <div class="order-history-card__meta">
                                <span>{{ $order->items_count }} item{{ $order->items_count === 1 ? '' : 's' }}</span>
                                <span>{{ optional($order->created_at)->format('d M Y') }}</span>
                            </div>
                            <div class="order-history-card__total">
                                Total: £{{ number_format((float) $order->total_amount, 2) }}
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="order-history-pagination">
                    {{ $orders->links() }}
                </div>
            @else
                <div class="order-history-empty">
                    <h2>No orders yet</h2>
                    <p>You have not placed any orders yet. Once you check out, your orders will appear here.</p>
                    <a href="{{ route('products.index') }}" class="item-review-link">Browse products</a>
                </div>
            @endif
        </div>
    </div>
@endsection

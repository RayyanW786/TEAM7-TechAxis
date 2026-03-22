@extends('layouts.main')
@section('title', 'Orders | Tech Axis')
@push('styles')
    <link href="{{ asset('css/admin/orders.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
@endpush
@section('content')
    <div class="orders-container">
        <h1>Orders</h1>
        <div class="orders-list">
        </div>
    </div>
@endsection
@push('scripts')
    <script src="{{ asset('js/admin/admin_orders.js') }}"></script>
@endpush
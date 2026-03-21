@extends('layouts.main')
@section('title', 'Admin Dashboard - Tech Axis')
@push('styles')
    <link href="{{ asset('css/admin/dashboard.css') }}" rel="stylesheet">
@endpush
@section('content')
    <div class="dashboard-container">
        <h1>Admin Dashboard</h1>
        <p>Welcome to the admin dashboard!</p>
        <div class="admin-links">
            <a href="{{ route('admin.orders') }}" class="admin-panel">
                <strong>📦 Orders</strong>
                <br>
                Manage customer orders
            </a>
            <a href="{{ route('admin.products.index') }}" class="admin-panel">
                <strong>🛒 Products</strong>
                <br>
                Manage product listings and stock
            </a>
            <a href="{{ url('/admin/reviews') }}" class="admin-panel">
                <strong>Reviews</strong>
                <br>
                Monitor product and service reviews
            </a>
            <a href="{{ route('under-construction') }}" class="admin-panel">
                <strong>👥 Support Tickets</strong>
                <br>
                Manage and resolve customer support tickets
            </a>
            <a href="{{ route('password.change') }}" class="admin-panel">
                🔐 Change Password
            </a>
            <a href="{{ route('home') }}" class="admin-panel"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <strong>🚪 Log-out</strong>
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </div>
    </div>
@endsection

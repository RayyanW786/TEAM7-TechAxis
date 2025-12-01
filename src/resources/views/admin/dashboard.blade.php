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
            <a href="" class="admin-panel">
                <strong>📦 Orders</strong>
                <br>
                Manage customer orders   
            </a>
            <a href="" class="admin-panel">
                <strong>🛒 Products</strong>
                <br>
                Manage product listings and stock
            </a>
            <a href="" class="admin-panel">
                <strong>🔐 Change Password</strong>
            </a>
        </div>
    </div>
@endsection
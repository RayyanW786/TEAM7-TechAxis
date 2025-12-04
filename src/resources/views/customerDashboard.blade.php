@extends('layouts.main')
@section('title', 'Dashboard | Tech Axis')
@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&family=Roboto:wght@300;400;500&display=swap"
        rel="stylesheet">

    <!-- Dashboard CSS -->
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">
@endpush
@section('content')
    <div class="customerdashboard-container">
        <h1 class="customerdashboard-title">Customer Dashboard</h1>

        <div class="dash-actions">
            <a href="{{ url('/orders') }}" class="dash-box">
                <h2>View Orders</h2>
                <p>Check your order history and track deliveries.</p>
            </a>

            <a href="{{ route('under-construction') }}" class="dash-box">
                <h2>Manage Account</h2>
                <p>Edit your personal details or change your password.</p>
            </a>

            <a href="{{ url('/contact') }}" class="dash-box">
                <h2>Contact Support</h2>
                <p>Need help? Open a new support ticket.</p>
            </a>
            <a href="{{ route('home') }}" class="dash-box"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <h2>Log-out</h2>
                <p>Sign out of your account securely.</p>
            </a>
        </div>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
        </form>
    </div>
@endsection
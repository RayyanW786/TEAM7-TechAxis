@extends('layouts.main')
@section('title', 'About | Tech Axis')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/about.css') }}">
@endpush
@section('content')

    <main class="ta-main">
        <!-- Page title panel -->
        <section class="ta-panel ta-panel--title">
            <h1>About Tech Axis</h1>
        </section>

        <!-- Center logo -->
        <div class="ta-logo-large">
            <img src="{{ asset('images/techaxis-logo.png') }}" alt="Tech Axis logo">
        </div>

        <!-- Content panel -->
        <section class="ta-panel ta-panel--content">
            <p class="ta-intro-text">
                Our company vision and mission statement …
            </p>

            <div class="ta-links">
                <a href="#vision" class="ta-link">OUR VISION</a>
                <a href="#mission" class="ta-link">OUR MISSION</a>
            </div>

            <div id="vision" class="ta-subsection">
                <h2>Our Vision</h2>
                <p>
                    To become a leading online tech retailer that makes high-quality hardware
                    and accessories accessible, affordable, and easy to discover.
                </p>
            </div>

            <div id="mission" class="ta-subsection">
                <h2>Our Mission</h2>
                <p>
                    To provide a smooth and secure shopping experience, combining a modern
                    storefront with reliable customer support and a clear focus on user needs.
                </p>
            </div>

            <div
                class="ta-service-reviews"
                data-service-reviews-root
                data-login-url="{{ route('login.page') }}"
                data-is-authenticated="{{ auth()->check() ? '1' : '0' }}"
            >
                <div class="ta-service-reviews-head">
                    <h2>Customer testimonials</h2>
                    <p>Read what customers say about the Tech Axis experience and leave your own service review.</p>
                </div>
                <div data-service-reviews-app></div>
            </div>
        </section>
    </main>
@endsection

@push('scripts')
<script type="module" src="{{ asset('js/storefront/service-reviews.js') }}"></script>
@endpush

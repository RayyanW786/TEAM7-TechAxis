<!-- Tech Axis HomePage
By E
-->
@extends('layouts.main')
@section('title', 'Tech Axis - Gaming & Tech Products')
@push('styles')
    <link href="{{ asset('css/techaxis.css') }}" rel="stylesheet">
@endpush
@section('content')

    <!-- Site Banner -->

    <section class="Banner">
        <div class="container banner-content">

            <h1> LEVEL UP YOUR GAME </h1>
            <p> Explore elite gaming gear, powerful tech, and exclusive merchandise — crafted for players who demand more.
            <p>

                <a href="{{ url('/products') }}" class="primary-btn"> Latest Arrivals!</a>

            <h2 class="explore-text">EXPLORE NOW</h2>

        </div>
    </section>

    <!-- Categories-->

    <section class="categories-section">

        <h2 class="section-heading">Shop by Category</h2>

        <div class="category-boxes">

            <a href="{{ route('products.index', ['category' => 'consoles-accessories']) }}" class="category-item">
                <div class="category-icon">🎮</div>
                <h3>Consoles & Accessories</h3>
            </a>
            <a href="{{ route('products.index', ['category' => 'pc-gaming']) }}" class="category-item">
                <div class="category-icon">🖥️</div>
                <h3>PC Gaming</h3>
            </a>
            <a href="{{ route('products.index', ['category' => 'merchandise']) }}" class="category-item">
                <div class="category-icon">👕</div>
                <h3>Merchandise</h3>
            </a>
            <a href="{{ route('products.index', ['category' => 'pc-components']) }}" class="category-item">
                <div class="category-icon">⚙️</div>
                <h3>PC Components</h3>
            </a>

            </a>
            <a href="{{ route('products.index', ['category' => 'phones-gadgets']) }}" class="category-item">
                <div class="category-icon">📱</div>
                <h3>Phones & Gadgets</h3>
            </a>
        </div>
    </section>


    <!-- Featured Items -->
    <section class="item section">
        <h2 class="section-heading">Featured Items</h2>

        <div
            class="featured-grid"
            id="featured-products-grid"
            data-api-url="{{ url('/api/products/featured?limit=6') }}"
            data-product-base-url="{{ url('/products') }}"
        ></div>

        <noscript>
            <p style="text-align:center; opacity:.8;">Enable JavaScript to see featured products.</p>
        </noscript>
    </section>
    
    @push('scripts')
        <script src="{{ asset('js/home-featured-products.js') }}" defer></script>
    @endpush


@endsection
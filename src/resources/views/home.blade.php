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
        <div class="featured-grid">

            <!--  1st product  -->
            <div class="featured-item">

                <img src="{{asset('images/mouse.jpg') }}" alt="Quantum Pro Gaming Mouse" class="product-image">
                <h3 class="product-title"><a href="{{ url('/product/1') }}">Quantum Pro Gaming Mouse

                    </a></h3>

                <p>High-precision RGB gaming mouse with customizable buttons</p>
                <div class="product-price">£79.99</div>
                <a href="{{ url('/cart') }}" class="primary-btn">Add to Cart</a>
            </div>

            <!--  2nd product  -->

            <div class="featured-item">

                <img src="{{asset('images/keyboard.jpg') }}" alt="Mechanical Keyboard" class="product-image">
                <h3 class="product-title"><a href="{{ url('/product/1') }}">Corsair K100 RGB Mechanical Keyboard

                    </a></h3>


                <p> gaming keyboard with OPX optical-mechanical switches</p>
                <div class="product-price">£129.99</div>
                <a href="{{ url('/cart') }}" class="primary-btn">Add to Cart</a>
            </div>


            <!--  3rd product  -->
            <div class="featured-item">

                <img src="{{asset('images/monitor.jpg') }}" alt="Mechanical Keyboard" class="product-image">
                <h3 class="product-title"><a href="{{ url('/product/1') }}">Gaming Monitor

                    </a></h3>

                <p>ASUS ROG Swift PG279QM, 27" 1440p gaming monitor with 240Hz refresh rate</p>
                <div class="product-price">£399.99</div>
                <a href="{{ url('/cart') }}" class="primary-btn">Add to Cart</a>
            </div>



            <!--  4th product  -->
            <div class="featured-item">

                <img src="{{asset('images/headset.jpg') }}" alt="Mechanical Keyboard" class="product-image">
                <h3 class="product-title"><a href="{{ url('/product/1') }}">Wireless Headset

                    </a></h3>

                <p>Multi-platform gaming headset with active noise cancellation</p>
                <div class="product-price">£149.99</div>
                <a href="{{ url('/cart') }}" class="primary-btn">Add to Cart</a>
            </div>

        </div>
    </section>

@endsection
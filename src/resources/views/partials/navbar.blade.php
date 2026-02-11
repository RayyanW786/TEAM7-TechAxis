<header class="webpage-header">
    <div class="web-header-content">

        <a href="{{ url('/') }}" class="brand-logo">
            <img src="{{ asset('images/TechAxis-LOGO.png') }}" alt="Tech Axis Logo">
        </a>


        <nav class="main-nav">
            <ul>


                <ul class="navmenu">
                    <li><a href="{{ url('/') }}">Home</a></li>
                    <li><a href="{{ route('products.index') }}">Shop</a></li>
                    <li><a href="{{ url('/about') }}">About</a></li>
                    <li><a href="{{ url('/contact') }}">Contact</a></li>
                    @auth
                        <li><a href="{{ url('/dashboard') }}">Account</a></li>
                    @endauth
                </ul>
        </nav>

        <div class="user-controls">
            <a href="{{ route('cart.show') }}" title="Shopping Cart">🛒</a>
            @guest
                <a href="{{ url('/login') }}" title="Account">👤</a>
            @endguest
        </div>
    </div>
</header>
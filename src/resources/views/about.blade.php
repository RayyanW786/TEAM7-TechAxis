<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About Tech Axis</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">

    <!-- About page styles -->
    <link rel="stylesheet" href="{{ asset('css/about.css') }}">
</head>
<body class="about-page">

    <!-- Top navigation bar -->
    <header class="ta-header">
        <div class="ta-header-inner">
            <div class="ta-logo">
                <img src="{{ asset('images/techaxis-logo.png') }}" alt="Tech Axis logo">
            </div>

            <nav class="ta-nav">
                <a href="{{ url('/') }}" class="ta-nav-link">Home</a>
                <a href="{{ url('/shop') }}" class="ta-nav-link">Shop</a>
                <a href="{{ url('/about') }}" class="ta-nav-link ta-nav-link--active">About</a>
                <a href="{{ url('/contact') }}" class="ta-nav-link">Contact</a>
                <a href="{{ url('/account') }}" class="ta-nav-link">Account</a>
            </nav>

            <div class="ta-icons">
                <span class="ta-icon">🔍</span>
                <span class="ta-icon">🛒</span>
            </div>
        </div>
    </header>

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
        </section>
    </main>

    <!-- Footer bar -->
    <footer class="ta-footer">
        <div class="ta-footer-inner">
            <div class="ta-footer-links">
                <a href="{{ url('/about') }}">About</a>
                <a href="{{ url('/contact') }}">Contact</a>
                <a href="{{ url('/support') }}">Support</a>
            </div>
            <div class="ta-footer-copy">
                © 2025 Tech Axis
            </div>
        </div>
    </footer>

</body>
</html>

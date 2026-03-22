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
        <section class="ta-panel ta-panel--title">
            <h1>About Tech Axis</h1>
            <p class="ta-tagline">
                Your trusted destination for modern technology, reliable service, and a smoother online shopping experience.
            </p>
        </section>

        <div class="ta-logo-large">
            <img src="{{ asset('images/techaxis-logo.png') }}" alt="Tech Axis logo">
        </div>

        <section class="ta-panel ta-panel--content">
            <p class="ta-intro-text">
                Tech Axis is an online technology retailer built to make quality tech products easier to discover, compare,
                and purchase. We offer a customer-focused shopping experience designed around convenience, clarity, and trust,
                helping customers find the right products for work, study, gaming, and everyday use.
            </p>

            <p class="ta-intro-text">
                We believe technology should be accessible and straightforward. That is why Tech Axis focuses on a clean,
                modern storefront that makes browsing simple, product information easy to understand, and the overall journey
                from discovery to checkout more efficient and enjoyable.
            </p>

            <div class="ta-links">
                <a href="#story" class="ta-link">OUR STORY</a>
                <a href="#vision" class="ta-link">OUR VISION</a>
                <a href="#mission" class="ta-link">OUR MISSION</a>
                <a href="#choose-us" class="ta-link">WHY CHOOSE US</a>
            </div>

            <div id="story" class="ta-subsection">
                <h2>Our Story</h2>
                <p>
                    Tech Axis was created with the goal of building a modern digital retail space for technology products.
                    As technology continues to play a central role in everyday life, we wanted to create a platform where
                    customers could shop with confidence, explore products with ease, and enjoy a more professional and
                    user-friendly online experience.
                </p>
            </div>

            <div id="vision" class="ta-subsection">
                <h2>Our Vision</h2>
                <p>
                    To become a leading online tech retailer that makes high-quality hardware and accessories accessible,
                    affordable, and easy to discover for a wide range of customers.
                </p>
            </div>

            <div id="mission" class="ta-subsection">
                <h2>Our Mission</h2>
                <p>
                    Our mission is to provide a smooth and secure shopping experience through a modern storefront,
                    dependable service, and a strong focus on customer needs. We aim to make technology shopping
                    simpler, faster, and more convenient without compromising on quality or trust.
                </p>
            </div>

            <div id="choose-us" class="ta-subsection">
                <h2>Why Choose Tech Axis</h2>
                <p>
                    At Tech Axis, we aim to deliver more than just products. We aim to provide a reliable shopping
                    experience that customers can return to with confidence.
                </p>

                <ul class="ta-tech-list">
                    <li><strong>Quality technology products</strong> selected to support modern personal, academic, and professional needs.</li>
                    <li><strong>Simple and clear browsing</strong> that helps customers find what they need quickly and efficiently.</li>
                    <li><strong>Affordable access to technology</strong> with a strong focus on value, usability, and reliability.</li>
                    <li><strong>A consistent and professional online experience</strong> designed to feel smooth from homepage to checkout.</li>
                    <li><strong>Customer-focused service</strong> that puts clarity, trust, and ease of use at the centre of the platform.</li>
                </ul>
            </div>
        </section>
    </main>
@endsection
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Dashboard - Tech Axis</title>

    <!-- Tech Axis Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">

    <!-- Dashboard CSS -->
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">
</head>
<body>

    <!-- NAVBAR (from partner's code) -->
    <header class="webpage-header">
        <div class="web-header-content container">
            <div class="brand-logo">
                <img src="{{ asset('images/logo.png') }}" alt="Tech Axis Logo">
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="{{ url('/') }}">Home</a></li>
                    <li><a href="{{ url('/products') }}">Products</a></li>
                    <li><a href="{{ url('/about') }}">About</a></li>
                    <li><a href="{{ url('/contact') }}">Contact</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- DASHBOARD CONTENT -->
    <div class="customerdashboard-container">
        <h1 class="customerdashboard-title">Customer Dashboard</h1>

        <div class="dash-actions">
            <a href="{{ url('/orders') }}" class="dash-box">
                <h2>View Orders</h2>
                <p>Check your order history and track deliveries.</p>
            </a>

            <a href="{{ url('/account/settings') }}" class="dash-box">
                <h2>Manage Account</h2>
                <p>Edit your personal details or change your password.</p>
            </a>

            <a href="{{ url('/support') }}" class="dash-box">
                <h2>Contact Support</h2>
                <p>Need help? Open a new support ticket.</p>
            </a>
        </div>

        <div class="recent-orders-section">
            <h2 class="section-title">Recent Orders</h2>
            <div class="orders-table">
                <div class="order-row">
                    <div class="order-col order-id">#1024</div>
                    <div class="order-col order-item">Corsair K100 RGB Keyboard</div>
                    <div class="order-col order-status status-delivered">Delivered</div>
                    <div class="order-col order-date">12 Feb 2025</div>
                </div>

                <div class="order-row">
                    <div class="order-col order-id">#1023</div>
                    <div class="order-col order-item">ROG Swift 27" Monitor</div>
                    <div class="order-col order-status status-shipped">Shipped</div>
                    <div class="order-col order-date">9 Feb 2025</div>
                </div>

                <div class="order-row">
                    <div class="order-col order-id">#1022</div>
                    <div class="order-col order-item">Wireless RGB Headset</div>
                    <div class="order-col order-status status-processing">Processing</div>
                    <div class="order-col order-date">8 Feb 2025</div>
                </div>

                <div class="order-row">
                    <div class="order-col order-id">#1021</div>
                    <div class="order-col order-item">Quantum Pro Gaming Mouse</div>
                    <div class="order-col order-status status-cancelled">Cancelled</div>
                    <div class="order-col order-date">4 Feb 2025</div>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER (from partner's code) -->
    <footer class="site-footer">
        <div class="footer-content container">
            <p>&copy; 2025 Tech Axis. All Rights Reserved.</p>
            <div class="footer-links">
                <a href="{{ url('/') }}">Home</a>
                <a href="{{ url('/products') }}">Products</a>
                <a href="{{ url('/about') }}">About</a>
                <a href="{{ url('/contact') }}">Contact</a>
            </div>
        </div>
    </footer>

</body>
</html>

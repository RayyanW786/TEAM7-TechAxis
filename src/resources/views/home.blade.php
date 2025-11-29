<!-- Tech Axis HomePage
By E
--> 

<!DOCTYPE html>
 <html lang="en-GB">

    <head>

     <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Tech Axis - Gaming & Tech Products</title>
<!--ICON FAVI -->
<link rel="icon" href="/images/TechAxis-LOGO.png" type="image/png">
<!--FONT-->
        <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Roboto:wght@300;400;700&display=swap" 
        rel="stylesheet">

<!-- Css Team Style Sheet-->
<link href="{{ asset('css/techaxis.css') }}" rel="stylesheet"> 
</head>
<body>

<!-- website Header -->
<header class="webpage-header">
        <div class="web-header-content">

            <a href="{{ url('/') }}" class="brand-logo">
                <img src="{{ asset('images/TechAxis-LOGO.png') }}" alt="Tech Axis Logo">
</a>


<nav class="main-nav">
<ul>

      
         <ul class="navmenu"> 
            <li><a href="{{ url('/') }}">Home</a></li>
                    <li><a href="{{ url('/Shop') }}">Shop</a></li>
                    <li><a href="{{ url('/About') }}">About</a></li>
                    <li><a href="{{ url('/Contact') }}">Contact</a></li>
                    <li><a href="{{ url('/Account') }}">Account</a></li>

                </ul>
</nav>

            <div class="user-controls">
               <a href="{{ url('/cart') }}" title="Shopping Cart">🛒</a>
                <a href="{{ url('/login') }}" title="Account">👤</a>
                
            </div>
        </div>
    </header>


    <!-- Site Banner -->

    <section class="Banner">
      <div class="container banner-content">

      <h1> LEVEL UP YOUR GAME  </h1>
<p> Explore elite gaming gear, powerful tech, and exclusive merchandise — crafted for players who demand more. <p>

<a href="{{ url ('/products') }}" class="primary-btn"> Latest Arrivals!</a> 

      <h2 class="explore-text">EXPLORE NOW</h2>

</div>
</section>

<!-- Categories--> 

<section class="categories-section">

   <h2 class="section-heading">Shop by Category</h2> 

   <div class="category-boxes">

<a href="{{ url('/category/consoles') }}" class="category-item">
                <div class="category-icon">🎮</div>
                <h3>Consoles & Accessories</h3>
</a>
    <a href="{{ url('/category/pc-gaming') }}" class="category-item">
                <div class="category-icon">🖥️</div>
                <h3>PC Gaming</h3>
</a>
  <a href="{{ url('/category/merch') }}" class="category-item">
                <div class="category-icon">👕</div>
                <h3>Merchandise</h3>
</a>
 <a href="{{ url('/category/components') }}" class="category-item">
                <div class="category-icon">⚙️</div>
                <h3>PC Components</h3>
</a>

</a>
<a href="{{ url('/category/phones') }}" class="category-item">
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



<!-- Footer--> 

 <footer class="site-footer">
   <div class="footer-content">


 <div class="footer-links">
<a href="{{ url('/contact') }}">Contact Us</a>
<a href="{{ url('/about') }}">About Us</a>

</div>

   <p>  2025 Tech Axis. All rights reserved <p>
      <P> Contact support@techaxis.com || CS2TP Team 7 Project </p>
</div>


 
</footer>
</body>
</html>

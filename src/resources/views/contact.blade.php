<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tech Axis | Contact Us</title>
    <style>
       
        body {
            margin: 0;
            padding: 0;
            background: black;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            color: white;
        }

        a {
            text-decoration: none;
            color: white;
        }

        
        header {
            background: black;
            border-bottom: 5px solid red;
            padding: 10px 50px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-text {
            font-size: 28px;
            font-weight: bold;
            color: red;
        }

        nav a {
            margin: 0 15px;
            font-size: 18px;
            font-weight: 600;
        }

        nav a.active {
            color: yellow;
        }

        .icons img {
            width: 20px;
            margin-left: 10px;
            filter: invert(1);
            cursor: pointer;
        }

        .page-title {
            margin-top: 30px;
            text-align: center;
            font-size: 36px;
            font-weight: bold;
            border: 3px solid white;
            width: 40%;
            margin-left: auto;
            margin-right: auto;
            padding: 10px;
            letter-spacing: 2px;
        }

        .subtitle {
            background: #222;
            width: 80%;
            margin: 20px auto;
            padding: 12px;
            border: 1px solid gray;
            font-size: 17px;
        }

        .contact-container {
            display: flex;
            width: 85%;
            margin: 20px auto;
            gap: 20px;
        }

        
        .info-box {
            width: 30%;
            background: #1c1c1c;
            border: 2px solid gray;
            padding: 20px;
        }

        .info-title {
            font-size: 22px;
            margin-bottom: 10px;
            border-bottom: 1px solid gray;
            padding-bottom: 5px;
        }

        .info-item {
            margin-bottom: 20px;
            font-size: 16px;
        }

        .info-item img {
            width: 18px;
            margin-right: 8px;
            filter: invert(1);
        }

        
        .form-box {
            width: 70%;
            background: #1c1c1c;
            border: 2px solid gray;
            padding: 20px;
        }

        .form-title {
            text-align: center;
            font-size: 20px;
            margin-bottom: 20px;
        }

        .form-row {
            margin-bottom: 15px;
        }

        .form-row label {
            width: 100px;
            display: inline-block;
            font-weight: 600;
        }

        input, textarea {
            background: black;
            border: 1px solid gray;
            color: white;
            padding: 6px;
            width: 60%;
        }

        textarea {
            height: 100px;
            resize: none;
        }

        .send-btn {
            float: right;
            margin-top: 10px;
            padding: 10px 18px;
            background: red;
            color: white;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }

        
        footer {
            margin-top: 50px;
            background: red;
            padding: 10px;
            text-align: center;
            color: white;
            border-top: 5px solid white;
            font-weight: 600;
        }

        footer a {
            margin: 0 15px;
            color: white;
            font-weight: bold;
        }

       
    @media (max-width: 900px) {

    
        .contact-container {
            flex-direction: column;
            width: 95%;
        }

        .info-box, .form-box {
            width: 100%;
        }

        input, textarea {
            width: 90%;
        }

        .form-row label {
            display: block;
            margin-bottom: 5px;
        }

        .page-title {
            width: 80%;
            font-size: 28px;
        }

        nav a {
            margin: 0 8px;
            font-size: 16px;
        }

        header {
            flex-direction: column;
            text-align: center;
            gap: 10px;
        }

        .icons img {
            width: 18px;
        }
}

    </style>
</head>

<body>

    
    <header>
        <div class="logo">
            <img src="Logo.png" width="150">
        </div>

        <nav>
            <a href="#">Home</a>
            <a href="#">Shop</a>
            <a href="#">About</a>
            <a class="active" href="#">Contact</a>
            <a href="#">Account</a>
        </nav>

        <div class="icons">
            <img src="https://cdn-icons-png.flaticon.com/512/622/622669.png">
            <img src="https://cdn-icons-png.flaticon.com/512/1170/1170678.png">
        </div>
    </header>

    <main> 
    
    <div class="page-title">CONTACT US</div>

    <div class="subtitle">
        Get in touch: &nbsp; Have questions about our products or need support?  
        <b>We're here to help!</b>
    </div>

    <div class="contact-container">

       
        <div class="info-box">
            <div class="info-title">Contact INFO</div>

            <div class="info-item">
                <img src="https://cdn-icons-png.flaticon.com/512/684/684908.png"> 
                <b>Address:</b><br> Tech Axis HQ, Birmingham, UK
            </div>

            <div class="info-item">
                <img src="https://cdn-icons-png.flaticon.com/512/724/724664.png">
                <b>Phone:</b><br> +44 7900 123456
            </div>

            <div class="info-item">
                <img src="https://cdn-icons-png.flaticon.com/512/561/561127.png">
                <b>Email:</b><br> support@TechAxis.co.uk
            </div>

            <div class="info-item">
                <img src="https://cdn-icons-png.flaticon.com/512/2088/2088617.png">
                <b>Hours:</b><br>
                Mon–Fri 09:00–18:00 <br>
                Sat 10:00–16:00
            </div>
        </div>

        
        <div class="form-box">
            <div class="form-title">[Contact Form]</div>

           
            @if(session('success'))
                <p style="color: lightgreen; font-weight: bold; text-align:center;">
                    {{ session('success') }}
                </p>
            @endif

            <form action="{{ route('contact.submit') }}" method="POST">
                @csrf

                <div class="form-row">
                    <label>Full Name:</label>
                    <input type="text" name="full_name" placeholder="Enter your name" required>
                </div>

                <div class="form-row">
                    <label>Email:</label>
                    <input type="email" name="email" placeholder="Enter your email" required>
                </div>

                <div class="form-row">
                    <label>Subject:</label>
                    <input type="text" name="subject" placeholder="Subject" required>
                </div>

                <div class="form-row">
                    <label>Message:</label>
                    <textarea name="message" placeholder="Type your message here..." required></textarea>
                </div>

                <button class="send-btn" type="submit">SEND MESSAGE</button>
            </form>
        </div>
    </div>
    </main>


    <footer>
        <a href="#">About</a>
        <a href="#">Contact</a>
        <a href="#">Support</a>
        &nbsp;&nbsp; | &nbsp;&nbsp;
        © 2025 Tech Axis
    </footer>

</body>
</html>


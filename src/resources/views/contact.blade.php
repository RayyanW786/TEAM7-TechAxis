<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tech Axis | Support Tickets</title>

    <style>
        html, body {
            height: 100%;
            margin: 0;
            background: black;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            color: white;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
            width: 85%;
            margin: 0 auto;
            padding-top: 30px;
        }

        .page-title {
            text-align: center;
            font-size: 36px;
            font-weight: bold;
            border: 2px solid white;
            width: 40%;
            margin-inline: auto;
            padding: 10px;
            letter-spacing: 2px;
        }

        .subtitle {
            background: #222;
            width: 90%;
            margin: 18px auto;
            padding: 12px;
            border: 1px solid gray;
            font-size: 17px;
            text-align: center;
        }

        .contact-container {
            display: flex;
            width: 90%;
            margin: 25px auto;
            gap: 20px;
        }

        .info-box {
            width: 30%;
            background: #1c1c1c;
            border: 1px solid gray;
            padding: 20px;
            border-radius: 5px;
        }

        .info-title {
            font-size: 22px;
            margin-bottom: 15px;
            border-bottom: 1px solid gray;
            padding-bottom: 8px;
            text-align: center;
        }

        .info-item {
            margin-bottom: 18px;
            font-size: 16px;
            line-height: 1.6;
        }

        .form-box {
            width: 70%;
            background: #1c1c1c;
            border: 1px solid gray;
            padding: 20px;
            border-radius: 5px;
        }

        .form-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 18px;
        }

        .form-row {
            margin-bottom: 15px;
        }

        .form-row label {
            font-weight: 600;
            display: block;
            margin-bottom: 6px;
        }

        input, textarea {
            background: black;
            border: 1px solid gray;
            color: white;
            padding: 8px;
            width: 100%;
            border-radius: 4px;
        }

        textarea {
            height: 120px;
            resize: none;
        }

        .send-btn {
            margin-top: 10px;
            padding: 12px;
            background: red;
            color: white;
            font-weight: bold;
            border: none;
            width: 100%;
            font-size: 17px;
            border-radius: 5px;
            cursor: pointer;
        }

        .success-msg {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-top: 15px;
            color: lime;
            padding: 10px;
            border: 1px solid lime;
            border-radius: 5px;
            background: #000;
            display: none; 
        }

        @media (max-width: 900px) {
            .contact-container {
                flex-direction: column;
            }
            .info-box, .form-box {
                width: 100%;
            }
            .page-title {
                width: 80%;
                font-size: 26px;
            }
        }
    </style>
</head>

<body>

<main>
    <div class="page-title">SUPPORT</div>

    <div class="subtitle">
        Need help? Submit a ticket below and we’ll respond inside the website.
    </div>

    <div class="contact-container">

        <div class="info-box">
            <div class="info-title">Contact Info</div>
            <div class="info-item"><b>Address:</b><br>Tech Axis HQ, Birmingham, UK</div>
            <div class="info-item"><b>Phone:</b><br>+44 7900 123456</div>
            <div class="info-item"><b>Email:</b><br>Replies via website panel</div>
            <div class="info-item"><b>Working Hours:</b><br>Mon–Fri 09:00–18:00<br>Sat 10:00–16:00</div>
        </div>

        <div class="form-box">
            <div class="form-title">Submit a Ticket</div>

            
            <form id="ticketForm" action="{{ route('tickets.submit') }}" method="POST">
                

                <div class="form-row">
                    <label>Full Name</label>
                    <input type="text" name="ticket_name" placeholder="Your name" required>
                </div>

                <div class="form-row">
                    <label>Email</label>
                    <input type="email" name="ticket_email" placeholder="Your email" required>
                </div>

                <div class="form-row">
                    <label>Subject</label>
                    <input type="text" name="ticket_subject" placeholder="Issue title" required>
                </div>

                <div class="form-row">
                    <label>Message</label>
                    <textarea name="ticket_message" placeholder="Describe your issue..." required></textarea>
                </div>

                <button class="send-btn" type="submit">SEND TICKET</button>

                <div id="successMessage" class="success-msg">
                    ✅ Ticket Submitted Successfully!
                </div>

            </form>
        </div>

    </div>
</main>

<script>
    
    document.getElementById("ticketForm").addEventListener("submit", function (e) {
        e.preventDefault(); 
        document.getElementById("successMessage").style.display = "block";
    });
</script>

</body>
</html>



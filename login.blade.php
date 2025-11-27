<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login Page</title>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600&family=Lato:wght@300;400;700&family=Roboto:wght@300;400;700&display=swap');

    body {
      margin: 0;
      padding: 0;
      background: #000000;
      font-family: 'Lato', 'Roboto', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      color: #ffffff;
    }
    .card {
      background: #1C1C1C;
      padding: 2rem;
      width: 350px;
      border-radius: 16px;
      box-shadow: 0 0 12px rgba(255, 0, 0, 0.4);
      border: 2px solid #FF0000;
    }
    h2 {
      text-align: center;
      margin-bottom: 1.5rem;
      font-family: 'Orbitron', sans-serif;
      font-size: 1.8rem;
      color: #FF0000;
    }
    input {
      width: 100%;
      padding: 0.85rem;
      margin: 0.6rem 0;
      border-radius: 10px;
      background: #000000;
      border: 2px solid #FF0000;
      color: #ffffff;
      font-size: 1rem;
    }
    input:focus {
      outline: none;
      box-shadow: 0 0 8px #FF0000;
    }
    button {
      width: 100%;
      padding: 0.85rem;
      margin-top: 1rem;
      background: #FF0000;
      color: white;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-size: 1.1rem;
      font-family: 'Orbitron', sans-serif;
      box-shadow: 0 4px 0 #660000;
    }
    button:hover {
      background: #cc0000;
    }
  </style>
</head>
<body>
  <div class="card">
    <h2>Login</h2>
    <form action="/login" method="POST">
      @csrf
      <input type="text" placeholder="Username" required />
      <input type="password" placeholder="Password" required />
      <button type="submit">Sign In</button>
          @csrf
    </form>
  </div>
</body>
</html>


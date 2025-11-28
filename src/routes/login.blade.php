<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login Page</title>

  <!-- Link external CSS -->
  <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>

<body>
  <div class="card">
    <h2>Login</h2>
    <form action="/login" method="POST">
      @csrf
      <input type="text" placeholder="Student email" required />
      <input type="password" placeholder="Password" required />
      <button type="submit">Sign In</button>
    </form>
  </div>
</body>
</html>

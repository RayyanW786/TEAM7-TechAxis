<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Tech Axis')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}">
    <link href="{{ asset('css/global.css') }}" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Roboto:wght@300;400;700&display=swap"
        rel="stylesheet">
    <link rel="icon" href="/images/TechAxis-LOGO.png" type="image/png">

    @stack('styles')
</head>

<body class="techaxis">

    @include('partials.navbar')

    <main class="container py-4">
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')

    @include('partials.footer')
</body>

</html>
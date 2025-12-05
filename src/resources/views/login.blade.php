@extends('layouts.main')
@section('title', 'Login - Tech Axis')
@push('styles')
     <link href="{{ asset('css/login.css') }}" rel="stylesheet">
@endpush
@section('content')
  <div class="card">
    <h2>Login</h2>
    @if ($errors->any())
      <div class="error-messages">
        <ul>
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif
    <form action="{{ route('login') }}" method="POST">
      @csrf
      <input type="email" name="email" placeholder="Student Email" required />
      <input type="password" name="password" placeholder="Password" required />
      <button type="submit">Sign In</button>
    </form>
    <br>
    <p>Don't have an Account? <a href="{{ route('register.page') }}">Register</a></p>
  </div>
@endsection

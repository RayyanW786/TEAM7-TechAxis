@extends('layouts.main')
@section('title', 'Login - Tech Axis')
@push('styles')
     <link href="{{ asset('css/login.css') }}" rel="stylesheet">
@endpush
@section('content')
  <div class="card">
    <h2>Login</h2>
    <form action="/login" method="POST">
      @csrf
      <input type="text" placeholder="Student Email" required />
      <input type="password" placeholder="Password" required />
      <button type="submit">Sign In</button>
    </form>
  </div>
@endsection

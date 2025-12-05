@extends('layouts.main')
@section('title', 'Tech Axis - Under Construction')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/underconstruction.css') }}">
@endpush
@section('content')
    <div class="underconstruction-container">
        <h1 class="underconstruction-heading">Page Under Construction</h1>
        <p class="underconstruction-message">
            We're working hard to bring you this page. Please check back later!
        </p>
    </div>
@endsection
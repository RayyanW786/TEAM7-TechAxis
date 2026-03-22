@extends('layouts.main')

@section('title', 'Reviews | Admin')

@push('styles')
<link href="{{ asset('css/admin/admin-shell.css') }}" rel="stylesheet">
<link href="{{ asset('css/admin/reviews.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="admin-shell admin-reviews-page" data-admin-reviews-root>
    <div class="admin-shell-header">
        <div>
            <span class="admin-shell-kicker">Reviews</span>
            <h1 class="admin-shell-title">Review management</h1>
            <p class="admin-shell-copy">Monitor product feedback, service testimonials, and review quality across the storefront.</p>
        </div>
        <div class="admin-shell-actions">
            <a href="{{ route('admin.dashboard') }}" class="admin-shell-button">Back to dashboard</a>
        </div>
    </div>

    <div data-admin-reviews-app></div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin/reviews.js') }}"></script>
@endpush

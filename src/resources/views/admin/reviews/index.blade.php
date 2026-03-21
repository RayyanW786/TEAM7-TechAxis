@extends('layouts.main')

@section('title', 'Reviews | Admin')

@push('styles')
<link href="{{ asset('css/admin/reviews.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="dashboard-container admin-reviews-page" data-admin-reviews-root>
    <div class="admin-reviews-header">
        <div>
            <h1>Reviews</h1>
            <p>Monitor product feedback, service testimonials, and review quality across the storefront.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="admin-review-backlink">Back to dashboard</a>
    </div>

    <div data-admin-reviews-app></div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin/reviews.js') }}"></script>
@endpush

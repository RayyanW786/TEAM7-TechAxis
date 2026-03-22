@extends('layouts.main')

@section('title', 'Admin Dashboard - Tech Axis')

@push('styles')
    <link href="{{ asset('css/admin/admin-shell.css') }}" rel="stylesheet">
    <link href="{{ asset('CSS/admin/dashboard.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="admin-shell admin-dashboard">
        <div class="admin-shell-header">
            <div>
                <span class="admin-shell-kicker">Admin dashboard</span>
                <h1 class="admin-shell-title">Admin dashboard</h1>
                <p class="admin-shell-copy">
                    Manage customers, products, inventory, discounts, orders, tickets, reports, and reviews from one place.
                </p>
            </div>

            <div class="admin-shell-actions admin-shell-actions--dashboard">
                <a href="{{ route('products.index') }}" class="admin-shell-button admin-shell-button--primary">View storefront</a>
                <a href="{{ route('password.change') }}" class="admin-shell-button">Change password</a>
                <a href="{{ route('home') }}" class="admin-shell-button"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    Log out
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </div>

        <div class="admin-dashboard-intro">
            <div class="admin-dashboard-note">
                <strong>Customer operations</strong>
                <p>Orders, tickets, support</p>
            </div>
            <div class="admin-dashboard-note">
                <strong>Catalog control</strong>
                <p>Products, stock, variants</p>
            </div>
            <div class="admin-dashboard-note">
                <strong>Commercial tools</strong>
                <p>Discounts, reports, reviews</p>
            </div>
            <div class="admin-dashboard-note">
                <strong>Storefront access</strong>
                <p>Admins can browse as customers</p>
            </div>
        </div>

        <div class="admin-link-list">
            <a href="{{ route('admin.orders') }}" class="admin-link-card">
                <strong>Orders</strong>
                <span>Search orders, inspect item lines, update statuses, and manage shipments.</span>
                <span class="admin-link-card__meta">Order queue</span>
            </a>
            <a href="{{ route('admin.products.index') }}" class="admin-link-card">
                <strong>Products</strong>
                <span>Maintain catalog content, images, pricing, stock, and variant-level inventory.</span>
                <span class="admin-link-card__meta">Catalog and variants</span>
            </a>
            <a href="{{ route('admin.customers.index') }}" class="admin-link-card">
                <strong>Customers</strong>
                <span>View, create, update, and remove customer accounts with profile and order context.</span>
                <span class="admin-link-card__meta">Customer records</span>
            </a>
            <a href="{{ route('admin.tickets.index') }}" class="admin-link-card">
                <strong>Support tickets</strong>
                <span>Work refund requests and product support conversations with assignment and internal notes.</span>
                <span class="admin-link-card__meta">Support workflow</span>
            </a>
            <a href="{{ route('admin.discounts.index') }}" class="admin-link-card">
                <strong>Discount codes</strong>
                <span>Create, schedule, deactivate, and monitor order-wide promotions end to end.</span>
                <span class="admin-link-card__meta">Promotions</span>
            </a>
            <a href="{{ route('admin.inventory.index') }}" class="admin-link-card">
                <strong>Inventory</strong>
                <span>Track alerts, transactions, incoming stock, and restock priorities in one place.</span>
                <span class="admin-link-card__meta">Stock control</span>
            </a>
            <a href="{{ route('admin.reports.index') }}" class="admin-link-card">
                <strong>Reports</strong>
                <span>Review live operational metrics, revenue windows, stock health, and open work.</span>
                <span class="admin-link-card__meta">Reporting</span>
            </a>
            <a href="{{ route('admin.reviews.index') }}" class="admin-link-card">
                <strong>Reviews</strong>
                <span>Monitor product and service feedback already flowing from the storefront.</span>
                <span class="admin-link-card__meta">Feedback insights</span>
            </a>
            <a href="{{ route('products.index') }}" class="admin-link-card">
                <strong>Open storefront</strong>
                <span>Use the customer-facing experience while staying signed in as an admin.</span>
                <span class="admin-link-card__meta">Customer journey</span>
            </a>
        </div>
    </div>
@endsection

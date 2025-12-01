<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AdminReportsController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerProfileController;
use App\Http\Controllers\Api\DiscountCodeController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\OptionTypeController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductImageController;
use App\Http\Controllers\Api\ReturnRequestController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ShipmentController;
use App\Http\Controllers\Api\SupportTicketController;
use Illuminate\Support\Facades\Route;

// Public
Route::get('/brands', [BrandController::class, 'index']);
Route::get('/brands/{brand}', [BrandController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/products/{product}/images', [ProductImageController::class, 'index']);
Route::get('/products/{product}/reviews', [ReviewController::class, 'productIndex']);

Route::get('/option-types', [OptionTypeController::class, 'index']);

Route::middleware(['web'])->group(function () {
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::patch('/cart/items/{cartItemId}', [CartController::class, 'setItemQuantity']);
    Route::delete('/cart/items', [CartController::class, 'removeItem']);
    Route::post('/cart/checkout', [CartController::class, 'checkout']);

    // Auth required
    Route::middleware(['auth'])->group(function () {
        Route::get('/me/profile', [CustomerProfileController::class, 'show']);
        Route::patch('/me/profile', [CustomerProfileController::class, 'update']);

        Route::get('/me/addresses', [AddressController::class, 'index']);
        Route::post('/me/addresses', [AddressController::class, 'store']);
        Route::patch('/me/addresses/{address}', [AddressController::class, 'update']);
        Route::delete('/me/addresses/{address}', [AddressController::class, 'destroy']);

        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);

        Route::get('/tickets', [SupportTicketController::class, 'index']);
        Route::get('/tickets/{ticket}', [SupportTicketController::class, 'show']);
        Route::post('/tickets', [SupportTicketController::class, 'store']);
        Route::post('/tickets/{ticket}/messages', [SupportTicketController::class, 'addMessage']);

        Route::get('/returns', [ReturnRequestController::class, 'index']);
        Route::post('/returns', [ReturnRequestController::class, 'requestReturn']);

        Route::post('/products/{product}/reviews', [ReviewController::class, 'upsertProduct']);
        Route::post('/service-review', [ReviewController::class, 'upsertService']);
    });

    // Admin only

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/brands', [BrandController::class, 'store']);
        Route::patch('/brands/{brand}', [BrandController::class, 'update']);
        Route::delete('/brands/{brand}', [BrandController::class, 'destroy']);

        Route::post('/categories', [CategoryController::class, 'store']);
        Route::patch('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
        Route::post('/categories/reorder', [CategoryController::class, 'reorder']);

        Route::post('/products', [ProductController::class, 'store']);
        Route::patch('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
        Route::post('/products/{product}/variants', [ProductController::class, 'upsertVariant']);
        Route::delete('/products/{product}/variants/{variantId}', [ProductController::class, 'destroyVariant']);

        Route::post('/products/{product}/images', [ProductImageController::class, 'store']);
        Route::delete('/products/{product}/images/{image}', [ProductImageController::class, 'destroy']);
        Route::post('/products/{product}/images/{image}/primary', [ProductImageController::class, 'setPrimary']);
        Route::post('/products/{product}/images/reorder', [ProductImageController::class, 'reorder']);

        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);

        Route::post('/orders/{order}/shipments', [ShipmentController::class, 'store']);
        Route::patch('/orders/{order}/shipments/{shipment}', [ShipmentController::class, 'update']);
        Route::delete('/orders/{order}/shipments/{shipment}', [ShipmentController::class, 'destroy']);

        Route::post('/tickets/{ticket}/close', [SupportTicketController::class, 'close']);
        Route::post('/tickets/{ticket}/assign', [SupportTicketController::class, 'assign']);

        Route::post('/returns/{returnId}/process', [ReturnRequestController::class, 'process']);

        Route::get('/discount-codes', [DiscountCodeController::class, 'index']);
        Route::post('/discount-codes', [DiscountCodeController::class, 'store']);
        Route::get('/discount-codes/{discountCode}', [DiscountCodeController::class, 'show']);
        Route::patch('/discount-codes/{discountCode}', [DiscountCodeController::class, 'update']);
        Route::delete('/discount-codes/{discountCode}', [DiscountCodeController::class, 'destroy']);

        Route::post('/option-types', [OptionTypeController::class, 'store']);
        Route::post('/option-types/{optionType}/values', [OptionTypeController::class, 'addValue']);
        Route::delete('/option-types/{optionType}', [OptionTypeController::class, 'destroy']);
        Route::delete('/option-types/{optionType}/values/{value}', [OptionTypeController::class, 'destroyValue']);

        Route::get('/inventory/alerts', [InventoryController::class, 'alerts']);
        Route::get('/inventory/transactions', [InventoryController::class, 'transactions']);
        Route::post('/inventory/transactions', [InventoryController::class, 'storeTransaction']);

        Route::get('/admin/sales-summary', [AdminReportsController::class, 'salesSummary']);
    });

});
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\Storefront\ProductPageController;
use App\Http\Controllers\Storefront\CartPageController;
use App\Http\Controllers\Storefront\CheckoutPageController;
use App\Http\Controllers\Storefront\OrderPageController;
use Illuminate\Support\Facades\Auth;


Route::get('/', function () {
    return view('home');
});
Route::get('/register', function () {
    return view('register');
});
Route::get('/login', function () {
    return view('login');
});
Route::post('/register', [UsersController::class, 'register'])->name('register');
Route::post('/login', [UsersController::class, 'login'])->name('login');
Route::post('/logout', [UsersController::class, 'logout'])->name('logout');
Route::middleware(['auth', 'admin'])->group(function () {
    // Protected routes can be added here
    Route::get('/admin/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');
});
Route::get('/customer/dashboard', function () {
    return view('customer.dashboard');
})->middleware('auth')->name('customer.dashboard');
Route::get('/dashboard', function () {
    if (!Auth::user()) {
        return redirect()->route('login');
    }
    if (Auth::user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    } else {
        return redirect()->route('customer.dashboard');
    }
})->middleware('auth')->name('dashboard');

Route::get('/products', [ProductPageController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductPageController::class, 'show'])->name('products.show');

Route::get('/cart', [CartPageController::class, 'show'])->name('cart.show');

Route::get('/checkout', [CheckoutPageController::class, 'show'])
    ->middleware('auth')
    ->name('checkout.show');

Route::get('/orders/{order}', [OrderPageController::class, 'show'])
    ->middleware('auth')
    ->name('orders.show');
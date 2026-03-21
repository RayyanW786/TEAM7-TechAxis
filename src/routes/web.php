<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\Storefront\ProductPageController;
use App\Http\Controllers\Storefront\CartPageController;
use App\Http\Controllers\Storefront\CheckoutPageController;
use App\Http\Controllers\Storefront\OrderPageController;
use App\Http\Controllers\Storefront\SupportTicketPageController;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;


// Home page
Route::get('/', function () {
    return view('home');
})->name('home');
Route::get('/register', function () {
    return view('register');
})->name('register.page');
Route::get('/login', function () {
    return view('login');
})->name('login.page');
Route::post('/register', [UsersController::class, 'register'])->name('register');
Route::post('/login', [UsersController::class, 'login'])->name('login');
Route::post('/logout', [UsersController::class, 'logout'])->name('logout');
Route::middleware(['auth', 'admin'])->group(function () {
    // Protected routes can be added here
    Route::get('/admin/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');
    Route::get('/admin/orders', function () {
        return view('admin.orders');
    })->name('admin.orders');
    Route::get('/admin/products', function () {
        return view('admin.products.index');
    })->name('admin.products.index');
    Route::get('/admin/products/create', function () {
        return view('admin.products.create');
    })->name('admin.products.create');
    Route::get('/admin/products/{product}', function (Product $product) {
        return view('admin.products.edit', compact('product'));
    });
});

// About page
Route::view('/about', 'about')->name('about');

// Support / Tickets (customer side)
Route::middleware('auth')->group(function () {
    Route::get('/contact', [SupportTicketPageController::class, 'index'])->name('contact');

    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/tickets', [SupportTicketPageController::class, 'index'])->name('tickets.index');
        Route::post('/tickets', [SupportTicketPageController::class, 'store'])->name('tickets.store');

        Route::get('/tickets/{ticket}', [SupportTicketPageController::class, 'show'])->name('tickets.show');
        Route::post('/tickets/{ticket}/messages', [SupportTicketPageController::class, 'storeMessage'])->name('tickets.messages.store');
        Route::get('/tickets/{ticket}/messages', [SupportTicketPageController::class, 'messages'])->name('tickets.messages.index');
    });

    Route::get('/change-password', function () {
        return view('change_password');
    })->name('password.change');

    Route::post('/change-password', [UsersController::class, 'changePassword'])
        ->name('password.update');
});

Route::view('/orders', 'admin.orders')->middleware('auth')->name('orders');

Route::get('/customer/dashboard', function () {
    return view('customerDashboard');
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
Route::view('/under-construction', 'undersconstruction')->name('under-construction');
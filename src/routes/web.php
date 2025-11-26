<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/register', function () {
    return view(REGISTER PAGE PLACEHOLDER);
});
Route::get('/login', function () {
    return view(LOGIN PAGE PLACEHOLDER);
});
Route::post('/register', [UsersController::class, 'register'])->name('register');
Route::post('/login', [UsersController::class, 'login'])->name('login');
Route::post('/logout', [UsersController::class, 'logout'])->name('logout');
Route::middleware('auth', 'admin')->group(function () {
    // Protected routes can be added here
    Route::get('/admin/dashboard', function () {
        return view(ADMIN DASHBOARD PLACEHOLDER);
    })->name(adminDashboard);
});
Route::get('/customer/dashboard', function () {
    return view(CUSTOMER DASHBOARD PLACEHOLDER);
})->middleware('auth')->name('customerDashboard');
Route::get('/dashboard', function () {
    if (!Auth::user()) {
        return redirect()->route('login');
    }
    if (Auth::user()->isAdmin()) {
        return redirect()->route('adminDashboard');
    } else {
        return redirect()->route('customerDashboard');
    }
})->middleware('auth')->name('dashboard');


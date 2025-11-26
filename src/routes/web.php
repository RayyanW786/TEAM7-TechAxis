<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;

Route::get('/', function () {
    return view('welcome');
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
    })->name('adminDashboard');
});
Route::get('/customer/dashboard', function () {
    return view('customer.dashboard');
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


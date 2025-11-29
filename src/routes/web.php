<?php

use Illuminate\Support\Facades\Route;

// For now the account page loads everything (login/register/dashboard)

// Main Page - just shows the account section for now
Route::get('/', function () {
    return view('account');
});

// Account Page (LOGIN, REGISTER & Dashboard Layout)
Route::get('/account', function () {
    return view('account');
});

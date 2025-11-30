<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;


Route::get('/', function () {
    return view('welcome');
});


Route::get('/contact', [ContactController::class, 'index'])->name('tickets.show');
Route::post('/contact', [ContactController::class, 'store'])->name('tickets.submit');

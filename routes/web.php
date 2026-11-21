<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');
Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

// ========================================
// API ROUTES
// ========================================
Route::prefix('api')->group(function () {

    // Authentication
    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::post('/login', 'login')->name('api.auth.login');
        Route::post('/register', 'register')->name('api.auth.register');

        Route::post('/logout', 'logout')
            ->name('api.auth.logout');
    });
});
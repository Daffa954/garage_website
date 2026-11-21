<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GarageController;
use App\Http\Controllers\RentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');
Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::middleware(['auth.session'])->group(function () {
    Route::get('/vehicles', [VehicleController::class, 'index'])
        ->name('vehicles.index');
    Route::get('/rents', [RentController::class, 'index'])
        ->name('rents.index');
});

Route::middleware(['auth.session', 'admin.only'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'index'])
        ->name('admin.users.index');
});

// Route::middleware(['admin.only'])->group(function () {
Route::middleware('auth.session')->group(function () {
    Route::get('/garages', [GarageController::class, 'index'])
        ->name('garages.index');
});
// });


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

    Route::prefix('vehicle')->controller(VehicleController::class)->group(function () {
        Route::post('/', 'store')->name('api.vehicle.store');
    });

    Route::prefix('garage')->controller(GarageController::class)->group(function () {
        Route::post('/', 'store')->name('api.garage.store');
    });

    Route::prefix('user')->middleware(['auth.session', 'admin.only'])->controller(UserController::class)->group(function () {
        Route::patch('/{id}/verify', 'toggleVerify')->name('api.user.verify');
    });

    Route::prefix('rent')->middleware('auth.session')->controller(RentController::class)->group(function () {
        Route::post('/', 'store')->name('api.rent.store');
    });
});
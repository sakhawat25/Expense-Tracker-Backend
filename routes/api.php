<?php

// use App\Http\Controllers\Auth\AuthenticationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\Auth\AuthenticationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportsController;

Route::middleware(['auth:sanctum'])->group(function() {
    Route::get('/user', function(Request $request) {
        return $request->user();
    });
});

Route::prefix('v1')->group(function() {
    // Authentication
    Route::controller(AuthenticationController::class)->group(function() {
        Route::post('/login', 'login')->middleware('throttle:5,1')->name('login');
        Route::post('/register', 'register')->name('register');
    });

    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        Route::post('logout', [AuthenticationController::class, 'logout']);

        Route::apiResource('expenses', ExpenseController::class);

        Route::get('/dashboard', [DashboardController::class, 'index']);

        Route::controller(ReportsController::class)->group(function() {
            Route::get('/reports', 'index');
            Route::post('/reports/filter', 'filter');
        });
    });
});

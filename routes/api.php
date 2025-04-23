<?php

use App\Http\Controllers\Auth\AuthenticationController;
use App\Http\Controllers\ExpenseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

        Route::post('logout', [AuthenticationController::class, 'logout'])->name('logout');

        Route::apiResource('expenses', ExpenseController::class);
    });
});

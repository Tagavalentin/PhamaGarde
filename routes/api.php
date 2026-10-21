<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PharmacyController;
use Illuminate\Support\Facades\Route;

Route::prefix('pharma')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/pharmacies', [PharmacyController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

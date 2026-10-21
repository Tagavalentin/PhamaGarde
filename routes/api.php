<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MedicineController;
use App\Http\Controllers\Api\PharmacyController;
use Illuminate\Support\Facades\Route;

Route::prefix('pharma')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/pharmacies', [PharmacyController::class, 'index']);
    Route::get('/medicines', [MedicineController::class, 'index'])->middleware('throttle:60,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

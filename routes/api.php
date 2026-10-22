<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MedicineController;
use App\Http\Controllers\Api\PharmacyController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix('pharma')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/register-pharmacist', [AuthController::class, 'registerPharmacist']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/pharmacies', [PharmacyController::class, 'index']);
    Route::get('/medicines', [MedicineController::class, 'index'])->middleware('throttle:60,1');
    Route::get('/medicines/{id}/availability', [MedicineController::class, 'availability']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Pharmacist routes
        Route::middleware('validated.pharmacist')->group(function () {
            // Stock
            Route::get('/my-pharmacy/stock', [StockController::class, 'index']);
            Route::put('/my-pharmacy/stock/{medicine_id}', [StockController::class, 'update']);
            Route::delete('/my-pharmacy/stock/{medicine_id}', [StockController::class, 'destroy']);

            // Schedule
            Route::get('/my-pharmacy/hours', [ScheduleController::class, 'getHours']);
            Route::put('/my-pharmacy/hours', [ScheduleController::class, 'updateHours']);
            Route::get('/my-pharmacy/duty', [ScheduleController::class, 'getDuties']);
            Route::post('/my-pharmacy/duty', [ScheduleController::class, 'storeDuty']);
            Route::delete('/my-pharmacy/duty/{id}', [ScheduleController::class, 'destroyDuty']);
        });

        // Admin routes
        Route::middleware('role:admin')->group(function () {
            Route::get('/admin/pharmacies/pending', [AdminController::class, 'pendingPharmacies']);
            Route::post('/admin/pharmacies/{id}/approve', [AdminController::class, 'approvePharmacy']);
            Route::post('/admin/pharmacies/{id}/reject', [AdminController::class, 'rejectPharmacy']);
        });
    });
});

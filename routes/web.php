<?php

use App\Http\Controllers\Web\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/inscription-pharmacien', [AuthController::class, 'showPharmacistRegister'])->name('pharmacist.register');
Route::post('/inscription-pharmacien', [AuthController::class, 'registerPharmacist']);

Route::get('/dashboard', function () {
    return inertia('Dashboard');
})->name('dashboard');

Route::get('/mon-stock', function () {
    return inertia('MonStock');
})->name('stock.index');

Route::get('/admin/demandes', function () {
    return inertia('AdminDemandes');
})->name('admin.demandes');

Route::get('/carte', function () {
    return inertia('Carte');
})->name('carte');

Route::get('/medicaments/{id}', function ($id) {
    return inertia('Medicament', ['id' => $id]);
})->name('medicament.show');

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

Route::get('/mes-horaires', function () {
    return inertia('MesHoraires');
})->name('schedule.hours');

Route::get('/mes-gardes', function () {
    return inertia('MesGardes');
})->name('schedule.duties');

Route::get('/admin/demandes', function () {
    return inertia('AdminDemandes');
})->name('admin.demandes');

Route::get('/admin/catalogue', function () {
    return inertia('AdminCatalogue');
})->name('admin.catalogue');

Route::get('/carte', function () {
    return inertia('Carte');
})->name('carte');

Route::get('/medicaments/{id}', function ($id) {
    return inertia('Medicament', ['id' => $id]);
})->name('medicament.show');

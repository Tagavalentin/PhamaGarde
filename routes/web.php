<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/carte', function () {
    return inertia('Carte');
})->name('carte');

Route::get('/medicaments/{id}', function ($id) {
    return inertia('Medicament', ['id' => $id]);
})->name('medicament.show');

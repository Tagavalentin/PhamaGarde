<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/carte', function () {
    return inertia('Carte');
})->name('carte');

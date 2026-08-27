<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/comprobantes/manual', function () {
    return view('boletas.manual-integration');
})->name('comprobantes.manual');

Route::get('/boletas/manual', function () {
    return view('boletas.manual');
})->name('boletas.manual');

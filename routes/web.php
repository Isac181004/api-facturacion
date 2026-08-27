<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('facturacion.index');
});

Route::get('/facturacion', function () {
    return view('facturacion.index');
})->name('facturacion.index');

Route::get('/boletas/manual', function () {
    return view('boletas.manual');
})->name('boletas.manual');

<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/comprobantes/manual', function () {
    return view('boletas.manual-integration');
})->name('comprobantes.manual');

Route::get('/comprobantes/manual/{preload}', function (string $preload) {
    return view('boletas.manual-integration');
})->where('preload', '[A-Za-z0-9]{48}')->name('comprobantes.manual.preload');

Route::get('/boletas/manual', function () {
    return view('boletas.manual');
})->name('boletas.manual');

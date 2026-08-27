<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/boletas/manual', function () {
    return view('boletas.manual');
})->name('boletas.manual');

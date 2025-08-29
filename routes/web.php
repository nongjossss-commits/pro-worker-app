<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployerController;
use App\Http\Controllers\ImporterController;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('employers', EmployerController::class);
Route::resource('importers', ImporterController::class)->middleware('auth');

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployerController;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('employers', EmployerController::class);

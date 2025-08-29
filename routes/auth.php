<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

Route::post('login', [AuthenticatedSessionController::class, 'store']);

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
                ->middleware('auth')
                ->name('logout');

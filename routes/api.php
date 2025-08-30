<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Employer;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// The '/api' prefix is automatically applied to this route by the framework.
// The full URL will be /api/employers/{employer}/employees
Route::get('/employers/{employer}/employees', function (Employer $employer) {
    return $employer->employees;
})->middleware('auth:web');

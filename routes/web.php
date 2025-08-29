<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployerController;
use App\Http\Controllers\ImporterController;
use App\Http\Controllers\ProfileController;
Route::get('/', function () {
    return view('welcome');
});
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
Route::middleware('auth')->group(function () {
    // Profile routes from Breeze
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Application routes that require login
    Route::resource('employers', EmployerController::class);
    Route::resource('importers', ImporterController::class);
});
require __DIR__.'/auth.php';

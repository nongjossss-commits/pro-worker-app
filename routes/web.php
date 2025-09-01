<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\DelegateController;
use App\Http\Controllers\EmployerController;
use App\Http\Controllers\ImporterController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
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
    Route::resource('employers.employees', EmployeeController::class);
    Route::resource('importers', ImporterController::class);
    Route::resource('agents', AgentController::class);
    Route::resource('delegates', DelegateController::class);

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/cancel', [NotificationController::class, 'cancel'])->name('notifications.cancel');
    Route::get('/notifications/export', [NotificationController::class, 'export'])->name('notifications.export');
});
require __DIR__.'/auth.php';

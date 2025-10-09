<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmployerController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ImporterController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\DelegateController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\RolesPermissionsController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('employers', EmployerController::class);
    Route::resource('employees', EmployeeController::class);
    Route::get('employees/history', [EmployeeController::class, 'employmentHistory'])->name('employees.history');
    Route::post('employees/{employee}/terminate', [EmployeeController::class, 'terminate'])->name('employees.terminate');
    Route::get('employees/{employee}/locate', [EmployeeController::class, 'locate'])->name('employees.locate');

    Route::resource('importers', ImporterController::class);
    Route::resource('agents', AgentController::class);
    Route::resource('delegates', DelegateController::class);

    Route::get('/thai-addresses', [AddressController::class, 'getThaiAddressData'])->name('addresses.thai_data');
    Route::post('/employers/{employer}/addresses', [AddressController::class, 'store'])->name('employers.addresses.store');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
});

Route::middleware(['auth', 'role:Admin'])->name('admin.')->prefix('admin')->group(function () {
    Route::get('/roles-permissions', [RolesPermissionsController::class, 'index'])->name('roles_permissions.index');
    // Add other admin routes here
});

require __DIR__.'/auth.php';
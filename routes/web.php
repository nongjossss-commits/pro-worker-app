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
use App\Http\Controllers\JobOwnerController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    // Redirect to a more meaningful page like employers index
    return redirect()->route('employers.index');
})->middleware(['auth', 'verified'])->name('dashboard');

// ALL APPLICATION ROUTES MUST BE INSIDE THIS AUTH GROUP
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Resources
    Route::resource('employers', EmployerController::class);
    Route::resource('employees', EmployeeController::class);
    Route::resource('importers', ImporterController::class);
    Route::resource('agents', AgentController::class);
    Route::resource('delegates', DelegateController::class);
    Route::resource('job-owners', JobOwnerController::class)->only(['index', 'store', 'destroy']);

    // Employee specific routes
    Route::get('employees/history', [EmployeeController::class, 'employmentHistory'])->name('employees.history');
    Route::post('employees/{employee}/terminate', [EmployeeController::class, 'terminate'])->name('employees.terminate');
    Route::get('employees/{employee}/locate', [EmployeeController::class, 'locate'])->name('employees.locate');
    Route::post('/employees/bulk-terminate', [EmployeeController::class, 'bulkTerminate'])->name('employees.bulk.terminate');

    // Employer specific routes
    Route::get('/employers/{employer}/export-employees', [EmployerController::class, 'exportEmployees'])->name('employers.exportEmployees');

    // Address routes
    Route::get('/thai-addresses', [AddressController::class, 'getThaiAddressData'])->name('addresses.thai_data');
    Route::post('/employers/{employer}/addresses', [AddressController::class, 'store'])->name('employers.addresses.store');
    Route::put('/addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/renew', [NotificationController::class, 'renew'])->name('notifications.renew');
    Route::post('/notifications/{notification}/cancel', [NotificationController::class, 'cancel'])->name('notifications.cancel');
});

// Admin-only routes
Route::middleware(['auth', 'role:Admin'])->name('admin.')->prefix('admin')->group(function () {
    Route::get('/roles-permissions', [RolesPermissionsController::class, 'index'])->name('roles_permissions.index');
    Route::post('/roles', [RolesPermissionsController::class, 'storeRole'])->name('roles.store');
    Route::post('/permissions', [RolesPermissionsController::class, 'storePermission'])->name('permissions.store');
    Route::post('/roles/{role}/permissions', [RolesPermissionsController::class, 'assignPermissions'])->name('roles.permissions.assign');
});

require __DIR__.'/auth.php';
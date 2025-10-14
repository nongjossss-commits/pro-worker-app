<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\DelegateController;
use App\Http\Controllers\EmployerController;
use App\Http\Controllers\ImporterController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\JobOwnerController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\AddressController;

Route::get('/thai-addresses', [AddressController::class, 'getThaiAddressData'])->name('addresses.thai_data');
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
    Route::get('/employers/export', [EmployerController::class, 'export'])->name('employers.export');
    Route::get('/employers/{employer}/export-employees', [EmployerController::class, 'exportEmployees'])->name('employers.exportEmployees');
    Route::get('/employers/{employer}/export-history', [EmployerController::class, 'exportHistory'])->name('employers.exportHistory');
    Route::resource('employers', EmployerController::class);
    Route::get('/employers/{employer}/employees/filter', [EmployerController::class, 'filterEmployees'])->name('employers.employees.filter');
    Route::get('employers/{employer}/history', [EmployerController::class, 'filterHistory'])->name('employers.history.filter');
    Route::post('employees/{employee}/terminate', [EmployeeController::class, 'terminate'])->name('employees.terminate');
    Route::post('/employees/{employee}/restore', [EmployeeController::class, 'restore'])->name('employees.restore');
    Route::delete('/employees/{employee}/force-delete', [EmployeeController::class, 'forceDelete'])->name('employees.forceDelete');
    Route::get('/employees/{employee}/locate', [EmployeeController::class, 'locate'])->name('employees.locate');
    Route::get('/employees/{employee}/create-job', [JobController::class, 'createFromEmployee'])->name('jobs.create_from_employee');
    Route::get('/employees/export', [EmployeeController::class, 'export'])->name('employees.export');
    Route::resource('employees', EmployeeController::class);
    Route::resource('importers', ImporterController::class);
    Route::resource('agents', AgentController::class);
    Route::resource('delegates', DelegateController::class);

    Route::get('/notifications/export', [NotificationController::class, 'export'])->name('notifications.export');
    Route::get('/employees/export', [EmployeeController::class, 'export'])->name('employees.export');
    Route::get('/employers/{employer}/export-employees', [EmployerController::class, 'exportEmployees'])->name('employers.exportEmployees');

    Route::resource('job-owners', JobOwnerController::class)->only(['index', 'store', 'destroy']);

    Route::post('/addresses', [App\Http\Controllers\AddressController::class, 'store'])->name('addresses.store');
    Route::get('/addresses/{address}/edit', [App\Http\Controllers\AddressController::class, 'edit'])->name('addresses.edit');
    Route::put('/addresses/{address}', [App\Http\Controllers\AddressController::class, 'update'])->name('addresses.update');
    Route::delete('/addresses/{address}', [App\Http\Controllers\AddressController::class, 'destroy'])->name('addresses.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}/view-employee', [NotificationController::class, 'viewEmployee'])->name('notifications.view-employee');
    Route::post('/notifications/{notification}/cancel', [NotificationController::class, 'cancel'])->name('notifications.cancel');
    Route::post('/notifications/{notification}/renew', [NotificationController::class, 'renew'])->name('notifications.renew');
    Route::post('/notifications/{notification}/restore', [NotificationController::class, 'restore'])->name('notifications.restore');
    Route::delete('/notifications/{notification}/force-delete', [NotificationController::class, 'forceDelete'])->name('notifications.forceDelete');
});

// === เพิ่มโค้ดส่วนนี้เข้าไป ===
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/roles-permissions', [App\Http\Controllers\Admin\AdminController::class, 'indexRolesAndPermissions'])->name('roles_permissions.index');
});
// === สิ้นสุดส่วนที่ต้องเพิ่ม ===

require __DIR__.'/auth.php';

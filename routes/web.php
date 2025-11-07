<?php
// routes/web.php
use Illuminate\Support\Facades\Route;
// =============================================================
// V2.4-S21: Controller Imports - Ensure correct Namespaces
// (Jules: กรุณาตรวจสอบ Path เหล่านี้ให้ตรงกับโครงสร้างโปรเจกต์จริงของคุณ)
// =============================================================
// --- Core/Auth (ตัวอย่าง) ---
use App\Http\Controllers\DashboardController;
// --- Employer Controllers ---
use App\Http\Controllers\Employer\EmployerEmployeeController;
use App\Http\Controllers\Employer\JobTicketController;
use App\Http\Controllers\Employer\TicketReplyController;
// V2.4-S21: CRITICAL FIX (ReflectionException) - Import TemporaryUploadController จาก Path ที่ถูกต้อง.
// (สมมติฐานว่าอยู่ใน Employer namespace ตามการวิเคราะห์ Snapshot)
use App\Http\Controllers\Employer\TemporaryUploadController;
// --- Admin Controllers ---
use App\Http\Controllers\Admin\AdminJobTicketController;
use App\Http\Controllers\Admin\AdminTicketReplyController;
// =============================================================
// Public Routes
// =============================================================
Route::get('/', function () {
    return redirect()->route('login');
});
// ===================================================================================
// Authenticated Routes (Protected by 'auth' middleware)
// V2.4-S21: CRITICAL FIX (Auth Flaw) - EVERYTHING below MUST be inside this group.
// ===================================================================================
Route::middleware(['auth'])->group(function () {
    // --- Core Routes (ตัวอย่าง) ---
    // Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // --- Employer Routes ---
    // (Jules: ตรวจสอบ Resource Routes อื่นๆ ที่จำเป็นสำหรับ Employer)
    Route::resource('tickets', JobTicketController::class);
    Route::post('tickets/{ticket}/replies', [TicketReplyController::class, 'store'])->name('tickets.replies.store');
    // --- Admin Routes ---
    Route::middleware(['can:manage-tickets'])->prefix('admin')->name('admin.')->group(function () {
        // (Jules: ตรวจสอบ Resource Routes อื่นๆ ที่จำเป็นสำหรับ Admin)
        // Smart Tickets (Admin Management)
        Route::get('tickets/create', [AdminJobTicketController::class, 'create'])->name('tickets.create');
        Route::post('tickets', [AdminJobTicketController::class, 'store'])->name('tickets.store');
        Route::resource('tickets', AdminJobTicketController::class)->except(['create', 'store']);
        Route::post('tickets/{ticket}/replies', [AdminTicketReplyController::class, 'store'])->name('tickets.replies.store');
    });
    // ======================================================================
    // --- Routes for API-WEB (AJAX calls) ---
    // V2.4-S21: CRITICAL FIX - This group MUST be inside the 'auth' group.
    // ======================================================================
    Route::prefix('api-web')->name('api-web.')->group(function () {
        // Temporary Uploads - Pointing to the CORRECT controller path
        Route::post('temp_upload', [App\Http\Controllers\Api\TemporaryUploadController::class, 'store'])->name('temp_upload.store');
        // Employee Data (Used by Smart Ticket Modals)
        Route::prefix('employer')->name('employer.')->group(function () {
            Route::get('employees', [EmployerEmployeeController::class, 'index'])->name('employees.index');
        });
    });
});
// ===================================================================================
// End of Authenticated Routes
// ===================================================================================
require __DIR__.'/auth.php';

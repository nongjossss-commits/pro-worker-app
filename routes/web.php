<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\DelegateController;
use App\Http\Controllers\EmployerController;
use App\Http\Controllers\ImporterController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\Admin\AdminJobTicketController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\JobOwnerController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Api\EmployerEmployeeController;
use App\Http\Controllers\Api\TemporaryUploadController;
use App\Http\Controllers\TicketReplyController;
use App\Http\Controllers\Admin\TicketStatusController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\ChatController; // Added ChatController

Route::get('/thai-addresses', [AddressController::class, 'getThaiAddressData'])->name('addresses.thai_data');

// Language Switch Route
Route::get('lang/{locale}', [App\Http\Controllers\LanguageController::class, 'switch'])->name('lang.switch');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/preview', [PreviewController::class, 'show'])->name('global.preview');
    // Profile routes from Breeze
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Chat Routes
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/contacts', [ChatController::class, 'fetchContacts'])->name('contacts');
        Route::get('/messages/{userId}', [ChatController::class, 'fetchMessages'])->name('messages');
        Route::post('/messages/{userId}/read', [ChatController::class, 'markAsRead'])->name('read');
        Route::post('/send', [ChatController::class, 'sendMessage'])->name('send');
        Route::post('/profile/update', [ChatController::class, 'updateProfile'])->name('profile.update_info');
        Route::post('/upload', [ChatController::class, 'uploadFile'])->name('upload');
    });

    // Application routes that require login
    Route::get('/employers/export', [EmployerController::class, 'export'])->name('employers.export');
    Route::get('/employers/{employer}/export-employees', [EmployerController::class, 'exportEmployees'])->name('employers.exportEmployees');
    Route::get('/employers/{employer}/export-history', [EmployerController::class, 'exportHistory'])->name('employers.exportHistory');
    Route::resource('employers', EmployerController::class);
    Route::get('/employers/{employer}/employees/filter', [EmployerController::class, 'filterEmployees'])->name('employers.employees.filter');
    Route::get('employers/{employer}/history', [EmployerController::class, 'filterHistory'])->name('employers.history.filter');
    Route::post('employees/{employee}/terminate', [EmployeeController::class, 'terminate'])->name('employees.terminate');
    Route::post('employees/{employee}/reinstate', [EmployeeController::class, 'reinstate'])->name('employees.reinstate');
    Route::post('/employees/{employee}/restore', [EmployeeController::class, 'restore'])->name('employees.restore')->withTrashed();
    Route::delete('/employees/{employee}/force-delete', [EmployeeController::class, 'forceDelete'])->name('employees.forceDelete')->withTrashed();
    Route::get('/employees/{employee}/locate', [EmployeeController::class, 'locate'])->name('employees.locate');
    Route::get('/employees/{employee}/create-job', [JobController::class, 'createFromEmployee'])->name('jobs.create_from_employee');
    Route::get('/employees/export', [EmployeeController::class, 'export'])->name('employees.export');
    Route::post('/employees/advanced-export', [EmployeeController::class, 'advancedExport'])->name('employees.advanced_export');
    Route::get('/employees/history', [EmployeeController::class, 'historyIndex'])->name('employees.history');
    Route::get('/employees/{employee}/documents/{field}', [EmployeeController::class, 'serveDocument'])->name('employees.documents.serve');

    // Advanced Bulk Edit Routes (Must come BEFORE resource route)
    Route::post('employees/bulk-edit/select-fields', [EmployeeController::class, 'bulkEditSelectFields'])->name('employees.bulk_edit.select_fields');
    Route::post('employees/bulk-edit/form', [EmployeeController::class, 'bulkEditForm'])->name('employees.bulk_edit.form');
    Route::put('employees/bulk-update', [EmployeeController::class, 'bulkUpdate'])->name('employees.bulk_update');

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

    // --- V2.4: Employer Ticket Routes ---
    Route::resource('tickets', TicketController::class)->only([
        'index', 'create', 'store', 'show'
    ]);

    Route::post('tickets/{ticket}/replies', [TicketReplyController::class, 'store'])->name('tickets.replies.store');
    Route::delete('tickets/messages/{message}', [\App\Http\Controllers\TicketMessageController::class, 'destroy'])->name('tickets.messages.destroy');

    // --- V2.4-S5/S6: Internal API Routes for Web Interface ---
    Route::prefix('api-web')->name('api-web.')->group(function () {
        Route::get('employer/employees', [EmployerEmployeeController::class, 'index'])->name('employer.employees.index');
        Route::get('employers/list', [EmployerController::class, 'listApi'])->name('employers.list');
        Route::post('temp-upload', [TemporaryUploadController::class, 'store'])->name('temp_upload.store');
    });

    Route::post('employees/{employee}/transfer', [EmployeeController::class, 'transfer'])->name('employees.transfer');
    Route::post('employees/bulk-transfer', [EmployeeController::class, 'bulkTransfer'])->name('employees.bulkTransfer');
    Route::post('employees/bulk-to-ticket', [App\Http\Controllers\TicketRedirectController::class, 'bulkToTicket'])->name('employees.bulk_to_ticket');

    // === Group & Team Routes ===
    Route::get('/groups', [App\Http\Controllers\GroupTeamController::class, 'index'])->name('groups.index');
    Route::get('/groups/affiliated', [App\Http\Controllers\GroupTeamController::class, 'indexAffiliated'])->name('groups.affiliated.index');
    Route::get('/groups/affiliated/{employer}/manage', [App\Http\Controllers\GroupTeamController::class, 'manageAffiliated'])->name('groups.affiliated.manage');
    Route::get('/groups/independent/manage', [App\Http\Controllers\GroupTeamController::class, 'manageIndependent'])->name('groups.independent.manage');
    Route::post('/groups', [App\Http\Controllers\GroupTeamController::class, 'storeGroup'])->name('groups.store');
    Route::put('/groups/{group}', [App\Http\Controllers\GroupTeamController::class, 'updateGroup'])->name('groups.update');
    Route::delete('/groups/{group}', [App\Http\Controllers\GroupTeamController::class, 'destroyGroup'])->name('groups.destroy');
    Route::post('/groups/{group}/teams', [App\Http\Controllers\GroupTeamController::class, 'storeTeam'])->name('groups.teams.store');
    Route::put('/groups/teams/{team}', [App\Http\Controllers\GroupTeamController::class, 'updateTeam'])->name('groups.teams.update');
    Route::delete('/groups/teams/{team}', [App\Http\Controllers\GroupTeamController::class, 'destroyTeam'])->name('groups.teams.destroy');
    Route::get('/api-web/groups/employees/search', [App\Http\Controllers\GroupTeamController::class, 'searchEmployees'])->name('api-web.groups.employees.search');
    Route::post('/groups/teams/{team}/members', [App\Http\Controllers\GroupTeamController::class, 'addMember'])->name('groups.teams.members.add');
    Route::delete('/groups/teams/{team}/members/{employee}', [App\Http\Controllers\GroupTeamController::class, 'removeMember'])->name('groups.teams.members.remove');
    // Route for "Tag" click
    Route::get('/groups/{group}/locate/{employee}', [App\Http\Controllers\GroupTeamController::class, 'locateMember'])->name('groups.locate_member');
});

// === V2.4: Admin/Staff Ticket Management Routes (NEW Group) ===
Route::middleware(['auth', 'permission:manage-tickets'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('tickets/create', [AdminJobTicketController::class, 'create'])->name('tickets.create');
    Route::post('tickets', [AdminJobTicketController::class, 'store'])->name('tickets.store');

    Route::get('tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
    Route::get('tickets/{ticket}', [AdminTicketController::class, 'show'])->name('tickets.show');
    Route::post('tickets/{ticket}/replies', [TicketReplyController::class, 'store'])->name('tickets.replies.store');

    Route::post('tickets/{ticket}/resolve', [TicketStatusController::class, 'resolve'])->name('tickets.resolve');
    Route::post('tickets/{ticket}/reject', [TicketStatusController::class, 'reject'])->name('tickets.reject');
    Route::post('tickets/{ticket}/forward', [TicketStatusController::class, 'forward'])->name('tickets.forward');
    Route::post('tickets/{ticket}/update-assignment', [AdminTicketController::class, 'updateAssignment'])->name('tickets.updateAssignment');

    Route::delete('tickets/messages/{message}', [\App\Http\Controllers\TicketMessageController::class, 'destroy'])->name('tickets.messages.destroy');
});

use App\Http\Controllers\Admin\NotificationSettingController;
use App\Http\Controllers\Admin\ActivityLogController;

// === Existing Admin Routes (role:admin) ===
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/activity-logs/search', [ActivityLogController::class, 'search'])->name('activity-logs.search');
    Route::get('/activity-logs/{year}', [ActivityLogController::class, 'showYear'])->name('activity-logs.year');
    Route::get('/activity-logs/{year}/{month}', [ActivityLogController::class, 'showMonth'])->name('activity-logs.month');
    Route::get('/activity-logs/{year}/{month}/{day}', [ActivityLogController::class, 'showDay'])->name('activity-logs.day');

    Route::get('/notification-settings', [NotificationSettingController::class, 'index'])->name('notification_settings.index');
    Route::post('/notification-settings', [NotificationSettingController::class, 'update'])->name('notification_settings.update');
    Route::resource('users', UserController::class);
    Route::get('/roles-permissions', [App\Http\Controllers\Admin\AdminController::class, 'indexRolesAndPermissions'])->name('roles_permissions.index');

    Route::get('/settings/completeness', [App\Http\Controllers\Admin\CompletenessSettingsController::class, 'index'])->name('settings.completeness.index');
    Route::post('/settings/completeness', [App\Http\Controllers\Admin\CompletenessSettingsController::class, 'store'])->name('settings.completeness.store');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::resource('notifications', App\Http\Controllers\Admin\NotificationSettingController::class);
    });
});

// === Download Center Routes ===
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/downloads/tasks', [App\Http\Controllers\DownloadController::class, 'index'])->name('downloads.index');
    Route::post('/downloads/initiate', [App\Http\Controllers\DownloadController::class, 'initiate'])->name('downloads.initiate');
    Route::get('/downloads/{task}/file', [App\Http\Controllers\DownloadController::class, 'download'])->name('downloads.download');
});

// === Incomplete Employees ===
Route::middleware(['auth', 'permission:manage-tickets'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/incomplete-employees', [App\Http\Controllers\Admin\IncompleteEmployeeController::class, 'index'])->name('incomplete_employees.index');
});

// --- Central Trash System ---
Route::middleware(['auth', 'permission:view-trash'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/trash', [\App\Http\Controllers\Admin\TrashController::class, 'index'])
         ->name('trash.index');

    Route::get('/trash/export', [\App\Http\Controllers\Admin\TrashController::class, 'exportTrash'])->name('trash.export');

    Route::post('/trash/settings', [\App\Http\Controllers\Admin\TrashController::class, 'updateSettings'])->name('trash.updateSettings');

    Route::post('/trash/{model}/{id}/restore', [\App\Http\Controllers\Admin\TrashController::class, 'restore'])
         ->name('trash.restore')
         ->withTrashed();

    Route::delete('/trash/{model}/{id}/force-delete', [\App\Http\Controllers\Admin\TrashController::class, 'forceDelete'])
         ->name('trash.forceDelete')
         ->withTrashed();
});

require __DIR__.'/auth.php';

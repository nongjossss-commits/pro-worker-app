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
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FinancialController; // Import
use App\Http\Controllers\SuperAdmin\SettingsController as SuperAdminSettingsController;

Route::middleware(['auth', 'role:super-admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/settings', [SuperAdminSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SuperAdminSettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/visibility', [SuperAdminSettingsController::class, 'updateVisibility'])->name('settings.update-visibility');
    Route::get('/sidebar', [SuperAdminSettingsController::class, 'renderSidebar'])->name('sidebar');
});

// Menu Unlock Routes (Publicly accessible for auth users)
Route::middleware(['auth'])->group(function () {
    Route::get('/menu-unlock/{key}', [SuperAdminSettingsController::class, 'unlockForm'])->name('menu.unlock.form');
    Route::post('/menu-unlock/{key}', [SuperAdminSettingsController::class, 'unlock'])->name('menu.unlock');
    Route::get('/menu-check/{key}', [SuperAdminSettingsController::class, 'checkAccess'])->name('menu.check'); // NEW AJAX Check
});

Route::get('/thai-addresses', [AddressController::class, 'getThaiAddressData'])->name('addresses.thai_data');

// Language Switch Route
Route::get('lang/{locale}', [App\Http\Controllers\LanguageController::class, 'switch'])->name('lang.switch');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/index', [App\Http\Controllers\HomeController::class, 'index'])->middleware(['auth', 'verified'])->name('home');

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'menu:dashboard'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/preview', [PreviewController::class, 'show'])->name('global.preview');
    // Profile routes from Breeze
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Chat Routes
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/contacts', [ChatController::class, 'fetchContacts'])->name('contacts');
        Route::get('/messages/{id}', [ChatController::class, 'fetchMessages'])->name('messages'); // Changed {userId} to {id}
        Route::get('/check-new', [ChatController::class, 'checkNewMessages'])->name('check_new');
        Route::post('/mark-as-read', [ChatController::class, 'markAsRead'])->name('mark_as_read');
        Route::post('/send', [ChatController::class, 'sendMessage'])->name('send');
        Route::post('/profile/update', [ChatController::class, 'updateProfile'])->name('profile.update_info');
        Route::post('/upload', [ChatController::class, 'uploadFile'])->name('upload');
        Route::post('/groups', [ChatController::class, 'createGroup'])->name('groups.create'); // NEW
        Route::post('/groups/{id}/update', [ChatController::class, 'updateGroup'])->name('groups.update'); // NEW
        Route::delete('/groups/{id}', [ChatController::class, 'destroyGroup'])->name('groups.destroy'); // NEW
        Route::get('/groups/{id}', [ChatController::class, 'getGroupDetails'])->name('groups.details'); // NEW
        Route::post('/groups/{id}/members', [ChatController::class, 'addMember'])->name('groups.members.add'); // NEW
        Route::delete('/groups/{id}/members/{userId}', [ChatController::class, 'removeMember'])->name('groups.members.remove'); // NEW
        Route::get('/search-users', [ChatController::class, 'searchUsers'])->name('users.search'); // NEW
    });

    // Application routes that require login
    Route::get('/employers/export', [EmployerController::class, 'export'])->name('employers.export');
    Route::get('/employers/{employer}/export-employees', [EmployerController::class, 'exportEmployees'])->name('employers.exportEmployees');
    Route::get('/employers/{employer}/export-history', [EmployerController::class, 'exportHistory'])->name('employers.exportHistory');
    Route::resource('employers', EmployerController::class)->middleware('menu:employers');
    Route::get('/employers/{employer}/locate', [EmployerController::class, 'locate'])->name('employers.locate');
    Route::get('/employers/{employer}/employees/filter', [EmployerController::class, 'filterEmployees'])->name('employers.employees.filter');
    Route::get('employers/{employer}/history', [EmployerController::class, 'filterHistory'])->name('employers.history.filter');
    Route::post('employees/{employee}/terminate', [EmployeeController::class, 'terminate'])->name('employees.terminate');
    Route::get('/employers/{employer}/documents/{field}/pdf', [EmployerController::class, 'downloadDocumentAsPdf'])->name('employers.documents.pdf');
    Route::post('employees/{employee}/reinstate', [EmployeeController::class, 'reinstate'])->name('employees.reinstate');
    Route::post('/employees/{employee}/restore', [EmployeeController::class, 'restore'])->name('employees.restore')->withTrashed();
    Route::delete('/employees/{employee}/force-delete', [EmployeeController::class, 'forceDelete'])->name('employees.forceDelete')->withTrashed();
    Route::get('/employees/{employee}/locate', [EmployeeController::class, 'locate'])->name('employees.locate');
    Route::get('/employees/{employee}/create-job', [JobController::class, 'createFromEmployee'])->name('jobs.create_from_employee');
    Route::get('/employees/export', [EmployeeController::class, 'export'])->name('employees.export');
    Route::post('/employees/advanced-export', [EmployeeController::class, 'advancedExport'])->name('employees.advanced_export');
    Route::get('/employees/history', [EmployeeController::class, 'historyIndex'])->name('employees.history');
    Route::get('/employees/{employee}/documents/{field}', [EmployeeController::class, 'serveDocument'])->name('employees.documents.serve');
    Route::get('/employees/{employee}/documents/{field}/pdf', [EmployeeController::class, 'downloadDocumentAsPdf'])->name('employees.documents.pdf');
    Route::get('custom-fields/{id}/pdf', [App\Http\Controllers\CustomFieldController::class, 'downloadCustomFieldPdf'])->name('custom-fields.pdf');

    // Import Routes
    Route::get('employees/import', [App\Http\Controllers\ImportEmployeeController::class, 'index'])->name('employees.import_view');
    Route::post('employees/import', [App\Http\Controllers\ImportEmployeeController::class, 'store'])->name('employees.import');
    Route::get('employees/template', [App\Http\Controllers\ImportEmployeeController::class, 'downloadTemplate'])->name('employees.template');
    Route::post('employees/fetch-batch', [App\Http\Controllers\ImportEmployeeController::class, 'fetchBatch'])->name('employees.fetch_batch');
    Route::post('employees/import/cancel', [App\Http\Controllers\ImportEmployeeController::class, 'cancelImport'])->name('employees.import.cancel');

    // Advanced Bulk Edit Routes (Must come BEFORE resource route)
    Route::post('employees/bulk-edit/select-fields', [EmployeeController::class, 'bulkEditSelectFields'])->name('employees.bulk_edit.select_fields');
    Route::post('employees/bulk-edit/form', [EmployeeController::class, 'bulkEditForm'])->name('employees.bulk_edit.form');
    Route::put('employees/bulk-update', [EmployeeController::class, 'bulkUpdate'])->name('employees.bulk_update');
    // Also allow POST for AJAX bulk update if needed, though PUT is standard resource
    Route::post('employees/bulk-update', [EmployeeController::class, 'bulkUpdate']);

    Route::post('employees/photo/enhance', [EmployeeController::class, 'enhancePhoto'])->name('employees.photo.enhance');

    Route::resource('employees', EmployeeController::class)->middleware('menu:employees');
    Route::resource('importers', ImporterController::class)->middleware('menu:importers');
    Route::resource('agents', AgentController::class)->middleware('menu:agents');
    Route::resource('delegates', DelegateController::class)->middleware('menu:delegates');

    Route::get('/notifications/export', [NotificationController::class, 'export'])->name('notifications.export');
    Route::get('/employees/export', [EmployeeController::class, 'export'])->name('employees.export');
    Route::get('/employers/{employer}/export-employees', [EmployerController::class, 'exportEmployees'])->name('employers.exportEmployees');

    Route::resource('job-owners', JobOwnerController::class)->only(['index', 'store', 'destroy']);

    Route::post('/addresses', [App\Http\Controllers\AddressController::class, 'store'])->name('addresses.store');
    Route::get('/addresses/{address}/edit', [App\Http\Controllers\AddressController::class, 'edit'])->name('addresses.edit');
    Route::put('/addresses/{address}', [App\Http\Controllers\AddressController::class, 'update'])->name('addresses.update');
    Route::delete('/addresses/{address}', [App\Http\Controllers\AddressController::class, 'destroy'])->name('addresses.destroy');
    Route::post('/addresses/{address}/set-document', [App\Http\Controllers\AddressController::class, 'setDocumentAddress'])->name('addresses.set_document');

    Route::get('/notifications', [NotificationController::class, 'index'])->middleware('menu:notifications')->name('notifications.index');
    Route::post('/notifications/check-expiries', [NotificationController::class, 'checkExpiries'])->name('notifications.check-expiries');
    Route::get('/notifications/{notification}/view-employee', [NotificationController::class, 'viewEmployee'])->name('notifications.view-employee');
    Route::post('/notifications/{notification}/cancel', [NotificationController::class, 'cancel'])->name('notifications.cancel');
    Route::post('/notifications/{notification}/renew', [NotificationController::class, 'renew'])->name('notifications.renew');
    Route::post('/notifications/{notification}/restore', [NotificationController::class, 'restore'])->name('notifications.restore');
    Route::delete('/notifications/{notification}/force-delete', [NotificationController::class, 'forceDelete'])->name('notifications.forceDelete');

    // Web Push Subscriptions
    Route::post('/push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'store'])->name('push.subscribe');

    // --- V2.4: Employer Ticket Routes ---
    Route::resource('tickets', TicketController::class)->only([
        'index', 'create', 'store', 'show', 'destroy'
    ])->middleware('menu:employer_ticket');

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
Route::middleware(['auth', 'permission:manage-tickets', 'menu:ticket_inbox'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('tickets/create', [AdminJobTicketController::class, 'create'])->name('tickets.create');
    Route::post('tickets', [AdminJobTicketController::class, 'store'])->name('tickets.store');

    Route::get('tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
    Route::get('tickets/{ticket}', [AdminTicketController::class, 'show'])->name('tickets.show');
    Route::post('tickets/{ticket}/replies', [TicketReplyController::class, 'store'])->name('tickets.replies.store');

    // PDF Generation
    Route::post('pdf-templates/generate', [\App\Http\Controllers\Admin\PdfGenerationController::class, 'showGenerateModal'])->name('pdf-templates.generate.modal');
    Route::get('pdf-templates/generate', function () {
        return redirect()->route('employees.index')->with('error', 'Please select employees to generate PDF.');
    });
    Route::post('pdf-templates/generate/process', [\App\Http\Controllers\Admin\PdfGenerationController::class, 'process'])->name('pdf-templates.generate.process');

    // PDF Templates
    Route::get('pdf-templates/list-templates', [\App\Http\Controllers\Admin\PdfTemplateController::class, 'listTemplates'])->name('pdf-templates.list'); // AJAX API
    Route::get('pdf-templates/{pdf_template}/file', [\App\Http\Controllers\Admin\PdfTemplateController::class, 'file'])->name('pdf-templates.file');
    Route::resource('pdf-templates', \App\Http\Controllers\Admin\PdfTemplateController::class)->middleware('menu:pdf_templates')->except(['show']);
    Route::get('pdf-templates/{pdf_template}/builder', [\App\Http\Controllers\Admin\PdfTemplateController::class, 'builder'])->name('pdf-templates.builder');

    Route::post('tickets/{ticket}/resolve', [TicketStatusController::class, 'resolve'])->name('tickets.resolve');
    Route::post('tickets/{ticket}/reject', [TicketStatusController::class, 'reject'])->name('tickets.reject');
    Route::post('tickets/{ticket}/forward', [TicketStatusController::class, 'forward'])->name('tickets.forward');
    Route::post('tickets/{ticket}/in-progress', [TicketStatusController::class, 'inProgress'])->name('tickets.in_progress');
    Route::post('tickets/{ticket}/update-assignment', [AdminTicketController::class, 'updateAssignment'])->name('tickets.updateAssignment');
    Route::post('tickets/{ticket}/hide', [AdminTicketController::class, 'hide'])->name('tickets.hide'); // Re-enabled for individual ticket hiding
    Route::post('tickets/employers/{user}/hide', [AdminTicketController::class, 'hideEmployer'])->name('tickets.hideEmployer'); // V2.5.1 Hide Employer Box
    Route::post('tickets/employers/{user}/unhide', [AdminTicketController::class, 'unhideEmployer'])->name('tickets.unhideEmployer'); // V2.5.2 Unhide Employer Box

    Route::delete('tickets/messages/{message}', [\App\Http\Controllers\TicketMessageController::class, 'destroy'])->name('tickets.messages.destroy');

    // Business Types
    Route::resource('business-types', \App\Http\Controllers\Admin\BusinessTypeController::class)->only(['index', 'store', 'destroy']);

    // Global Witnesses Management
    Route::get('/witnesses', [App\Http\Controllers\Admin\GlobalWitnessController::class, 'index'])->name('witnesses.index');
    Route::put('/witnesses/{id}', [App\Http\Controllers\Admin\GlobalWitnessController::class, 'update'])->name('witnesses.update');
});

// === Production & Workflow User Routes ===
Route::middleware(['auth'])->group(function () {
    // Registration Resolution Routes (P Production > Registration)
    // MOVED ABOVE 'production' resource to prevent route masking
    Route::prefix('production/registration')->middleware('menu:registration_resolution')->name('production.registration.')->group(function () {
        Route::get('/', [App\Http\Controllers\Production\RegistrationController::class, 'index'])->name('index');
        Route::get('/employer/{employer}/employees', [App\Http\Controllers\Production\RegistrationController::class, 'fetchEmployees'])->name('employer.employees')->withTrashed();
        Route::get('/employer/{employer}/history', [App\Http\Controllers\Production\RegistrationController::class, 'fetchHistory'])->name('employer.history');
        Route::get('/import', [App\Http\Controllers\Production\RegistrationController::class, 'importView'])->name('import');
        Route::get('/create', [App\Http\Controllers\Production\RegistrationController::class, 'create'])->name('create');
        Route::post('/store', [App\Http\Controllers\Production\RegistrationController::class, 'store'])->name('store');

        // Stats & Lazy Loading Routes
        Route::post('/stats-batch', [App\Http\Controllers\Production\RegistrationController::class, 'batchStats'])->name('stats.batch');
        Route::get('/employer/{employer}/finance-tab', [App\Http\Controllers\Production\RegistrationController::class, 'loadFinancialTab'])->name('finance.tab');

        // Step Management
        Route::post('/steps', [App\Http\Controllers\Production\RegistrationController::class, 'storeStep'])->name('steps.store');
        Route::put('/steps/{step}', [App\Http\Controllers\Production\RegistrationController::class, 'updateStep'])->name('steps.update');
        Route::post('/steps/reorder', [App\Http\Controllers\Production\RegistrationController::class, 'reorderSteps'])->name('steps.reorder');
        Route::delete('/steps/{step}', [App\Http\Controllers\Production\RegistrationController::class, 'destroyStep'])->name('steps.destroy');

        // Progress Updates
        Route::post('/progress/{employee}', [App\Http\Controllers\Production\RegistrationController::class, 'updateProgress'])->name('progress.update');

        // Operator
        Route::post('/{employee}/toggle-operator', [App\Http\Controllers\Production\RegistrationController::class, 'toggleOperator'])->name('toggle_operator');

        // Custom Fields
        Route::post('/custom-fields/{employee}', [App\Http\Controllers\Production\RegistrationController::class, 'storeCustomField'])->name('custom_fields.store');
        Route::delete('/custom-fields/{field}', [App\Http\Controllers\Production\RegistrationController::class, 'destroyCustomField'])->name('custom_fields.destroy');

        // Employer Custom Fields
        Route::post('/employer-custom-fields/{employer}', [App\Http\Controllers\Production\RegistrationController::class, 'storeEmployerCustomField'])->name('employer_custom_fields.store');
        Route::put('/employer-custom-fields/{field}', [App\Http\Controllers\Production\RegistrationController::class, 'updateEmployerCustomField'])->name('employer_custom_fields.update');
        Route::delete('/employer-custom-fields/{field}', [App\Http\Controllers\Production\RegistrationController::class, 'destroyEmployerCustomField'])->name('employer_custom_fields.destroy');

        // Finalize & Restore (NEW)
        Route::post('/{employee}/finalize', [App\Http\Controllers\Production\RegistrationController::class, 'finalize'])->name('finalize');
        Route::post('/{employee}/restore-state', [App\Http\Controllers\Production\RegistrationController::class, 'restoreState'])->name('restore_state');
        Route::post('/bulk-finalize', [App\Http\Controllers\Production\RegistrationController::class, 'bulkFinalize'])->name('bulk_finalize');

        // Cancel, Restore (General), Delete
        Route::post('/{employee}/cancel', [App\Http\Controllers\Production\RegistrationController::class, 'cancel'])->name('cancel');
        Route::post('/{employee}/restore', [App\Http\Controllers\Production\RegistrationController::class, 'restore'])->name('restore');
        Route::delete('/{employee}/destroy', [App\Http\Controllers\Production\RegistrationController::class, 'destroy'])->name('destroy');

        // Employer Actions (NEW)
        Route::post('/employer/{employer}/cancel', [App\Http\Controllers\Production\RegistrationController::class, 'cancelEmployer'])->name('cancel_employer');
        Route::post('/employer/{employer}/restore', [App\Http\Controllers\Production\RegistrationController::class, 'restoreEmployer'])->name('restore_employer');
        Route::post('/employer/{employer}/resolution-status', [App\Http\Controllers\Production\RegistrationController::class, 'updateResolutionStatus'])->name('employer_resolution.update');

        // Biometrics
        Route::post('/{employee}/biometrics', [App\Http\Controllers\Production\RegistrationController::class, 'updateBiometrics'])->name('biometrics.update');
        Route::post('/{employee}/biometrics-toggle', [App\Http\Controllers\Production\RegistrationController::class, 'toggleBiometrics'])->name('biometrics.toggle');

        // Appointments (NEW)
        Route::post('/{employee}/appointment', [App\Http\Controllers\Production\RegistrationController::class, 'updateAppointment'])->name('appointment');
        Route::post('/{employee}/appointment-complete', [App\Http\Controllers\Production\RegistrationController::class, 'toggleAppointmentComplete'])->name('appointment_complete');

        // Daily Check
        Route::post('/{employee}/check-daily', [App\Http\Controllers\Production\RegistrationController::class, 'checkDaily'])->name('check_daily');
        Route::post('/{employee}/toggle-daily-check', [App\Http\Controllers\Production\RegistrationController::class, 'toggleDailyCheck'])->name('toggle_daily_check');

        Route::post('/settings/notification', [App\Http\Controllers\Production\RegistrationController::class, 'updateNotificationSettings'])->name('settings.notification');
        Route::get('/api/calendar', [App\Http\Controllers\Production\RegistrationController::class, 'getCalendarData'])->name('api.calendar');
        Route::get('/api/appointments-by-date', [App\Http\Controllers\Production\RegistrationController::class, 'getAppointmentsByDate'])->name('api.appointments_by_date');

        // Trash Routes
        Route::get('/trash', [App\Http\Controllers\Production\RegistrationController::class, 'fetchTrash'])->name('trash');
        Route::post('/trash/{id}/restore', [App\Http\Controllers\Production\RegistrationController::class, 'restoreTrash'])->name('trash.restore');
    });

    // Renewal Resolution Routes (NEW)
    Route::prefix('production/renewal')->middleware('menu:renewal_resolution')->name('production.renewal.')->group(function () {
        Route::get('/', [App\Http\Controllers\Production\RenewalController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Production\RenewalController::class, 'create'])->name('create');
        Route::post('/store', [App\Http\Controllers\Production\RenewalController::class, 'store'])->name('store');
        Route::get('/employer/{employer}/employees', [App\Http\Controllers\Production\RenewalController::class, 'fetchEmployees'])->name('employer.employees')->withTrashed();
        Route::get('/employer/{employer}/history', [App\Http\Controllers\Production\RenewalController::class, 'fetchHistory'])->name('employer.history');
        Route::get('/import', [App\Http\Controllers\Production\RenewalController::class, 'importView'])->name('import');
        Route::post('/configure-expiry', [App\Http\Controllers\Production\RenewalController::class, 'configureExpiry'])->name('configure_expiry');
        Route::post('/steps/reorder', [App\Http\Controllers\Production\RenewalController::class, 'reorderSteps'])->name('steps.reorder');

        // Step Management (Missing in original file for Renewal, adding now for consistency)
        Route::post('/steps', [App\Http\Controllers\Production\RenewalController::class, 'storeStep'])->name('steps.store');
        Route::put('/steps/{step}', [App\Http\Controllers\Production\RenewalController::class, 'updateStep'])->name('steps.update');
        Route::delete('/steps/{step}', [App\Http\Controllers\Production\RenewalController::class, 'destroyStep'])->name('steps.destroy');

        // Progress Updates (Missing in original file, needed for Renewal toggle step)
        Route::post('/progress/{employee}', [App\Http\Controllers\Production\RenewalController::class, 'updateProgress'])->name('progress.update');

        // Operator
        Route::post('/{employee}/toggle-operator', [App\Http\Controllers\Production\RenewalController::class, 'toggleOperator'])->name('toggle_operator');

        // Insurance
        Route::post('/{employee}/update-insurance', [App\Http\Controllers\Production\RenewalController::class, 'updateInsurance'])->name('update_insurance');

        // Daily Check
        Route::post('/{employee}/check-daily', [App\Http\Controllers\Production\RenewalController::class, 'checkDaily'])->name('check_daily');
        Route::post('/{employee}/toggle-daily-check', [App\Http\Controllers\Production\RenewalController::class, 'toggleDailyCheck'])->name('toggle_daily_check');

        // Actions
        Route::post('/{employee}/finalize', [App\Http\Controllers\Production\RenewalController::class, 'finalize'])->name('finalize');
        Route::post('/{employee}/cancel', [App\Http\Controllers\Production\RenewalController::class, 'cancel'])->name('cancel');
        Route::post('/{employee}/restore', [App\Http\Controllers\Production\RenewalController::class, 'restore'])->name('restore');
        Route::delete('/{employee}/destroy', [App\Http\Controllers\Production\RenewalController::class, 'destroy'])->name('destroy');

        // Employer Actions
        Route::post('/employer/{employer}/cancel', [App\Http\Controllers\Production\RenewalController::class, 'cancelEmployer'])->name('cancel_employer');
        Route::post('/employer/{employer}/restore', [App\Http\Controllers\Production\RenewalController::class, 'restoreEmployer'])->name('restore_employer');

        // Trash Routes
        Route::get('/trash', [App\Http\Controllers\Production\RenewalController::class, 'fetchTrash'])->name('trash');
        Route::post('/trash/{id}/restore', [App\Http\Controllers\Production\RenewalController::class, 'restoreTrash'])->name('trash.restore');
    });

    Route::resource('production', \App\Http\Controllers\ProductionController::class)->middleware('menu:production');

    // Additional Production Routes
    Route::post('production/{id}/add-employee', [\App\Http\Controllers\ProductionController::class, 'addEmployee'])->name('production.add_employee');
    Route::post('production/{id}/add-new-employee', [\App\Http\Controllers\ProductionController::class, 'addNewEmployee'])->name('production.add_new_employee');

    // NEW: Pre-Production Routes
    Route::post('production/{item}/send-to-workflow', [\App\Http\Controllers\ProductionController::class, 'sendToWorkflow'])->name('production.item.send_to_workflow');
    Route::post('production/steps', [\App\Http\Controllers\ProductionController::class, 'storeStep'])->name('production.steps.store');

    Route::post('production/{id}/toggle-status', [\App\Http\Controllers\ProductionController::class, 'toggleStatus'])->name('production.toggle_status');
    Route::post('production/{id}/upload-logo', [\App\Http\Controllers\ProductionController::class, 'uploadLogo'])->name('production.upload_logo');

    Route::middleware('menu:finance')->group(function() {
        Route::post('production/{id}/financial-groups', [\App\Http\Controllers\ProductionController::class, 'storeFinancialGroup'])->name('production.financial_groups.store');
        Route::put('production/{id}/financial-groups/{groupId}', [\App\Http\Controllers\ProductionController::class, 'updateFinancialGroup'])->name('production.financial_groups.update');
        Route::delete('production/{id}/financial-groups/{groupId}', [\App\Http\Controllers\ProductionController::class, 'destroyFinancialGroup'])->name('production.financial_groups.destroy');
    });

    // Financial Hub Routes (Central Menu)
    Route::middleware('menu:finance')->prefix('finance')->name('finance.')->group(function () {
        Route::get('/', [App\Http\Controllers\FinancialHubController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\FinancialHubController::class, 'createManual'])->name('create');
        Route::post('/store', [App\Http\Controllers\FinancialHubController::class, 'storeManual'])->name('store');
    });

    // Financial Routes
    Route::middleware('menu:finance')->group(function() {
        Route::post('production/{id}/transactions', [FinancialController::class, 'storeTransaction']);
        Route::put('production/transactions/{id}', [FinancialController::class, 'updateTransaction']); // For status updates
        Route::post('production/transactions/{id}', [FinancialController::class, 'updateTransaction']); // For file uploads (method spoofing)
        Route::delete('production/transactions/{id}', [FinancialController::class, 'destroyTransaction']);
    });
    // Replaced by dedicated ProductionDocumentController
    // Route::get('production/{id}/documents/{type}', [FinancialController::class, 'generateDocument']);
    Route::get('production/{id}/documents/{type}', [App\Http\Controllers\ProductionDocumentController::class, 'show'])->name('production.documents.show');

    // Workflow Routes
    Route::get('workflow', [\App\Http\Controllers\WorkflowController::class, 'index'])->middleware('menu:workflow')->name('workflow.index');
    Route::post('workflow/store', [\App\Http\Controllers\WorkflowController::class, 'store'])->name('workflow.store');
    Route::get('workflow/{order}/items', [\App\Http\Controllers\WorkflowController::class, 'fetchOrderItems'])->name('workflow.items');
    Route::get('workflow/{order}/history', [\App\Http\Controllers\WorkflowController::class, 'fetchOrderHistory'])->name('workflow.history');
    Route::get('workflow/item/{item}/card', [\App\Http\Controllers\WorkflowController::class, 'getItemHtml'])->name('workflow.item.card');
    Route::post('workflow/item/{item}/step-toggle', [\App\Http\Controllers\WorkflowController::class, 'toggleStep'])->name('workflow.step.toggle');
    Route::post('workflow/item/{item}/toggle-operator', [\App\Http\Controllers\WorkflowController::class, 'toggleOperator'])->name('workflow.item.toggle_operator');
    Route::post('workflow/item/{item}/appointment', [\App\Http\Controllers\WorkflowController::class, 'updateAppointmentDate'])->name('workflow.item.appointment');
    Route::post('workflow/item/{item}/appointment-complete', [\App\Http\Controllers\WorkflowController::class, 'toggleAppointmentComplete'])->name('workflow.item.appointment_complete');
    Route::post('workflow/appointments/export', [\App\Http\Controllers\WorkflowController::class, 'exportAppointments'])->name('workflow.appointments.export');
    Route::post('workflow/item/{item}/check-daily', [\App\Http\Controllers\WorkflowController::class, 'checkDaily'])->name('workflow.item.check_daily');
    Route::post('workflow/item/{item}/group', [\App\Http\Controllers\WorkflowController::class, 'updateGroup'])->name('workflow.item.group');
    Route::post('workflow/item/{item}/finalize', [\App\Http\Controllers\WorkflowController::class, 'finalizeItem'])->name('workflow.item.finalize');
    Route::post('workflow/item/{item}/cancel', [\App\Http\Controllers\WorkflowController::class, 'cancelItem'])->name('workflow.item.cancel');
    Route::post('workflow/item/{item}/restore', [\App\Http\Controllers\WorkflowController::class, 'restoreItem'])->name('workflow.item.restore');
    Route::post('workflow/item/{item}/send-back', [\App\Http\Controllers\WorkflowController::class, 'sendBackToPreProduction'])->name('workflow.item.send_back');
    Route::delete('workflow/item/{item}', [\App\Http\Controllers\WorkflowController::class, 'destroyItem'])->name('workflow.item.destroy');
    Route::get('workflow/api/resigned-employees', [\App\Http\Controllers\WorkflowController::class, 'searchResignedEmployees'])->name('workflow.api.resigned');
    Route::get('workflow/api/global-employees', [\App\Http\Controllers\WorkflowController::class, 'searchGlobalEmployees'])->name('workflow.api.global');
    Route::get('workflow/api/active-employees/{employerId}', [\App\Http\Controllers\WorkflowController::class, 'fetchEmployerActiveEmployees'])->name('workflow.api.active');
    Route::get('workflow/api/employer-teams/{employerId}', [\App\Http\Controllers\WorkflowController::class, 'getEmployerTeams'])->name('workflow.api.employer_teams');
    Route::get('workflow/api/calendar', [\App\Http\Controllers\WorkflowController::class, 'getCalendarData'])->name('workflow.api.calendar');
    Route::get('workflow/api/appointments-by-date', [\App\Http\Controllers\WorkflowController::class, 'getAppointmentsByDate'])->name('workflow.api.appointments_by_date');

    Route::post('workflow/item/{item}/team', [\App\Http\Controllers\WorkflowController::class, 'updateItemTeam'])->name('workflow.item.team');

    // Workflow Trash Routes
    Route::get('workflow/trash', [\App\Http\Controllers\WorkflowController::class, 'fetchTrash'])->name('workflow.trash');
    Route::post('workflow/trash/{id}/restore', [\App\Http\Controllers\WorkflowController::class, 'restoreTrash'])->name('workflow.trash.restore');
    Route::delete('workflow/trash/{id}/force-delete', [\App\Http\Controllers\WorkflowController::class, 'forceDeleteTrash'])->name('workflow.trash.force_delete');
    Route::post('workflow/trash/settings', [\App\Http\Controllers\WorkflowController::class, 'updateTrashSettings'])->name('workflow.trash.settings.update');

    Route::get('workflow/{id}', [\App\Http\Controllers\WorkflowController::class, 'show'])->name('workflow.show'); // Board
    Route::get('workflow/item/{item}', [\App\Http\Controllers\WorkflowController::class, 'showItem'])->name('workflow.item.show');
    Route::post('workflow/item/{item}/step', [\App\Http\Controllers\WorkflowController::class, 'storeStep'])->name('workflow.item.step.store');
    Route::post('workflow/item/{item}/update-credentials', [\App\Http\Controllers\WorkflowController::class, 'updateCredentials'])->name('workflow.item.update_credentials');

    // Workflow API Routes
    Route::post('workflow/api/bulk-step', [\App\Http\Controllers\WorkflowController::class, 'bulkStoreStep'])->name('workflow.api.bulk_step');

    // Workflow Configuration Routes (Steps)
    Route::post('workflow/steps', [\App\Http\Controllers\WorkflowController::class, 'storeStep'])->name('workflow.steps.store');
    Route::put('workflow/steps/{id}', [\App\Http\Controllers\WorkflowController::class, 'updateStep'])->name('workflow.steps.update');
    Route::delete('workflow/steps/{id}', [\App\Http\Controllers\WorkflowController::class, 'destroyStep'])->name('workflow.steps.destroy');
    Route::post('workflow/steps/reorder', [\App\Http\Controllers\WorkflowController::class, 'reorderSteps'])->name('workflow.steps.reorder');
    Route::post('workflow/settings/{workTypeId}/notification', [\App\Http\Controllers\WorkflowController::class, 'updateNotificationSettings'])->name('workflow.settings.notification');
});

use App\Http\Controllers\Admin\NotificationSettingController;
use App\Http\Controllers\Admin\ActivityLogController;

// === Existing Admin Routes (role:admin) ===
Route::middleware(['auth', 'role:admin|super-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->middleware('menu:activity_logs')->name('activity-logs.index');
    Route::get('/activity-logs/search', [ActivityLogController::class, 'search'])->name('activity-logs.search');
    Route::get('/activity-logs/{year}', [ActivityLogController::class, 'showYear'])->name('activity-logs.year');
    Route::get('/activity-logs/{year}/{month}', [ActivityLogController::class, 'showMonth'])->name('activity-logs.month');
    Route::get('/activity-logs/{year}/{month}/{day}', [ActivityLogController::class, 'showDay'])->name('activity-logs.day');

    Route::get('/notification-settings', [NotificationSettingController::class, 'index'])->name('notification_settings.index');
    Route::post('/notification-settings', [NotificationSettingController::class, 'update'])->name('notification_settings.update');
    Route::resource('users', UserController::class)->middleware('menu:user_management');
    Route::get('/roles-permissions', [App\Http\Controllers\Admin\AdminController::class, 'indexRolesAndPermissions'])->middleware('menu:roles_permissions')->name('roles_permissions.index');

    Route::get('/settings/completeness', [App\Http\Controllers\Admin\CompletenessSettingsController::class, 'index'])->name('settings.completeness.index');
    Route::post('/settings/completeness', [App\Http\Controllers\Admin\CompletenessSettingsController::class, 'store'])->name('settings.completeness.store');

    // Financial Settings
    Route::get('/settings/financial', [FinancialController::class, 'indexSettings'])->name('settings.financial.index');
    Route::post('/settings/financial', [FinancialController::class, 'storeProfile'])->name('settings.financial.store');
    Route::post('/settings/financial/{id}', [FinancialController::class, 'updateProfile'])->name('settings.financial.update');

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
Route::middleware(['auth', 'permission:manage-tickets', 'menu:incomplete_data'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/incomplete-employees', [App\Http\Controllers\Admin\IncompleteEmployeeController::class, 'index'])->name('incomplete_employees.index');
});

// --- Central Trash System ---
Route::middleware(['auth', 'permission:view-trash', 'menu:central_trash'])->prefix('admin')->name('admin.')->group(function () {

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

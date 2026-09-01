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
use App\Http\Controllers\SuperAdmin\DownloadProfileController;

Route::middleware(['auth', 'role:super-admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/settings', [SuperAdminSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SuperAdminSettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/visibility', [SuperAdminSettingsController::class, 'updateVisibility'])->name('settings.update-visibility');
    Route::get('/sidebar', [SuperAdminSettingsController::class, 'renderSidebar'])->name('sidebar');

    Route::post('/attachments/descriptions', [SuperAdminSettingsController::class, 'updateAttachmentDescriptions'])->name('attachments.descriptions');
    Route::post('/attachments/descriptions/single', [SuperAdminSettingsController::class, 'updateSingleAttachmentDescription'])->name('attachments.descriptions.single');
    Route::post('/settings/max-employees', [SuperAdminSettingsController::class, 'updateMaxEmployees'])->name('settings.max-employees');

    // Program Pricelist (SaaS pricing — for selling Pro-Worker to other clients)
    Route::post('/program-pricelist', [SuperAdminSettingsController::class, 'saveProgramPricelist'])->name('program-pricelist.save');
    Route::get('/program-pricelist/view', [SuperAdminSettingsController::class, 'programPricelistView'])->name('program-pricelist.view');

    // Program Sales — Provider Info + Trial/Service Contracts
    Route::post('/program-sales/provider', [SuperAdminSettingsController::class, 'saveProviderInfo'])->name('program-sales.provider.save');
    Route::get('/program-sales/contract/{type}', [SuperAdminSettingsController::class, 'programContractView'])
        ->where('type', 'trial|service')
        ->name('program-sales.contract.view');

    // Branding (logo + theme colors + app name)
    Route::post('/brand/logo', [SuperAdminSettingsController::class, 'uploadBrandLogo'])->name('brand.logo.upload');
    Route::post('/brand/logo/active', [SuperAdminSettingsController::class, 'setActiveBrandLogo'])->name('brand.logo.active');
    Route::post('/brand/logo/delete', [SuperAdminSettingsController::class, 'deleteBrandLogo'])->name('brand.logo.delete');
    Route::post('/brand/colors', [SuperAdminSettingsController::class, 'updateBrandColors'])->name('brand.colors.update');
    Route::post('/brand/colors/reset', [SuperAdminSettingsController::class, 'resetBrandColors'])->name('brand.colors.reset');
    Route::post('/brand/name', [SuperAdminSettingsController::class, 'updateBrandName'])->name('brand.name.update');
    Route::get('/manuals/bundle', [SuperAdminSettingsController::class, 'manualBundle'])->name('manuals.bundle');
    Route::get('/manuals/finance-bundle', [SuperAdminSettingsController::class, 'financeManualBundle'])->name('manuals.finance_bundle');
    Route::get('/manuals/training-bundle', [SuperAdminSettingsController::class, 'trainingBundle'])->name('manuals.training_bundle');
    Route::get('/manuals/training-finance-bundle', [SuperAdminSettingsController::class, 'trainingFinanceBundle'])->name('manuals.training_finance_bundle');

    Route::resource('download-profiles', DownloadProfileController::class)->except(['show']);

    // Contract lifecycle — Super Admin manages system license/contract end date,
    // temporary access extensions, and attachments. Handled by
    // App\Http\Controllers\SuperAdmin\ContractController.
    Route::post('/contract/save', [\App\Http\Controllers\SuperAdmin\ContractController::class, 'saveContract'])
         ->name('contract.save');
    Route::post('/contract/grace/enable', [\App\Http\Controllers\SuperAdmin\ContractController::class, 'enableGrace'])
         ->name('contract.grace.enable');
    Route::post('/contract/grace/stop', [\App\Http\Controllers\SuperAdmin\ContractController::class, 'stopGrace'])
         ->name('contract.grace.stop');
    Route::post('/contract/attachment/{slot}', [\App\Http\Controllers\SuperAdmin\ContractController::class, 'uploadAttachment'])
         ->where('slot', '[1-3]')
         ->name('contract.attachment.upload');
    Route::delete('/contract/attachment/{slot}', [\App\Http\Controllers\SuperAdmin\ContractController::class, 'deleteAttachment'])
         ->where('slot', '[1-3]')
         ->name('contract.attachment.delete');
    Route::get('/contract/attachment/{slot}/download', [\App\Http\Controllers\SuperAdmin\ContractController::class, 'downloadAttachment'])
         ->where('slot', '[1-3]')
         ->name('contract.attachment.download');
});

// Menu Unlock Routes (Publicly accessible for auth users)
Route::middleware(['auth'])->group(function () {
    Route::get('/menu-unlock/{key}', [SuperAdminSettingsController::class, 'unlockForm'])->name('menu.unlock.form');
    Route::post('/menu-unlock/{key}', [SuperAdminSettingsController::class, 'unlock'])->name('menu.unlock');
    Route::get('/menu-check/{key}', [SuperAdminSettingsController::class, 'checkAccess'])->name('menu.check'); // NEW AJAX Check
});

Route::get('/thai-addresses', [AddressController::class, 'getThaiAddressData'])->name('addresses.thai_data');

// Combined appointment reminder calendar (Registration + Renewal + Workflow)
// — popped up after login, see AuthenticatedSessionController + layouts/app.blade.php.
Route::middleware(['auth'])->group(function () {
    Route::get('/appointments/calendar', [\App\Http\Controllers\AppointmentReminderController::class, 'calendarData'])->name('appointments.calendar');
    Route::get('/appointments/by-date', [\App\Http\Controllers\AppointmentReminderController::class, 'appointmentsByDate'])->name('appointments.by-date');
});

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
    Route::post('/api/image-enhance', [\App\Http\Controllers\ImageEnhanceController::class, 'enhance'])->name('api.image-enhance');
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
    Route::post('/employers/check-duplicate', [EmployerController::class, 'checkDuplicate'])->name('employers.check_duplicate');
    Route::resource('employers', EmployerController::class)->middleware('menu:employers');
    Route::get('/employers/{employer}/locate', [EmployerController::class, 'locate'])->name('employers.locate');
    Route::get('/employers/{employer}/employees/filter', [EmployerController::class, 'filterEmployees'])->name('employers.employees.filter');
    Route::get('employers/{employer}/history', [EmployerController::class, 'filterHistory'])->name('employers.history.filter');
    // Employee state-change routes — guarded at the route level too (defence
    // in depth on top of the controller __construct middleware).
    Route::post('employees/{employee}/terminate', [EmployeeController::class, 'terminate'])->middleware('permission:terminate-employees')->name('employees.terminate');
    Route::get('/employers/{employer}/documents/{field}/pdf', [EmployerController::class, 'downloadDocumentAsPdf'])->name('employers.documents.pdf');
    Route::post('employees/{employee}/reinstate', [EmployeeController::class, 'reinstate'])->middleware('permission:terminate-employees')->name('employees.reinstate');
    Route::post('/employees/{employee}/restore', [EmployeeController::class, 'restore'])->middleware('permission:restore-employees')->name('employees.restore')->withTrashed();
    Route::delete('/employees/{employee}/force-delete', [EmployeeController::class, 'forceDelete'])->middleware('permission:force-delete-employees')->name('employees.forceDelete')->withTrashed();
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
    Route::put('employees/{employee}/update-menu-fields', [EmployeeController::class, 'updateMenuFields'])->name('employees.update_menu_fields');
    // Also allow POST for AJAX bulk update if needed, though PUT is standard resource
    Route::post('employees/bulk-update', [EmployeeController::class, 'bulkUpdate']);

    Route::post('employees/photo/enhance', [EmployeeController::class, 'enhancePhoto'])->name('employees.photo.enhance');

    Route::get('employees/search', [EmployeeController::class, 'search'])->name('employees.search');
    // Signal endpoint — when editing employee from a modal iframe, the form redirects here
    // so the parent window can detect "save complete" and close the modal.
    Route::get('employees/edit-modal-saved', function () {
        return response('<!doctype html><html><body><script>/* Edit Modal Save Signal */</script></body></html>');
    })->name('employees.edit-modal-saved');
    Route::post('/employees/check-duplicate', [EmployeeController::class, 'checkDuplicate'])->name('employees.check_duplicate');
    Route::resource('employees', EmployeeController::class)->middleware('menu:employees');
    Route::get('/importers/{importer}/documents/{field}/pdf', [ImporterController::class, 'downloadDocumentAsPdf'])->name('importers.documents.pdf');
    Route::resource('importers', ImporterController::class)->middleware('menu:importers');

    Route::resource('agents', AgentController::class)->middleware('menu:agents');

    Route::get('/delegates/{delegate}/documents/{field}/pdf', [DelegateController::class, 'downloadDocumentAsPdf'])->name('delegates.documents.pdf');
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

    Route::get('/notifications/popup-summary', [NotificationController::class, 'popupSummary'])->middleware('menu:notifications')->name('notifications.popup-summary');
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

        // Operators API for toggle assignment popup
        Route::get('operators', [App\Http\Controllers\Admin\UserController::class, 'listOperators'])->name('operators.list');
    });

    Route::post('employees/{employee}/transfer', [EmployeeController::class, 'transfer'])->name('employees.transfer');
    Route::post('employees/bulk-transfer', [EmployeeController::class, 'bulkTransfer'])->name('employees.bulkTransfer');
    Route::post('employees/bulk-move-attachments', [EmployeeController::class, 'bulkMoveAttachments'])->name('employees.bulkMoveAttachments');
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

    Route::post('tickets/{ticket}/resolve', [TicketStatusController::class, 'resolve'])->name('tickets.resolve');
    Route::post('tickets/{ticket}/reject', [TicketStatusController::class, 'reject'])->name('tickets.reject');
    Route::post('tickets/{ticket}/forward', [TicketStatusController::class, 'forward'])->name('tickets.forward');
    Route::post('tickets/{ticket}/in-progress', [TicketStatusController::class, 'inProgress'])->name('tickets.in_progress');
    Route::post('tickets/{ticket}/update-assignment', [AdminTicketController::class, 'updateAssignment'])->name('tickets.updateAssignment');
    Route::post('tickets/{ticket}/hide', [AdminTicketController::class, 'hide'])->name('tickets.hide'); // Re-enabled for individual ticket hiding
    Route::post('tickets/employers/{user}/hide', [AdminTicketController::class, 'hideEmployer'])->name('tickets.hideEmployer'); // V2.5.1 Hide Employer Box
    Route::post('tickets/employers/{user}/unhide', [AdminTicketController::class, 'unhideEmployer'])->name('tickets.unhideEmployer'); // V2.5.2 Unhide Employer Box

    Route::delete('tickets/messages/{message}', [\App\Http\Controllers\TicketMessageController::class, 'destroy'])->name('tickets.messages.destroy');

    // Global Witnesses Management
    Route::get('/witnesses', [App\Http\Controllers\Admin\GlobalWitnessController::class, 'index'])->name('witnesses.index');
    Route::put('/witnesses/{id}', [App\Http\Controllers\Admin\GlobalWitnessController::class, 'update'])->name('witnesses.update');
});

// === Business Types (used by employer create/edit dropdown — must be accessible to any authenticated user) ===
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('business-types', [\App\Http\Controllers\Admin\BusinessTypeController::class, 'index'])->name('business-types.index');
});
Route::middleware(['auth', 'role:admin|super-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('business-types', [\App\Http\Controllers\Admin\BusinessTypeController::class, 'store'])->name('business-types.store');
    Route::delete('business-types/{business_type}', [\App\Http\Controllers\Admin\BusinessTypeController::class, 'destroy'])->name('business-types.destroy');
});

// === PDF Templates (separate group — NOT tied to ticket_inbox menu) ===
Route::middleware(['auth', 'permission:manage-tickets', 'menu:pdf_templates'])->prefix('admin')->name('admin.')->group(function () {
    // PDF Generation (used from employees page — generate PDF for selected employees)
    Route::post('pdf-templates/generate', [\App\Http\Controllers\Admin\PdfGenerationController::class, 'showGenerateModal'])->name('pdf-templates.generate.modal');
    Route::get('pdf-templates/generate', function () {
        return redirect()->route('employees.index')->with('error', 'Please select employees to generate PDF.');
    });
    Route::post('pdf-templates/generate/process', [\App\Http\Controllers\Admin\PdfGenerationController::class, 'process'])->name('pdf-templates.generate.process');
    Route::get('pdf-templates/{pdf_template}/quick-print', [\App\Http\Controllers\Admin\PdfGenerationController::class, 'quickPrint'])->name('pdf-templates.quick-print');

    // Custom Witnesses API
    Route::get('pdf-templates/witnesses', [\App\Http\Controllers\Admin\WitnessController::class, 'index'])->name('pdf-templates.witnesses.index');
    Route::post('pdf-templates/witnesses', [\App\Http\Controllers\Admin\WitnessController::class, 'store'])->name('pdf-templates.witnesses.store');
    Route::post('pdf-templates/witnesses/{id}', [\App\Http\Controllers\Admin\WitnessController::class, 'update'])->name('pdf-templates.witnesses.update');
    Route::delete('pdf-templates/witnesses/{id}', [\App\Http\Controllers\Admin\WitnessController::class, 'destroy'])->name('pdf-templates.witnesses.destroy');

    // PDF Templates CRUD
    Route::post('pdf-templates/upload-image', [\App\Http\Controllers\Admin\PdfTemplateController::class, 'uploadImage'])->name('pdf-templates.upload-image');
    Route::get('pdf-templates/list-templates', [\App\Http\Controllers\Admin\PdfTemplateController::class, 'listTemplates'])->name('pdf-templates.list');
    Route::get('pdf-templates/{pdf_template}/file', [\App\Http\Controllers\Admin\PdfTemplateController::class, 'file'])->name('pdf-templates.file');
    Route::get('pdf-templates/{pdf_template}/preview', [\App\Http\Controllers\Admin\PdfTemplateController::class, 'preview'])->name('pdf-templates.preview');
    Route::resource('pdf-templates', \App\Http\Controllers\Admin\PdfTemplateController::class)->except(['show']);
    Route::get('pdf-templates/{pdf_template}/builder', [\App\Http\Controllers\Admin\PdfTemplateController::class, 'builder'])->name('pdf-templates.builder');
});

// === Production & Workflow User Routes ===
Route::middleware(['auth'])->group(function () {
    // Registration Resolution — Redirect base URL to default tab
    Route::get('production/registration', function () {
        $tab = \App\Models\ResolutionTab::where('type', 'registration')->ordered()->first();
        return redirect()->route('production.registration.index', ['resolutionTab' => $tab->id]);
    })->middleware('menu:registration_resolution')->name('production.registration.redirect');

    // Registration Resolution Routes (with tab parameter)
    Route::prefix('production/registration/{resolutionTab}')->middleware('menu:registration_resolution')->name('production.registration.')->group(function () {
        Route::get('/', [App\Http\Controllers\Production\RegistrationController::class, 'dashboard'])->name('index');
        Route::get('/operations', [App\Http\Controllers\Production\RegistrationController::class, 'index'])->name('operations');
        Route::get('/employer/{employer}/employees', [App\Http\Controllers\Production\RegistrationController::class, 'fetchEmployees'])->name('employer.employees')->withTrashed();
        Route::get('/employer/{employer}/select-all-ids', [App\Http\Controllers\Production\RegistrationController::class, 'selectAllEmployerEmployeeIds'])->name('employer.select_all_ids')->withTrashed();
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

        // Finalize & Restore
        Route::post('/{employee}/finalize', [App\Http\Controllers\Production\RegistrationController::class, 'finalize'])->name('finalize');
        Route::post('/{employee}/restore-state', [App\Http\Controllers\Production\RegistrationController::class, 'restoreState'])->name('restore_state');
        Route::post('/bulk-finalize', [App\Http\Controllers\Production\RegistrationController::class, 'bulkFinalize'])->name('bulk_finalize');

        // Cancel, Restore (General), Delete
        Route::post('/{employee}/cancel', [App\Http\Controllers\Production\RegistrationController::class, 'cancel'])->name('cancel');
        Route::post('/{employee}/restore', [App\Http\Controllers\Production\RegistrationController::class, 'restore'])->name('restore');
        Route::delete('/{employee}/destroy', [App\Http\Controllers\Production\RegistrationController::class, 'destroy'])->name('destroy');

        // Employer Actions
        Route::post('/employer/{employer}/cancel', [App\Http\Controllers\Production\RegistrationController::class, 'cancelEmployer'])->name('cancel_employer');
        Route::post('/employer/{employer}/restore', [App\Http\Controllers\Production\RegistrationController::class, 'restoreEmployer'])->name('restore_employer');
        Route::post('/employer/{employer}/resolution-status', [App\Http\Controllers\Production\RegistrationController::class, 'updateResolutionStatus'])->name('employer_resolution.update');
        Route::post('/employer/{employer}/resolution-note', [App\Http\Controllers\Production\RegistrationController::class, 'updateResolutionNote'])->name('employer_resolution.update_note');

        // Biometrics
        Route::post('/{employee}/biometrics', [App\Http\Controllers\Production\RegistrationController::class, 'updateBiometrics'])->name('biometrics.update');
        Route::post('/{employee}/biometrics-toggle', [App\Http\Controllers\Production\RegistrationController::class, 'toggleBiometrics'])->name('biometrics.toggle');

        // Remarks
        Route::post('/{employee}/remarks', [App\Http\Controllers\Production\RegistrationController::class, 'updateRemarks'])->name('remarks');

        // Appointments
        Route::post('/{employee}/appointment', [App\Http\Controllers\Production\RegistrationController::class, 'updateAppointment'])->name('appointment');
        Route::post('/{employee}/appointment-complete', [App\Http\Controllers\Production\RegistrationController::class, 'toggleAppointmentComplete'])->name('appointment_complete');

        Route::post('/settings/notification', [App\Http\Controllers\Production\RegistrationController::class, 'updateNotificationSettings'])->name('settings.notification');
        Route::post('/settings/resolution', [App\Http\Controllers\Production\RegistrationController::class, 'updateResolutionSettings'])->name('settings.resolution');
        Route::get('/api/calendar', [App\Http\Controllers\Production\RegistrationController::class, 'getCalendarData'])->name('api.calendar');
        Route::get('/api/appointments-by-date', [App\Http\Controllers\Production\RegistrationController::class, 'getAppointmentsByDate'])->name('api.appointments_by_date');

        // Trash Routes
        Route::get('/trash', [App\Http\Controllers\Production\RegistrationController::class, 'fetchTrash'])->name('trash');
        Route::post('/trash/{id}/restore', [App\Http\Controllers\Production\RegistrationController::class, 'restoreTrash'])->name('trash.restore');
    });

    // Renewal Resolution — Redirect base URL to default tab
    Route::get('production/renewal', function () {
        $tab = \App\Models\ResolutionTab::where('type', 'renewal')->ordered()->first();
        return redirect()->route('production.renewal.index', ['resolutionTab' => $tab->id]);
    })->middleware('menu:renewal_resolution')->name('production.renewal.redirect');

    // Renewal Resolution Routes (with tab parameter)
    Route::prefix('production/renewal/{resolutionTab}')->middleware('menu:renewal_resolution')->name('production.renewal.')->group(function () {
        Route::get('/', [App\Http\Controllers\Production\RenewalController::class, 'dashboard'])->name('index');
        Route::get('/operations', [App\Http\Controllers\Production\RenewalController::class, 'index'])->name('operations');
        Route::get('/create', [App\Http\Controllers\Production\RenewalController::class, 'create'])->name('create');
        Route::post('/store', [App\Http\Controllers\Production\RenewalController::class, 'store'])->name('store');
        Route::get('/employer/{employer}/employees', [App\Http\Controllers\Production\RenewalController::class, 'fetchEmployees'])->name('employer.employees')->withTrashed();
        Route::get('/employer/{employer}/select-all-ids', [App\Http\Controllers\Production\RenewalController::class, 'selectAllEmployerEmployeeIds'])->name('employer.select_all_ids')->withTrashed();
        Route::get('/employer/{employer}/history', [App\Http\Controllers\Production\RenewalController::class, 'fetchHistory'])->name('employer.history');

        // Stats & Lazy Loading Routes
        Route::post('/stats-batch', [App\Http\Controllers\Production\RenewalController::class, 'batchStats'])->name('stats.batch');
        Route::get('/employer/{employer}/finance-tab', [App\Http\Controllers\Production\RenewalController::class, 'loadFinancialTab'])->name('finance.tab');
        Route::get('/import', [App\Http\Controllers\Production\RenewalController::class, 'importView'])->name('import');
        Route::post('/configure-expiry', [App\Http\Controllers\Production\RenewalController::class, 'configureExpiry'])->name('configure_expiry');

        // Step Management
        Route::post('/steps', [App\Http\Controllers\Production\RenewalController::class, 'storeStep'])->name('steps.store');
        Route::put('/steps/{step}', [App\Http\Controllers\Production\RenewalController::class, 'updateStep'])->name('steps.update');
        Route::post('/steps/reorder', [App\Http\Controllers\Production\RenewalController::class, 'reorderSteps'])->name('steps.reorder');
        Route::delete('/steps/{step}', [App\Http\Controllers\Production\RenewalController::class, 'destroyStep'])->name('steps.destroy');

        // Progress Updates
        Route::post('/progress/{employee}', [App\Http\Controllers\Production\RenewalController::class, 'updateProgress'])->name('progress.update');

        // Operator
        Route::post('/{employee}/toggle-operator', [App\Http\Controllers\Production\RenewalController::class, 'toggleOperator'])->name('toggle_operator');

        // Remarks
        Route::post('/{employee}/remarks', [App\Http\Controllers\Production\RenewalController::class, 'updateRemarks'])->name('remarks');

        // Insurance
        Route::post('/{employee}/update-insurance', [App\Http\Controllers\Production\RenewalController::class, 'updateInsurance'])->name('update_insurance');

        // Appointments & Calendar
        Route::post('/{employee}/appointment', [App\Http\Controllers\Production\RenewalController::class, 'updateAppointment'])->name('appointment');
        Route::post('/{employee}/appointment-complete', [App\Http\Controllers\Production\RenewalController::class, 'toggleAppointmentComplete'])->name('appointment_complete');
        Route::post('/settings/notification', [App\Http\Controllers\Production\RenewalController::class, 'updateNotificationSettings'])->name('settings.notification');
        Route::get('/api/calendar', [App\Http\Controllers\Production\RenewalController::class, 'getCalendarData'])->name('api.calendar');
        Route::get('/api/appointments-by-date', [App\Http\Controllers\Production\RenewalController::class, 'getAppointmentsByDate'])->name('api.appointments_by_date');

        // Actions
        Route::post('/{employee}/finalize', [App\Http\Controllers\Production\RenewalController::class, 'finalize'])->name('finalize');
        Route::post('/{employee}/cancel', [App\Http\Controllers\Production\RenewalController::class, 'cancel'])->name('cancel');
        Route::post('/{employee}/restore', [App\Http\Controllers\Production\RenewalController::class, 'restore'])->name('restore');
        Route::delete('/{employee}/destroy', [App\Http\Controllers\Production\RenewalController::class, 'destroy'])->name('destroy');

        // Dual-listed employees (Registration-Resolution employee also usable
        // in this tab via EmployeeRenewalLink — see that model's docblock).
        // These act ONLY on the link, never on the real Employee row.
        Route::post('/link/{link}/progress', [App\Http\Controllers\Production\RenewalController::class, 'updateLinkProgress'])->name('link.progress.update');
        Route::post('/link/{link}/finalize', [App\Http\Controllers\Production\RenewalController::class, 'finalizeLink'])->name('link.finalize');
        Route::post('/link/{link}/cancel', [App\Http\Controllers\Production\RenewalController::class, 'cancelLink'])->name('link.cancel');
        Route::post('/link/{link}/restore', [App\Http\Controllers\Production\RenewalController::class, 'restoreLink'])->name('link.restore');

        // Settings
        Route::post('/settings/resolution', [App\Http\Controllers\Production\RenewalController::class, 'updateResolutionSettings'])->name('settings.resolution');

        // Employer Actions
        Route::post('/employer/{employer}/cancel', [App\Http\Controllers\Production\RenewalController::class, 'cancelEmployer'])->name('cancel_employer');
        Route::post('/employer/{employer}/restore', [App\Http\Controllers\Production\RenewalController::class, 'restoreEmployer'])->name('restore_employer');
        Route::post('/employer/{employer}/resolution-note', [App\Http\Controllers\Production\RenewalController::class, 'updateResolutionNote'])->name('employer_resolution.update_note');

        // Trash Routes
        Route::get('/trash', [App\Http\Controllers\Production\RenewalController::class, 'fetchTrash'])->name('trash');
        Route::post('/trash/{id}/restore', [App\Http\Controllers\Production\RenewalController::class, 'restoreTrash'])->name('trash.restore');
    });

    Route::resource('production', \App\Http\Controllers\ProductionController::class)->middleware('menu:production');
    Route::post('production/stats-batch', [\App\Http\Controllers\ProductionController::class, 'batchStats'])->name('production.stats.batch');
    Route::get('production/order/{order}/employees', [\App\Http\Controllers\ProductionController::class, 'fetchEmployees'])->name('production.order.employees');
    Route::get('production/order/{order}/select-all-ids', [\App\Http\Controllers\ProductionController::class, 'selectAllOrderEmployeeIds'])->name('production.order.select_all_ids');

    // Additional Production Routes
    Route::post('production/{id}/add-employee', [\App\Http\Controllers\ProductionController::class, 'addEmployee'])->name('production.add_employee');
    Route::post('production/{id}/add-new-employee', [\App\Http\Controllers\ProductionController::class, 'addNewEmployee'])->name('production.add_new_employee');

    // Read and Sale (Sales Leads)
    Route::middleware('role:super-admin|admin|staff')->prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SalesLeadController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\SalesLeadController::class, 'store'])->name('store');
        Route::put('/{sales}/status', [\App\Http\Controllers\SalesLeadController::class, 'updateStatus'])->name('status.update');
        Route::put('/{sales}', [\App\Http\Controllers\SalesLeadController::class, 'update'])->name('update');
        Route::delete('/{sales}', [\App\Http\Controllers\SalesLeadController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/restore', [\App\Http\Controllers\SalesLeadController::class, 'restore'])->name('restore');

        Route::post('/{sales}/employees', [\App\Http\Controllers\SalesLeadController::class, 'storeEmployee'])->name('employees.store');
        Route::put('/{sales}/employees/{employee}', [\App\Http\Controllers\SalesLeadController::class, 'updateEmployee'])->name('employees.update');
        Route::delete('/{sales}/employees/{employee}', [\App\Http\Controllers\SalesLeadController::class, 'destroyEmployee'])->name('employees.destroy');

        Route::post('/{sales}/quotation', [\App\Http\Controllers\SalesLeadController::class, 'storeQuotation'])->name('quotation.store');

        Route::post('/{sales}/transition', [\App\Http\Controllers\SalesLeadController::class, 'transition'])->name('transition');

        Route::post('/{sales}/import', [\App\Http\Controllers\Sales\SalesLeadImportController::class, 'importExcel'])->name('import');

        Route::get('/{sales}/document/{type}', [\App\Http\Controllers\SalesLeadController::class, 'generateDocument'])->name('document.generate');
        Route::post('/{sales}/payment', [\App\Http\Controllers\SalesLeadController::class, 'storeSalesPayment'])->name('payment.store');
        Route::delete('/{sales}/payment/{paymentIndex}', [\App\Http\Controllers\SalesLeadController::class, 'destroySalesPayment'])->name('payment.destroy');

        // Financial Tab (full finance features like production)
        Route::get('/{sales}/finance-tab', [\App\Http\Controllers\SalesLeadController::class, 'loadFinancialTab'])->name('finance.tab');
    });

    // NEW: Pre-Production Routes — send-to-workflow gated by approve-production
    // (seeded but previously unused). Admin + super-admin get it by default;
    // staff can be granted manually if a particular office allows it.
    Route::post('production/{item}/send-to-workflow', [\App\Http\Controllers\ProductionController::class, 'sendToWorkflow'])->middleware('permission:approve-production')->name('production.item.send_to_workflow');
    Route::post('production/bulk-send-to-workflow', [\App\Http\Controllers\ProductionController::class, 'bulkSendToWorkflow'])->middleware('permission:approve-production')->name('production.bulk_send_to_workflow');
    Route::post('production/order/{order}/send-to-workflow', [\App\Http\Controllers\ProductionController::class, 'sendOrderToWorkflow'])->middleware('permission:approve-production')->name('production.order.send_to_workflow');

    // ProductionOrder custom fields — gated by manage-own-workflow (admin + staff).
    Route::post('production/order/{order}/custom-fields', [\App\Http\Controllers\ProductionController::class, 'storeOrderCustomField'])->middleware('permission:manage-own-workflow')->name('production.order.custom_fields.store');
    Route::put('production/order/custom-fields/{field}', [\App\Http\Controllers\ProductionController::class, 'updateOrderCustomField'])->middleware('permission:manage-own-workflow')->name('production.order.custom_fields.update');
    Route::delete('production/order/custom-fields/{field}', [\App\Http\Controllers\ProductionController::class, 'destroyOrderCustomField'])->middleware('permission:manage-own-workflow')->name('production.order.custom_fields.destroy');
    Route::post('production/steps', [\App\Http\Controllers\ProductionController::class, 'storeStep'])->name('production.steps.store');
    Route::put('production/steps/{id}', [\App\Http\Controllers\ProductionController::class, 'updateStep'])->name('production.steps.update');
    Route::delete('production/steps/{id}', [\App\Http\Controllers\ProductionController::class, 'destroyStep'])->name('production.steps.destroy');
    Route::post('production/steps/reorder', [\App\Http\Controllers\ProductionController::class, 'reorderSteps'])->name('production.steps.reorder');

    Route::post('production/{id}/toggle-status', [\App\Http\Controllers\ProductionController::class, 'toggleStatus'])->name('production.toggle_status');
    Route::post('production/{employee}/update-outsource-login', [\App\Http\Controllers\ProductionController::class, 'updateOutsourceLogin'])->name('production.update_outsource_login');
    Route::put('production/items/{item}/update-fields', [\App\Http\Controllers\ProductionController::class, 'updateItemFields'])->name('production.items.update_fields');
    Route::post('production/{id}/upload-logo', [\App\Http\Controllers\ProductionController::class, 'uploadLogo'])->name('production.upload_logo');
    Route::post('production/{order}/remarks', [\App\Http\Controllers\ProductionController::class, 'updateRemarks'])->name('production.order.remarks');

    Route::middleware('menu:finance')->group(function() {
        Route::get('production/{id}/finance-tab', [\App\Http\Controllers\ProductionController::class, 'fetchFinanceTab'])->name('production.finance_tab');
    });

    // Financial group CRUD (used by registration, renewal, workflow, AND sales — no menu:finance lock)
    Route::post('production/{id}/financial-groups', [\App\Http\Controllers\ProductionController::class, 'storeFinancialGroup'])->name('production.financial_groups.store');
    Route::put('production/{id}/financial-groups/{groupId}', [\App\Http\Controllers\ProductionController::class, 'updateFinancialGroup'])->name('production.financial_groups.update');
    Route::delete('production/{id}/financial-groups/{groupId}', [\App\Http\Controllers\ProductionController::class, 'destroyFinancialGroup'])->name('production.financial_groups.destroy');
    // Draft (not-yet-registered) employees — manual bills only, see storeDraftEmployee()'s own guard.
    Route::post('production/{id}/financial-groups/{groupId}/draft-employee', [\App\Http\Controllers\ProductionController::class, 'storeDraftEmployee'])->name('production.financial_groups.draft_employee');

    // Financial Hub Routes (Central Menu)
    Route::middleware('menu:finance')->prefix('finance')->name('finance.')->group(function () {
        // Finance Additions
        Route::resource('bank-accounts', App\Http\Controllers\Finance\BankAccountController::class)->except(['create', 'edit', 'show']);
        Route::resource('expense-categories', App\Http\Controllers\Finance\ExpenseCategoryController::class)->except(['create', 'edit', 'show']);
        Route::resource('income-categories', App\Http\Controllers\Finance\IncomeCategoryController::class)->except(['create', 'edit', 'show']);
        Route::resource('expenses', App\Http\Controllers\Finance\ExpenseController::class)->only(['index', 'store', 'destroy']);

        // Ledger (Phase 1 — universal income/expense ledger)
        Route::get('/ledger', [App\Http\Controllers\Finance\LedgerEntryController::class, 'index'])->name('ledger.index');
        Route::post('/ledger', [App\Http\Controllers\Finance\LedgerEntryController::class, 'store'])->name('ledger.store');

        // Quick Capture (Phase 3 — AI extraction, manual fallback)
        // Must come before /ledger/{ledger} so 'capture' isn't captured as an ID.
        Route::get('/ledger/capture', [App\Http\Controllers\Finance\QuickCaptureController::class, 'show'])->name('ledger.capture');
        Route::post('/ledger/capture/extract', [App\Http\Controllers\Finance\QuickCaptureController::class, 'extract'])->name('ledger.capture.extract');
        Route::post('/ledger/capture', [App\Http\Controllers\Finance\QuickCaptureController::class, 'save'])->name('ledger.capture.save');

        Route::get('/ledger/{ledger}', [App\Http\Controllers\Finance\LedgerEntryController::class, 'show'])->name('ledger.show');
        Route::match(['put', 'post'], '/ledger/{ledger}/update', [App\Http\Controllers\Finance\LedgerEntryController::class, 'update'])->name('ledger.update');
        Route::delete('/ledger/{ledger}', [App\Http\Controllers\Finance\LedgerEntryController::class, 'destroy'])->name('ledger.destroy');

        // "บันทึกรายรับรายจ่าย" — Labor Company Books-styled front end over
        // the Ledger/BankAccount backend above (see Finance\FinanceBookController
        // docblock). /books-expense/create must come before /books/{account}
        // so 'books-expense' isn't captured as an account ID.
        Route::get('/books-expense/create', [App\Http\Controllers\Finance\FinanceBookController::class, 'createExpense'])->name('books.expense.create');
        Route::get('/books', [App\Http\Controllers\Finance\FinanceBookController::class, 'index'])->name('books.index');
        Route::get('/books/{account}', [App\Http\Controllers\Finance\FinanceBookController::class, 'show'])->name('books.show');
        Route::get('/books/{account}/export', [App\Http\Controllers\Finance\FinanceBookController::class, 'export'])->name('books.export');
        Route::post('/books-entry/{ledger}/correct', [App\Http\Controllers\Finance\FinanceBookController::class, 'correctEntry'])->name('books.correct');

        Route::get('/books-reports', [App\Http\Controllers\Finance\FinanceReportController::class, 'index'])->name('books-reports.index');
        Route::get('/books-reports/pdf', [App\Http\Controllers\Finance\FinanceReportController::class, 'pdf'])->name('books-reports.pdf');
        Route::get('/books-reports/export', [App\Http\Controllers\Finance\FinanceReportController::class, 'export'])->name('books-reports.export');

        // Tax Invoices (Phase 2.1 — ใบกำกับภาษีขาย)
        Route::get('tax-invoices/{taxInvoice}/pdf', [App\Http\Controllers\Finance\TaxInvoiceController::class, 'pdf'])->name('tax-invoices.pdf');
        Route::resource('tax-invoices', App\Http\Controllers\Finance\TaxInvoiceController::class)->except(['edit']);

        // WHT Certificates (Phase 2.1 — ใบหัก ณ ที่จ่าย ทั้ง issued+received)
        Route::get('wht-certificates/{whtCertificate}/pdf', [App\Http\Controllers\Finance\WhtCertificateController::class, 'pdf'])->name('wht-certificates.pdf');
        Route::resource('wht-certificates', App\Http\Controllers\Finance\WhtCertificateController::class)->except(['edit']);

        // Tax Reports (Phase 2.3 — ภ.พ.30 + ภ.ง.ด.3/53)
        Route::get('tax-reports', [App\Http\Controllers\Finance\TaxReportController::class, 'index'])->name('tax-reports.index');
        Route::get('tax-reports/vat', [App\Http\Controllers\Finance\TaxReportController::class, 'vat'])->name('tax-reports.vat');
        Route::get('tax-reports/vat/export', [App\Http\Controllers\Finance\TaxReportController::class, 'exportVat'])->name('tax-reports.vat.export');
        Route::get('tax-reports/wht', [App\Http\Controllers\Finance\TaxReportController::class, 'wht'])->name('tax-reports.wht');
        Route::get('tax-reports/wht/export', [App\Http\Controllers\Finance\TaxReportController::class, 'exportWht'])->name('tax-reports.wht.export');

        // Monthly Bundle (Phase 4 — 1-click ZIP: Summary + Ledger + tax forms + attachments)
        Route::get('monthly-bundle/export', [App\Http\Controllers\Finance\MonthlyExportController::class, 'export'])->name('monthly-bundle.export');

        // Bank Reconciliation + Finance Audit (Phase 5)
        Route::get('reconciliation', [App\Http\Controllers\Finance\ReconciliationController::class, 'index'])->name('reconciliation.index');
        Route::get('reconciliation/{bankAccount}', [App\Http\Controllers\Finance\ReconciliationController::class, 'account'])->name('reconciliation.account');
        Route::post('reconciliation/{bankAccount}/repair', [App\Http\Controllers\Finance\ReconciliationController::class, 'repair'])->name('reconciliation.repair');
        Route::get('audit', [App\Http\Controllers\Finance\FinanceAuditController::class, 'index'])->name('audit.index');

        // WHT (ใบหัก ณ ที่จ่าย) Inbox — รับรายได้แล้วแต่ยังขาดใบ ณ ที่จ่าย
        Route::get('/wht-inbox', [\App\Http\Controllers\FinancialController::class, 'whtInbox'])->name('wht_inbox');
        Route::post('/wht-inbox/{transaction}/received', [\App\Http\Controllers\FinancialController::class, 'markWhtReceived'])->name('wht_received');
        Route::post('/wht-inbox/{transaction}/no-certificate', [\App\Http\Controllers\FinancialController::class, 'markWhtNoCertificate'])->name('wht_no_certificate');

        Route::get('/', [App\Http\Controllers\FinancialHubController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\FinancialHubController::class, 'createManual'])->name('create');
        Route::post('/store', [App\Http\Controllers\FinancialHubController::class, 'storeManual'])->name('store');
        Route::get('/export-monthly', [App\Http\Controllers\FinancialHubController::class, 'exportMonthly'])->name('export_monthly');
    });

    // Profiles Builder & API (Shares the same finance menu password, but can be controlled via its own visibility setting in Super Admin)
    Route::middleware('menu:finance')->prefix('finance')->name('finance.')->group(function () {
        Route::get('/profiles', [App\Http\Controllers\FinancialProfileController::class, 'builder'])->name('profiles.builder');
        Route::get('/api/profiles', [App\Http\Controllers\FinancialProfileController::class, 'index']);
        Route::get('/api/profiles/{profile}', [App\Http\Controllers\FinancialProfileController::class, 'show']);
        Route::post('/api/profiles', [App\Http\Controllers\FinancialProfileController::class, 'store']);
        Route::match(['put', 'post'], '/api/profiles/{profile}', [App\Http\Controllers\FinancialProfileController::class, 'update']); // Allow both to support FormData with spoofed _method=PUT
        Route::delete('/api/profiles/{profile}', [App\Http\Controllers\FinancialProfileController::class, 'destroy']);

        // Bank Accounts scoped to a Financial Profile (used by both the
        // builder UI's inline panel and the Tax Invoice form dropdown).
        Route::get('/api/profiles/{profile}/bank-accounts', [App\Http\Controllers\FinancialProfileController::class, 'listBankAccounts']);
        Route::post('/api/profiles/{profile}/bank-accounts', [App\Http\Controllers\FinancialProfileController::class, 'storeBankAccount']);
        Route::match(['put', 'post'], '/api/profiles/{profile}/bank-accounts/{bankAccount}', [App\Http\Controllers\FinancialProfileController::class, 'updateBankAccount']);
        Route::delete('/api/profiles/{profile}/bank-accounts/{bankAccount}', [App\Http\Controllers\FinancialProfileController::class, 'destroyBankAccount']);

        // Preset Thai banks (read-only) for the Production Invoice picker.
        Route::get('/api/thai-banks', function () {
            return response()->json(config('thai_banks', []));
        });
    });


    // Financial Routes
    Route::middleware('menu:finance')->group(function() {
        Route::post('production/{id}/transactions', [FinancialController::class, 'storeTransaction']);
        Route::put('production/transactions/{id}', [FinancialController::class, 'updateTransaction']); // For status updates
        Route::post('production/transactions/{id}', [FinancialController::class, 'updateTransaction']); // For file uploads (method spoofing)
        Route::delete('production/transactions/{id}', [FinancialController::class, 'destroyTransaction']);
        Route::post('production/transactions/{id}/payments', [FinancialController::class, 'storePayment']);
        Route::match(['post', 'put'], 'production/payments/{id}', [FinancialController::class, 'updatePayment']);
        Route::delete('production/payments/{id}', [FinancialController::class, 'destroyPayment']);
    });
    // Replaced by dedicated ProductionDocumentController
    // Route::get('production/{id}/documents/{type}', [FinancialController::class, 'generateDocument']);
    Route::get('production/{id}/documents/{type}', [App\Http\Controllers\ProductionDocumentController::class, 'show'])->name('production.documents.show');
    Route::get('production/{id}/documents/payment/{paymentId}/{type}', [App\Http\Controllers\ProductionDocumentController::class, 'showPaymentDocument'])->name('production.documents.payment.show');

    // Workflow Routes
    Route::get('workflow', [\App\Http\Controllers\WorkflowController::class, 'index'])->middleware('menu:workflow')->name('workflow.index');
    Route::post('workflow/stats-batch', [\App\Http\Controllers\WorkflowController::class, 'batchStats'])->name('workflow.stats.batch');
    Route::get('workflow/order/{order}/employees', [\App\Http\Controllers\WorkflowController::class, 'fetchEmployees'])->name('workflow.order.employees');
    Route::get('workflow/order/{order}/select-all-ids', [\App\Http\Controllers\WorkflowController::class, 'selectAllOrderEmployeeIds'])->name('workflow.order.select_all_ids');
    Route::post('workflow/store', [\App\Http\Controllers\WorkflowController::class, 'store'])->name('workflow.store');
    Route::get('workflow/{order}/items', [\App\Http\Controllers\WorkflowController::class, 'fetchOrderItems'])->name('workflow.items');
    Route::get('workflow/{order}/history', [\App\Http\Controllers\WorkflowController::class, 'fetchOrderHistory'])->name('workflow.history');
    Route::get('workflow/item/{item}/card', [\App\Http\Controllers\WorkflowController::class, 'getItemHtml'])->name('workflow.item.card');
    Route::post('workflow/item/{item}/step-toggle', [\App\Http\Controllers\WorkflowController::class, 'toggleStep'])->name('workflow.step.toggle');
    Route::post('workflow/item/{item}/toggle-operator', [\App\Http\Controllers\WorkflowController::class, 'toggleOperator'])->name('workflow.item.toggle_operator');
    Route::post('workflow/item/{item}/appointment', [\App\Http\Controllers\WorkflowController::class, 'updateAppointmentDate'])->name('workflow.item.appointment');
    Route::post('workflow/item/{item}/appointment-complete', [\App\Http\Controllers\WorkflowController::class, 'toggleAppointmentComplete'])->name('workflow.item.appointment_complete');
    Route::post('workflow/appointments/export', [\App\Http\Controllers\WorkflowController::class, 'exportAppointments'])->name('workflow.appointments.export');
    Route::post('workflow/item/{item}/remarks', [\App\Http\Controllers\WorkflowController::class, 'updateRemarks'])->name('workflow.item.remarks');
    Route::post('workflow/order/{order}/remarks', [\App\Http\Controllers\WorkflowController::class, 'updateOrderRemarks'])->name('workflow.order.remarks');
    Route::post('workflow/order/{order}/mou-import-type', [\App\Http\Controllers\WorkflowController::class, 'updateMouImportType'])->name('workflow.order.mou_import_type');
    Route::post('workflow/item/{item}/group', [\App\Http\Controllers\WorkflowController::class, 'updateGroup'])->name('workflow.item.group');
    Route::post('workflow/item/{item}/finalize', [\App\Http\Controllers\WorkflowController::class, 'finalizeItem'])->name('workflow.item.finalize');
    Route::post('workflow/item/{item}/notify-out-fields', [\App\Http\Controllers\WorkflowController::class, 'updateNotifyOutFields'])->name('workflow.item.notify_out_fields');
    Route::post('workflow/item/{item}/cancel', [\App\Http\Controllers\WorkflowController::class, 'cancelItem'])->name('workflow.item.cancel');
    Route::post('workflow/item/{item}/restore', [\App\Http\Controllers\WorkflowController::class, 'restoreItem'])->name('workflow.item.restore');
    Route::post('workflow/item/{item}/send-back', [\App\Http\Controllers\WorkflowController::class, 'sendBackToPreProduction'])->name('workflow.item.send_back');
    // ส่ง ProductionOrder ทั้งใบกลับไป Pre-Production (สำหรับ MOU demand card)
    Route::post('workflow/order/{order}/send-back', [\App\Http\Controllers\WorkflowController::class, 'sendOrderBackToPreProduction'])->name('workflow.order.send_back');
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
    Route::post('workflow/settings/auto', [\App\Http\Controllers\WorkflowController::class, 'updateAutoSettings'])->name('workflow.settings.auto');

    // โหมดเช็คงาน (Job Check Mode)
    Route::get('job-check/status', [\App\Http\Controllers\JobCheckSessionController::class, 'status'])->name('job-check.status');
    Route::get('job-check/history', [\App\Http\Controllers\JobCheckSessionController::class, 'history'])->name('job-check.history');
    Route::post('job-check/summary', [\App\Http\Controllers\JobCheckSessionController::class, 'summarize'])->name('job-check.summary');
    Route::post('job-check/start', [\App\Http\Controllers\JobCheckSessionController::class, 'start'])->name('job-check.start');
    Route::post('job-check/pause', [\App\Http\Controllers\JobCheckSessionController::class, 'pause'])->name('job-check.pause');
    Route::post('job-check/resume', [\App\Http\Controllers\JobCheckSessionController::class, 'resume'])->name('job-check.resume');
    Route::post('job-check/cancel', [\App\Http\Controllers\JobCheckSessionController::class, 'cancel'])->name('job-check.cancel');
    Route::post('job-check/finish', [\App\Http\Controllers\JobCheckSessionController::class, 'finish'])->name('job-check.finish');
    Route::get('job-check/{session}/download/{type}', [\App\Http\Controllers\JobCheckSessionController::class, 'download'])->name('job-check.download');
});

use App\Http\Controllers\Admin\NotificationSettingController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\DuplicateRecordController;

// === Existing Admin Routes (role:admin) ===
Route::middleware(['auth', 'role:admin|super-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->middleware('menu:activity_logs')->name('activity-logs.index');
    Route::get('/activity-logs/search', [ActivityLogController::class, 'search'])->name('activity-logs.search');
    Route::get('/activity-logs/search-subject', [ActivityLogController::class, 'searchSubject'])->name('activity-logs.search-subject');
    Route::get('/activity-logs/subject-history', [ActivityLogController::class, 'subjectHistory'])->name('activity-logs.subject-history');
    Route::get('/activity-logs/{year}', [ActivityLogController::class, 'showYear'])->name('activity-logs.year');
    Route::get('/activity-logs/{year}/{month}', [ActivityLogController::class, 'showMonth'])->name('activity-logs.month');
    Route::get('/activity-logs/{year}/{month}/{day}', [ActivityLogController::class, 'showDay'])->name('activity-logs.day');

    Route::get('/duplicate-records', [DuplicateRecordController::class, 'index'])->middleware('menu:duplicate_records')->name('duplicate-records.index');

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

// === Resolution Tabs Management (Super Admin Only) ===
Route::middleware(['auth', 'role:super-admin'])->prefix('admin/resolution-tabs')->name('admin.resolution-tabs.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ResolutionTabController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\Admin\ResolutionTabController::class, 'store'])->name('store');
    Route::put('/{resolutionTab}', [\App\Http\Controllers\Admin\ResolutionTabController::class, 'update'])->name('update');
    Route::post('/{resolutionTab}/toggle-badge', [\App\Http\Controllers\Admin\ResolutionTabController::class, 'toggleBadge'])->name('toggle-badge');
    Route::post('/reorder', [\App\Http\Controllers\Admin\ResolutionTabController::class, 'reorder'])->name('reorder');
    Route::delete('/{resolutionTab}', [\App\Http\Controllers\Admin\ResolutionTabController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/restore', [\App\Http\Controllers\Admin\ResolutionTabController::class, 'restore'])->name('restore');
    Route::delete('/{id}/force-delete', [\App\Http\Controllers\Admin\ResolutionTabController::class, 'forceDelete'])->name('force-delete');
});

// === Work Permit Type Management (Super Admin Only) ===
Route::middleware(['auth', 'role:super-admin'])->prefix('admin/work-permit-types')->name('admin.work-permit-types.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\WorkPermitTypeController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\Admin\WorkPermitTypeController::class, 'store'])->name('store');
    Route::put('/{workPermitType}', [\App\Http\Controllers\Admin\WorkPermitTypeController::class, 'update'])->name('update');
    Route::post('/reorder', [\App\Http\Controllers\Admin\WorkPermitTypeController::class, 'reorder'])->name('reorder');
    Route::delete('/{workPermitType}', [\App\Http\Controllers\Admin\WorkPermitTypeController::class, 'destroy'])->name('destroy');
});

// === Work Type Tab Management (Pre-Production/Workflow tabs, Super Admin Only) ===
Route::middleware(['auth', 'role:super-admin'])->prefix('admin/work-types')->name('admin.work-types.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\WorkTypeController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\Admin\WorkTypeController::class, 'store'])->name('store');
    Route::put('/{workType}', [\App\Http\Controllers\Admin\WorkTypeController::class, 'update'])->name('update');
    Route::post('/reorder', [\App\Http\Controllers\Admin\WorkTypeController::class, 'reorder'])->name('reorder');
    Route::delete('/{workType}', [\App\Http\Controllers\Admin\WorkTypeController::class, 'destroy'])->name('destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/labor.php';

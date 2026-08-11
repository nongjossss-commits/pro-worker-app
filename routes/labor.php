<?php

use App\Http\Controllers\Labor\LaborAuditLogController;
use App\Http\Controllers\Labor\LaborBillController;
use App\Http\Controllers\Labor\LaborBillPaymentController;
use App\Http\Controllers\Labor\LaborBookController;
use App\Http\Controllers\Labor\LaborChargeEntryController;
use App\Http\Controllers\Labor\LaborChargeTypeController;
use App\Http\Controllers\Labor\LaborDashboardController;
use App\Http\Controllers\Labor\LaborLedgerController;
use App\Http\Controllers\Labor\LaborReportController;
use App\Http\Controllers\Labor\LaborTaxInvoiceController;
use App\Http\Controllers\Labor\LaborTeamController;
use App\Http\Controllers\Labor\LaborTeamMemberController;
use App\Http\Controllers\Labor\LaborUserController;
use App\Http\Controllers\Labor\LaborWhtCertificateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pro Walker Labor module
|--------------------------------------------------------------------------
| Isolated from the main operations app — its own controllers, views, and
| audit trail. Every route requires auth + the `labor.access` gate (see
| App\Http\Middleware\EnsureLaborAccess for why it isn't permission-based).
| Route names are prefixed `labor.` so App\Http\Middleware\ConfineToLaborModule
| can recognize them and let dedicated labor-* roles stay inside.
*/

Route::middleware(['auth', 'labor.access'])
    ->prefix('pro-walker-labor')
    ->name('labor.')
    ->group(function () {
        Route::get('/', [LaborDashboardController::class, 'index'])->name('dashboard');

        Route::get('/teams', [LaborTeamController::class, 'index'])->name('teams.index');
        Route::post('/teams', [LaborTeamController::class, 'store'])->name('teams.store');
        Route::get('/teams/{team}', [LaborTeamController::class, 'show'])->name('teams.show');
        Route::put('/teams/{team}', [LaborTeamController::class, 'update'])->name('teams.update');
        Route::put('/teams/{team}/billing-schedule', [LaborTeamController::class, 'updateBillingSchedule'])->name('teams.billing-schedule.update');

        Route::post('/teams/{team}/ledger', [LaborLedgerController::class, 'store'])->name('ledger.store');
        Route::put('/teams/{team}/ledger/{entry}', [LaborLedgerController::class, 'update'])->name('ledger.update');
        Route::delete('/teams/{team}/ledger/{entry}', [LaborLedgerController::class, 'destroy'])->name('ledger.destroy');
        Route::post('/teams/{team}/ledger/{entryId}/restore', [LaborLedgerController::class, 'restore'])->name('ledger.restore');

        Route::get('/audit-log', [LaborAuditLogController::class, 'index'])->name('audit-log.index');

        // Central Billing — Accounting Staff logs a charge by request number + qty,
        // priced from the Super Admin-managed catalog. See LaborChargeEntryController.
        Route::get('/charges', [LaborChargeEntryController::class, 'index'])->name('charges.index');
        Route::post('/charges', [LaborChargeEntryController::class, 'store'])->name('charges.store');
        Route::put('/charges/{entry}', [LaborChargeEntryController::class, 'update'])->name('charges.update');
        Route::delete('/charges/{entry}', [LaborChargeEntryController::class, 'destroy'])->name('charges.destroy');
        Route::post('/charges/{entryId}/restore', [LaborChargeEntryController::class, 'restore'])->name('charges.restore');

        // Central "ลูกทีม" registry — an ID is created with its team chosen right
        // then and there; no other route creates one, and none moves it later.
        Route::get('/team-members', [LaborTeamMemberController::class, 'index'])->name('team-members.index');
        Route::post('/team-members', [LaborTeamMemberController::class, 'store'])->name('team-members.store');
        Route::put('/team-members/{member}', [LaborTeamMemberController::class, 'update'])->name('team-members.update');
        Route::delete('/team-members/{member}', [LaborTeamMemberController::class, 'destroy'])->name('team-members.destroy');
        Route::get('/team-members/search', [LaborTeamMemberController::class, 'search'])->name('team-members.search');

        // Central Billing statements — periodic snapshots for each team, generated
        // manually here or automatically by the scheduled command (per-team cadence
        // set on the team's own page). See LaborBillService for what "billed" means.
        Route::get('/bills', [LaborBillController::class, 'index'])->name('bills.index');
        Route::post('/bills', [LaborBillController::class, 'store'])->name('bills.store');
        Route::get('/bills/{bill}', [LaborBillController::class, 'show'])->name('bills.show');
        Route::get('/bills/{bill}/download', [LaborBillController::class, 'download'])->name('bills.download');
        Route::post('/bills/{bill}/void', [LaborBillController::class, 'void'])->name('bills.void');
        Route::put('/billing-settings', [LaborBillController::class, 'updateSettings'])->name('billing-settings.update');

        // Payments against a bill (partial payments supported) + receipt per payment.
        Route::post('/bills/{bill}/payments', [LaborBillPaymentController::class, 'store'])->name('bills.payments.store');
        Route::post('/bills/{bill}/payments/{payment}/receipt', [LaborBillPaymentController::class, 'issueReceipt'])->name('bills.payments.receipt.issue');
        Route::get('/bills/{bill}/payments/{payment}/receipt/download', [LaborBillPaymentController::class, 'downloadReceipt'])->name('bills.payments.receipt.download');

        // ใบกำกับภาษี (VAT tax invoices), usually issued from a bill.
        Route::get('/tax-invoices', [LaborTaxInvoiceController::class, 'index'])->name('tax-invoices.index');
        Route::get('/tax-invoices/create', [LaborTaxInvoiceController::class, 'create'])->name('tax-invoices.create');
        Route::post('/tax-invoices', [LaborTaxInvoiceController::class, 'store'])->name('tax-invoices.store');
        Route::get('/tax-invoices/{taxInvoice}', [LaborTaxInvoiceController::class, 'show'])->name('tax-invoices.show');
        Route::put('/tax-invoices/{taxInvoice}', [LaborTaxInvoiceController::class, 'update'])->name('tax-invoices.update');
        Route::delete('/tax-invoices/{taxInvoice}', [LaborTaxInvoiceController::class, 'destroy'])->name('tax-invoices.destroy');
        Route::get('/tax-invoices/{taxInvoice}/pdf', [LaborTaxInvoiceController::class, 'pdf'])->name('tax-invoices.pdf');

        // ใบหัก ณ ที่จ่าย (WHT certificates) — usually received from the team/customer paying a bill.
        Route::get('/wht-certificates', [LaborWhtCertificateController::class, 'index'])->name('wht-certificates.index');
        Route::get('/wht-certificates/create', [LaborWhtCertificateController::class, 'create'])->name('wht-certificates.create');
        Route::post('/wht-certificates', [LaborWhtCertificateController::class, 'store'])->name('wht-certificates.store');
        Route::get('/wht-certificates/{whtCertificate}', [LaborWhtCertificateController::class, 'show'])->name('wht-certificates.show');
        Route::put('/wht-certificates/{whtCertificate}', [LaborWhtCertificateController::class, 'update'])->name('wht-certificates.update');
        Route::delete('/wht-certificates/{whtCertificate}', [LaborWhtCertificateController::class, 'destroy'])->name('wht-certificates.destroy');
        Route::get('/wht-certificates/{whtCertificate}/pdf', [LaborWhtCertificateController::class, 'pdf'])->name('wht-certificates.pdf');

        // Cross-team summary — open to labor-shareholder too (read-only oversight
        // role); labor-team is excluded inside the controller (own team only).
        Route::get('/reports', [LaborReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [LaborReportController::class, 'export'])->name('reports.export');

        // สมุดบัญชี — company books, separate from the main app's Finance and
        // from the per-team billing ledger. Same visibility rule as Reports:
        // open to labor-shareholder (read-only), excluded for labor-team,
        // gated per-action inside LaborBookController (not here) since view
        // vs manage differs by method.
        Route::get('/books', [LaborBookController::class, 'index'])->name('books.index');
        Route::post('/books', [LaborBookController::class, 'store'])->name('books.store');
        Route::get('/books/{account}', [LaborBookController::class, 'show'])->name('books.show');
        Route::get('/books/{account}/export', [LaborBookController::class, 'exportTransactions'])->name('books.export');
        Route::put('/books/{account}', [LaborBookController::class, 'update'])->name('books.update');
        Route::delete('/books/{account}', [LaborBookController::class, 'destroy'])->name('books.destroy');
        Route::post('/books/{account}/transactions', [LaborBookController::class, 'storeTransaction'])->name('books.transactions.store');
        Route::put('/books/{account}/transactions/{transaction}', [LaborBookController::class, 'updateTransaction'])->name('books.transactions.update');
        Route::delete('/books/{account}/transactions/{transaction}', [LaborBookController::class, 'destroyTransaction'])->name('books.transactions.destroy');

        Route::middleware('role:super-admin')->prefix('charge-types')->name('charge-types.')->group(function () {
            Route::post('/', [LaborChargeTypeController::class, 'store'])->name('store');
            Route::put('/{chargeType}', [LaborChargeTypeController::class, 'update'])->name('update');
        });

        // Login credentials for labor-accounting / labor-shareholder / labor-team —
        // Super Admin's exclusive responsibility (see LaborUserController).
        Route::middleware('role:super-admin')->prefix('users')->name('users.')->group(function () {
            Route::get('/', [LaborUserController::class, 'index'])->name('index');
            Route::get('/create', [LaborUserController::class, 'create'])->name('create');
            Route::post('/', [LaborUserController::class, 'store'])->name('store');
            Route::get('/{user}/edit', [LaborUserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [LaborUserController::class, 'update'])->name('update');
            Route::patch('/{user}/toggle-status', [LaborUserController::class, 'toggleStatus'])->name('toggle-status');
        });
    });

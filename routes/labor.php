<?php

use App\Http\Controllers\Labor\LaborAuditLogController;
use App\Http\Controllers\Labor\LaborBillController;
use App\Http\Controllers\Labor\LaborChargeEntryController;
use App\Http\Controllers\Labor\LaborChargeTypeController;
use App\Http\Controllers\Labor\LaborDashboardController;
use App\Http\Controllers\Labor\LaborLedgerController;
use App\Http\Controllers\Labor\LaborReportController;
use App\Http\Controllers\Labor\LaborTeamController;
use App\Http\Controllers\Labor\LaborTeamMemberController;
use App\Http\Controllers\Labor\LaborUserController;
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
        Route::get('/bills/{bill}/download', [LaborBillController::class, 'download'])->name('bills.download');
        Route::post('/bills/{bill}/void', [LaborBillController::class, 'void'])->name('bills.void');
        Route::put('/billing-settings', [LaborBillController::class, 'updateSettings'])->name('billing-settings.update');

        // Cross-team summary — open to labor-shareholder too (read-only oversight
        // role); labor-team is excluded inside the controller (own team only).
        Route::get('/reports', [LaborReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [LaborReportController::class, 'export'])->name('reports.export');

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

<?php

namespace App\Observers;

use App\Models\LaborAuditLog;
use App\Models\LaborLedgerEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Writes every create/update/delete/restore on a ledger entry to
 * labor_audit_logs — a trail kept separate from the main app's ActivityLog,
 * visible only to Super Admin. This is the safety net against mistakes or
 * fraud by Accounting Staff, who otherwise have full edit rights.
 */
class LaborLedgerEntryObserver
{
    public function created(LaborLedgerEntry $entry): void
    {
        $this->log($entry, 'created', null, $entry->toArray());
    }

    public function updated(LaborLedgerEntry $entry): void
    {
        $this->log($entry, 'updated', $entry->getOriginal(), $entry->getChanges());
    }

    public function deleted(LaborLedgerEntry $entry): void
    {
        $this->log($entry, 'deleted', $entry->toArray(), null);
    }

    public function restored(LaborLedgerEntry $entry): void
    {
        $this->log($entry, 'restored', null, $entry->toArray());
    }

    protected function log(LaborLedgerEntry $entry, string $action, ?array $before, ?array $after): void
    {
        LaborAuditLog::create([
            'user_id' => Auth::id(),
            'labor_team_id' => $entry->labor_team_id,
            'labor_ledger_entry_id' => $entry->id,
            'action' => $action,
            'changes' => ['before' => $before, 'after' => $after],
            'ip_address' => Request::ip(),
        ]);
    }
}

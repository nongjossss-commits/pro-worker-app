<?php

namespace App\Observers;

use App\Models\LaborAuditLog;
use App\Models\LaborBill;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Same audit trail as LaborLedgerEntryObserver, for LaborBill instead —
 * every generated/voided bill is traceable, Super Admin-only visibility.
 */
class LaborBillObserver
{
    public function created(LaborBill $bill): void
    {
        $this->log($bill, 'created', null, $bill->toArray());
    }

    public function updated(LaborBill $bill): void
    {
        $this->log($bill, 'updated', $bill->getOriginal(), $bill->getChanges());
    }

    public function deleted(LaborBill $bill): void
    {
        $this->log($bill, 'deleted', $bill->toArray(), null);
    }

    protected function log(LaborBill $bill, string $action, ?array $before, ?array $after): void
    {
        LaborAuditLog::create([
            'user_id' => Auth::id(),
            'labor_team_id' => $bill->labor_team_id,
            'labor_bill_id' => $bill->id,
            'action' => $action,
            'changes' => ['before' => $before, 'after' => $after],
            'ip_address' => Request::ip(),
        ]);
    }
}

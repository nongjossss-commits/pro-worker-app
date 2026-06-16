<?php

namespace App\Http\Controllers\Finance;

use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Services\BankReconciliationService;
use Illuminate\Http\Request;

class ReconciliationController extends Controller
{
    public function __construct(protected BankReconciliationService $service)
    {
    }

    public function index()
    {
        $summary = $this->service->overview();
        return view('financial.reconciliation.index', compact('summary'));
    }

    public function account(BankAccount $bankAccount)
    {
        $detail = $this->service->detail($bankAccount);
        return view('financial.reconciliation.account', compact('detail'));
    }

    /**
     * Repair the account's current_balance to the value re-computed from
     * the ledger. Logged so the audit trail captures the manual fix.
     */
    public function repair(Request $request, BankAccount $bankAccount)
    {
        $before = (float) $bankAccount->current_balance;
        $newBalance = $this->service->repair($bankAccount);

        ActivityLogHelper::logAction(
            'status_change',
            "ปรับ current_balance ของบัญชี [{$bankAccount->bank_name}] จาก {$before} เป็น {$newBalance}",
            BankAccount::class,
            $bankAccount->id,
            [
                'before' => $before,
                'after' => $newBalance,
                'source' => 'reconciliation_repair',
            ]
        );

        return redirect()->route('finance.reconciliation.account', $bankAccount)
            ->with('success', __('Balance repaired. New balance: :v', ['v' => number_format($newBalance, 2)]));
    }
}

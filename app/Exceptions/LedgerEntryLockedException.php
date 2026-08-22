<?php

namespace App\Exceptions;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Thrown by LedgerService::createEntry()/updateEntry()/deleteEntry() when
 * the target accounting day is already closed (see AccountingPeriodService
 * — closes at 05:00 the day after entry_date). Every caller of those
 * methods gets this for free, so the 05:00 cutoff rule can't be bypassed
 * from any one edit path (the main Ledger page, the "บันทึกรายรับรายจ่าย"
 * Books pages, or bill-payment editing) without going through the
 * Super-Admin-only LedgerService::createCorrection() flow instead — which
 * never calls back into the guarded methods with a backdated date (its
 * reversal + replacement are always dated today, always open).
 *
 * Same self-rendering pattern as EmployeeQuotaExceededException — handles
 * both AJAX (JSON 423 Locked) and normal form posts.
 */
class LedgerEntryLockedException extends Exception
{
    public function __construct(public readonly Carbon $entryDate)
    {
        parent::__construct(__(
            'This entry is from :date, which has already been closed (books lock at 05:00 the next day). Ask a Super Admin to make a correction instead of editing it directly.',
            ['date' => $entryDate->format('d/m/Y')]
        ));
    }

    public function render(Request $request)
    {
        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return response()->json([
                'error' => $this->getMessage(),
                'error_code' => 'ledger_entry_locked',
                'message' => $this->getMessage(),
            ], Response::HTTP_LOCKED);
        }

        return back()->withErrors(['ledger' => $this->getMessage()]);
    }
}

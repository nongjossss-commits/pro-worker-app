<?php

namespace App\Services;

use App\Models\LaborBill;
use App\Models\LaborBillingSetting;
use App\Models\LaborTeam;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Generates Labor billing statements. A bill is a dated, immutable snapshot
 * of what a team owed — it does NOT close out or tag labor_ledger_entries.
 * Charges stay "outstanding" until an actual payment (a negative ledger
 * entry) is recorded, so the same balance can legitimately reappear as
 * "previous balance" on the next bill until it's paid off.
 */
class LaborBillService
{
    public function generate(
        LaborTeam $team,
        Carbon $periodStart,
        Carbon $periodEnd,
        ?int $financialProfileId = null,
        bool $isAuto = false
    ): LaborBill {
        if ($periodEnd->lt($periodStart)) {
            throw new RuntimeException('period_end must not be before period_start.');
        }

        return DB::transaction(function () use ($team, $periodStart, $periodEnd, $financialProfileId, $isAuto) {
            $previousBalance = (float) $team->ledgerEntries()
                ->where('entry_date', '<', $periodStart->toDateString())
                ->sum('amount');

            $periodCharges = (float) $team->ledgerEntries()
                ->whereBetween('entry_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->sum('amount');

            $profileId = $financialProfileId ?? LaborBillingSetting::current()->financial_profile_id;

            $bill = LaborBill::create([
                'labor_team_id' => $team->id,
                'bill_no' => $this->generateBillNo((int) $periodEnd->year),
                'financial_profile_id' => $profileId,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'previous_balance' => $previousBalance,
                'period_charges' => $periodCharges,
                'total_due' => $previousBalance + $periodCharges,
                'status' => 'issued',
                'is_auto_generated' => $isAuto,
                'issued_at' => now(),
                'created_by' => Auth::id(),
            ]);

            $pdfPath = app(LaborBillPdfService::class)->generateAndStore($bill->fresh(['team', 'financialProfile']));
            $bill->update(['pdf_path' => $pdfPath]);

            return $bill->fresh();
        });
    }

    public function void(LaborBill $bill, string $reason): LaborBill
    {
        if ($bill->status === 'void') {
            throw new RuntimeException('Bill is already void.');
        }

        // Assign the void status first (in memory only) so the PDF renderer's
        // watermark check sees it, then persist everything — including the
        // regenerated PDF path — in one update, so this is one audit entry.
        $bill->status = 'void';
        $bill->voided_at = now();
        $bill->void_reason = $reason;

        $pdfPath = app(LaborBillPdfService::class)->generateAndStore($bill->loadMissing('team', 'financialProfile'));

        $bill->pdf_path = $pdfPath;
        $bill->save();

        return $bill->fresh();
    }

    /**
     * Running number per calendar year: LB-YYYY-####. Same locking pattern
     * as TaxInvoiceService::generateInvoiceNo() — sequence never reuses a
     * number, even across voided/deleted bills.
     */
    protected function generateBillNo(int $year): string
    {
        $prefix = sprintf('LB-%04d-', $year);

        $last = LaborBill::withTrashed()
            ->where('bill_no', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('bill_no');

        $seq = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}

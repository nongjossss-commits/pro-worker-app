<?php

namespace App\Services;

use App\Models\LaborBill;
use App\Models\LaborBillingSetting;
use App\Models\LaborLedgerEntry;
use App\Models\LaborTeam;
use App\Models\LaborTeamMember;
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
 *
 * Itemized / monthly bills (generateItemized(), generateMonthly()) are the
 * exception: they list only the entries ticked when issuing them, with no
 * brought-forward balance, and tag those entries (labor_bill_id) so they can
 * never be billed twice. Voiding one releases its entries again.
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

    /**
     * One bill per team from hand-picked, not-yet-billed charge entries.
     * $entryIds may span several teams; returns the new bills.
     *
     * @return \Illuminate\Support\Collection<int, LaborBill>
     */
    public function generateItemized(
        array $entryIds,
        ?int $financialProfileId = null,
        array $chargeTypeIds = [],
        string $billType = 'itemized'
    ) {
        return DB::transaction(function () use ($entryIds, $financialProfileId, $chargeTypeIds, $billType) {
            $entries = LaborLedgerEntry::query()
                ->whereIn('id', $entryIds)
                ->charges()
                ->unbilled()
                ->lockForUpdate()
                ->orderBy('entry_date')
                ->get();

            if ($entries->count() !== count(array_unique($entryIds))) {
                throw new RuntimeException('บางรายการถูกวางบิลไปแล้วหรือไม่ใช่รายการค่าใช้จ่าย กรุณาโหลดรายการใหม่');
            }

            $profileId = $financialProfileId ?? LaborBillingSetting::current()->financial_profile_id;
            $chargeTypeIds = array_values(array_map('intval', $chargeTypeIds));

            return $entries->groupBy('labor_team_id')->map(function ($teamEntries, $teamId) use ($profileId, $chargeTypeIds, $billType) {
                $start = $teamEntries->min('entry_date');
                $end = $teamEntries->max('entry_date');
                $total = round((float) $teamEntries->sum('amount'), 2);

                $bill = LaborBill::create([
                    'labor_team_id' => $teamId,
                    'bill_no' => $this->generateBillNo((int) $end->year),
                    'bill_type' => $billType,
                    'charge_type_ids' => $chargeTypeIds ?: null,
                    'financial_profile_id' => $profileId,
                    'period_start' => $start->toDateString(),
                    'period_end' => $end->toDateString(),
                    'previous_balance' => 0,
                    'period_charges' => $total,
                    'total_due' => $total,
                    'status' => 'issued',
                    'is_auto_generated' => false,
                    'issued_at' => now(),
                    'created_by' => Auth::id(),
                ]);

                // Query-builder update: tagging an entry isn't an edit of the
                // charge itself, so it stays out of the ledger audit log (the
                // bill's own "created" log already records what was billed).
                LaborLedgerEntry::whereIn('id', $teamEntries->pluck('id'))->update(['labor_bill_id' => $bill->id]);

                $pdfPath = app(LaborBillPdfService::class)->generateAndStore($bill->fresh(['team', 'financialProfile']));
                $bill->update(['pdf_path' => $pdfPath]);

                return $bill->fresh();
            })->values();
        });
    }

    /**
     * "ใบวางบิลเรียกเก็บค่าใช้จ่ายรายเดือน": records one charge per team
     * member ($rows: member_id, amount, description) for the month, then
     * issues one bill — one PDF — per team.
     *
     * @return \Illuminate\Support\Collection<int, LaborBill>
     */
    public function generateMonthly(
        Carbon $month,
        int $chargeTypeId,
        Carbon $entryDate,
        array $rows,
        ?int $financialProfileId = null
    ) {
        return DB::transaction(function () use ($month, $chargeTypeId, $entryDate, $rows, $financialProfileId) {
            $members = LaborTeamMember::whereIn('id', array_column($rows, 'member_id'))->get()->keyBy('id');
            $entryIds = [];

            foreach ($rows as $row) {
                $member = $members[$row['member_id']] ?? null;
                if (!$member) {
                    continue;
                }
                $entryIds[] = LaborLedgerEntry::create([
                    'labor_team_id' => $member->labor_team_id,
                    'labor_team_member_id' => $member->id,
                    'labor_charge_type_id' => $chargeTypeId,
                    'entry_date' => $entryDate->toDateString(),
                    'description' => $row['description'],
                    'amount' => round((float) $row['amount'], 2),
                    'created_by' => Auth::id(),
                ])->id;
            }

            if (!$entryIds) {
                throw new RuntimeException('ไม่มีรายการให้วางบิล');
            }

            $bills = $this->generateItemized($entryIds, $financialProfileId, [$chargeTypeId], 'monthly');

            // A monthly bill covers the whole month, whatever day its charges are dated.
            foreach ($bills as $bill) {
                $bill->period_start = $month->copy()->startOfMonth();
                $bill->period_end = $month->copy()->endOfMonth();
                $bill->pdf_path = app(LaborBillPdfService::class)->generateAndStore($bill->loadMissing('team', 'financialProfile'));
                $bill->save();
            }

            return $bills->map->fresh();
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

        return DB::transaction(function () use ($bill) {
            // Render first: the voided PDF still lists the bill's entries.
            $pdfPath = app(LaborBillPdfService::class)->generateAndStore($bill->loadMissing('team', 'financialProfile'));

            $bill->pdf_path = $pdfPath;
            $bill->save();

            // Itemized/monthly: its entries become "not billed yet" again.
            LaborLedgerEntry::where('labor_bill_id', $bill->id)->update(['labor_bill_id' => null]);

            return $bill->fresh();
        });
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

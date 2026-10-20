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
 * Itemized / monthly bills (generateItemized(), saveMonthly()) are the
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

    /** Comparable form of a monthly bill heading (spacing / case ignored). */
    public static function normalizeHeading(?string $v): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $v)));
    }

    /**
     * The team's (non-void) monthly bill for $month under $title, if any —
     * older bills without a title match by their charge-type names.
     */
    public function findMonthlyBill(int $teamId, Carbon $month, string $title): ?LaborBill
    {
        $want = self::normalizeHeading($title);

        return LaborBill::where('labor_team_id', $teamId)
            ->where('bill_type', 'monthly')->where('status', '!=', 'void')
            ->whereDate('period_start', $month->copy()->startOfMonth()->toDateString())
            ->orderBy('id')->get()
            ->first(fn (LaborBill $bill) => self::normalizeHeading($bill->heading) === $want);
    }

    /**
     * A bill that has a payment, tax invoice or WHT certificate is settled
     * paperwork — its amount can't be changed here (void it and re-issue).
     */
    public function monthlyBillLocked(LaborBill $bill): bool
    {
        return $bill->payments()->exists() || $bill->taxInvoices()->exists() || $bill->whtCertificates()->exists();
    }

    /**
     * "ใบวางบิลเรียกเก็บค่าใช้จ่ายรายเดือน": per team, for the month and
     * heading ($title, typed by the accountant, e.g. "ค่าจองบิน ชุดตุลาคม"):
     *  - no bill yet → records one charge per ticked member and issues one
     *    bill (one PDF);
     *  - a bill already exists → it is updated in place (same bill no.):
     *    members already on it get their amount / description changed,
     *    members not on it yet are added; totals and the PDF are redone. So
     *    a team billed for 3 people first can get the rest later, and a
     *    correction never leaves a second bill for the same charge.
     * The charges carry no charge type (they're not one of the configured
     * rate-based types), so they stay out of the charge-entry list /
     * per-type figures but count on the team balance like any other charge.
     * A bill with payments / tax invoice / WHT certificate is not changed
     * (RuntimeException) — nothing is saved in that case.
     *
     * @return array{created: \Illuminate\Support\Collection<int, LaborBill>, updated: \Illuminate\Support\Collection<int, LaborBill>}
     */
    public function saveMonthly(
        Carbon $month,
        string $title,
        Carbon $entryDate,
        array $rows,
        ?int $financialProfileId = null
    ): array {
        $title = trim($title);

        return DB::transaction(function () use ($month, $title, $entryDate, $rows, $financialProfileId) {
            $members = LaborTeamMember::whereIn('id', array_column($rows, 'member_id'))->get()->keyBy('id');
            $byTeam = [];
            foreach ($rows as $row) {
                $member = $members[$row['member_id']] ?? null;
                if ($member) {
                    $byTeam[$member->labor_team_id][] = ['member' => $member, 'amount' => round((float) $row['amount'], 2), 'description' => $row['description']];
                }
            }
            if (!$byTeam) {
                throw new RuntimeException('ไม่มีรายการให้วางบิล');
            }

            $newEntryIds = [];
            $updated = collect();
            foreach ($byTeam as $teamId => $teamRows) {
                $bill = $this->findMonthlyBill((int) $teamId, $month, $title);
                if (!$bill) {
                    foreach ($teamRows as $r) {
                        $newEntryIds[] = $this->monthlyEntry($r, $entryDate)->id;
                    }
                    continue;
                }

                if ($this->monthlyBillLocked($bill)) {
                    throw new RuntimeException(__('Bill :no already has a payment, tax invoice or WHT certificate, so it can no longer be changed — void it and issue a new one instead.', ['no' => $bill->bill_no]));
                }

                $existing = $bill->entries()->whereNotNull('labor_team_member_id')->get()->keyBy('labor_team_member_id');
                $changed = false;
                foreach ($teamRows as $r) {
                    $entry = $existing[$r['member']->id] ?? null;
                    if ($entry) {
                        if ((float) $entry->amount !== $r['amount'] || $entry->description !== $r['description']) {
                            $entry->update(['amount' => $r['amount'], 'description' => $r['description']]);
                            $changed = true;
                        }
                    } else {
                        $this->monthlyEntry($r, $entryDate, $bill->id);
                        $changed = true;
                    }
                }
                if ($changed) {
                    $total = round((float) $bill->entries()->sum('amount'), 2);
                    $bill->period_charges = $total;
                    $bill->total_due = round((float) $bill->previous_balance + $total, 2);
                    if ($bill->title === null || trim($bill->title) === '') {
                        $bill->title = $title;
                    }
                    $bill->pdf_path = app(LaborBillPdfService::class)->generateAndStore($bill->loadMissing('team', 'financialProfile'));
                    $bill->save();
                    $updated->push($bill->fresh());
                }
            }

            $created = collect();
            if ($newEntryIds) {
                $created = $this->generateItemized($newEntryIds, $financialProfileId, [], 'monthly');

                // A monthly bill covers the whole month, whatever day its charges are dated.
                foreach ($created as $bill) {
                    $bill->title = $title;
                    $bill->period_start = $month->copy()->startOfMonth();
                    $bill->period_end = $month->copy()->endOfMonth();
                    $bill->pdf_path = app(LaborBillPdfService::class)->generateAndStore($bill->loadMissing('team', 'financialProfile'));
                    $bill->save();
                }
                $created = $created->map->fresh();
            }

            return ['created' => $created->values(), 'updated' => $updated->values()];
        });
    }

    private function monthlyEntry(array $r, Carbon $entryDate, ?int $billId = null): LaborLedgerEntry
    {
        return LaborLedgerEntry::create([
            'labor_bill_id' => $billId,
            'labor_team_id' => $r['member']->labor_team_id,
            'labor_team_member_id' => $r['member']->id,
            'entry_date' => $entryDate->toDateString(),
            'description' => $r['description'],
            'amount' => $r['amount'],
            'created_by' => Auth::id(),
        ]);
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

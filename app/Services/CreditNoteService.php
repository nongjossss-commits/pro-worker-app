<?php

namespace App\Services;

use App\Helpers\ActivityLogHelper;
use App\Models\CreditNote;
use App\Models\FinancialTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * ใบลดหนี้ — reduces a specific FinancialTransaction's effective balance.
 * Deliberately does NOT touch LedgerEntry/BankAccount.current_balance: a
 * credit note here represents "the customer owes less," not a cash
 * movement (a real cash refund, if one ever happens, is a separate expense
 * entry through the existing Ledger flow — out of scope for this service).
 * So BankReconciliationService, which re-derives balances purely from
 * ledger_entries, is entirely unaffected by anything this class does.
 */
class CreditNoteService
{
    /**
     * สร้างใบลดหนี้ใหม่ (draft) — running number ต่อปีภาษี
     */
    public function create(array $data): CreditNote
    {
        $this->validatePayload($data);

        return DB::transaction(function () use ($data) {
            $fiscalYear = (int) ($data['fiscal_year'] ?? Carbon::parse($data['credit_note_date'])->year);
            $creditNoteNo = $this->generateCreditNoteNo($fiscalYear);

            $note = CreditNote::create(array_merge($data, [
                'credit_note_no' => $creditNoteNo,
                'fiscal_year' => $fiscalYear,
                'status' => $data['status'] ?? 'draft',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]));

            ActivityLogHelper::logAction(
                'create',
                "สร้างใบลดหนี้ {$note->credit_note_no} (ยอด {$note->total_credit} บาท)",
                CreditNote::class,
                $note->id,
                ['credit_note_no' => $note->credit_note_no, 'total_credit' => (float) $note->total_credit, 'status' => $note->status],
            );

            return $note;
        });
    }

    /**
     * ออกใบลดหนี้จริง (lock เลข) แล้วปรับยอด credit_amount/status ของบิลที่ถูกลด
     */
    public function issue(CreditNote $note): CreditNote
    {
        if ($note->status !== 'draft') {
            throw new RuntimeException("Cannot issue credit note in status [{$note->status}].");
        }

        return DB::transaction(function () use ($note) {
            $transaction = $note->financialTransaction()->lockForUpdate()->first();
            $alreadyCredited = $transaction->creditNotes()->where('status', 'issued')->sum('total_credit');
            $creditableAmount = $transaction->amount - ($transaction->discount_amount ?? 0);

            if (($alreadyCredited + $note->total_credit) > $creditableAmount) {
                throw new RuntimeException(sprintf(
                    'Credit note total (%.2f) would exceed bill #%d\'s creditable amount (%.2f already credited of %.2f billed).',
                    $note->total_credit,
                    $transaction->id,
                    $alreadyCredited,
                    $creditableAmount,
                ));
            }

            $note->update([
                'status' => 'issued',
                'issued_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            $this->recalculateTransaction($transaction);

            ActivityLogHelper::logAction(
                'status_change',
                "ออกใบลดหนี้ {$note->credit_note_no} (issued) ลดยอดบิล #{$note->financial_transaction_id}",
                CreditNote::class,
                $note->id,
                ['credit_note_no' => $note->credit_note_no, 'from' => 'draft', 'to' => 'issued', 'financial_transaction_id' => $note->financial_transaction_id],
            );

            return $note->fresh();
        });
    }

    /**
     * ยกเลิกใบลดหนี้ — ไม่ลบเลขทิ้ง (ลำดับห้ามขาดตามกฎหมาย) แล้วคืนยอดบิลที่เคยถูกลด
     */
    public function void(CreditNote $note, string $reason): CreditNote
    {
        if (!in_array($note->status, ['draft', 'issued'], true)) {
            throw new RuntimeException("Cannot void credit note in status [{$note->status}].");
        }

        return DB::transaction(function () use ($note, $reason) {
            $wasIssued = $note->status === 'issued';

            $note->update([
                'status' => 'void',
                'voided_at' => now(),
                'void_reason' => $reason,
                'updated_by' => Auth::id(),
            ]);

            // Only a previously-ISSUED note actually affected the
            // transaction's credit_amount — a draft never did.
            if ($wasIssued) {
                $this->recalculateTransaction($note->financialTransaction);
            }

            ActivityLogHelper::logAction(
                'status_change',
                "ยกเลิกใบลดหนี้ {$note->credit_note_no} (void)",
                CreditNote::class,
                $note->id,
                ['credit_note_no' => $note->credit_note_no, 'reason' => $reason],
            );

            return $note->fresh();
        });
    }

    /**
     * แก้ไข — เฉพาะ draft เท่านั้น
     */
    public function update(CreditNote $note, array $data): CreditNote
    {
        if ($note->isLocked()) {
            throw new RuntimeException('Cannot edit a locked credit note. Void it and create a new one.');
        }

        $note->update(array_merge($data, [
            'updated_by' => Auth::id(),
        ]));

        return $note->fresh();
    }

    /**
     * Recompute a transaction's credit_amount from its ISSUED credit notes
     * (never incremented directly — same recalculate-from-DB discipline as
     * paid_amount). Re-derives status the same way
     * FinancialController::destroyPayment() does (not storePayment() —
     * that one only ever adds, so it never needs to fall back to
     * 'pending'; this method must, since void() is a reversal that can
     * bring the covered amount back down to zero, e.g. voiding a note on
     * an otherwise-untouched bill).
     */
    protected function recalculateTransaction(FinancialTransaction $transaction): void
    {
        $transaction->credit_amount = $transaction->creditNotes()->where('status', 'issued')->sum('total_credit');

        $effectiveAmount = $transaction->amount - ($transaction->discount_amount ?? 0) - $transaction->credit_amount;
        $covered = ($transaction->paid_amount ?? 0) + $transaction->credit_amount;

        if ($covered >= $effectiveAmount) {
            $transaction->status = 'paid';
        } elseif ($covered > 0) {
            $transaction->status = 'partial';
        } else {
            $transaction->status = 'pending';
        }

        $transaction->save();
    }

    // -------- Internal --------

    /**
     * Generate running number ต่อ fiscal year — same collision-safe pattern
     * as TaxInvoiceService::generateInvoiceNo().
     * Format: CN-YYYY-####
     */
    protected function generateCreditNoteNo(int $fiscalYear): string
    {
        $prefix = sprintf('CN-%04d-', $fiscalYear);

        $last = CreditNote::withTrashed()
            ->where('fiscal_year', $fiscalYear)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('credit_note_no');

        $seq = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    protected function validatePayload(array $data): void
    {
        foreach (['credit_note_date', 'financial_transaction_id', 'customer_name', 'subtotal', 'vat_amount', 'total_credit', 'reason'] as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                throw new InvalidArgumentException("Missing required field [{$field}] for credit note.");
            }
        }
    }
}

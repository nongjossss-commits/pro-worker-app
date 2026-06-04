<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LedgerService
{
    /**
     * คำนวณ breakdown ของยอด: gross → vat → subtotal → wht → net
     *
     * Input:
     *   - gross_amount        (decimal)
     *   - vat_treatment       ('none','taxable','exempt','zero_rate')
     *   - vat_rate            (decimal, default 7 ถ้า taxable)
     *   - vat_inclusive       (bool — gross รวม VAT แล้วหรือไม่)
     *   - wht_type            ('none','pnd3','pnd53')
     *   - wht_rate            (decimal)
     *   - account_type        ('personal'|'company') — Personal บังคับ none
     *
     * Output:
     *   gross, vat_treatment, vat_rate, vat_amount, subtotal,
     *   wht_type, wht_rate, wht_amount, net_amount
     */
    public function calculateBreakdown(array $input): array
    {
        $accountType = $input['account_type'] ?? 'company';
        $gross = (float) ($input['gross_amount'] ?? 0);

        // Personal account = off-book — บังคับไม่มี VAT/WHT
        if ($accountType === 'personal') {
            return [
                'gross_amount' => round($gross, 2),
                'vat_treatment' => 'none',
                'vat_rate' => 0,
                'vat_amount' => 0,
                'subtotal' => round($gross, 2),
                'wht_type' => 'none',
                'wht_rate' => 0,
                'wht_amount' => 0,
                'net_amount' => round($gross, 2),
            ];
        }

        $vatTreatment = $input['vat_treatment'] ?? 'none';
        $vatRate = (float) ($input['vat_rate'] ?? 0);
        $vatInclusive = (bool) ($input['vat_inclusive'] ?? false);
        $whtType = $input['wht_type'] ?? 'none';
        $whtRate = (float) ($input['wht_rate'] ?? 0);

        // -------- คำนวณ VAT --------
        $vatAmount = 0;
        $subtotal = $gross;

        if ($vatTreatment === 'taxable' && $vatRate > 0) {
            if ($vatInclusive) {
                // gross รวม VAT แล้ว → แยกออก
                $subtotal = $gross / (1 + ($vatRate / 100));
                $vatAmount = $gross - $subtotal;
            } else {
                // gross ไม่รวม VAT → เพิ่ม
                $subtotal = $gross;
                $vatAmount = $gross * ($vatRate / 100);
            }
        }

        // -------- คำนวณ WHT --------
        // WHT คิดจาก subtotal (ไม่รวม VAT) ตามมาตรฐานไทย
        $whtAmount = 0;
        if ($whtType !== 'none' && $whtRate > 0) {
            $whtAmount = $subtotal * ($whtRate / 100);
        }

        // -------- Net (เงินจริงที่เข้า/ออกบัญชี) --------
        // Income:  ลูกค้าจ่ายเรา = (subtotal + vat) - wht
        // Expense: เราจ่าย supplier = (subtotal + vat) - wht ที่หักไว้
        $totalWithVat = $subtotal + $vatAmount;
        $netAmount = $totalWithVat - $whtAmount;

        return [
            'gross_amount' => round($gross, 2),
            'vat_treatment' => $vatTreatment,
            'vat_rate' => round($vatRate, 2),
            'vat_amount' => round($vatAmount, 2),
            'subtotal' => round($subtotal, 2),
            'wht_type' => $whtType,
            'wht_rate' => round($whtRate, 2),
            'wht_amount' => round($whtAmount, 2),
            'net_amount' => round($netAmount, 2),
        ];
    }

    /**
     * สร้าง LedgerEntry ใหม่ — auto balance update + entry_no
     */
    public function createEntry(array $data): LedgerEntry
    {
        $this->validatePayload($data);

        return DB::transaction(function () use ($data) {
            $account = BankAccount::lockForUpdate()->findOrFail($data['bank_account_id']);
            $data['account_type'] = $account->account_type;

            $breakdown = $this->calculateBreakdown($data);

            $entry = LedgerEntry::create(array_merge($data, $breakdown, [
                'entry_no' => $this->generateEntryNo($data['entry_date']),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'ai_source' => $data['ai_source'] ?? 'manual',
                'ai_status' => $data['ai_status'] ?? 'confirmed',
            ]));

            $this->applyBalanceImpact($account, $entry->type, $entry->net_amount, +1);

            return $entry->fresh();
        });
    }

    /**
     * แก้ไข LedgerEntry — reverse impact เก่า แล้ว apply ใหม่
     */
    public function updateEntry(LedgerEntry $entry, array $data): LedgerEntry
    {
        $this->validatePayload($data);

        return DB::transaction(function () use ($entry, $data) {
            // Reverse balance เก่า
            $oldAccount = BankAccount::lockForUpdate()->findOrFail($entry->bank_account_id);
            $this->applyBalanceImpact($oldAccount, $entry->type, $entry->net_amount, -1);

            // Apply ใหม่
            $newAccount = $oldAccount;
            if (isset($data['bank_account_id']) && $data['bank_account_id'] != $entry->bank_account_id) {
                $newAccount = BankAccount::lockForUpdate()->findOrFail($data['bank_account_id']);
            }
            $data['account_type'] = $newAccount->account_type;

            $breakdown = $this->calculateBreakdown($data);
            $entry->update(array_merge($data, $breakdown, [
                'updated_by' => Auth::id(),
            ]));

            $this->applyBalanceImpact($newAccount, $entry->type, $entry->net_amount, +1);

            return $entry->fresh();
        });
    }

    /**
     * ลบ LedgerEntry — soft delete + reverse balance
     */
    public function deleteEntry(LedgerEntry $entry): bool
    {
        return DB::transaction(function () use ($entry) {
            $account = BankAccount::lockForUpdate()->findOrFail($entry->bank_account_id);
            $this->applyBalanceImpact($account, $entry->type, $entry->net_amount, -1);

            $entry->updated_by = Auth::id();
            $entry->save();

            return (bool) $entry->delete();
        });
    }

    /**
     * Restore — กลับมาจาก soft delete + re-apply balance
     */
    public function restoreEntry(int $entryId): LedgerEntry
    {
        return DB::transaction(function () use ($entryId) {
            $entry = LedgerEntry::onlyTrashed()->findOrFail($entryId);
            $entry->restore();

            $account = BankAccount::lockForUpdate()->findOrFail($entry->bank_account_id);
            $this->applyBalanceImpact($account, $entry->type, $entry->net_amount, +1);

            return $entry->fresh();
        });
    }

    // -------- Internal helpers --------

    protected function generateEntryNo(string $entryDate): string
    {
        $date = \Carbon\Carbon::parse($entryDate);
        $prefix = sprintf('LE-%04d-%02d-', $date->year, $date->month);

        $last = LedgerEntry::withTrashed()
            ->where('entry_no', 'like', $prefix . '%')
            ->orderByDesc('entry_no')
            ->value('entry_no');

        $seq = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    protected function applyBalanceImpact(BankAccount $account, string $type, $netAmount, int $sign): void
    {
        // income → ยอดเข้า (+), expense → ยอดออก (-)
        $direction = $type === 'income' ? 1 : -1;
        $delta = $direction * $sign * (float) $netAmount;

        $account->current_balance = (float) $account->current_balance + $delta;
        $account->save();
    }

    protected function validatePayload(array $data): void
    {
        $required = ['entry_date', 'type', 'bank_account_id', 'gross_amount'];
        foreach ($required as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                throw new InvalidArgumentException("LedgerService: missing required field [{$field}]");
            }
        }

        if (!in_array($data['type'], ['income', 'expense'], true)) {
            throw new InvalidArgumentException("LedgerService: invalid type [{$data['type']}]");
        }

        if ((float) $data['gross_amount'] < 0) {
            throw new InvalidArgumentException("LedgerService: gross_amount must be >= 0");
        }
    }
}

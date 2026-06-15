<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\TaxInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class TaxInvoiceService
{
    /**
     * สร้าง Tax Invoice ใหม่ — running number ต่อปีภาษี
     */
    public function create(array $data): TaxInvoice
    {
        $this->validatePayload($data);

        return DB::transaction(function () use ($data) {
            $fiscalYear = (int) ($data['fiscal_year'] ?? Carbon::parse($data['invoice_date'])->year);
            $invoiceNo = $this->generateInvoiceNo($fiscalYear);

            return TaxInvoice::create(array_merge($data, [
                'invoice_no' => $invoiceNo,
                'fiscal_year' => $fiscalYear,
                'status' => $data['status'] ?? 'draft',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]));
        });
    }

    /**
     * สร้าง Tax Invoice จาก LedgerEntry (auto-fill จากข้อมูลที่บันทึกใน ledger)
     */
    public function createFromLedger(LedgerEntry $entry, array $overrides = []): TaxInvoice
    {
        $entry->loadMissing('bankAccount.financialProfile');

        if ($entry->type !== 'income') {
            throw new InvalidArgumentException('TaxInvoice can only be issued from income entries.');
        }
        if ($entry->vat_treatment !== 'taxable') {
            throw new InvalidArgumentException('TaxInvoice requires VAT-taxable income entry.');
        }
        $issuerProfileId = $entry->bankAccount?->financial_profile_id;
        if (!$issuerProfileId) {
            throw new InvalidArgumentException('Bank account must be linked to a FinancialProfile (biller).');
        }

        return $this->create(array_merge([
            'invoice_date' => $entry->entry_date,
            'issuer_profile_id' => $issuerProfileId,
            'customer_name' => $entry->counterparty_name ?: 'Walk-in Customer',
            'customer_tax_id' => $entry->counterparty_tax_id,
            'subtotal' => $entry->subtotal,
            'vat_rate' => $entry->vat_rate,
            'vat_amount' => $entry->vat_amount,
            'total' => (float) $entry->subtotal + (float) $entry->vat_amount,
            'related_ledger_entry_id' => $entry->id,
            'status' => 'issued',
        ], $overrides));
    }

    /**
     * Mark as issued (lock ตัวเลข)
     */
    public function issue(TaxInvoice $invoice): TaxInvoice
    {
        if ($invoice->status !== 'draft') {
            throw new RuntimeException("Cannot issue invoice in status [{$invoice->status}].");
        }

        $invoice->update([
            'status' => 'issued',
            'issued_at' => now(),
            'updated_by' => Auth::id(),
        ]);

        return $invoice->fresh();
    }

    /**
     * Void invoice — เก็บไว้แต่ลบจาก active reports
     * ไม่ลบเลขทิ้ง (ลำดับห้ามขาด ตามกฎหมาย)
     */
    public function void(TaxInvoice $invoice, string $reason): TaxInvoice
    {
        if (!in_array($invoice->status, ['draft', 'issued'], true)) {
            throw new RuntimeException("Cannot void invoice in status [{$invoice->status}].");
        }

        $invoice->update([
            'status' => 'void',
            'voided_at' => now(),
            'void_reason' => $reason,
            'updated_by' => Auth::id(),
        ]);

        return $invoice->fresh();
    }

    /**
     * แก้ไข — เฉพาะ draft เท่านั้น
     */
    public function update(TaxInvoice $invoice, array $data): TaxInvoice
    {
        if ($invoice->isLocked()) {
            throw new RuntimeException('Cannot edit a locked invoice. Void it and create a new one.');
        }

        $invoice->update(array_merge($data, [
            'updated_by' => Auth::id(),
        ]));

        return $invoice->fresh();
    }

    // -------- Internal --------

    /**
     * Generate running number ต่อ fiscal year
     * Format: INV-YYYY-####
     */
    protected function generateInvoiceNo(int $fiscalYear): string
    {
        $prefix = sprintf('INV-%04d-', $fiscalYear);

        // ค้นเลขล่าสุดในปีภาษีเดียวกัน (รวม trashed + void เพื่อรักษาลำดับต่อเนื่อง)
        $last = TaxInvoice::withTrashed()
            ->where('fiscal_year', $fiscalYear)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('invoice_no');

        $seq = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    protected function validatePayload(array $data): void
    {
        $required = ['invoice_date', 'issuer_profile_id', 'customer_name', 'subtotal', 'vat_amount', 'total'];
        foreach ($required as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                throw new InvalidArgumentException("TaxInvoiceService: missing required field [{$field}]");
            }
        }
    }
}

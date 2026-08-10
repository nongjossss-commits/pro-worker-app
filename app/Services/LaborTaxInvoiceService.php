<?php

namespace App\Services;

use App\Models\FinancialProfile;
use App\Models\LaborBill;
use App\Models\LaborTaxInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Mirrors App\Services\TaxInvoiceService (main app's ใบกำกับภาษี) for the
 * Pro Walker Labor module — same draft/issue/void lifecycle and running-number
 * discipline, sourced from LaborBill instead of LedgerEntry.
 */
class LaborTaxInvoiceService
{
    public function create(array $data): LaborTaxInvoice
    {
        $this->validatePayload($data);

        return DB::transaction(function () use ($data) {
            $fiscalYear = (int) ($data['fiscal_year'] ?? Carbon::parse($data['invoice_date'])->year);
            $invoiceNo = $this->generateInvoiceNo($fiscalYear);

            return LaborTaxInvoice::create(array_merge($data, [
                'invoice_no' => $invoiceNo,
                'fiscal_year' => $fiscalYear,
                'status' => $data['status'] ?? 'draft',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]));
        });
    }

    /**
     * Compute (without persisting) the suggested field values for a new
     * invoice from a LaborBill: subtotal from period_charges, VAT from the
     * bill's FinancialProfile, customer from the team's customer_* fields.
     * Used both to pre-fill the "create" form (no invoice_no burned just to
     * preview) and by createFromBill() below.
     */
    public function previewFromBill(LaborBill $bill): array
    {
        $bill->loadMissing('team', 'financialProfile');

        $profile = $bill->financialProfile;
        if (!$profile) {
            throw new InvalidArgumentException('Bill has no FinancialProfile (issuer) set.');
        }

        $vatRate = $profile->is_vat_registered ? (float) $profile->vat_rate : 0.0;
        $subtotal = (float) $bill->period_charges;
        $vatAmount = round($subtotal * $vatRate / 100, 2);

        return [
            'invoice_date' => now()->toDateString(),
            'labor_bill_id' => $bill->id,
            'issuer_profile_id' => $profile->id,
            'customer_name' => $bill->team->name,
            'customer_tax_id' => $bill->team->customer_tax_id,
            'customer_branch' => $bill->team->customer_branch,
            'customer_address' => $bill->team->customer_address,
            'subtotal' => $subtotal,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'total' => $subtotal + $vatAmount,
        ];
    }

    /**
     * Create a real (numbered) invoice pre-filled from a LaborBill.
     */
    public function createFromBill(LaborBill $bill, array $overrides = []): LaborTaxInvoice
    {
        return $this->create(array_merge($this->previewFromBill($bill), $overrides));
    }

    public function issue(LaborTaxInvoice $invoice): LaborTaxInvoice
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

    public function void(LaborTaxInvoice $invoice, string $reason): LaborTaxInvoice
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

    public function update(LaborTaxInvoice $invoice, array $data): LaborTaxInvoice
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
     * Format: LTI-YYYY-#### — separate sequence from the main app's
     * INV-YYYY-#### (different table, `tax_invoices` vs `labor_tax_invoices`).
     */
    protected function generateInvoiceNo(int $fiscalYear): string
    {
        $prefix = sprintf('LTI-%04d-', $fiscalYear);

        $last = LaborTaxInvoice::withTrashed()
            ->where('fiscal_year', $fiscalYear)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('invoice_no');

        $seq = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    protected function validatePayload(array $data): void
    {
        $required = ['invoice_date', 'issuer_profile_id', 'customer_name', 'subtotal', 'vat_amount', 'total'];
        foreach ($required as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                throw new InvalidArgumentException("LaborTaxInvoiceService: missing required field [{$field}]");
            }
        }
    }
}

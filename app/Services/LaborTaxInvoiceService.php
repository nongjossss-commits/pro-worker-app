<?php

namespace App\Services;

use App\Models\FinancialProfile;
use App\Models\LaborBill;
use App\Models\LaborCustomer;
use App\Models\LaborTaxInvoice;
use App\Models\LaborTaxInvoiceItem;
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

        // labor_team_id is always derived here, never trusted from the
        // caller — it must match whichever of labor_bill_id/
        // labor_customer_id actually identifies the team, so accounting's
        // team filter on the invoice list can never be spoofed or drift.
        if (!empty($data['labor_bill_id'])) {
            $bill = LaborBill::findOrFail($data['labor_bill_id']);
            $data['labor_team_id'] = $bill->labor_team_id;
            $data['period_start'] = $data['period_start'] ?? $bill->period_start;
            $data['period_end'] = $data['period_end'] ?? $bill->period_end;
        } elseif (!empty($data['labor_customer_id'])) {
            $customer = LaborCustomer::findOrFail($data['labor_customer_id']);
            $data['labor_team_id'] = $customer->labor_team_id;
            $this->assertNoOverlappingPeriod($customer, $data['period_start'], $data['period_end']);
        } else {
            $data['labor_team_id'] = null;
        }

        // Amounts are always recomputed from the line items server-side —
        // never trusted from the client — so a JS bug or tampered request
        // can never produce an invoice whose subtotal/vat/total don't
        // actually match its own item rows.
        $items = $data['items'];
        unset($data['items']);
        $totals = $this->computeTotalsFromItems($items, (float) $data['vat_rate']);
        $data = array_merge($data, $totals['fields']);

        return DB::transaction(function () use ($data, $totals) {
            $fiscalYear = (int) ($data['fiscal_year'] ?? Carbon::parse($data['invoice_date'])->year);
            $invoiceNo = $this->generateInvoiceNo($fiscalYear);

            $invoice = LaborTaxInvoice::create(array_merge($data, [
                'invoice_no' => $invoiceNo,
                'fiscal_year' => $fiscalYear,
                'status' => $data['status'] ?? 'draft',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]));

            $this->saveItems($invoice, $totals['items']);

            return $invoice->fresh('items');
        });
    }

    /**
     * Validates and normalizes a raw items array into rounded amounts, and
     * computes the invoice-level subtotal/vat/total from them — the single
     * source of truth for every money figure on the invoice, whether it
     * came from previewFromBill()'s one auto-filled row or a fully manual
     * external-customer breakdown.
     */
    protected function computeTotalsFromItems(array $items, float $vatRate): array
    {
        $normalized = [];
        $subtotal = 0.0;

        foreach (array_values($items) as $i => $item) {
            $description = trim((string) ($item['description'] ?? ''));
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);

            if ($description === '') {
                throw new InvalidArgumentException("LaborTaxInvoiceService: item #" . ($i + 1) . " is missing a description.");
            }
            if ($quantity <= 0) {
                throw new InvalidArgumentException("LaborTaxInvoiceService: item \"{$description}\" must have a quantity greater than 0.");
            }

            $amount = round($quantity * $unitPrice, 2);
            $subtotal += $amount;

            $normalized[] = [
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => $amount,
                'sort_order' => $i,
            ];
        }

        if (empty($normalized)) {
            throw new InvalidArgumentException('LaborTaxInvoiceService: at least one line item is required.');
        }

        $subtotal = round($subtotal, 2);
        $vatAmount = round($subtotal * $vatRate / 100, 2);

        return [
            'items' => $normalized,
            'fields' => [
                'subtotal' => $subtotal,
                'vat_amount' => $vatAmount,
                'total' => round($subtotal + $vatAmount, 2),
            ],
        ];
    }

    protected function saveItems(LaborTaxInvoice $invoice, array $items): void
    {
        $invoice->items()->delete();
        foreach ($items as $item) {
            $invoice->items()->create($item);
        }
    }

    /**
     * "Cut bills monthly correctly" guard for external-customer invoices —
     * there's no ledger to auto-chain periods from (unlike LaborBillService/
     * GenerateLaborBills), so this rejects an overlapping period outright
     * rather than silently allowing the same month to be billed twice.
     */
    protected function assertNoOverlappingPeriod(LaborCustomer $customer, string $periodStart, string $periodEnd): void
    {
        $overlaps = $customer->taxInvoices()
            ->where('status', '!=', 'void')
            ->where('period_start', '<=', $periodEnd)
            ->where('period_end', '>=', $periodStart)
            ->exists();

        if ($overlaps) {
            throw new InvalidArgumentException(
                "This period overlaps an existing invoice already issued to {$customer->name}. Adjust the dates or void the other invoice first."
            );
        }
    }

    /**
     * Compute (without persisting) the suggested field values for a new
     * standalone invoice to an external customer — mirrors previewFromBill()
     * but with no ledger/bill to derive amounts from, so subtotal/vat/total
     * are left blank for manual entry (see the confirmed "lump sum" design).
     * period_start defaults to the day after this customer's last non-void
     * invoice's period_end (or their own creation date, for their first
     * invoice) — same no-gap chaining idea as GenerateLaborBills, just
     * computed on read instead of stored.
     */
    public function previewFromCustomer(LaborCustomer $customer): array
    {
        $lastInvoice = $customer->taxInvoices()
            ->where('status', '!=', 'void')
            ->orderByDesc('period_end')
            ->first();

        $periodStart = $lastInvoice && $lastInvoice->period_end
            ? $lastInvoice->period_end->copy()->addDay()
            : $customer->created_at->copy()->startOfDay();

        return [
            'invoice_date' => now()->toDateString(),
            'labor_customer_id' => $customer->id,
            'labor_team_id' => $customer->labor_team_id,
            'period_start' => $periodStart->toDateString(),
            'period_end' => now()->toDateString(),
            'customer_name' => $customer->name,
            'customer_tax_id' => $customer->tax_id,
            'customer_branch' => $customer->branch,
            'customer_address' => $customer->address,
            'items' => [],
            'subtotal' => null,
            'vat_rate' => 7,
            'vat_amount' => null,
            'total' => null,
        ];
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
            'labor_team_id' => $bill->labor_team_id,
            'period_start' => $bill->period_start->toDateString(),
            'period_end' => $bill->period_end->toDateString(),
            'issuer_profile_id' => $profile->id,
            'customer_name' => $bill->team->name,
            'customer_tax_id' => $bill->team->customer_tax_id,
            'customer_branch' => $bill->team->customer_branch,
            'customer_address' => $bill->team->customer_address,
            // One auto-filled row from the bill's aggregate — still an
            // editable/extendable starting point, not a fixed single line,
            // per the confirmed "items everywhere, including bill-derived
            // invoices" design.
            'items' => [[
                'description' => 'ค่าบริการตามใบวางบิลเลขที่ ' . $bill->bill_no
                    . ' งวด ' . $bill->period_start->format('d/m/Y') . '-' . $bill->period_end->format('d/m/Y'),
                'quantity' => 1,
                'unit_price' => $subtotal,
                'amount' => $subtotal,
            ]],
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

        $totals = null;
        if (array_key_exists('items', $data)) {
            $items = $data['items'];
            unset($data['items']);
            $totals = $this->computeTotalsFromItems($items, (float) ($data['vat_rate'] ?? $invoice->vat_rate));
            $data = array_merge($data, $totals['fields']);
        }

        return DB::transaction(function () use ($invoice, $data, $totals) {
            $invoice->update(array_merge($data, [
                'updated_by' => Auth::id(),
            ]));

            if ($totals !== null) {
                $this->saveItems($invoice, $totals['items']);
            }

            return $invoice->fresh('items');
        });
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
        // subtotal/vat_amount/total are deliberately NOT required here —
        // they're always derived from `items` in create()/update(), never
        // trusted from the caller (see computeTotalsFromItems()).
        $required = ['invoice_date', 'issuer_profile_id', 'customer_name', 'vat_rate', 'items'];

        // A standalone customer invoice has no bill/ledger to derive a
        // period from, so the operator must state it explicitly — this is
        // the "cut bills monthly correctly" tracking the feature exists for.
        if (empty($data['labor_bill_id']) && !empty($data['labor_customer_id'])) {
            $required[] = 'period_start';
            $required[] = 'period_end';
        }

        foreach ($required as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                throw new InvalidArgumentException("LaborTaxInvoiceService: missing required field [{$field}]");
            }
        }

        if (!is_array($data['items']) || empty($data['items'])) {
            throw new InvalidArgumentException('LaborTaxInvoiceService: at least one line item is required.');
        }

        if (!empty($data['period_start']) && !empty($data['period_end']) && $data['period_end'] < $data['period_start']) {
            throw new InvalidArgumentException('period_end must not be before period_start.');
        }
    }
}

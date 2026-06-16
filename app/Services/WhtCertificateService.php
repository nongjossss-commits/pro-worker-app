<?php

namespace App\Services;

use App\Helpers\ActivityLogHelper;
use App\Models\LedgerEntry;
use App\Models\WhtCertificate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class WhtCertificateService
{
    /**
     * สร้าง WHT Certificate ใหม่ — running number per type+period
     */
    public function create(array $data): WhtCertificate
    {
        $this->validatePayload($data);

        return DB::transaction(function () use ($data) {
            $paidAt = Carbon::parse($data['paid_at']);
            $year = (int) ($data['tax_period_year'] ?? $paidAt->year);
            $month = (int) ($data['tax_period_month'] ?? $paidAt->month);

            $certNo = $this->generateCertNo(
                $data['type'],
                $data['wht_type'],
                $year,
                $month
            );

            $cert = WhtCertificate::create(array_merge($data, [
                'cert_no' => $certNo,
                'tax_period_year' => $year,
                'tax_period_month' => $month,
                'status' => $data['status'] ?? 'draft',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]));

            ActivityLogHelper::logAction(
                'create',
                "สร้างใบหัก ณ ที่จ่าย {$cert->cert_no} ({$cert->type}, {$cert->wht_amount} บาท)",
                WhtCertificate::class,
                $cert->id,
                ['cert_no' => $cert->cert_no, 'type' => $cert->type, 'wht_amount' => (float) $cert->wht_amount],
            );

            return $cert;
        });
    }

    /**
     * สร้าง WHT Cert จาก LedgerEntry
     *
     * direction:
     *   - 'issued'   = expense entry, เราออกใบให้ supplier (payee)
     *   - 'received' = income entry, ลูกค้าออกใบให้เรา (payee)
     */
    public function createFromLedger(LedgerEntry $entry, string $direction, array $overrides = []): WhtCertificate
    {
        $entry->loadMissing('bankAccount.financialProfile');

        if (!in_array($direction, ['issued', 'received'], true)) {
            throw new InvalidArgumentException("Invalid direction [{$direction}]. Use 'issued' or 'received'.");
        }
        if (!in_array($entry->wht_type, ['pnd3', 'pnd53'], true) || $entry->wht_amount <= 0) {
            throw new InvalidArgumentException('Ledger entry has no WHT to certify.');
        }
        if ($direction === 'issued' && $entry->type !== 'expense') {
            throw new InvalidArgumentException('Issued WHT cert requires expense entry.');
        }
        if ($direction === 'received' && $entry->type !== 'income') {
            throw new InvalidArgumentException('Received WHT cert requires income entry.');
        }

        $issuer = $entry->bankAccount?->financialProfile;

        // direction = 'issued' (expense): เราจ่าย supplier → เราเป็น payer, supplier เป็น payee
        // direction = 'received' (income): ลูกค้าจ่ายเรา → ลูกค้าเป็น payer, เราเป็น payee
        if ($direction === 'issued') {
            $payerName = $issuer?->name ?: 'Our Company';
            $payerTaxId = $issuer?->tax_id;
            $payeeName = $entry->counterparty_name ?: 'Supplier';
            $payeeTaxId = $entry->counterparty_tax_id;
        } else {
            $payerName = $entry->counterparty_name ?: 'Customer';
            $payerTaxId = $entry->counterparty_tax_id;
            $payeeName = $issuer?->name ?: 'Our Company';
            $payeeTaxId = $issuer?->tax_id;
        }

        return $this->create(array_merge([
            'type' => $direction,
            'wht_type' => $entry->wht_type,
            'payer_name' => $payerName,
            'payer_tax_id' => $payerTaxId,
            'payee_name' => $payeeName,
            'payee_tax_id' => $payeeTaxId,
            'amount_paid' => $entry->subtotal,
            'wht_rate' => $entry->wht_rate,
            'wht_amount' => $entry->wht_amount,
            'paid_at' => $entry->entry_date,
            'source_type' => LedgerEntry::class,
            'source_id' => $entry->id,
        ], $overrides));
    }

    public function issue(WhtCertificate $cert): WhtCertificate
    {
        if ($cert->status !== 'draft') {
            throw new RuntimeException("Cannot issue cert in status [{$cert->status}].");
        }

        $cert->update([
            'status' => 'issued',
            'updated_by' => Auth::id(),
        ]);

        ActivityLogHelper::logAction(
            'status_change',
            "ออกใบหัก ณ ที่จ่าย {$cert->cert_no} (issued)",
            WhtCertificate::class,
            $cert->id,
            ['cert_no' => $cert->cert_no, 'from' => 'draft', 'to' => 'issued'],
        );

        return $cert->fresh();
    }

    public function markSubmitted(WhtCertificate $cert): WhtCertificate
    {
        if ($cert->status !== 'issued') {
            throw new RuntimeException("Cannot mark submitted from status [{$cert->status}].");
        }

        $cert->update([
            'status' => 'submitted',
            'updated_by' => Auth::id(),
        ]);

        ActivityLogHelper::logAction(
            'status_change',
            "ยื่นใบหัก ณ ที่จ่าย {$cert->cert_no} ต่อกรมสรรพากร (submitted)",
            WhtCertificate::class,
            $cert->id,
            ['cert_no' => $cert->cert_no, 'from' => 'issued', 'to' => 'submitted'],
        );

        return $cert->fresh();
    }

    public function update(WhtCertificate $cert, array $data): WhtCertificate
    {
        if ($cert->isLocked()) {
            throw new RuntimeException('Cannot edit a submitted certificate.');
        }

        $cert->update(array_merge($data, [
            'updated_by' => Auth::id(),
        ]));

        return $cert->fresh();
    }

    // -------- Internal --------

    /**
     * Generate cert_no per type+wht_type+period
     * Format: WHT-{TYPE}-{YYYY}{MM}-####
     * เช่น WHT-PND53-202606-0001
     */
    protected function generateCertNo(string $type, string $whtType, int $year, int $month): string
    {
        $prefix = sprintf('WHT-%s-%04d%02d-', strtoupper($whtType), $year, $month);

        $last = WhtCertificate::withTrashed()
            ->where('type', $type)
            ->where('wht_type', $whtType)
            ->where('tax_period_year', $year)
            ->where('tax_period_month', $month)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('cert_no');

        $seq = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    protected function validatePayload(array $data): void
    {
        $required = ['type', 'wht_type', 'payer_name', 'payee_name', 'amount_paid', 'wht_rate', 'wht_amount', 'paid_at'];
        foreach ($required as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                throw new InvalidArgumentException("WhtCertificateService: missing required field [{$field}]");
            }
        }

        if (!in_array($data['type'], ['issued', 'received'], true)) {
            throw new InvalidArgumentException("WhtCertificateService: invalid type [{$data['type']}]");
        }

        if (!in_array($data['wht_type'], ['pnd3', 'pnd53'], true)) {
            throw new InvalidArgumentException("WhtCertificateService: invalid wht_type [{$data['wht_type']}]");
        }
    }
}

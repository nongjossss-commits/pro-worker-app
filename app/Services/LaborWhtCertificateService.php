<?php

namespace App\Services;

use App\Models\LaborWhtCertificate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Mirrors App\Services\WhtCertificateService for the Pro Walker Labor
 * module. Almost always `type = received` — the team/customer paying a
 * LaborBill withholds tax and hands Labor the certificate — but `issued`
 * is kept available for parity with the main app's model.
 */
class LaborWhtCertificateService
{
    public function create(array $data): LaborWhtCertificate
    {
        $this->validatePayload($data);

        return DB::transaction(function () use ($data) {
            $paidAt = \Carbon\Carbon::parse($data['paid_at']);
            $year = (int) ($data['tax_period_year'] ?? $paidAt->year);
            $month = (int) ($data['tax_period_month'] ?? $paidAt->month);

            $certNo = $this->generateCertNo($data['type'], $data['wht_type'], $year, $month);

            return LaborWhtCertificate::create(array_merge($data, [
                'cert_no' => $certNo,
                'tax_period_year' => $year,
                'tax_period_month' => $month,
                'status' => $data['status'] ?? 'draft',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]));
        });
    }

    public function issue(LaborWhtCertificate $cert): LaborWhtCertificate
    {
        if ($cert->status !== 'draft') {
            throw new RuntimeException("Cannot issue cert in status [{$cert->status}].");
        }

        $cert->update(['status' => 'issued', 'updated_by' => Auth::id()]);

        return $cert->fresh();
    }

    public function markSubmitted(LaborWhtCertificate $cert): LaborWhtCertificate
    {
        if ($cert->status !== 'issued') {
            throw new RuntimeException("Cannot mark submitted from status [{$cert->status}].");
        }

        $cert->update(['status' => 'submitted', 'updated_by' => Auth::id()]);

        return $cert->fresh();
    }

    public function update(LaborWhtCertificate $cert, array $data): LaborWhtCertificate
    {
        if ($cert->isLocked()) {
            throw new RuntimeException('Cannot edit a submitted certificate.');
        }

        $cert->update(array_merge($data, ['updated_by' => Auth::id()]));

        return $cert->fresh();
    }

    // -------- Internal --------

    /**
     * Format: LWHT-{TYPE}-{YYYY}{MM}-#### — separate sequence from the main
     * app's WHT-{TYPE}-{YYYY}{MM}-#### (different table).
     */
    protected function generateCertNo(string $type, string $whtType, int $year, int $month): string
    {
        $prefix = sprintf('LWHT-%s-%04d%02d-', strtoupper($whtType), $year, $month);

        $last = LaborWhtCertificate::withTrashed()
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

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    protected function validatePayload(array $data): void
    {
        $required = ['type', 'wht_type', 'payer_name', 'payee_name', 'amount_paid', 'wht_rate', 'wht_amount', 'paid_at'];
        foreach ($required as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                throw new InvalidArgumentException("LaborWhtCertificateService: missing required field [{$field}]");
            }
        }

        if (!in_array($data['type'], ['issued', 'received'], true)) {
            throw new InvalidArgumentException("LaborWhtCertificateService: invalid type [{$data['type']}]");
        }

        if (!in_array($data['wht_type'], ['pnd3', 'pnd53'], true)) {
            throw new InvalidArgumentException("LaborWhtCertificateService: invalid wht_type [{$data['wht_type']}]");
        }
    }
}

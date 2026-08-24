<?php

namespace App\Services;

use App\Models\ProWorkerContract;
use App\Models\ProWorkerContractTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Issues a Pro Worker <-> Employer contract: generates the anti-forgery
 * random contract number, renders the PDF (via ProWorkerContractPdfService
 * — which also embeds a QR code linking to the public verify page), and
 * records the ProWorkerContract row.
 */
class ProWorkerContractService
{
    /**
     * @param  int|null  $employerId  set only when the issuer has main-app
     *         Employer access (see LaborContractController::create()) —
     *         external teams leave this null and rely on $employerNameSnapshot
     *         alone. Deliberately independent of $fieldValues/field_mapping.
     */
    public function issue(ProWorkerContractTemplate $template, User $issuer, array $fieldValues, ?int $employerId, string $employerNameSnapshot): ProWorkerContract
    {
        return DB::transaction(function () use ($template, $issuer, $fieldValues, $employerId, $employerNameSnapshot) {
            $contractNo = $this->generateContractNo();

            $filePath = app(ProWorkerContractPdfService::class)->generate($template, $fieldValues, $contractNo);

            return ProWorkerContract::create([
                'contract_no' => $contractNo,
                'pro_worker_contract_template_id' => $template->id,
                'issued_by' => $issuer->id,
                'labor_team_id' => $issuer->labor_team_id,
                'employer_id' => $employerId,
                'employer_name_snapshot' => $employerNameSnapshot,
                'field_values' => $fieldValues,
                'file_path' => $filePath,
                'worker_count' => $this->resolveWorkerCount($template, $fieldValues),
                'issued_at' => now(),
            ]);
        });
    }

    /**
     * Corrects an already-issued contract's data — re-renders the PDF at
     * the SAME file_path (so it overwrites in place) and keeps contract_no/
     * issued_at/issued_by/labor_team_id untouched, since this contract has
     * no financial consequence and only feeds issuance statistics (see
     * ProWorkerContract's docblock — no delete/cancel route exists, only
     * this correction path). employer_id/employer_name_snapshot CAN be
     * corrected here, same as field_values.
     */
    public function update(ProWorkerContract $contract, array $fieldValues, ?int $employerId, string $employerNameSnapshot): ProWorkerContract
    {
        return DB::transaction(function () use ($contract, $fieldValues, $employerId, $employerNameSnapshot) {
            $template = $contract->template;

            $filePath = app(ProWorkerContractPdfService::class)->generate($template, $fieldValues, $contract->contract_no);

            $contract->update([
                'field_values' => $fieldValues,
                'file_path' => $filePath,
                'worker_count' => $this->resolveWorkerCount($template, $fieldValues),
                'employer_id' => $employerId,
                'employer_name_snapshot' => $employerNameSnapshot,
            ]);

            return $contract;
        });
    }

    /**
     * Finds the template's `worker_count`-type field (if any) and reads
     * its numeric value out of $fieldValues — denormalized onto
     * pro_worker_contracts.worker_count so reports can sum() it directly.
     */
    protected function resolveWorkerCount(ProWorkerContractTemplate $template, array $fieldValues): ?int
    {
        $field = collect($template->field_mapping ?? [])->firstWhere('type', 'worker_count');
        if (!$field || !isset($field['key']) || !isset($fieldValues[$field['key']]) || $fieldValues[$field['key']] === '') {
            return null;
        }

        return (int) $fieldValues[$field['key']];
    }

    /**
     * `PWC-YYYY-` (the original, recognizable prefix) + a 9-character
     * random alphanumeric suffix, e.g. `PWC-2026-E126A4Z19` — NOT a
     * running count (the original all-digit `PWC-YYYY-####` suffix let
     * anyone guess the next/previous valid number; a later revision tried
     * a bare 12-digit random number instead, but the prefix was worth
     * keeping for recognizability). Mixing letters into the suffix also
     * gives far more entropy per character than digits alone (36 choices
     * vs 10) for the same visual length. random_int() is a CSPRNG (unlike
     * mt_rand(), used for the similar random-ID pattern in
     * EmployerController::store()) since the whole point here is
     * unguessability, not just uniqueness.
     */
    protected function generateContractNo(): string
    {
        $prefix = sprintf('PWC-%04d-', now()->year);

        do {
            $candidate = $prefix . $this->randomAlphanumeric(9);
        } while (ProWorkerContract::where('contract_no', $candidate)->exists());

        return $candidate;
    }

    protected function randomAlphanumeric(int $length): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $max = strlen($alphabet) - 1;

        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $alphabet[random_int(0, $max)];
        }

        return $result;
    }
}

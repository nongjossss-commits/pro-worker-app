<?php

namespace App\Services;

use App\Models\ProWorkerContract;
use App\Models\ProWorkerContractTemplate;
use Illuminate\Support\Facades\DB;

/**
 * Which issuance-form fields of a Pro Worker contract template hold the
 * employer's NAME and the employer's ID number — so the contract history
 * (Pro Walker Labour → Contract History) can show and search them.
 *
 * The employer picker was removed from the issuance form long ago (see
 * ProWorkerContractService::issue()), so employer_name_snapshot stayed
 * empty ("-") on every newer contract while the real name sat in the
 * template's own free-form fields. A Super Admin now picks the two fields
 * per template (Contract Templates → Form Order → "Employer fields for
 * search", stored in the template's meta_data) — until then they're
 * guessed from the field labels (e.g. "ชื่อนายจ้าง", "เลขประจำตัวนายจ้าง").
 *
 * The values are copied onto the contract (employer_name_snapshot /
 * employer_ref_snapshot) on issue / correction and re-synced for the whole
 * template when the setting changes — plain columns because field_values
 * is a JSON blob (Thai text \u-escaped), which LIKE can't search. A
 * contract still linked to a real Employer (employer_id, issued before the
 * picker was removed) keeps its own name.
 */
class ProWorkerContractEmployerFields
{
    public const NAME_KEY = 'employer_name_key';
    public const REF_KEY = 'employer_ref_key';
    public const NONE = '__none';

    public function __construct(protected ProWorkerFormFieldsResolver $formFields)
    {
    }

    /** Plain text fields of the template that can be picked: [key => label]. */
    public function textFields(ProWorkerContractTemplate $template): array
    {
        return collect($this->formFields->unifiedItems($template))
            ->where('kind', 'text')
            ->mapWithKeys(fn ($i) => [$i['key'] => trim((string) ($i['label'] ?? '')) ?: $i['key']])
            ->all();
    }

    /**
     * ['name' => key|null, 'ref' => key|null, 'nameAuto' => bool, 'refAuto' => bool]
     * — the Super Admin's choice, or the guess when none was saved.
     */
    public function keys(ProWorkerContractTemplate $template): array
    {
        $fields = $this->textFields($template);
        $meta = is_array($template->meta_data) ? $template->meta_data : [];
        $out = [];
        foreach (['name' => self::NAME_KEY, 'ref' => self::REF_KEY] as $role => $metaKey) {
            $saved = $meta[$metaKey] ?? null;
            if ($saved === self::NONE) {
                $out[$role] = null;
                $out[$role . 'Auto'] = false;
            } elseif ($saved !== null && isset($fields[$saved])) {
                $out[$role] = $saved;
                $out[$role . 'Auto'] = false;
            } else {
                $out[$role] = $this->guess($fields, $role);
                $out[$role . 'Auto'] = true;
            }
        }

        return $out;
    }

    /** Best guess from the labels (first match wins). */
    public function guess(array $fields, string $role): ?string
    {
        // (not "บริษัท"/"company" — the contract also has Pro Worker's own company fields)
        $employer = '/(นายจ้าง|employer|ผู้ว่าจ้าง|นิติบุคคล)/iu';
        $number = '/(เลข|รหัส|ประจำตัว|ผู้เสียภาษี|tax|\bid\b|no\.|number)/iu';
        $notName = '/(ที่อยู่|address|โทร|phone|อีเมล|email|ตำแหน่ง|position|ลงนาม|sign)/iu';

        foreach ($fields as $key => $label) {
            $text = $label . ' ' . $key;
            if ($role === 'ref' && preg_match($employer, $text) && preg_match($number, $text)) {
                return $key;
            }
            if ($role === 'name' && preg_match($employer, $text) && !preg_match($number, $text) && !preg_match($notName, $text)) {
                return $key;
            }
        }

        return null;
    }

    /** ['name' => ?string, 'ref' => ?string] read from a contract's field values. */
    public function extract(ProWorkerContractTemplate $template, array $fieldValues): array
    {
        $keys = $this->keys($template);
        $read = function (?string $key) use ($fieldValues) {
            if (!$key || !isset($fieldValues[$key]) || is_array($fieldValues[$key])) {
                return null;
            }
            $value = trim(preg_replace('/\s+/u', ' ', (string) $fieldValues[$key]));

            return $value === '' ? null : mb_substr($value, 0, 255);
        };

        return ['name' => $read($keys['name']), 'ref' => $read($keys['ref'])];
    }

    /**
     * Columns to write for one contract: the employer ID always; the name
     * only when the contract isn't linked to a real Employer record.
     */
    public function columnsFor(ProWorkerContractTemplate $template, array $fieldValues, ?int $employerId): array
    {
        $found = $this->extract($template, $fieldValues);
        $cols = ['employer_ref_snapshot' => $found['ref']];
        if (!$employerId) {
            $cols['employer_name_snapshot'] = $found['name'];
        }

        return $cols;
    }

    /**
     * Re-copy name / ID onto every contract of the template (after the
     * setting changed). Query-builder update on purpose: this is derived
     * search data, not an edit — it must not appear in each contract's
     * edit history (LogActivity). Returns the number of contracts changed.
     */
    public function syncTemplate(ProWorkerContractTemplate $template): int
    {
        $changed = 0;
        ProWorkerContract::where('pro_worker_contract_template_id', $template->id)
            ->select(['id', 'employer_id', 'field_values', 'employer_name_snapshot', 'employer_ref_snapshot'])
            ->chunkById(200, function ($contracts) use ($template, &$changed) {
                foreach ($contracts as $c) {
                    $cols = $this->columnsFor($template, $c->field_values ?? [], $c->employer_id);
                    $dirty = array_filter($cols, fn ($v, $k) => $c->{$k} !== $v, ARRAY_FILTER_USE_BOTH);
                    if ($dirty) {
                        DB::table('pro_worker_contracts')->where('id', $c->id)->update($dirty);
                        $changed++;
                    }
                }
            });

        return $changed;
    }

    public function syncAll(): int
    {
        $changed = 0;
        foreach (ProWorkerContractTemplate::all() as $template) {
            $changed += $this->syncTemplate($template);
        }

        return $changed;
    }
}

<?php

namespace App\Services;

use App\Models\ProWorkerContractTemplate;

/**
 * Groups a Pro Worker contract template's field_mapping into the shapes the
 * issuance/correction form (labor/contracts/_fields.blade.php) needs, plus
 * a single formOrder-sorted list (unifiedItems()) that the Super Admin-only
 * field-order settings screen (labor/contract_templates/form_order.blade.php)
 * reorders and _fields.blade.php now renders from in one pass. The four
 * groupX() methods were moved here unchanged from LaborContractController
 * (which now calls this service instead of keeping its own copies) so the
 * form-order screen can reuse the exact same grouping logic instead of
 * duplicating it.
 */
class ProWorkerFormFieldsResolver
{
    /**
     * Group a template's field_mapping into distinct address blocks
     * (paired address_th/address_en items sharing an addressGroup).
     */
    public function addressGroups(ProWorkerContractTemplate $template): array
    {
        $groups = [];
        foreach (($template->field_mapping ?? []) as $position => $item) {
            $groupId = $item['addressGroup'] ?? null;
            if (!$groupId) {
                continue;
            }
            $type = $item['type'] ?? null;
            if ($type === 'address_th') {
                $groups[$groupId]['keyTh'] = $item['key'];
                $groups[$groupId]['labelTh'] = $item['label'];
            } elseif ($type === 'address_en') {
                $groups[$groupId]['keyEn'] = $item['key'];
                $groups[$groupId]['labelEn'] = $item['label'];
            } else {
                continue;
            }
            $groups[$groupId]['formOrder'] ??= ($item['formOrder'] ?? $position);
            $groups[$groupId]['formWidth'] ??= ($item['formWidth'] ?? 12);
            $groups[$groupId]['showOnVerify'] ??= (bool) ($item['showOnVerify'] ?? false);
        }

        return $groups;
    }

    /**
     * Same shape as addressGroups() above, for the "ประเภทกิจการ" (Business
     * Type) tool — one dropdown at issuance time (see
     * resources/views/labor/_business_type_group.blade.php) fills both the
     * Thai and English positions from App\Models\BusinessType, the same
     * lookup already used on the Employer create/edit forms.
     */
    public function businessTypeGroups(ProWorkerContractTemplate $template): array
    {
        $groups = [];
        foreach (($template->field_mapping ?? []) as $position => $item) {
            $groupId = $item['businessTypeGroup'] ?? null;
            if (!$groupId) {
                continue;
            }
            $type = $item['type'] ?? null;
            if ($type === 'business_type_th') {
                $groups[$groupId]['keyTh'] = $item['key'];
                $groups[$groupId]['labelTh'] = $item['label'];
            } elseif ($type === 'business_type_en') {
                $groups[$groupId]['keyEn'] = $item['key'];
                $groups[$groupId]['labelEn'] = $item['label'];
            } else {
                continue;
            }
            $groups[$groupId]['formOrder'] ??= ($item['formOrder'] ?? $position);
            $groups[$groupId]['formWidth'] ??= ($item['formWidth'] ?? 12);
            $groups[$groupId]['showOnVerify'] ??= (bool) ($item['showOnVerify'] ?? false);
        }

        return $groups;
    }

    /**
     * Same shape again, for the "สัญชาติ" (Nationality) tool — the
     * issuance-side picker (_nationality_group.blade.php) is a hardcoded
     * fixed 4-option select rather than a fetched lookup, since there is no
     * App\Models equivalent of BusinessType for nationality and the set is
     * small and closed.
     */
    public function nationalityGroups(ProWorkerContractTemplate $template): array
    {
        $groups = [];
        foreach (($template->field_mapping ?? []) as $position => $item) {
            $groupId = $item['nationalityGroup'] ?? null;
            if (!$groupId) {
                continue;
            }
            $type = $item['type'] ?? null;
            if ($type === 'nationality_th') {
                $groups[$groupId]['keyTh'] = $item['key'];
                $groups[$groupId]['labelTh'] = $item['label'];
            } elseif ($type === 'nationality_en') {
                $groups[$groupId]['keyEn'] = $item['key'];
                $groups[$groupId]['labelEn'] = $item['label'];
            } else {
                continue;
            }
            $groups[$groupId]['formOrder'] ??= ($item['formOrder'] ?? $position);
            $groups[$groupId]['formWidth'] ??= ($item['formWidth'] ?? 12);
            $groups[$groupId]['showOnVerify'] ??= (bool) ($item['showOnVerify'] ?? false);
        }

        return $groups;
    }

    /**
     * "ค่าบริการ" (Service Fee) tool — one numeric input at issuance time
     * fills two positions (fee_number, sharing one key at both the Thai and
     * English spots on the document) plus two DERIVED text positions
     * (fee_th_text/fee_en_text) computed by
     * LaborContractController::resolveFeeGroupValues(), never typed
     * directly. label comes from whichever fee_number item was placed
     * first for the group (or renamed via the form-order settings screen).
     */
    public function feeGroups(ProWorkerContractTemplate $template): array
    {
        $groups = [];
        foreach (($template->field_mapping ?? []) as $position => $item) {
            $groupId = $item['feeGroup'] ?? null;
            if (!$groupId) {
                continue;
            }
            $type = $item['type'] ?? null;
            if ($type === 'fee_number' && !isset($groups[$groupId]['numeralKey'])) {
                $groups[$groupId]['numeralKey'] = $item['key'];
                $groups[$groupId]['label'] = $item['label'];
            } elseif ($type === 'fee_th_text') {
                $groups[$groupId]['thTextKey'] = $item['key'];
            } elseif ($type === 'fee_en_text') {
                $groups[$groupId]['enTextKey'] = $item['key'];
            } else {
                continue;
            }
            $groups[$groupId]['formOrder'] ??= ($item['formOrder'] ?? $position);
            $groups[$groupId]['formWidth'] ??= ($item['formWidth'] ?? 12);
            $groups[$groupId]['showOnVerify'] ??= (bool) ($item['showOnVerify'] ?? false);
        }

        return $groups;
    }

    /**
     * The single, formOrder-sorted list of everything the issuance/
     * correction form renders — standalone text/worker_count fields plus
     * one entry per address/business-type/nationality/fee group. Replaces
     * _fields.blade.php's previous structure of 4 separate per-type loops
     * (which could never interleave regardless of field_mapping order)
     * with one unified render loop, so the form-order settings screen can
     * freely interleave field types.
     *
     * `formOrder` and `formWidth` are new, optional field_mapping
     * properties, both written by
     * LaborContractTemplateController::updateFormOrder(). A template built
     * before this feature existed has neither set on any item, so every
     * entry here falls back to its original field_mapping array position
     * (formOrder) and full-row width (formWidth = 12, a Bootstrap column
     * span out of 12 — see _fields.blade.php's row/col wrapping), meaning
     * existing templates keep displaying in their current order and
     * one-field-per-row layout until a Super Admin explicitly opens the
     * "จัดลำดับฟอร์ม" screen and adjusts them.
     */
    public function unifiedItems(ProWorkerContractTemplate $template): array
    {
        $mapping = $template->field_mapping ?? [];
        $items = [];

        // $seenKeys mirrors the dedup _fields.blade.php used to rely on
        // directly — the Template Builder lets an admin "copy" a text/
        // worker_count field so the same key appears at multiple positions
        // on the PDF (see builder.blade.php's copyItem()); only one input
        // should ever be rendered for it.
        $seenKeys = [];
        foreach ($mapping as $position => $item) {
            if (!in_array($item['type'] ?? null, ['text', 'worker_count'], true)) {
                continue;
            }
            $key = $item['key'] ?? null;
            if ($key === null || in_array($key, $seenKeys, true)) {
                continue;
            }
            $seenKeys[] = $key;
            $items[] = [
                'kind' => $item['type'],
                'key' => $key,
                'label' => $item['label'] ?? '',
                'formOrder' => $item['formOrder'] ?? $position,
                'formWidth' => $item['formWidth'] ?? 12,
                'showOnVerify' => (bool) ($item['showOnVerify'] ?? false),
            ];
        }

        foreach ($this->addressGroups($template) as $groupId => $group) {
            $items[] = array_merge(['kind' => 'address', 'groupId' => $groupId], $group);
        }

        foreach ($this->businessTypeGroups($template) as $groupId => $group) {
            $items[] = array_merge(['kind' => 'business_type', 'groupId' => $groupId], $group);
        }

        foreach ($this->nationalityGroups($template) as $groupId => $group) {
            $items[] = array_merge(['kind' => 'nationality', 'groupId' => $groupId], $group);
        }

        foreach ($this->feeGroups($template) as $groupId => $group) {
            if (empty($group['numeralKey'])) {
                continue;
            }
            $items[] = array_merge(['kind' => 'fee', 'groupId' => $groupId], $group);
        }

        usort($items, fn ($a, $b) => $a['formOrder'] <=> $b['formOrder']);

        return $items;
    }

    /**
     * The fields a Super Admin has explicitly opted (via the "Show on the
     * public verification page" toggle in the Template Builder — see
     * builder.blade.php) to surface on the public, no-login QR-verify page
     * (LaborContractController::publicVerify()), so whoever scanned the
     * code can compare the value against what's actually printed on the
     * physical document — a bare "this contract number exists" check alone
     * can't catch a forged document that reuses a genuine number.
     *
     * Deliberately reads live from the ISSUED CONTRACT's own $fieldValues
     * (never a separate snapshot column) — the exact same values the PDF
     * was rendered from, so there's no way for this to drift stale or go
     * blank the way employer_name_snapshot did once the employer-picker
     * was removed from the issuance form (see ProWorkerContractService's
     * docblock). `showOnVerify` defaults to false on every field (see the
     * groupX() methods and the loop above), so a template that predates
     * this feature shows nothing extra until a Super Admin opts fields in.
     *
     * Only the 6 kinds unifiedItems() ever produces (text, worker_count,
     * address, business_type, nationality, fee) are eligible — static_text
     * (identical on every contract, proves nothing) and issue_date
     * (computed fresh at render time, never stored in field_values) were
     * already excluded from unifiedItems() itself, so no extra filtering
     * is needed here.
     */
    public function verifyVisibleItems(ProWorkerContractTemplate $template, array $fieldValues): array
    {
        $result = [];

        foreach ($this->unifiedItems($template) as $item) {
            if (empty($item['showOnVerify'])) {
                continue;
            }

            [$label, $value] = match ($item['kind']) {
                'text', 'worker_count' => [$item['label'] ?? '', $fieldValues[$item['key']] ?? ''],
                'address' => [$item['labelTh'] ?? __('Address'), $fieldValues[$item['keyTh'] ?? ''] ?? ''],
                'business_type' => [$item['labelTh'] ?? __('Business Type'), $fieldValues[$item['keyTh'] ?? ''] ?? ''],
                'nationality' => [$item['labelTh'] ?? __('Nationality'), $fieldValues[$item['keyTh'] ?? ''] ?? ''],
                'fee' => [
                    $item['label'] ?? __('Service Fee'),
                    isset($fieldValues[$item['numeralKey'] ?? '']) && $fieldValues[$item['numeralKey']] !== ''
                        ? number_format((float) $fieldValues[$item['numeralKey']], 2)
                        : '',
                ],
                default => [null, null],
            };

            if ($label === null) {
                continue;
            }

            $result[] = ['label' => $label, 'value' => $value];
        }

        return $result;
    }
}

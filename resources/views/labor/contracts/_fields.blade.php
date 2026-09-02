{{--
    Shared field inputs for the issuance form (create.blade.php) and the
    correction form (edit.blade.php) — kept as one partial so the two forms
    can never drift out of sync on which field types render as what.

    Expects: $template, $formItems (from
    App\Services\ProWorkerFormFieldsResolver::unifiedItems() — already
    formOrder-sorted and already covers every field/group type: text/
    worker_count fields, address/business-type/nationality/fee groups, all
    in ONE list, so a Super Admin's field-order settings (see
    contract_templates/form_order.blade.php) can freely interleave them).
    Optional: $values (assoc array of previously submitted values, e.g.
    $contract->field_values on the edit form — defaults to old()-only
    prefill for a fresh issuance).

    No field here is `required` — a contract can be issued completely
    blank and filled in later via the correction flow (see
    LaborContractController::validateFields()).

    Each item's `formWidth` (a Bootstrap column span out of 12, set via
    the "จัดลำดับฟอร์ม" settings screen — see
    LaborContractTemplateController::updateFormOrder()) sizes its column
    inside the shared row below, so short fields (e.g. a Service Fee
    numeral, a Nationality dropdown) can sit side by side on one line
    instead of always taking a full row. Defaults to 12 (full row) so a
    template that never had formWidth set keeps its original one-field-
    per-row layout exactly.
--}}
@php($values = $values ?? [])

<div class="row g-3">
@foreach($formItems as $formItem)
    @php($formWidth = $formItem['formWidth'] ?? 12)
    @switch($formItem['kind'])
        @case('text')
        @case('worker_count')
            <div class="col-md-{{ $formWidth }} mb-3">
                <label class="form-label">{{ $formItem['label'] }}</label>
                @if($formItem['kind'] === 'worker_count')
                    <input type="number" min="0" name="fields[{{ $formItem['key'] }}]" class="form-control"
                           value="{{ old('fields.' . $formItem['key'], $values[$formItem['key']] ?? '') }}">
                @else
                    <input type="text" name="fields[{{ $formItem['key'] }}]" class="form-control"
                           value="{{ old('fields.' . $formItem['key'], $values[$formItem['key']] ?? '') }}">
                @endif
            </div>
            @break

        @case('address')
            <div class="col-md-{{ $formWidth }} mb-3">
                <label class="form-label fw-bold">{{ $formItem['labelTh'] ?? __('Address') }}</label>
                @include('labor._address_group', [
                    'groupId' => $formItem['groupId'],
                    'keyTh' => $formItem['keyTh'] ?? null,
                    'keyEn' => $formItem['keyEn'] ?? null,
                    'labelTh' => $formItem['labelTh'] ?? '',
                    'labelEn' => $formItem['labelEn'] ?? '',
                    'prefill' => empty($values) ? [] : [
                        'province' => $values["{$formItem['groupId']}_province"] ?? '',
                        'district' => $values["{$formItem['groupId']}_district"] ?? '',
                        'subdistrict' => $values["{$formItem['groupId']}_subdistrict"] ?? '',
                        'no' => $values["{$formItem['groupId']}_no"] ?? '',
                        'moo' => $values["{$formItem['groupId']}_moo"] ?? '',
                        'soi' => $values["{$formItem['groupId']}_soi"] ?? '',
                        'road' => $values["{$formItem['groupId']}_road"] ?? '',
                        'soi_en' => $values["{$formItem['groupId']}_soi_en"] ?? '',
                        'road_en' => $values["{$formItem['groupId']}_road_en"] ?? '',
                    ],
                ])
            </div>
            @break

        @case('business_type')
            <div class="col-md-{{ $formWidth }}">
                @include('labor._business_type_group', [
                    'groupId' => $formItem['groupId'],
                    'keyTh' => $formItem['keyTh'] ?? null,
                    'keyEn' => $formItem['keyEn'] ?? null,
                    'labelTh' => $formItem['labelTh'] ?? __('Business Type'),
                    'prefillTh' => $values[$formItem['keyTh'] ?? ''] ?? '',
                    'prefillEn' => $values[$formItem['keyEn'] ?? ''] ?? '',
                ])
            </div>
            @break

        @case('nationality')
            <div class="col-md-{{ $formWidth }}">
                @include('labor._nationality_group', [
                    'groupId' => $formItem['groupId'],
                    'keyTh' => $formItem['keyTh'] ?? null,
                    'keyEn' => $formItem['keyEn'] ?? null,
                    'labelTh' => $formItem['labelTh'] ?? __('Nationality'),
                    'prefillTh' => $values[$formItem['keyTh'] ?? ''] ?? '',
                    'prefillEn' => $values[$formItem['keyEn'] ?? ''] ?? '',
                ])
            </div>
            @break

        @case('fee')
            @if(!empty($formItem['numeralKey']))
                <div class="col-md-{{ $formWidth }} mb-3">
                    <label class="form-label">{{ $formItem['label'] ?? __('Service Fee') }}</label>
                    <input type="number" min="0" step="0.01" name="fields[{{ $formItem['numeralKey'] }}]" class="form-control"
                           value="{{ old('fields.' . $formItem['numeralKey'], $values[$formItem['numeralKey']] ?? '') }}">
                    <div class="form-text">{{ __('Type the amount once — the spelled-out Thai and English text on the document are generated automatically.') }}</div>
                </div>
            @endif
            @break
    @endswitch
@endforeach
</div>

@once
@push('scripts')
<script>
    window.proworkerBusinessTypesUrl = '{{ route('admin.business-types.index') }}';
    window.proworkerSelectLabel = '{{ __('Select...') }}';
</script>
@endpush
@endonce

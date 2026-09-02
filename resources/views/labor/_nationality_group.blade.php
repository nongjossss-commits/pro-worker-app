{{--
    One "สัญชาติ" (Nationality) field-pair from a Pro Worker contract
    template — same idea as _business_type_group.blade.php (a single
    dropdown fills BOTH a Thai and an English hidden input on change), but
    the list here is a small FIXED set of 4 nationalities hardcoded
    directly in this partial rather than fetched from a lookup table/API —
    there is no App\Models equivalent of BusinessType for nationality, and
    the set is small and closed. Thai option text matches the existing
    convention already used throughout the rest of the app (Employee
    create/edit forms) — "เมียนมา" for Myanmar, not the more casual "พม่า".
    No separate JS file is needed since the list never changes at runtime.

    Expects: $groupId (unique per nationality field-pair on this template),
    $keyTh, $keyEn (the field_mapping `key` for each half), $labelTh.
    Optional: $prefillTh, $prefillEn (previously-submitted values, from
    contracts/edit.blade.php).
--}}
@php($prefillTh = $prefillTh ?? '')
@php($prefillEn = $prefillEn ?? '')
<div class="mb-3" data-group="{{ $groupId }}">
    <label class="form-label">{{ $labelTh ?? __('Nationality') }}</label>
    <select class="form-select proworker-nationality-select" id="nationalitySelect_{{ $groupId }}"
            onchange="(function(sel){
                var opt = sel.options[sel.selectedIndex];
                var th = document.getElementById('nationalityTh_{{ $groupId }}');
                var en = document.getElementById('nationalityEn_{{ $groupId }}');
                if (th) th.value = opt.dataset.th || '';
                if (en) en.value = opt.dataset.en || '';
            })(this)">
        <option value="">{{ __('Select...') }}</option>
        <option value="ลาว" data-th="ลาว" data-en="Laos" @selected($prefillTh === 'ลาว')>ลาว / Laos</option>
        <option value="เมียนมา" data-th="เมียนมา" data-en="Myanmar" @selected($prefillTh === 'เมียนมา')>เมียนมา / Myanmar</option>
        <option value="กัมพูชา" data-th="กัมพูชา" data-en="Cambodia" @selected($prefillTh === 'กัมพูชา')>กัมพูชา / Cambodia</option>
        <option value="เวียดนาม" data-th="เวียดนาม" data-en="Vietnam" @selected($prefillTh === 'เวียดนาม')>เวียดนาม / Vietnam</option>
    </select>
    <input type="hidden" name="fields[{{ $keyTh }}]" id="nationalityTh_{{ $groupId }}" value="{{ $prefillTh }}">
    <input type="hidden" name="fields[{{ $keyEn }}]" id="nationalityEn_{{ $groupId }}" value="{{ $prefillEn }}">
</div>

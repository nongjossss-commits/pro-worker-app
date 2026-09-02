{{--
    One "ประเภทกิจการ" (Business Type) field-pair from a Pro Worker contract
    template — a single dropdown, sourced from the same App\Models\BusinessType
    list already used on the Employer create/edit forms (route
    admin.business-types.index, open to any authenticated user), that fills
    BOTH a Thai and an English hidden input on change. Same idea as
    _address_group.blade.php's picker, just a plain select instead of a
    composed address string.

    Expects: $groupId (unique per business-type field-pair on this template),
    $keyTh, $keyEn (the field_mapping `key` for each half), $labelTh.
    Optional: $prefillTh, $prefillEn (previously-submitted values, from
    contracts/edit.blade.php).
--}}
@php($prefillTh = $prefillTh ?? '')
@php($prefillEn = $prefillEn ?? '')
<div class="mb-3" data-group="{{ $groupId }}">
    <label class="form-label">{{ $labelTh ?? __('Business Type') }}</label>
    <select class="form-select proworker-business-type-select" id="bizTypeSelect_{{ $groupId }}">
        <option value="">{{ __('Select...') }}</option>
    </select>
    <input type="hidden" name="fields[{{ $keyTh }}]" id="bizTypeTh_{{ $groupId }}" value="{{ $prefillTh }}">
    <input type="hidden" name="fields[{{ $keyEn }}]" id="bizTypeEn_{{ $groupId }}" value="{{ $prefillEn }}">
</div>

{{--
    One "ที่อยู่" (Address) field-pair from a Pro Worker contract template —
    renders a single province/district/subdistrict cascading picker + no/
    moo/soi/road inputs that composes BOTH a Thai and an English address
    string on change (same format as PdfGeneratorService::formatAddress(),
    mirrored in public/js/proworker-address-picker.js). Values are
    submitted as plain hidden inputs — this is transient form data, not
    persisted as an App\Models\Address polymorphic row (no parent record to
    attach one to here), so it deliberately does NOT go through the
    /addresses CRUD endpoints partials/_address_management.blade.php uses.

    Soi/Road have separate English inputs (house no./moo are pure numbers,
    shared as-is between both languages) — proper-noun street names can't
    be auto-translated reliably, so the template creator/issuer types both.

    The raw part inputs (province/district/.../soi_en/road_en) are also
    submitted under fields[{groupId}_xxx] purely so LaborContractController
    ::edit() can re-hydrate this picker's dropdowns later (see
    proworker-address-picker.js's prefillFromData()) — the PDF renderer
    only ever reads the two composed keys ($keyTh/$keyEn), so these extra
    keys are otherwise inert.

    Expects: $groupId (unique per address field-pair on this template),
    $keyTh, $keyEn (the field_mapping `key` for each half), $labelTh, $labelEn.
    Optional: $prefill (assoc array of part values to restore on load, from
    a previously-issued contract's field_values — used by contracts/edit.blade.php).
--}}
@php($prefill = $prefill ?? [])
<div class="proworker-address-group border rounded p-3 mb-3" data-group="{{ $groupId }}"
     @if($prefill) data-prefill="{{ json_encode($prefill) }}" @endif>
    <div class="row g-2">
        <div class="col-md-4">
            <label class="form-label small">{{ __('Province') }} *</label>
            <select id="addrProvince_{{ $groupId }}" name="fields[{{ $groupId }}_province]" class="form-select form-select-sm pw-addr-province" required>
                <option value="">-- {{ __('Select') }} --</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small">{{ __('District') }} *</label>
            <select id="addrDistrict_{{ $groupId }}" name="fields[{{ $groupId }}_district]" class="form-select form-select-sm pw-addr-district" required disabled>
                <option value="">-- {{ __('Select') }} --</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small">{{ __('Subdistrict') }} *</label>
            <select id="addrSubDistrict_{{ $groupId }}" name="fields[{{ $groupId }}_subdistrict]" class="form-select form-select-sm pw-addr-subdistrict" required disabled>
                <option value="">-- {{ __('Select') }} --</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">{{ __('No.') }}</label>
            <input type="text" id="addrNo_{{ $groupId }}" name="fields[{{ $groupId }}_no]" class="form-control form-control-sm pw-addr-part">
        </div>
        <div class="col-md-2">
            <label class="form-label small">{{ __('Moo') }}</label>
            <input type="text" id="addrMoo_{{ $groupId }}" name="fields[{{ $groupId }}_moo]" class="form-control form-control-sm pw-addr-part">
        </div>
        <div class="col-md-3">
            <label class="form-label small">{{ __('Soi') }}</label>
            <input type="text" id="addrSoi_{{ $groupId }}" name="fields[{{ $groupId }}_soi]" class="form-control form-control-sm pw-addr-part">
        </div>
        <div class="col-md-3">
            <label class="form-label small">{{ __('Road') }}</label>
            <input type="text" id="addrRoad_{{ $groupId }}" name="fields[{{ $groupId }}_road]" class="form-control form-control-sm pw-addr-part">
        </div>
        <div class="col-md-2">
            <label class="form-label small">{{ __('Zip Code') }}</label>
            <input type="text" id="addrZipCode_{{ $groupId }}" class="form-control form-control-sm" readonly>
        </div>
        <div class="col-md-3">
            <label class="form-label small">{{ __('Soi (English)') }}</label>
            <input type="text" id="addrSoiEn_{{ $groupId }}" name="fields[{{ $groupId }}_soi_en]" class="form-control form-control-sm pw-addr-part">
        </div>
        <div class="col-md-3">
            <label class="form-label small">{{ __('Road (English)') }}</label>
            <input type="text" id="addrRoadEn_{{ $groupId }}" name="fields[{{ $groupId }}_road_en]" class="form-control form-control-sm pw-addr-part">
        </div>
    </div>
    <div class="form-text mt-2">
        {{ __('English address (auto-filled)') }}: <span id="addrEnPreview_{{ $groupId }}" class="text-muted"></span>
    </div>
    <input type="hidden" name="fields[{{ $keyTh }}]" id="composed_th_{{ $groupId }}">
    <input type="hidden" name="fields[{{ $keyEn }}]" id="composed_en_{{ $groupId }}">
</div>

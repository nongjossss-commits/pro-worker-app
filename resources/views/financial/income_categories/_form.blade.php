@php $c = $category ?? null; @endphp
<div class="mb-3">
    <label class="form-label">{{ __('Code') }}</label>
    <input type="text" name="code" class="form-control" value="{{ old('code', $c->code ?? '') }}" maxlength="20" placeholder="{{ __('e.g., INC-001') }}">
</div>
<div class="mb-3">
    <label class="form-label">{{ __('Name') }} *</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $c->name ?? '') }}" required maxlength="255">
</div>
<div class="mb-3">
    <label class="form-label">{{ __('Description') }}</label>
    <textarea name="description" class="form-control" rows="2">{{ old('description', $c->description ?? '') }}</textarea>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __('Default VAT Treatment') }} *</label>
        <select name="default_vat_treatment" class="form-select" required>
            <option value="none" {{ ($c->default_vat_treatment ?? 'taxable') === 'none' ? 'selected' : '' }}>{{ __('None (Off-book)') }}</option>
            <option value="taxable" {{ ($c->default_vat_treatment ?? 'taxable') === 'taxable' ? 'selected' : '' }}>{{ __('Taxable (VAT 7%)') }}</option>
            <option value="exempt" {{ ($c->default_vat_treatment ?? '') === 'exempt' ? 'selected' : '' }}>{{ __('Exempt (ยกเว้น VAT)') }}</option>
            <option value="zero_rate" {{ ($c->default_vat_treatment ?? '') === 'zero_rate' ? 'selected' : '' }}>{{ __('Zero-rate (อัตรา 0%)') }}</option>
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ __('WHT Type') }}</label>
        <select name="default_wht_type" class="form-select">
            <option value="none" {{ ($c->default_wht_type ?? 'none') === 'none' ? 'selected' : '' }}>{{ __('None') }}</option>
            <option value="pnd3" {{ ($c->default_wht_type ?? '') === 'pnd3' ? 'selected' : '' }}>{{ __('ภ.ง.ด.3 (บุคคล)') }}</option>
            <option value="pnd53" {{ ($c->default_wht_type ?? '') === 'pnd53' ? 'selected' : '' }}>{{ __('ภ.ง.ด.53 (นิติบุคคล)') }}</option>
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ __('WHT Rate (%)') }}</label>
        <input type="number" step="0.01" min="0" max="100" name="default_wht_rate" class="form-control" value="{{ old('default_wht_rate', $c->default_wht_rate ?? 0) }}">
    </div>
</div>

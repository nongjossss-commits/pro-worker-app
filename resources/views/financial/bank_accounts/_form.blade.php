@php $a = $account ?? null; @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('Account Type') }} *</label>
        <select name="account_type" class="form-select" required>
            <option value="company" {{ ($a->account_type ?? 'company') === 'company' ? 'selected' : '' }}>🏢 {{ __('Company (with VAT/WHT)') }}</option>
            <option value="personal" {{ ($a->account_type ?? '') === 'personal' ? 'selected' : '' }}>👤 {{ __('Personal (off-book)') }}</option>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Linked Profile (Biller)') }}</label>
        <select name="financial_profile_id" class="form-select">
            <option value="">— {{ __('None') }} —</option>
            @foreach($profiles as $p)
                <option value="{{ $p->id }}" {{ (string) ($a->financial_profile_id ?? '') === (string) $p->id ? 'selected' : '' }}>
                    {{ $p->name }} @if($p->tax_id)({{ $p->tax_id }})@endif
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Bank Name') }} *</label>
        <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $a->bank_name ?? '') }}" required placeholder="e.g., KBank, SCB, Cash">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Account Name') }}</label>
        <input type="text" name="account_name" class="form-control" value="{{ old('account_name', $a->account_name ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Account Number') }}</label>
        <input type="text" name="account_number" class="form-control" value="{{ old('account_number', $a->account_number ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Branch') }}</label>
        <input type="text" name="branch" class="form-control" value="{{ old('branch', $a->branch ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Tax ID (override profile)') }}</label>
        <input type="text" name="tax_id" class="form-control" value="{{ old('tax_id', $a->tax_id ?? '') }}" maxlength="15">
    </div>
    <div class="col-12">
        <label class="form-label">{{ __('Notes') }}</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $a->notes ?? '') }}</textarea>
    </div>
</div>

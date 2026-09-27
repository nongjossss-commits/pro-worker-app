@extends('layouts.app')

@section('title', __('Share settings'))

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h1 class="h3 mb-1"><i class="bi bi-send me-2"></i>{{ __('Share settings') }}</h1>
        <p class="text-muted mb-0">{{ __('Choose which data leaves the program when staff drag a card into LINE or another app, copy it as text, or copy/share it as a card image.') }}</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="alert alert-warning d-flex gap-2">
        <i class="bi bi-shield-exclamation fs-5"></i>
        <div>
            {{ __('Data sent to other apps is outside the program\'s control (personal data — PDPA). Share only what the team really needs.') }}
            {{ __('The name is always included. Every share is recorded in the Activity Log.') }}
        </div>
    </div>

    <form action="{{ route('admin.settings.share.store') }}" method="POST">
        @csrf
        <div class="row g-4">
            @foreach(['employee' => [__('Employee card'), $employeeFields, 'bi-person-badge'], 'employer' => [__('Employer card'), $employerFields, 'bi-building']] as $type => [$heading, $fields, $icon])
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center">
                            <span class="fw-bold"><i class="bi {{ $icon }} me-2"></i>{{ $heading }}</span>
                            <span>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-check-all="{{ $type }}">{{ __('Select all') }}</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-uncheck-all="{{ $type }}">{{ __('Clear') }}</button>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" checked disabled id="share_{{ $type }}_name">
                                <label class="form-check-label" for="share_{{ $type }}_name">
                                    {{ $type === 'employee' ? __('Name (EN)') : __('Name (TH)') }}
                                    <small class="text-muted">— {{ __('always included') }}</small>
                                </label>
                            </div>
                            <div class="row">
                                @foreach($fields as $key => [$label, $emoji])
                                    <div class="col-sm-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="{{ $type }}[]" value="{{ $key }}"
                                                   id="share_{{ $type }}_{{ $key }}" data-share-group="{{ $type }}"
                                                   {{ in_array($key, $allowed[$type], true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="share_{{ $type }}_{{ $key }}">
                                                @if($emoji){{ $emoji }} @else<i class="bi bi-image"></i> @endif{{ __($label) }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary px-5"><i class="bi bi-save me-2"></i>{{ __('Save') }}</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('click', function (e) {
        const on = e.target.closest('[data-check-all]');
        const off = e.target.closest('[data-uncheck-all]');
        const group = (on || off) && (on ? on.dataset.checkAll : off.dataset.uncheckAll);
        if (!group) return;
        document.querySelectorAll('[data-share-group="' + group + '"]').forEach(cb => { cb.checked = !!on; });
    });
</script>
@endpush
@endsection

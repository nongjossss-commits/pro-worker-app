@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 mb-0 text-gray-800">Employee Data Completeness Settings</h1>
            <p class="text-muted">Select fields that are mandatory. Employees missing these fields will appear in the "Incomplete Data" list.</p>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Mandatory Fields Checklist</h6>
            <button type="button" class="btn btn-sm btn-secondary" onclick="document.querySelectorAll('input[type=checkbox]').forEach(c => c.checked = false)">Uncheck All</button>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.settings.completeness.store') }}" method="POST">
                @csrf

                <div class="row">
                    @foreach($fieldGroups as $groupName => $fields)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border-left-primary">
                                <div class="card-header bg-light font-weight-bold">
                                    {{ $groupName }}
                                </div>
                                <div class="card-body">
                                    @foreach($fields as $fieldKey => $fieldLabel)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox"
                                                   name="fields[]"
                                                   value="{{ $fieldKey }}"
                                                   id="field_{{ $fieldKey }}"
                                                   {{ in_array($fieldKey, $selectedFields) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="field_{{ $fieldKey }}">
                                                {{ $fieldLabel }}
                                                <small class="text-muted d-block" style="font-size: 0.75rem;">({{ $fieldKey }})</small>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="row mt-4">
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary btn-lg px-5">
                            <i class="bi bi-save me-2"></i> Save Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

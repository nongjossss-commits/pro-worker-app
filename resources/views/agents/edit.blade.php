@extends('layouts.app')

@section('title', __('Edit Agent'))

@section('content')
<div class="content-section">
    <h2 class="mb-4">{{ __('Edit Agent') }}</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('agents.update', $agent) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="row mb-3">
            <div class="col-md-12">
                <label for="agentNameEn" class="form-label">{{ __('Agent Name') }}</label>
                <input type="text" class="form-control" id="agentNameEn" name="agentNameEn" value="{{ old('agentNameEn', $agent->agentNameEn) }}" required>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="agentLicense" class="form-label">{{ __('License Number') }}</label>
                <input type="text" class="form-control" id="agentLicense" name="agentLicense" value="{{ old('agentLicense', $agent->agentLicense) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="agentPhone" class="form-label">{{ __('Phone Number') }}</label>
                <input type="tel" class="form-control" id="agentPhone" name="agentPhone" value="{{ old('agentPhone', $agent->agentPhone) }}">
            </div>
            <div class="col-md-6">
                <label for="agentEmail" class="form-label">{{ __('Email') }}</label>
                <input type="email" class="form-control" id="agentEmail" name="agentEmail" value="{{ old('agentEmail', $agent->agentEmail) }}">
            </div>
        </div>
        <div class="mb-3">
            <label for="agentAddress" class="form-label">{{ __('Address') }}</label>
            <textarea class="form-control" id="agentAddress" name="agentAddress" rows="3">{{ old('agentAddress', $agent->agentAddress) }}</textarea>
        </div>

        <hr>
        <h5>{{ __('Other Documents') }}</h5>
        <div class="row mb-3">
            @for($i = 1; $i <= 3; $i++)
            @php $field = "agent_doc_other_$i"; $descField = "agent_doc_other_{$i}_desc"; @endphp
            <div class="col-md-4">
                <label for="{{ $field }}" class="form-label">{{ $i }}. {{ __('Other Document') }} {{ $i }} <span class="text-muted small">(รองรับไฟล์สูงสุด 30 MB)</span></label>
                <div class="input-group input-group-sm mb-2">
                    <input type="file" class="form-control form-control-sm @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">
                    <button type="button" class="btn btn-outline-secondary" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: '{{ $field }}' } }))">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>
                @if($agent->$field)
                <div class="mb-2">
                    <a href="{{ Storage::url($agent->$field) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="{{ __('View current file') }}">
                        <i class="bi bi-file-earmark"></i> {{ __('View current file') }}
                    </a>
                </div>
                @endif
                <input type="text" class="form-control form-control-sm" id="{{ $descField }}" name="{{ $descField }}" value="{{ old($descField, $agent->$descField) }}" placeholder="{{ __('Specify description...') }}">
                @error($field)
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            @endfor
        </div>

        <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
        <a href="{{ route('agents.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
    </form>
</div>
@endsection

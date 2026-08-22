@extends('layouts.app')

@section('title', __('Add Agent'))

@section('content')
<div class="content-section">
    <h2 class="mb-4">{{ __('Add Agent') }}</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('agents.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row mb-3">
            <div class="col-md-12">
                <label for="agentNameEn" class="form-label">{{ __('Agent Name') }}</label>
                <input type="text" class="form-control" id="agentNameEn" name="agentNameEn" value="{{ old('agentNameEn') }}" required>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="agentLicense" class="form-label">{{ __('License Number') }}</label>
                <input type="text" class="form-control" id="agentLicense" name="agentLicense" value="{{ old('agentLicense') }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="agentPhone" class="form-label">{{ __('Phone Number') }}</label>
                <input type="tel" class="form-control" id="agentPhone" name="agentPhone" value="{{ old('agentPhone') }}">
            </div>
            <div class="col-md-6">
                <label for="agentEmail" class="form-label">{{ __('Email') }}</label>
                <input type="email" class="form-control" id="agentEmail" name="agentEmail" value="{{ old('agentEmail') }}">
            </div>
        </div>
        <div class="mb-3">
            <label for="agentAddress" class="form-label">{{ __('Address') }}</label>
            <textarea class="form-control" id="agentAddress" name="agentAddress" rows="3">{{ old('agentAddress') }}</textarea>
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
                <input type="text" class="form-control form-control-sm" id="{{ $descField }}" name="{{ $descField }}" value="{{ old($descField) }}" placeholder="{{ __('Specify description...') }}">
                @error($field)
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            @endfor
        </div>

        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
        <a href="{{ route('agents.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
    </form>
</div>
@endsection

@extends('labor.layout')

@section('title', __('Edit') . ' ' . __('Contract No.') . ' ' . $contract->contract_no)

@section('content')
@include('labor.contracts._pwc_styles')
<div class="container-fluid pwc">
    <div class="pwc-hero">
        <div class="pwc-hero-main">
            <div class="pwc-hero-icon"><i class="bi bi-pencil-square"></i></div>
            <div>
                <h4 class="pwc-hero-title">{{ __('Edit') }} — {{ __('Contract No.') }} <span class="pwc-no ms-1 align-middle">{{ $contract->contract_no }}</span></h4>
                <p class="pwc-hero-sub">{{ __('Issued at') }} {{ $contract->issued_at->format('d/m/Y H:i') }} — {{ $contract->team->name ?? '-' }}. {{ __('The contract number and issue date never change here — only the filled-in data can be corrected.') }}</p>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card pwc-panel pwc-form">
        <div class="card-header">
            <span class="pwc-chip-icon"><i class="bi bi-file-earmark-text"></i></span>
            <h6 class="mb-0 fw-bold">{{ $template->name }}</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('labor.contracts.update', $contract) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="template_id" value="{{ $template->id }}">

                @include('labor.contracts._fields', ['template' => $template, 'formItems' => $formItems, 'values' => $contract->field_values ?? []])

                <div class="pwc-actionbar">
                <div class="form-text"><i class="bi bi-info-circle"></i>{{ __('Please double-check the document before confirming.') }}</div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" formaction="{{ route('labor.contracts.preview') }}" formtarget="_blank" class="btn btn-outline-secondary">
                        <i class="bi bi-eye me-1"></i>{{ __('Preview Document') }}
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>{{ __('Save Changes') }}
                    </button>
                </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/proworker-address-picker.js') }}?v={{ @filemtime(public_path('js/proworker-address-picker.js')) }}"></script>
<script src="{{ asset('js/proworker-business-type-picker.js') }}?v={{ @filemtime(public_path('js/proworker-business-type-picker.js')) }}"></script>
@endpush

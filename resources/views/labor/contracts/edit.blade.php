@extends('labor.layout')

@section('title', __('Edit') . ' ' . __('Contract No.') . ' ' . $contract->contract_no)

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <h4 class="fw-bold mb-0">{{ __('Edit') }} — {{ __('Contract No.') }} <span class="font-monospace">{{ $contract->contract_no }}</span></h4>
        <p class="text-muted small mb-0">{{ __('Issued at') }} {{ $contract->issued_at->format('d/m/Y H:i') }} — {{ $contract->team->name ?? '-' }}. {{ __('The contract number and issue date never change here — only the filled-in data can be corrected.') }}</p>
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

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold">{{ $template->name }}</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('labor.contracts.update', $contract) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="template_id" value="{{ $template->id }}">

                @include('labor.contracts._fields', ['template' => $template, 'addressGroups' => $addressGroups, 'values' => $contract->field_values ?? []])

                <div class="form-text mb-3">{{ __('Please double-check the document before confirming.') }}</div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="submit" formaction="{{ route('labor.contracts.preview') }}" formtarget="_blank" class="btn btn-outline-secondary">
                        <i class="bi bi-eye me-1"></i>{{ __('Preview Document') }}
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>{{ __('Save Changes') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/proworker-address-picker.js') }}?v={{ @filemtime(public_path('js/proworker-address-picker.js')) }}"></script>
@endpush

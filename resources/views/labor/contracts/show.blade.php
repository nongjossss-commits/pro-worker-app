@extends('labor.layout')

@section('title', __('Contract Issued'))

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body p-5">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                    <h4 class="fw-bold mt-3">{{ __('Contract Issued Successfully') }}</h4>
                    <p class="text-muted mb-1">{{ __('Contract No.') }}</p>
                    <p class="fs-3 fw-bold font-monospace">{{ $contract->contract_no }}</p>
                    <p class="text-muted small">{{ __('Issued at') }} {{ $contract->issued_at->format('d/m/Y H:i') }} — {{ $contract->team->name ?? '-' }}</p>

                    <div class="mt-3 text-start">
                        <label class="form-label small text-muted mb-1">{{ __('Verify Link (also encoded in the QR code on the document)') }}</label>
                        <input type="text" class="form-control form-control-sm font-monospace" readonly
                               value="{{ route('labor.contracts.public-verify', $contract->contract_no) }}"
                               onclick="this.select();">
                    </div>

                    <div class="d-flex justify-content-center gap-2 mt-4">
                        <a href="{{ route('labor.contracts.view', $contract) }}" target="_blank" class="btn btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>{{ __('Preview') }}
                        </a>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#downloadChoiceModal" data-download-url="{{ route('labor.contracts.download', $contract) }}">
                            <i class="bi bi-download me-1"></i>{{ __('Download PDF') }}
                        </button>
                        <a href="{{ route('labor.contracts.edit', $contract) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-pencil me-1"></i>{{ __('Edit') }}
                        </a>
                        <a href="{{ route('labor.contracts.create') }}" class="btn btn-outline-secondary">{{ __('Issue Another') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('labor.contracts._download_choice_modal')
@endsection

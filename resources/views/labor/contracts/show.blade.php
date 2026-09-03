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

                    @if($contract->signed_copy_path)
                        <span class="badge bg-success"><i class="bi bi-patch-check-fill me-1"></i>{{ __('Complete Contract') }} ({{ __('signed copy attached') }})</span>
                    @else
                        <span class="badge bg-secondary"><i class="bi bi-hourglass-split me-1"></i>{{ __('Awaiting signed copy') }}</span>
                    @endif

                    <div class="mt-3 text-start">
                        <label class="form-label small text-muted mb-1">{{ __('Verify Link (also encoded in the QR code on the document)') }}</label>
                        <input type="text" class="form-control form-control-sm font-monospace" readonly
                               value="{{ route('labor.contracts.public-verify', $contract->contract_no) }}"
                               onclick="this.select();">
                    </div>

                    <div class="d-flex justify-content-center flex-wrap gap-2 mt-4">
                        <a href="{{ route('labor.contracts.view', $contract) }}" target="_blank" class="btn btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>{{ __('Preview') }}
                        </a>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#downloadChoiceModal" data-download-url="{{ route('labor.contracts.download', $contract) }}">
                            <i class="bi bi-download me-1"></i>{{ __('Download PDF') }}
                        </button>
                        {{-- Editing the contract's DATA is issuer-only (see
                             LaborContractController::assertCanEditContract()) —
                             hidden entirely for anyone else so they never click
                             through to a 403; attaching the signed copy below is
                             a separate, looser action anyone who can view this
                             page may do. --}}
                        @if(auth()->id() === $contract->issued_by)
                        <a href="{{ route('labor.contracts.edit', $contract) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-pencil me-1"></i>{{ __('Edit') }}
                        </a>
                        @endif
                        <a href="{{ route('labor.contracts.history', $contract) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-clock-history me-1"></i>{{ __('Edit History') }}
                        </a>
                        <a href="{{ route('labor.contracts.create') }}" class="btn btn-outline-secondary">{{ __('Issue Another') }}</a>
                    </div>
                </div>

                {{-- Attaching the physical copy the employer signed and
                     returned — open to anyone who can view this contract, not
                     just the issuer (see routes/labor.php's contracts.signed-
                     copy.update). Reuses the exact camera-scan/upload/crop/
                     rotate component already used across the main app. --}}
                <div class="card-body border-top text-start">
                    <h6 class="fw-bold mb-2">{{ __('Signed Copy Attachment') }}</h6>
                    <p class="text-muted small mb-3">{{ __('Attach the fully signed contract the employer returned — a photo, scan, or PDF.') }}</p>
                    <form method="POST" action="{{ route('labor.contracts.signed-copy.update', $contract) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <x-file-input-group
                            id="signed_copy"
                            name="signed_copy"
                            :value="$contract->signed_copy_path"
                        />
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-upload me-1"></i>{{ $contract->signed_copy_path ? __('Replace Signed Copy') : __('Attach Signed Copy') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@include('labor.contracts._download_choice_modal')
@endsection

@extends('labor.layout')

@section('title', __('Contract Issued'))

@section('content')
@include('labor.contracts._pwc_styles')
<div class="container-fluid pwc">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-7">
            <div class="card pwc-done text-center">
                <div class="pwc-done-top">
                    <div class="pwc-done-check"><i class="bi bi-check-lg"></i></div>
                    <h4 class="fw-bold mt-3 mb-2">{{ __('Contract Issued Successfully') }}</h4>
                    <p class="text-muted mb-0 small text-uppercase fw-semibold">{{ __('Contract No.') }}</p>
                    <div class="pwc-done-no">{{ $contract->contract_no }}</div>
                    <p class="text-muted small mb-3"><i class="bi bi-calendar3 me-1"></i>{{ __('Issued at') }} {{ $contract->issued_at->format('d/m/Y H:i') }} — <i class="bi bi-people-fill me-1"></i>{{ $contract->team->name ?? '-' }}</p>

                    @if($contract->signed_copy_path)
                        <span class="pwc-status is-done">{{ __('Complete Contract') }} ({{ __('signed copy attached') }})</span>
                    @else
                        <span class="pwc-status is-wait">{{ __('Awaiting signed copy') }}</span>
                    @endif
                </div>
                <div class="card-body px-4 pb-4 pt-3">
                    <div class="text-start">
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
                            variant="card"
                            id="signed_copy"
                            name="signed_copy"
                            :label="__('Signed Copy')"
                            :value="$contract->signed_copy_path"
                            :pendingHint="__('press Upload to attach it')"
                            :clearInPending="false"
                        >
                            <div class="att-when-pending gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" data-att-clear>
                                    <i class="bi bi-x-lg me-1"></i>{{ __('Cancel') }}
                                </button>
                                <button type="submit" class="btn btn-success btn-sm flex-fill d-inline-flex justify-content-center align-items-center gap-1">
                                    <i class="bi bi-upload"></i>{{ $contract->signed_copy_path ? __('Replace Signed Copy') : __('Attach Signed Copy') }}
                                </button>
                            </div>
                        </x-file-input-group>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@include('labor.contracts._download_choice_modal')
@endsection

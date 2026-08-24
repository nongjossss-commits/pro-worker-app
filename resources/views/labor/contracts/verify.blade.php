@extends('labor.layout')

@section('title', __('Verify Contract'))

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">{{ __('Verify a Contract') }} (เช็คสัญญาของจริง)</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('labor.contracts.verify') }}" class="d-flex gap-2 mb-3">
                        @csrf
                        <input type="text" name="contract_no" class="form-control" placeholder="{{ __('e.g. PWC-2026-0001') }}" value="{{ $searched ?? '' }}" required>
                        <button type="submit" class="btn btn-primary text-nowrap">{{ __('Check') }}</button>
                    </form>

                    @isset($searched)
                        @if($contract)
                            <div class="alert alert-success">
                                <i class="bi bi-patch-check-fill me-1"></i>
                                <strong>{{ __('This is a genuine Pro Worker contract.') }}</strong>
                                <hr>
                                <div class="small">
                                    <div>{{ __('Contract No.') }}: <span class="font-monospace">{{ $contract->contract_no }}</span></div>
                                    <div>{{ __('Issued At') }}: {{ $contract->issued_at->format('d/m/Y H:i') }}</div>
                                    <div>{{ __('Issued by Team') }}: {{ $contract->team->name ?? '-' }}</div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-danger">
                                <i class="bi bi-x-octagon-fill me-1"></i>
                                <strong>{{ __('No contract found with this number — this may not be a genuine Pro Worker contract.') }}</strong>
                            </div>
                        @endif
                    @endisset
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

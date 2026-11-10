@extends('labor.layout')

@section('title', __('Verify Contract'))

@section('content')
@include('labor.contracts._pwc_styles')
<div class="container-fluid pwc">
    <div class="pwc-verify">
        <div class="pwc-hero">
            <div class="pwc-hero-main">
                <div class="pwc-hero-icon"><i class="bi bi-shield-check"></i></div>
                <div>
                    <h4 class="pwc-hero-title">{{ __('Verify a Contract') }}</h4>
                    @if(app()->getLocale() !== 'th')<p class="pwc-hero-sub">เช็คสัญญาของจริง</p>@endif
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('labor.contracts.verify') }}" class="pwc-verify-search">
            @csrf
            <span class="d-grid place-items-center ps-2 text-body-secondary align-self-center"><i class="bi bi-upc-scan fs-5"></i></span>
            <input type="text" name="contract_no" class="form-control" placeholder="{{ __('e.g. PWC-2026-0001') }}" value="{{ $searched ?? '' }}" required>
            <button type="submit" class="btn btn-primary text-nowrap"><i class="bi bi-search me-1"></i>{{ __('Check') }}</button>
        </form>

        @isset($searched)
            @if($contract)
                <div class="pwc-result is-ok">
                    <div class="pwc-result-icon"><i class="bi bi-patch-check-fill"></i></div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="pwc-result-title">{{ __('This is a genuine Pro Worker contract.') }}</div>
                        <div class="pwc-facts">
                            <div class="pwc-fact"><div class="pwc-fact-label">{{ __('Contract No.') }}</div><div class="pwc-fact-value font-monospace">{{ $contract->contract_no }}</div></div>
                            <div class="pwc-fact"><div class="pwc-fact-label">{{ __('Issued At') }}</div><div class="pwc-fact-value">{{ $contract->issued_at->format('d/m/Y H:i') }}</div></div>
                            <div class="pwc-fact"><div class="pwc-fact-label">{{ __('Issued by Team') }}</div><div class="pwc-fact-value">{{ $contract->team->name ?? '-' }}</div></div>
                        </div>
                    </div>
                </div>
            @else
                <div class="pwc-result is-bad">
                    <div class="pwc-result-icon"><i class="bi bi-x-lg"></i></div>
                    <div class="pwc-result-title mb-0 align-self-center">{{ __('No contract found with this number — this may not be a genuine Pro Worker contract.') }}</div>
                </div>
            @endif
        @endisset
    </div>
</div>
@endsection

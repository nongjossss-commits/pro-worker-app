{{--
    "ดูประวัติการแก้ไข" — every readable change ever made to this contract,
    newest first. Each entry's `changes` list is already plain, immediately
    understandable Thai sentences — see
    LaborContractController::describeContractChanges() for how field_values
    (one JSON blob holding every issuance-form answer) gets translated into
    per-field sentences instead of a raw JSON dump.

    Expects: $contract, $logs (a Collection of ['log' => ActivityLog,
    'changes' => string[]] from LaborContractController::history()).
--}}
@extends('labor.layout')

@section('title', __('Edit History') . ' — ' . $contract->contract_no)

@section('content')
@include('labor.contracts._pwc_styles')
<div class="container-fluid pwc">
    <div class="pwc-hero">
        <div class="pwc-hero-main">
            <div class="pwc-hero-icon"><i class="bi bi-clock-history"></i></div>
            <div>
                <h4 class="pwc-hero-title">{{ __('Edit History') }}</h4>
                <p class="pwc-hero-sub">
                    @if(app()->getLocale() !== 'th')ประวัติการแก้ไขสัญญา · @endif{{ __('Contract No.') }} <span class="pwc-no">{{ $contract->contract_no }}</span>
                    — {{ __('Total edits') }}: <strong>{{ $editCount }}</strong>
                </p>
            </div>
        </div>
        <div class="pwc-hero-actions">
            <a href="{{ route('labor.contracts.show', $contract) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back') }}
            </a>
        </div>
    </div>

    <div class="card pwc-panel">
        <div class="card-body">
            @if($logs->isEmpty())
                <p class="text-muted mb-0 text-center py-4"><i class="bi bi-inbox d-block fs-1 mb-2 opacity-50"></i>{{ __('No history recorded yet.') }}</p>
            @else
                <ol class="pwc-timeline">
                    @foreach($logs as $entry)
                        @php($log = $entry['log'])
                        <li class="pwc-tl-item {{ $log->action === 'create' ? 'is-create' : '' }}">
                            <span class="pwc-tl-dot"><i class="bi {{ $log->action === 'create' ? 'bi-file-earmark-plus' : 'bi-pencil' }}"></i></span>
                            <div class="pwc-tl-card">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-1">
                                <div>
                                    <span class="pwc-status {{ $log->action === 'create' ? 'is-done' : 'is-wait' }}" style="{{ $log->action === 'create' ? '' : 'color: var(--bs-primary); background: var(--pwc-tint); border-color: var(--pwc-line);' }}">
                                        {{ $log->action === 'create' ? __('Created') : __('Edited') }}
                                    </span>
                                    <span class="fw-bold ms-1">{{ $log->user->name ?? __('Unknown User') }}</span>
                                </div>
                                <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i>{{ $log->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <ul class="mb-0 small">
                                @foreach($entry['changes'] as $change)
                                    {{-- Plain text, not HTML — describeContractChanges()
                                         interpolates raw field values (e.g. an employer
                                         name someone typed) directly into these
                                         sentences, so this MUST stay escaped. --}}
                                    <li>{{ $change }}</li>
                                @endforeach
                            </ul>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </div>
</div>
@endsection

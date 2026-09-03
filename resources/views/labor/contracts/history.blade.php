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
<div class="container-fluid">
    <div class="mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0">{{ __('Edit History') }} (ประวัติการแก้ไขสัญญา)</h4>
            <p class="text-muted small mb-0">
                {{ __('Contract No.') }} <span class="font-monospace fw-bold">{{ $contract->contract_no }}</span>
                — {{ __('Total edits') }}: <strong>{{ $editCount }}</strong>
            </p>
        </div>
        <a href="{{ route('labor.contracts.show', $contract) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>{{ __('Back') }}
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            @if($logs->isEmpty())
                <p class="text-muted mb-0">{{ __('No history recorded yet.') }}</p>
            @else
                <div class="list-group list-group-flush">
                    @foreach($logs as $entry)
                        @php($log = $entry['log'])
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-1 mb-2">
                                <div>
                                    <span class="badge {{ $log->action === 'create' ? 'bg-success' : 'bg-primary' }}">
                                        {{ $log->action === 'create' ? __('Created') : __('Edited') }}
                                    </span>
                                    <span class="fw-bold ms-1">{{ $log->user->name ?? __('Unknown User') }}</span>
                                </div>
                                <span class="text-muted small">{{ $log->created_at->format('d/m/Y H:i') }}</span>
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
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

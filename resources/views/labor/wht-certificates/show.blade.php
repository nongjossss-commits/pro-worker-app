@extends('labor.layout')

@section('title', $cert->cert_no . ' - Pro Walker Labour')

@section('content')
<div class="mb-3">
    <a href="{{ route('labor.wht-certificates.index') }}" class="text-decoration-none small">&larr; {{ __('WHT Certificates') }}</a>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0">{{ $cert->cert_no }}</h4>
        <span class="text-muted">{{ strtoupper($cert->wht_type) }} — {{ $cert->type === 'received' ? __('Received') : __('Issued') }}</span>
    </div>
    <div>
        @if($cert->status === 'submitted')
            <span class="badge bg-success fs-6">{{ __('Submitted') }}</span>
        @elseif($cert->status === 'issued')
            <span class="badge bg-primary fs-6">{{ __('Issued') }}</span>
        @else
            <span class="badge bg-secondary fs-6">{{ __('Draft') }}</span>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">{{ __('Payer') }}</dt>
                    <dd class="col-sm-8">{{ $cert->payer_name }} @if($cert->payer_tax_id) ({{ $cert->payer_tax_id }}) @endif</dd>

                    <dt class="col-sm-4">{{ __('Payee') }}</dt>
                    <dd class="col-sm-8">{{ $cert->payee_name }} @if($cert->payee_tax_id) ({{ $cert->payee_tax_id }}) @endif</dd>

                    <dt class="col-sm-4">{{ __('Bill Reference') }}</dt>
                    <dd class="col-sm-8">
                        @if($cert->bill)
                            <a href="{{ route('labor.bills.show', $cert->bill) }}">{{ $cert->bill->bill_no }}</a>
                        @else
                            -
                        @endif
                    </dd>

                    <dt class="col-sm-4">{{ __('Payment Date') }}</dt>
                    <dd class="col-sm-8">{{ $cert->paid_at->format('d/m/Y') }}</dd>

                    <dt class="col-sm-4">{{ __('Amount Paid') }}</dt>
                    <dd class="col-sm-8">{{ number_format($cert->amount_paid, 2) }}</dd>

                    <dt class="col-sm-4">{{ __('WHT Rate') }}</dt>
                    <dd class="col-sm-8">{{ rtrim(rtrim((string) $cert->wht_rate, '0'), '.') }}%</dd>

                    <dt class="col-sm-4 fw-bold">{{ __('WHT Amount') }}</dt>
                    <dd class="col-sm-8 fw-bold">{{ number_format($cert->wht_amount, 2) }}</dd>

                    @if($cert->notes)
                    <dt class="col-sm-4">{{ __('Notes') }}</dt>
                    <dd class="col-sm-8">{{ $cert->notes }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body d-grid gap-2">
                <a href="{{ route('labor.wht-certificates.pdf', $cert) }}" target="_blank" class="btn btn-outline-secondary">
                    <i class="bi bi-file-pdf me-1"></i>{{ __('View PDF') }}
                </a>

                @if($cert->status === 'draft')
                <form method="POST" action="{{ route('labor.wht-certificates.update', $cert) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="action_issue" value="1">
                    <button type="submit" class="btn btn-success w-100">{{ __('Issue Certificate') }}</button>
                </form>
                <form method="POST" action="{{ route('labor.wht-certificates.destroy', $cert) }}" onsubmit="return confirm('{{ __('Delete this draft?') }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger w-100">{{ __('Delete Draft') }}</button>
                </form>
                @endif

                @if($cert->status === 'issued')
                <form method="POST" action="{{ route('labor.wht-certificates.update', $cert) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="action_submitted" value="1">
                    <button type="submit" class="btn btn-outline-success w-100">{{ __('Mark as Submitted to Revenue Dept.') }}</button>
                </form>
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body small text-muted">
                <div>{{ __('Created by') }}: {{ $cert->creator->name ?? '-' }}</div>
                <div>{{ __('Created at') }}: {{ $cert->created_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>
</div>
@endsection

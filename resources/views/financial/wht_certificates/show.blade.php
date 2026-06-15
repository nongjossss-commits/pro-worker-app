@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('finance.wht-certificates.index') }}" class="text-decoration-none small">&larr; {{ __('WHT Certificates') }}</a>
            <h1 class="h3 text-gray-800 mb-0 mt-1">
                {{ __('Certificate') }} <span class="text-muted">{{ $cert->cert_no }}</span>
                @php
                    $statusClass = match($cert->status) {
                        'draft' => 'secondary', 'issued' => 'success',
                        'submitted' => 'primary', default => 'light',
                    };
                @endphp
                <span class="badge bg-{{ $statusClass }}">{{ ucfirst($cert->status) }}</span>
                @if($cert->type === 'issued')
                    <span class="badge bg-warning-subtle text-dark border">↗ Issued</span>
                @else
                    <span class="badge bg-info-subtle text-info border">↙ Received</span>
                @endif
            </h1>
        </div>

        <div class="d-flex gap-2">
            @if($cert->status === 'draft')
                <form action="{{ route('finance.wht-certificates.update', $cert) }}" method="POST">
                    @csrf @method('PUT')
                    <button type="submit" name="action_issue" value="1" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> {{ __('Issue') }}
                    </button>
                </form>
                <form action="{{ route('finance.wht-certificates.destroy', $cert) }}" method="POST" onsubmit="return confirm('Delete?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            @endif
            @if($cert->status === 'issued')
                <form action="{{ route('finance.wht-certificates.update', $cert) }}" method="POST" onsubmit="return confirm('Mark as submitted to กรมสรรพากร?')">
                    @csrf @method('PUT')
                    <button type="submit" name="action_submitted" value="1" class="btn btn-primary">
                        <i class="bi bi-send-check"></i> {{ __('Mark Submitted') }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-md-8">
            <div class="card shadow mb-3">
                <div class="card-header"><strong>{{ __('Details') }}</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">{{ __('Form') }}</dt>
                        <dd class="col-sm-8">{{ strtoupper($cert->wht_type) }}</dd>
                        <dt class="col-sm-4">{{ __('Tax Period') }}</dt>
                        <dd class="col-sm-8">{{ str_pad($cert->tax_period_month, 2, '0', STR_PAD_LEFT) }}/{{ $cert->tax_period_year }}</dd>
                        <dt class="col-sm-4">{{ __('Paid At') }}</dt>
                        <dd class="col-sm-8">{{ optional($cert->paid_at)->format('d/m/Y') }}</dd>
                        <dt class="col-sm-4">{{ __('Income Type') }}</dt>
                        <dd class="col-sm-8">{{ $cert->income_type ?: '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow mb-3">
                <div class="card-header"><strong>{{ __('Parties') }}</strong></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>{{ __('Payer (ผู้จ่าย)') }}</strong>
                            <div>{{ $cert->payer_name }}</div>
                            <div class="small text-muted">{{ $cert->payer_tax_id ?: '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <strong>{{ __('Payee (ผู้รับเงิน)') }}</strong>
                            <div>{{ $cert->payee_name }}</div>
                            <div class="small text-muted">{{ $cert->payee_tax_id ?: '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @if($cert->certificate_path)
                <div class="card shadow mb-3">
                    <div class="card-header"><strong>{{ __('Attached Certificate') }}</strong></div>
                    <div class="card-body">
                        <a href="{{ asset('storage/' . $cert->certificate_path) }}" target="_blank" class="btn btn-outline-primary">
                            <i class="bi bi-file-earmark-pdf"></i> {{ __('View / Download') }}
                        </a>
                    </div>
                </div>
            @endif

            @if($cert->notes)
                <div class="card shadow">
                    <div class="card-header"><strong>{{ __('Notes') }}</strong></div>
                    <div class="card-body">{{ $cert->notes }}</div>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header"><strong>{{ __('Amounts') }}</strong></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><td>{{ __('Amount Paid') }}</td><td class="text-end">{{ number_format($cert->amount_paid, 2) }}</td></tr>
                        <tr><td>WHT {{ rtrim(rtrim($cert->wht_rate, '0'), '.') }}%</td><td class="text-end text-warning">{{ number_format($cert->wht_amount, 2) }}</td></tr>
                    </table>
                </div>
            </div>

            @if($cert->source_type && $cert->source_id)
                <div class="card shadow mt-3">
                    <div class="card-header"><strong>{{ __('Source') }}</strong></div>
                    <div class="card-body small">
                        @if($cert->source_type === \App\Models\LedgerEntry::class && ($src = $cert->source))
                            <a href="{{ route('finance.ledger.show', $src) }}">{{ $src->entry_no }}</a>
                            <div class="text-muted">{{ optional($src->entry_date)->format('d/m/Y') }}</div>
                        @else
                            <div class="text-muted">{{ class_basename($cert->source_type) }} #{{ $cert->source_id }}</div>
                        @endif
                    </div>
                </div>
            @endif

            <div class="card shadow mt-3">
                <div class="card-header"><strong>{{ __('Audit') }}</strong></div>
                <div class="card-body small text-muted">
                    <div>{{ __('Created') }}: {{ $cert->created_at?->format('d/m/Y H:i') }}
                        @if($cert->creator) by {{ $cert->creator->name }} @endif
                    </div>
                    <div>{{ __('Updated') }}: {{ $cert->updated_at?->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('finance.credit-notes.index') }}" class="text-decoration-none small">&larr; {{ __('Credit Notes') }}</a>
            <h1 class="h3 text-gray-800 mb-0 mt-1">
                {{ __('Credit Note') }} <span class="text-muted">{{ $note->credit_note_no }}</span>
                @php
                    $statusClass = match($note->status) {
                        'draft' => 'secondary', 'issued' => 'success', 'void' => 'danger', default => 'light',
                    };
                @endphp
                <span class="badge bg-{{ $statusClass }}">{{ ucfirst($note->status) }}</span>
            </h1>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('finance.credit-notes.pdf', $note) }}" target="_blank" class="btn btn-outline-secondary">
                <i class="bi bi-file-earmark-pdf"></i> {{ __('View PDF') }}
            </a>
            @if($note->status === 'issued')
                <a href="{{ route('finance.credit-notes.pdf', ['creditNote' => $note, 'copy' => 'copy']) }}" target="_blank" class="btn btn-outline-secondary">
                    <i class="bi bi-file-earmark-pdf"></i> {{ __('Copy (สำเนา)') }}
                </a>
            @endif
            @if($note->status === 'draft')
                <form action="{{ route('finance.credit-notes.issue', $note) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success" onclick="return confirm('{{ __('Issue this credit note? The number will be locked and the bill\'s balance will be reduced.') }}')">
                        <i class="bi bi-check-circle"></i> {{ __('Issue') }}
                    </button>
                </form>
                <form action="{{ route('finance.credit-notes.destroy', $note) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Delete this draft credit note?') }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            @endif
            @if($note->status === 'issued')
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#voidModal">
                    <i class="bi bi-x-circle"></i> {{ __('Void') }}
                </button>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-md-8">
            <div class="card shadow mb-3">
                <div class="card-header"><strong>{{ __('Header') }}</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">{{ __('Credit Note Date') }}</dt>
                        <dd class="col-sm-8">{{ optional($note->credit_note_date)->format('d/m/Y') }}</dd>

                        <dt class="col-sm-4">{{ __('Fiscal Year') }}</dt>
                        <dd class="col-sm-8">{{ $note->fiscal_year }}</dd>

                        <dt class="col-sm-4">{{ __('Issuer') }}</dt>
                        <dd class="col-sm-8">{{ $note->issuerProfile?->name ?? '—' }}</dd>

                        <dt class="col-sm-4">{{ __('Reduces Bill') }}</dt>
                        <dd class="col-sm-8">
                            #{{ $note->financial_transaction_id }}
                            @if($note->financialTransaction?->productionOrder?->employer)
                                — {{ $note->financialTransaction->productionOrder->employer->employerNameTh ?? $note->financialTransaction->productionOrder->employer->employerNameEn }}
                            @endif
                        </dd>

                        @if($note->relatedTaxInvoice)
                            <dt class="col-sm-4">{{ __('Related Tax Invoice') }}</dt>
                            <dd class="col-sm-8">
                                <a href="{{ route('finance.tax-invoices.show', $note->relatedTaxInvoice) }}">{{ $note->relatedTaxInvoice->invoice_no }}</a>
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="card shadow mb-3">
                <div class="card-header"><strong>{{ __('Customer') }}</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">{{ __('Name') }}</dt>
                        <dd class="col-sm-8">{{ $note->customer_name }}</dd>
                        <dt class="col-sm-4">{{ __('Tax ID') }}</dt>
                        <dd class="col-sm-8">{{ $note->customer_tax_id ?: '—' }}</dd>
                        <dt class="col-sm-4">{{ __('Branch') }}</dt>
                        <dd class="col-sm-8">{{ $note->customer_branch ?: '—' }}</dd>
                        <dt class="col-sm-4">{{ __('Address') }}</dt>
                        <dd class="col-sm-8">{{ $note->customer_address ?: '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow mb-3">
                <div class="card-header"><strong>{{ __('Reason for Credit') }}</strong></div>
                <div class="card-body">{{ $note->reason }}</div>
            </div>

            @if($note->notes)
                <div class="card shadow">
                    <div class="card-header"><strong>{{ __('Notes') }}</strong></div>
                    <div class="card-body">{{ $note->notes }}</div>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header"><strong>{{ __('Amounts') }}</strong></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><td>{{ __('Subtotal') }}</td><td class="text-end">{{ number_format($note->subtotal, 2) }}</td></tr>
                        <tr><td>VAT {{ rtrim(rtrim($note->vat_rate, '0'), '.') }}%</td><td class="text-end text-info">{{ number_format($note->vat_amount, 2) }}</td></tr>
                        <tr class="table-active"><th>{{ __('Total Credit') }}</th><th class="text-end text-danger">-{{ number_format($note->total_credit, 2) }}</th></tr>
                    </table>
                </div>
            </div>

            <div class="card shadow mt-3">
                <div class="card-header"><strong>{{ __('Audit') }}</strong></div>
                <div class="card-body small text-muted">
                    <div>{{ __('Created') }}: {{ $note->created_at?->format('d/m/Y H:i') }}
                        @if($note->creator) by {{ $note->creator->name }} @endif
                    </div>
                    @if($note->issued_at)
                        <div>{{ __('Issued') }}: {{ $note->issued_at?->format('d/m/Y H:i') }}</div>
                    @endif
                    @if($note->voided_at)
                        <div class="text-danger">{{ __('Voided') }}: {{ $note->voided_at?->format('d/m/Y H:i') }}</div>
                        @if($note->void_reason)
                            <div class="text-danger"><em>{{ $note->void_reason }}</em></div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($note->status === 'issued')
<div class="modal fade" id="voidModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('finance.credit-notes.void', $note) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger-subtle">
                    <h5 class="modal-title">{{ __('Void Credit Note') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">{{ __('Voiding keeps the credit note number reserved (Thai tax law requires no gaps in sequence) and restores the reduced amount back onto the bill\'s outstanding balance.') }}</p>
                    <label class="form-label">{{ __('Reason for void') }} *</label>
                    <textarea name="void_reason" class="form-control" rows="3" required maxlength="255"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('Confirm Void') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

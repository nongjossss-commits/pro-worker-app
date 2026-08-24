@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('finance.tax-invoices.index') }}" class="text-decoration-none small">&larr; {{ __('Tax Invoices') }}</a>
            <h1 class="h3 text-gray-800 mb-0 mt-1">
                {{ __('Invoice') }} <span class="text-muted">{{ $invoice->invoice_no }}</span>
                @php
                    $statusClass = match($invoice->status) {
                        'draft' => 'secondary', 'issued' => 'success',
                        'void' => 'danger', 'cancelled' => 'dark', default => 'light',
                    };
                @endphp
                <span class="badge bg-{{ $statusClass }}">{{ ucfirst($invoice->status) }}</span>
            </h1>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('finance.tax-invoices.pdf', $invoice) }}" target="_blank" class="btn btn-outline-secondary">
                <i class="bi bi-file-earmark-pdf"></i> {{ __('View PDF') }}
            </a>
            @if($invoice->status === 'issued')
                <a href="{{ route('finance.tax-invoices.pdf', ['taxInvoice' => $invoice, 'copy' => 'copy']) }}" target="_blank" class="btn btn-outline-secondary">
                    <i class="bi bi-file-earmark-pdf"></i> {{ __('Copy (สำเนา)') }}
                </a>
            @endif
            @if($invoice->status === 'draft')
                <form action="{{ route('finance.tax-invoices.update', $invoice) }}" method="POST" class="d-inline">
                    @csrf @method('PUT')
                    <button type="submit" name="action_issue" value="1" class="btn btn-success" onclick="return confirm('{{ __('Issue this invoice? The number will be locked.') }}')">
                        <i class="bi bi-check-circle"></i> {{ __('Issue') }}
                    </button>
                </form>
                <form action="{{ route('finance.tax-invoices.destroy', $invoice) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Delete this draft invoice?') }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            @endif
            @if($invoice->status === 'issued')
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
                        <dt class="col-sm-4">{{ __('Invoice Date') }}</dt>
                        <dd class="col-sm-8">{{ optional($invoice->invoice_date)->format('d/m/Y') }}</dd>

                        <dt class="col-sm-4">{{ __('Fiscal Year') }}</dt>
                        <dd class="col-sm-8">{{ $invoice->fiscal_year }}</dd>

                        <dt class="col-sm-4">{{ __('Issuer') }}</dt>
                        <dd class="col-sm-8">
                            {{ $invoice->issuerProfile?->name }}
                            @if($invoice->issuerProfile?->tax_id)
                                <span class="small text-muted">({{ $invoice->issuerProfile->tax_id }})</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow mb-3">
                <div class="card-header"><strong>{{ __('Customer') }}</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">{{ __('Name') }}</dt>
                        <dd class="col-sm-8">{{ $invoice->customer_name }}</dd>
                        <dt class="col-sm-4">{{ __('Tax ID') }}</dt>
                        <dd class="col-sm-8">{{ $invoice->customer_tax_id ?: '—' }}</dd>
                        <dt class="col-sm-4">{{ __('Branch') }}</dt>
                        <dd class="col-sm-8">{{ $invoice->customer_branch ?: '—' }}</dd>
                        <dt class="col-sm-4">{{ __('Address') }}</dt>
                        <dd class="col-sm-8">{{ $invoice->customer_address ?: '—' }}</dd>
                    </dl>
                </div>
            </div>

            @if($invoice->notes)
                <div class="card shadow">
                    <div class="card-header"><strong>{{ __('Notes') }}</strong></div>
                    <div class="card-body">{{ $invoice->notes }}</div>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header"><strong>{{ __('Amounts') }}</strong></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><td>{{ __('Subtotal') }}</td><td class="text-end">{{ number_format($invoice->subtotal, 2) }}</td></tr>
                        <tr><td>VAT {{ rtrim(rtrim($invoice->vat_rate, '0'), '.') }}%</td><td class="text-end text-info">{{ number_format($invoice->vat_amount, 2) }}</td></tr>
                        <tr class="table-active"><th>{{ __('Total') }}</th><th class="text-end">{{ number_format($invoice->total, 2) }}</th></tr>
                    </table>
                </div>
            </div>

            @if($invoice->ledgerEntry)
                <div class="card shadow mt-3">
                    <div class="card-header"><strong>{{ __('Linked Ledger Entry') }}</strong></div>
                    <div class="card-body small">
                        <div>
                            <a href="{{ route('finance.ledger.show', $invoice->ledgerEntry) }}">
                                {{ $invoice->ledgerEntry->entry_no }}
                            </a>
                        </div>
                        <div class="text-muted">{{ optional($invoice->ledgerEntry->entry_date)->format('d/m/Y') }}</div>
                        <div class="text-muted">{{ $invoice->ledgerEntry->bankAccount?->bank_name }}</div>
                    </div>
                </div>
            @endif

            <div class="card shadow mt-3">
                <div class="card-header"><strong>{{ __('Audit') }}</strong></div>
                <div class="card-body small text-muted">
                    <div>{{ __('Created') }}: {{ $invoice->created_at?->format('d/m/Y H:i') }}
                        @if($invoice->creator) by {{ $invoice->creator->name }} @endif
                    </div>
                    @if($invoice->issued_at)
                        <div>{{ __('Issued') }}: {{ $invoice->issued_at?->format('d/m/Y H:i') }}</div>
                    @endif
                    @if($invoice->voided_at)
                        <div class="text-danger">{{ __('Voided') }}: {{ $invoice->voided_at?->format('d/m/Y H:i') }}</div>
                        @if($invoice->void_reason)
                            <div class="text-danger"><em>{{ $invoice->void_reason }}</em></div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($invoice->status === 'issued')
<div class="modal fade" id="voidModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('finance.tax-invoices.update', $invoice) }}" method="POST">
                @csrf @method('PUT')
                <input type="hidden" name="action_void" value="1">
                <div class="modal-header bg-danger-subtle">
                    <h5 class="modal-title">{{ __('Void Invoice') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">{{ __('Voiding keeps the invoice number reserved (Thai tax law requires no gaps in sequence). The invoice will be excluded from VAT reports.') }}</p>
                    <label class="form-label">{{ __('Reason for void') }} *</label>
                    <textarea name="void_reason" class="form-control" rows="3" required></textarea>
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

@extends('labor.layout')

@section('title', $invoice->invoice_no . ' - Pro Walker Labour')

@section('content')
<div class="mb-3">
    <a href="{{ route('labor.tax-invoices.index') }}" class="text-decoration-none small">&larr; {{ __('Tax Invoices') }}</a>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0">{{ $invoice->invoice_no }}</h4>
        <span class="text-muted">{{ $invoice->customer_name }}</span>
    </div>
    <div>
        @if($invoice->status === 'issued')
            <span class="badge bg-success fs-6">{{ __('Issued') }}</span>
        @elseif($invoice->status === 'void')
            <span class="badge bg-danger fs-6">{{ __('Void') }}</span>
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
                    <dt class="col-sm-4">{{ __('Invoice Date') }}</dt>
                    <dd class="col-sm-8">{{ $invoice->invoice_date->format('d/m/Y') }}</dd>

                    <dt class="col-sm-4">{{ __('Bill Reference') }}</dt>
                    <dd class="col-sm-8">
                        @if($invoice->bill)
                            <a href="{{ route('labor.bills.show', $invoice->bill) }}">{{ $invoice->bill->bill_no }}</a>
                        @else
                            -
                        @endif
                    </dd>

                    <dt class="col-sm-4">{{ __('Customer Tax ID') }}</dt>
                    <dd class="col-sm-8">{{ $invoice->customer_tax_id ?: '-' }} @if($invoice->customer_branch) ({{ __('Branch') }} {{ $invoice->customer_branch }}) @endif</dd>

                    <dt class="col-sm-4">{{ __('Customer Address') }}</dt>
                    <dd class="col-sm-8">{{ $invoice->customer_address ?: '-' }}</dd>

                    <dt class="col-sm-4">{{ __('Subtotal') }}</dt>
                    <dd class="col-sm-8">{{ number_format($invoice->subtotal, 2) }}</dd>

                    <dt class="col-sm-4">{{ __('VAT') }} ({{ rtrim(rtrim((string) $invoice->vat_rate, '0'), '.') }}%)</dt>
                    <dd class="col-sm-8">{{ number_format($invoice->vat_amount, 2) }}</dd>

                    <dt class="col-sm-4 fw-bold">{{ __('Total') }}</dt>
                    <dd class="col-sm-8 fw-bold">{{ number_format($invoice->total, 2) }}</dd>

                    @if($invoice->notes)
                    <dt class="col-sm-4">{{ __('Notes') }}</dt>
                    <dd class="col-sm-8">{{ $invoice->notes }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        @if($invoice->status === 'void')
        <div class="alert alert-danger">
            {{ __('Voided') }} {{ optional($invoice->voided_at)->format('d/m/Y H:i') }} — {{ $invoice->void_reason }}
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body d-grid gap-2">
                <a href="{{ route('labor.tax-invoices.pdf', $invoice) }}" target="_blank" class="btn btn-outline-secondary">
                    <i class="bi bi-file-pdf me-1"></i>{{ __('View PDF') }}
                </a>

                @if($invoice->status === 'draft')
                <form method="POST" action="{{ route('labor.tax-invoices.update', $invoice) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="action_issue" value="1">
                    <button type="submit" class="btn btn-success w-100" onclick="return confirm('{{ __('Issue this invoice? The number will be locked.') }}')">
                        <i class="bi bi-check-circle me-1"></i>{{ __('Issue Invoice') }}
                    </button>
                </form>
                <form method="POST" action="{{ route('labor.tax-invoices.destroy', $invoice) }}" onsubmit="return confirm('{{ __('Delete this draft?') }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger w-100">{{ __('Delete Draft') }}</button>
                </form>
                @endif

                @if($invoice->status === 'issued')
                <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#voidModal">
                    <i class="bi bi-x-circle me-1"></i>{{ __('Void Invoice') }}
                </button>
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body small text-muted">
                <div>{{ __('Created by') }}: {{ $invoice->creator->name ?? '-' }}</div>
                <div>{{ __('Created at') }}: {{ $invoice->created_at->format('d/m/Y H:i') }}</div>
                @if($invoice->issued_at)
                <div>{{ __('Issued at') }}: {{ $invoice->issued_at->format('d/m/Y H:i') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($invoice->status === 'issued')
<div class="modal fade" id="voidModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('labor.tax-invoices.update', $invoice) }}">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Void Invoice') }} {{ $invoice->invoice_no }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">{{ __('Reason') }}</label>
                    <input type="text" name="void_reason" class="form-control" required autofocus>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="action_void" value="1">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('Void') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

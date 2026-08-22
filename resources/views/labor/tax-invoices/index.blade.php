@extends('labor.layout')

@section('title', 'Tax Invoices - Pro Walker Labor')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">{{ __('Tax Invoices') }} (ใบกำกับภาษี)</h4>
    <a href="{{ route('labor.tax-invoices.create') }}" class="btn btn-primary">
        <i class="bi bi-file-earmark-plus me-1"></i>{{ __('New Tax Invoice') }}
    </a>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">{{ __('Search') }}</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ __('Invoice no. or customer...') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small">{{ __('Fiscal Year') }}</label>
                <select name="fiscal_year" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    @foreach($fiscalYears as $y)
                        <option value="{{ $y }}" {{ (string) request('fiscal_year') === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">{{ __('Status') }}</label>
                <select name="status" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                    <option value="issued" {{ request('status') === 'issued' ? 'selected' : '' }}>{{ __('Issued') }}</option>
                    <option value="void" {{ request('status') === 'void' ? 'selected' : '' }}>{{ __('Void') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-secondary w-100">{{ __('Filter') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Invoice No.') }}</th>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Customer') }}</th>
                    <th>{{ __('Bill Ref.') }}</th>
                    <th class="text-end">{{ __('Total') }}</th>
                    <th class="text-center">{{ __('Status') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                <tr>
                    <td><a href="{{ route('labor.tax-invoices.show', $invoice) }}">{{ $invoice->invoice_no }}</a></td>
                    <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                    <td>{{ $invoice->customer_name }}</td>
                    <td>{{ $invoice->bill->bill_no ?? '-' }}</td>
                    <td class="text-end fw-bold">{{ number_format($invoice->total, 2) }}</td>
                    <td class="text-center">
                        @if($invoice->status === 'issued')
                            <span class="badge bg-success">{{ __('Issued') }}</span>
                        @elseif($invoice->status === 'void')
                            <span class="badge bg-danger">{{ __('Void') }}</span>
                        @else
                            <span class="badge bg-secondary">{{ __('Draft') }}</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('labor.tax-invoices.pdf', $invoice) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-pdf"></i></a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No tax invoices yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invoices->hasPages())
    <div class="card-footer bg-white">{{ $invoices->links() }}</div>
    @endif
</div>
@endsection

@extends('labor.layout')

@section('title', $bill->bill_no . ' - Pro Walker Labor')

@section('content')
<div class="mb-3">
    <a href="{{ route('labor.bills.index') }}" class="text-decoration-none small">&larr; {{ __('Bills') }}</a>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0">{{ $bill->bill_no }}</h4>
        <span class="text-muted">{{ $bill->team->name ?? '-' }} · {{ $bill->period_start->format('d/m/Y') }} - {{ $bill->period_end->format('d/m/Y') }}</span>
    </div>
    <div>
        @if($bill->status === 'void')
            <span class="badge bg-danger fs-6">{{ __('Void') }}</span>
        @else
            <span class="badge bg-success fs-6">{{ __('Issued') }}</span>
        @endif
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 stat-card h-100">
            <div class="card-body">
                <div class="text-muted small">{{ __('Total Due') }}</div>
                <div class="h4 fw-bold mb-0">{{ number_format($bill->total_due, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 stat-card h-100">
            <div class="card-body">
                <div class="text-muted small">{{ __('Total Paid') }}</div>
                <div class="h4 fw-bold mb-0 text-success">{{ number_format($bill->total_paid, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 stat-card h-100">
            <div class="card-body">
                <div class="text-muted small">{{ __('Balance Due') }}</div>
                <div class="h4 fw-bold mb-0 {{ $bill->balance_due > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($bill->balance_due, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 stat-card h-100">
            <div class="card-body d-flex flex-column gap-1">
                <a href="{{ route('labor.bills.download', $bill) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-pdf me-1"></i>{{ __('Preview / Download Bill') }}</a>
                <a href="{{ route('labor.tax-invoices.create', ['labor_bill_id' => $bill->id]) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-ruled me-1"></i>{{ __('Issue Tax Invoice') }}</a>
                <a href="{{ route('labor.wht-certificates.create', ['labor_bill_id' => $bill->id]) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-minus me-1"></i>{{ __('Record WHT') }}</a>
            </div>
        </div>
    </div>
</div>

@if($bill->taxInvoices->isNotEmpty() || $bill->whtCertificates->isNotEmpty())
<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white"><strong>{{ __('Related Tax Documents') }}</strong></div>
    <div class="card-body">
        @foreach($bill->taxInvoices as $inv)
            <a href="{{ route('labor.tax-invoices.show', $inv) }}" class="badge text-bg-light border me-2 mb-1 text-decoration-none">
                {{ __('Tax Invoice') }} {{ $inv->invoice_no }} ({{ number_format($inv->total, 2) }})
            </a>
        @endforeach
        @foreach($bill->whtCertificates as $cert)
            <a href="{{ route('labor.wht-certificates.show', $cert) }}" class="badge text-bg-light border me-2 mb-1 text-decoration-none">
                {{ __('WHT') }} {{ $cert->cert_no }} ({{ number_format($cert->wht_amount, 2) }})
            </a>
        @endforeach
    </div>
</div>
@endif

<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong>{{ __('Payments') }}</strong>
        @if($bill->status !== 'void')
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
            <i class="bi bi-cash-coin me-1"></i>{{ __('Record Payment') }}
        </button>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Method') }}</th>
                    <th class="text-end">{{ __('Amount') }}</th>
                    <th>{{ __('WHT') }}</th>
                    <th>{{ __('Receipt') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bill->payments as $payment)
                <tr>
                    <td>{{ $payment->paid_at->format('d/m/Y') }}</td>
                    <td>
                        @switch($payment->payment_method)
                            @case('cash') {{ __('Cash') }} @break
                            @case('transfer') {{ __('Transfer') }} @if($payment->bankAccount) ({{ $payment->bankAccount->bank_name }}) @endif @break
                            @case('promptpay') {{ __('PromptPay') }} @break
                            @default {{ __('Other') }}
                        @endswitch
                    </td>
                    <td class="text-end fw-bold">{{ number_format($payment->amount, 2) }}</td>
                    <td>
                        @if($payment->whtCertificate)
                            <a href="{{ route('labor.wht-certificates.show', $payment->whtCertificate) }}" class="small">{{ $payment->whtCertificate->cert_no }}</a>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($payment->hasReceipt())
                            <span class="badge bg-success">{{ $payment->receipt_no }}</span>
                        @else
                            <span class="text-muted small">{{ __('Not issued') }}</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @if($payment->hasReceipt())
                            <a href="{{ route('labor.bills.payments.receipt.download', [$bill, $payment]) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="{{ __('Preview / Download') }}"><i class="bi bi-file-pdf"></i></a>
                        @else
                            <form method="POST" action="{{ route('labor.bills.payments.receipt.issue', [$bill, $payment]) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Issue Receipt') }}</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No payments recorded yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($bill->status !== 'void')
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('labor.bills.payments.store', $bill) }}" enctype="multipart/form-data" x-data="{ method: 'transfer' }">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Record Payment') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Amount') }} *</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required autofocus>
                        <div class="form-text">{{ __('Balance due') }}: {{ number_format($bill->balance_due, 2) }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Payment Date') }} *</label>
                        <input type="date" name="paid_at" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Method') }} *</label>
                        <select name="payment_method" class="form-select" x-model="method" required>
                            <option value="transfer">{{ __('Bank Transfer') }}</option>
                            <option value="cash">{{ __('Cash') }}</option>
                            <option value="promptpay">{{ __('PromptPay') }}</option>
                            <option value="other">{{ __('Other') }}</option>
                        </select>
                    </div>
                    <div class="mb-3" x-show="method === 'transfer'">
                        <label class="form-label">{{ __('Bank Account (optional)') }}</label>
                        <select name="bank_account_id" class="form-select">
                            <option value="">-- {{ __('Select') }} --</option>
                            @foreach($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->bank_name }} · {{ $acc->account_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($bill->whtCertificates->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label">{{ __('WHT Certificate (optional)') }}</label>
                        <select name="wht_certificate_id" class="form-select">
                            <option value="">-- {{ __('None') }} --</option>
                            @foreach($bill->whtCertificates as $cert)
                                <option value="{{ $cert->id }}">{{ $cert->cert_no }} ({{ number_format($cert->wht_amount, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">{{ __('Payment Slip (optional)') }}</label>
                        <input type="file" name="slip" class="form-control">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">{{ __('Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save Payment') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('finance.tax-reports.index', ['period' => sprintf('%04d-%02d', $year, $month)]) }}" class="text-decoration-none small">&larr; {{ __('Tax Reports') }}</a>
            <h1 class="h3 text-gray-800 mb-0 mt-1">
                ภ.พ.30 — {{ __('VAT Report') }}
                <span class="badge bg-secondary-subtle text-dark">{{ $report['period_label'] }}</span>
            </h1>
        </div>
        <a href="{{ route('finance.tax-reports.vat.export', ['period' => sprintf('%04d-%02d', $year, $month)]) }}" class="btn btn-success">
            <i class="bi bi-file-earmark-excel"></i> {{ __('Export Excel') }}
        </a>
    </div>

    {{-- Summary cards --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-primary shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">{{ __('Output VAT (ภาษีขาย)') }}</div>
                    <div class="h4 mb-0 text-primary">{{ number_format($report['output_vat'], 2) }}</div>
                    <div class="small text-muted">{{ __('จากยอดขาย') }}: {{ number_format($report['output_subtotal'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">{{ __('Input VAT (ภาษีซื้อ)') }}</div>
                    <div class="h4 mb-0 text-warning">{{ number_format($report['input_vat'], 2) }}</div>
                    <div class="small text-muted">{{ __('จากยอดซื้อ') }}: {{ number_format($report['input_subtotal'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-{{ $report['net_vat'] >= 0 ? 'danger' : 'success' }} shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">{{ __('Net VAT (สุทธิ)') }}</div>
                    <div class="h4 mb-0 text-{{ $report['net_vat'] >= 0 ? 'danger' : 'success' }}">
                        {{ number_format(abs($report['net_vat']), 2) }}
                    </div>
                    <div class="small text-muted">
                        @if($report['net_vat'] >= 0)
                            {{ __('ต้องชำระเพิ่ม') }}
                        @else
                            {{ __('ได้รับคืน') }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-secondary shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">{{ __('Documents') }}</div>
                    <div class="h6 mb-0">{{ count($report['output_invoices']) }} ใบกำกับขาย</div>
                    <div class="h6 mb-0">{{ count($report['input_entries']) }} ใบกำกับซื้อ</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Output VAT table --}}
    <div class="card shadow mb-3">
        <div class="card-header bg-primary-subtle">
            <strong class="text-primary">{{ __('Output VAT — ใบกำกับขายที่ออกในเดือน') }}</strong>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('Invoice No') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Tax ID') }}</th>
                            <th class="text-end">{{ __('Subtotal') }}</th>
                            <th class="text-end">{{ __('VAT') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['output_invoices'] as $idx => $inv)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td><a href="{{ route('finance.tax-invoices.show', $inv) }}" class="text-decoration-none fw-bold">{{ $inv->invoice_no }}</a></td>
                                <td>{{ optional($inv->invoice_date)->format('d/m/Y') }}</td>
                                <td>{{ $inv->customer_name }}</td>
                                <td>{{ $inv->customer_tax_id ?: '—' }}</td>
                                <td class="text-end">{{ number_format($inv->subtotal, 2) }}</td>
                                <td class="text-end text-primary">{{ number_format($inv->vat_amount, 2) }}</td>
                                <td><a href="{{ route('finance.tax-invoices.pdf', $inv) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-pdf"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-3">{{ __('No issued tax invoices in this period.') }}</td></tr>
                        @endforelse
                    </tbody>
                    @if(count($report['output_invoices']) > 0)
                        <tfoot>
                            <tr class="table-active fw-bold">
                                <td colspan="5" class="text-end">{{ __('Total') }}</td>
                                <td class="text-end">{{ number_format($report['output_subtotal'], 2) }}</td>
                                <td class="text-end text-primary">{{ number_format($report['output_vat'], 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Input VAT table --}}
    <div class="card shadow">
        <div class="card-header bg-warning-subtle">
            <strong class="text-warning">{{ __('Input VAT — ใบกำกับซื้อ (จาก Ledger Expense)') }}</strong>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('Ledger #') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Vendor') }}</th>
                            <th>{{ __('Tax Invoice #') }}</th>
                            <th class="text-end">{{ __('Subtotal') }}</th>
                            <th class="text-end">{{ __('VAT') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['input_entries'] as $idx => $entry)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td><a href="{{ route('finance.ledger.show', $entry) }}" class="text-decoration-none">{{ $entry->entry_no }}</a></td>
                                <td>{{ optional($entry->entry_date)->format('d/m/Y') }}</td>
                                <td>{{ $entry->counterparty_name ?: '—' }}</td>
                                <td>{{ $entry->tax_invoice_no ?: '—' }}</td>
                                <td class="text-end">{{ number_format($entry->subtotal, 2) }}</td>
                                <td class="text-end text-warning">{{ number_format($entry->vat_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">{{ __('No purchase invoices logged. Record an expense with tax invoice no + VAT to capture input VAT.') }}</td></tr>
                        @endforelse
                    </tbody>
                    @if(count($report['input_entries']) > 0)
                        <tfoot>
                            <tr class="table-active fw-bold">
                                <td colspan="5" class="text-end">{{ __('Total') }}</td>
                                <td class="text-end">{{ number_format($report['input_subtotal'], 2) }}</td>
                                <td class="text-end text-warning">{{ number_format($report['input_vat'], 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

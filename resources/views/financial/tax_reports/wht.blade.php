@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('finance.tax-reports.index', ['period' => sprintf('%04d-%02d', $year, $month)]) }}" class="text-decoration-none small">&larr; {{ __('Tax Reports') }}</a>
            <h1 class="h3 text-gray-800 mb-0 mt-1">
                {{ $report['form_label'] }} — {{ __('WHT Report') }}
                <span class="badge bg-secondary-subtle text-dark">{{ $report['period_label'] }}</span>
            </h1>
        </div>
        <div class="d-flex gap-2">
            <div class="btn-group">
                <a href="{{ route('finance.tax-reports.wht', ['period' => sprintf('%04d-%02d', $year, $month), 'wht_type' => 'pnd3']) }}" class="btn btn-sm {{ $whtType === 'pnd3' ? 'btn-warning text-white' : 'btn-outline-warning' }}">ภ.ง.ด.3</a>
                <a href="{{ route('finance.tax-reports.wht', ['period' => sprintf('%04d-%02d', $year, $month), 'wht_type' => 'pnd53']) }}" class="btn btn-sm {{ $whtType === 'pnd53' ? 'btn-info text-white' : 'btn-outline-info' }}">ภ.ง.ด.53</a>
            </div>
            <a href="{{ route('finance.tax-reports.wht.export', ['period' => sprintf('%04d-%02d', $year, $month), 'wht_type' => $whtType]) }}" class="btn btn-success">
                <i class="bi bi-file-earmark-excel"></i> {{ __('Export Excel') }}
            </a>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card border-info shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">{{ __('Certificates Issued') }}</div>
                    <div class="h3 mb-0 text-info">{{ $report['count'] }} {{ __('ใบ') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-secondary shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">{{ __('Total Amount Paid') }}</div>
                    <div class="h4 mb-0">{{ number_format($report['total_amount_paid'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-warning shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">{{ __('Total WHT (ภาษีหัก)') }}</div>
                    <div class="h4 mb-0 text-warning">{{ number_format($report['total_wht'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Certificates table --}}
    <div class="card shadow">
        <div class="card-header">
            <strong>{{ __('Issued Certificates') }} ({{ $report['form_label'] }})</strong>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('Cert No') }}</th>
                            <th>{{ __('Paid Date') }}</th>
                            <th>{{ __('Payee') }}</th>
                            <th>{{ __('Tax ID') }}</th>
                            <th>{{ __('Income Type') }}</th>
                            <th class="text-end">{{ __('Amount Paid') }}</th>
                            <th class="text-end">{{ __('Rate %') }}</th>
                            <th class="text-end">{{ __('WHT') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['certificates'] as $idx => $cert)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td><a href="{{ route('finance.wht-certificates.show', $cert) }}" class="text-decoration-none fw-bold">{{ $cert->cert_no }}</a></td>
                                <td>{{ optional($cert->paid_at)->format('d/m/Y') }}</td>
                                <td>{{ $cert->payee_name }}</td>
                                <td>{{ $cert->payee_tax_id ?: '—' }}</td>
                                <td>{{ $cert->income_type ?: '—' }}</td>
                                <td class="text-end">{{ number_format($cert->amount_paid, 2) }}</td>
                                <td class="text-end">{{ rtrim(rtrim((string) $cert->wht_rate, '0'), '.') }}</td>
                                <td class="text-end text-warning fw-bold">{{ number_format($cert->wht_amount, 2) }}</td>
                                <td><a href="{{ route('finance.wht-certificates.pdf', $cert) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-pdf"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted py-3">{{ __('No :form certificates issued in this period.', ['form' => $report['form_label']]) }}</td></tr>
                        @endforelse
                    </tbody>
                    @if($report['count'] > 0)
                        <tfoot>
                            <tr class="table-active fw-bold">
                                <td colspan="6" class="text-end">{{ __('Total') }}</td>
                                <td class="text-end">{{ number_format($report['total_amount_paid'], 2) }}</td>
                                <td></td>
                                <td class="text-end text-warning">{{ number_format($report['total_wht'], 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

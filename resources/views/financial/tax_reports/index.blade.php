@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('finance.index') }}" class="text-decoration-none small">&larr; {{ __('Finance Hub') }}</a>
            <h1 class="h3 text-gray-800 mb-0 mt-1">{{ __('Tax Reports — รายงานภาษี') }}</h1>
        </div>
    </div>

    {{-- Period picker --}}
    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('finance.tax-reports.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">{{ __('Tax Period') }}</label>
                    <input type="month" name="period" class="form-control" value="{{ sprintf('%04d-%02d', $year, $month) }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary"><i class="bi bi-arrow-clockwise"></i> {{ __('Refresh') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        {{-- ภ.พ.30 --}}
        <div class="col-md-6">
            <div class="card shadow h-100">
                <div class="card-header bg-primary-subtle">
                    <strong class="text-primary"><i class="bi bi-receipt-cutoff me-1"></i> ภ.พ.30 — VAT</strong>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        {{ __('แบบแสดงรายการภาษีมูลค่าเพิ่ม — ยอด Output VAT (ใบกำกับขาย) หัก Input VAT (ใบกำกับซื้อ)') }}
                    </p>
                    <div class="d-flex gap-2">
                        <a href="{{ route('finance.tax-reports.vat', ['period' => sprintf('%04d-%02d', $year, $month)]) }}" class="btn btn-primary">
                            <i class="bi bi-eye"></i> {{ __('View Report') }}
                        </a>
                        <a href="{{ route('finance.tax-reports.vat.export', ['period' => sprintf('%04d-%02d', $year, $month)]) }}" class="btn btn-outline-success">
                            <i class="bi bi-file-earmark-excel"></i> {{ __('Export Excel') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ภ.ง.ด.3 --}}
        <div class="col-md-3">
            <div class="card shadow h-100">
                <div class="card-header bg-warning-subtle">
                    <strong class="text-warning"><i class="bi bi-file-earmark-text me-1"></i> ภ.ง.ด.3</strong>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">{{ __('WHT บุคคลธรรมดา') }}</p>
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route('finance.tax-reports.wht', ['period' => sprintf('%04d-%02d', $year, $month), 'wht_type' => 'pnd3']) }}" class="btn btn-warning text-white btn-sm">
                            <i class="bi bi-eye"></i> {{ __('View') }}
                        </a>
                        <a href="{{ route('finance.tax-reports.wht.export', ['period' => sprintf('%04d-%02d', $year, $month), 'wht_type' => 'pnd3']) }}" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-file-earmark-excel"></i> {{ __('Excel') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ภ.ง.ด.53 --}}
        <div class="col-md-3">
            <div class="card shadow h-100">
                <div class="card-header bg-info-subtle">
                    <strong class="text-info"><i class="bi bi-file-earmark-text me-1"></i> ภ.ง.ด.53</strong>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">{{ __('WHT นิติบุคคล') }}</p>
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route('finance.tax-reports.wht', ['period' => sprintf('%04d-%02d', $year, $month), 'wht_type' => 'pnd53']) }}" class="btn btn-info text-white btn-sm">
                            <i class="bi bi-eye"></i> {{ __('View') }}
                        </a>
                        <a href="{{ route('finance.tax-reports.wht.export', ['period' => sprintf('%04d-%02d', $year, $month), 'wht_type' => 'pnd53']) }}" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-file-earmark-excel"></i> {{ __('Excel') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info mt-3 mb-0">
        <i class="bi bi-info-circle"></i>
        {{ __('รายงานทั้ง 3 ฉบับสร้างจากข้อมูลในระบบ Ledger v2 (Tax Invoices + WHT Certificates) ของเดือนภาษีที่เลือก ใช้ประกอบการยื่นกรมสรรพากร') }}
    </div>
</div>
@endsection

@extends('layouts.app')

@section('content')
<x-help-button manual="finance" title="{{ __('Finance') }}" />
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">{{ __('Financial Hub') }}</h1>
        <div>
            <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#monthlyReportModal">
                <i class="bi bi-file-earmark-excel me-1"></i> {{ __('Monthly Report') }}
            </button>
            <a href="{{ route('finance.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> {{ __('Create Manual Bill') }}
            </a>
        </div>
    </div>

    <!-- Nav tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ $tab == 'overview' ? 'active' : '' }}" href="{{ route('finance.index', ['tab' => 'overview']) }}">
                <i class="bi bi-pie-chart me-1"></i> {{ __('Overview') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('finance.ledger.index') }}">
                <i class="bi bi-journal-bookmark me-1"></i> {{ __('Ledger') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('finance.tax-invoices.index') }}">
                <i class="bi bi-receipt-cutoff me-1"></i> {{ __('Tax Invoices') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('finance.wht-certificates.index') }}">
                <i class="bi bi-file-earmark-text me-1"></i> {{ __('WHT Certs') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('finance.tax-reports.index') }}">
                <i class="bi bi-bar-chart-line me-1"></i> {{ __('Tax Reports') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('finance.reconciliation.index') }}">
                <i class="bi bi-check2-square me-1"></i> {{ __('Reconciliation') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('finance.audit.index') }}">
                <i class="bi bi-clock-history me-1"></i> {{ __('Audit Log') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab == 'wht' ? 'active' : '' }}" href="{{ route('finance.index', ['tab' => 'wht']) }}">
                <i class="bi bi-receipt me-1"></i> {{ __('WHT Tracking') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab == 'expenses' ? 'active' : '' }}" href="{{ route('finance.index', ['tab' => 'expenses']) }}">
                <i class="bi bi-cash-stack me-1"></i> {{ __('General Expenses') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab == 'workflow' ? 'active' : '' }}" href="{{ route('finance.index', ['tab' => 'workflow']) }}">
                <i class="bi bi-briefcase me-1"></i> {{ __('Workflow & Pre Production') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab == 'registration' ? 'active' : '' }}" href="{{ route('finance.index', ['tab' => 'registration']) }}">
                <i class="bi bi-person-plus me-1"></i> {{ __('Registration Resolution') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab == 'renewal' ? 'active' : '' }}" href="{{ route('finance.index', ['tab' => 'renewal']) }}">
                <i class="bi bi-arrow-repeat me-1"></i> {{ __('Renewal Resolution') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab == 'manual' ? 'active' : '' }}" href="{{ route('finance.index', ['tab' => 'manual']) }}">
                <i class="bi bi-journal-text me-1"></i> {{ __('Manual Bills') }}
            </a>
        </li>
    </ul>

    @if($tab === 'overview')
    @include('financial.tabs.overview')
    @elseif($tab === 'workflow')
    @include('financial.tabs.workflow')
    @elseif($tab === 'registration')
    @include('financial.tabs.registration')
    @elseif($tab === 'renewal')
    @include('financial.tabs.renewal')
    @elseif($tab === 'manual')
    @include('financial.tabs.manual')
    @elseif($tab === 'wht')
    @include('financial.tabs.wht')
    @elseif($tab === 'expenses')
    @include('financial.tabs.expenses')
    @endif
</div>

<!-- Monthly Report Modal -->
<div class="modal fade" id="monthlyReportModal" tabindex="-1" aria-labelledby="monthlyReportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('finance.export_monthly') }}" method="GET">
                <div class="modal-header">
                    <h5 class="modal-title" id="monthlyReportModalLabel">{{ __('Export Monthly Report') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="report_month" class="form-label">{{ __('Select Month') }}</label>
                        <input type="month" class="form-control" id="report_month" name="month" required value="{{ date('Y-m') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Biller Profile') }}</label>
                        <select class="form-select" name="biller_id">
                            <option value="all">{{ __('All Profiles') }}</option>
                            @foreach(\App\Models\FinancialProfile::where('type', 'biller')->get() as $profile)
                                <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Export Type') }}</label>
                        <select class="form-select" name="export_type">
                            <option value="all">{{ __('All Income & Expenses (น้ำทั้งเนื้อ)') }}</option>
                            <option value="tax_only">{{ __('Tax Deductible Only (เฉพาะเนื้อๆ เพื่อยื่นภาษี)') }}</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-download"></i> {{ __('Download Report') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

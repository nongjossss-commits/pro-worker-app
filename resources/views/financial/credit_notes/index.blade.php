@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('finance.index') }}" class="text-decoration-none small">&larr; {{ __('Finance Hub') }}</a>
            <h1 class="h3 text-gray-800 mb-0 mt-1">{{ __('Credit Notes — ใบลดหนี้') }}</h1>
        </div>
        <a href="{{ route('finance.credit-notes.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> {{ __('New Credit Note') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('finance.credit-notes.index') }}" method="GET" class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="{{ __('Search credit note no, customer, tax ID…') }}" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="fiscal_year" class="form-select">
                        <option value="">{{ __('Year: All') }}</option>
                        @foreach($fiscalYears as $year)
                            <option value="{{ $year }}" {{ (string) request('fiscal_year') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">{{ __('Status: All') }}</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                        <option value="issued" {{ request('status') === 'issued' ? 'selected' : '' }}>{{ __('Issued') }}</option>
                        <option value="void" {{ request('status') === 'void' ? 'selected' : '' }}>{{ __('Void') }}</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-outline-primary"><i class="bi bi-search"></i> {{ __('Filter') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Credit Note #') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Reduces Bill') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th class="text-end">{{ __('Subtotal') }}</th>
                            <th class="text-end">{{ __('VAT') }}</th>
                            <th class="text-end">{{ __('Total Credit') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($creditNotes as $cn)
                            <tr class="{{ $cn->status === 'void' ? 'text-muted' : '' }}">
                                <td>
                                    <a href="{{ route('finance.credit-notes.show', $cn) }}" class="text-decoration-none fw-bold">
                                        {{ $cn->credit_note_no }}
                                    </a>
                                </td>
                                <td>{{ optional($cn->credit_note_date)->format('d/m/Y') }}</td>
                                <td>#{{ $cn->financial_transaction_id }}</td>
                                <td>
                                    <div>{{ $cn->customer_name }}</div>
                                    @if($cn->customer_tax_id)
                                        <div class="small text-muted">{{ $cn->customer_tax_id }}</div>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($cn->subtotal, 2) }}</td>
                                <td class="text-end text-info">{{ number_format($cn->vat_amount, 2) }}</td>
                                <td class="text-end fw-bold text-danger">-{{ number_format($cn->total_credit, 2) }}</td>
                                <td>
                                    @php
                                        $statusClass = match($cn->status) {
                                            'draft' => 'secondary',
                                            'issued' => 'success',
                                            'void' => 'danger',
                                            default => 'light',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">{{ ucfirst($cn->status) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('finance.credit-notes.show', $cn) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">{{ __('No credit notes yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">{{ $creditNotes->links() }}</div>
        </div>
    </div>
</div>
@endsection

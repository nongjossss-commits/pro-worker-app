@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('finance.index') }}" class="text-decoration-none small">&larr; {{ __('Finance Hub') }}</a>
            <h1 class="h3 text-gray-800 mb-0 mt-1">{{ __('WHT Certificates — ใบหัก ณ ที่จ่าย') }}</h1>
        </div>
        <a href="{{ route('finance.wht-certificates.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> {{ __('New Certificate') }}
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
            <form action="{{ route('finance.wht-certificates.index') }}" method="GET" class="row g-2">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="{{ __('Search cert no, payer, payee…') }}" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select">
                        <option value="">{{ __('Direction: All') }}</option>
                        <option value="issued" {{ request('type') === 'issued' ? 'selected' : '' }}>{{ __('Issued (เราออก)') }}</option>
                        <option value="received" {{ request('type') === 'received' ? 'selected' : '' }}>{{ __('Received (ลูกค้าออกให้เรา)') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="wht_type" class="form-select">
                        <option value="">{{ __('Form: All') }}</option>
                        <option value="pnd3" {{ request('wht_type') === 'pnd3' ? 'selected' : '' }}>ภ.ง.ด.3</option>
                        <option value="pnd53" {{ request('wht_type') === 'pnd53' ? 'selected' : '' }}>ภ.ง.ด.53</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="year" class="form-select">
                        <option value="">{{ __('Year: All') }}</option>
                        @foreach($periods->pluck('tax_period_year')->unique() as $y)
                            <option value="{{ $y }}" {{ (string) request('year') === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="month" class="form-select">
                        <option value="">{{ __('Month: All') }}</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ (string) request('month') === (string) $m ? 'selected' : '' }}>{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-1 d-grid">
                    <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
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
                            <th>{{ __('Cert #') }}</th>
                            <th>{{ __('Period') }}</th>
                            <th>{{ __('Direction') }}</th>
                            <th>{{ __('Form') }}</th>
                            <th>{{ __('Payer') }}</th>
                            <th>{{ __('Payee') }}</th>
                            <th class="text-end">{{ __('Amount') }}</th>
                            <th class="text-end">{{ __('WHT') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($certificates as $cert)
                            <tr>
                                <td>
                                    <a href="{{ route('finance.wht-certificates.show', $cert) }}" class="text-decoration-none fw-bold">
                                        {{ $cert->cert_no }}
                                    </a>
                                </td>
                                <td>{{ str_pad($cert->tax_period_month, 2, '0', STR_PAD_LEFT) }}/{{ $cert->tax_period_year }}</td>
                                <td>
                                    @if($cert->type === 'issued')
                                        <span class="badge bg-warning-subtle text-dark border">↗ Issued</span>
                                    @else
                                        <span class="badge bg-info-subtle text-info border">↙ Received</span>
                                    @endif
                                </td>
                                <td>{{ strtoupper($cert->wht_type) }}</td>
                                <td>
                                    <div>{{ $cert->payer_name }}</div>
                                    @if($cert->payer_tax_id)<div class="small text-muted">{{ $cert->payer_tax_id }}</div>@endif
                                </td>
                                <td>
                                    <div>{{ $cert->payee_name }}</div>
                                    @if($cert->payee_tax_id)<div class="small text-muted">{{ $cert->payee_tax_id }}</div>@endif
                                </td>
                                <td class="text-end">{{ number_format($cert->amount_paid, 2) }}</td>
                                <td class="text-end text-warning fw-bold">{{ number_format($cert->wht_amount, 2) }}</td>
                                <td>
                                    @php
                                        $statusClass = match($cert->status) {
                                            'draft' => 'secondary', 'issued' => 'success',
                                            'submitted' => 'primary', default => 'light',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">{{ ucfirst($cert->status) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('finance.wht-certificates.show', $cert) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted py-4">{{ __('No WHT certificates yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">{{ $certificates->links() }}</div>
        </div>
    </div>
</div>
@endsection

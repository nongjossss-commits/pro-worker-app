@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('finance.index') }}" class="text-decoration-none small">&larr; {{ __('Finance Hub') }}</a>
            <h1 class="h3 text-gray-800 mb-0 mt-1">{{ __('Finance Audit Log') }}</h1>
            <div class="small text-muted mt-1">
                {{ __('Every create / update / delete / status change on Ledger Entries, Tax Invoices, and WHT Certificates. Powered by the existing ActivityLog table.') }}
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('finance.audit.index') }}" method="GET" class="row g-2">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="{{ __('Search description…') }}" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="entity" class="form-select">
                        <option value="">{{ __('Entity: All') }}</option>
                        <option value="ledger" {{ request('entity') === 'ledger' ? 'selected' : '' }}>{{ __('Ledger Entry') }}</option>
                        <option value="invoice" {{ request('entity') === 'invoice' ? 'selected' : '' }}>{{ __('Tax Invoice') }}</option>
                        <option value="wht" {{ request('entity') === 'wht' ? 'selected' : '' }}>{{ __('WHT Certificate') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="action" class="form-select">
                        <option value="">{{ __('Action: All') }}</option>
                        @foreach($actions as $a)
                            <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $a)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="user_id" class="form-select">
                        <option value="">{{ __('User: All') }}</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ (string) request('user_id') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-1">
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
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
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('When') }}</th>
                            <th>{{ __('Entity') }}</th>
                            <th>{{ __('Action') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('User') }}</th>
                            <th>{{ __('IP') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td><span class="small text-muted">{{ $log->created_at->format('d/m/Y H:i') }}</span></td>
                                <td>
                                    @php
                                        $base = class_basename($log->subject_type);
                                        $entityClass = match($base) {
                                            'LedgerEntry' => 'primary',
                                            'TaxInvoice' => 'info',
                                            'WhtCertificate' => 'warning',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $entityClass }}-subtle text-dark border">{{ $base }}</span>
                                    <span class="small text-muted">#{{ $log->subject_id }}</span>
                                </td>
                                <td>
                                    @php
                                        $actionClass = match($log->action) {
                                            'create' => 'success',
                                            'update' => 'warning',
                                            'delete' => 'danger',
                                            'status_change' => 'info',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $actionClass }}">{{ ucfirst(str_replace('_', ' ', $log->action)) }}</span>
                                </td>
                                <td>{{ $log->description }}</td>
                                <td>{{ $log->user?->name ?? '—' }}</td>
                                <td class="small text-muted">{{ $log->ip_address ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No audit entries yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">{{ $logs->links() }}</div>
        </div>
    </div>
</div>
@endsection

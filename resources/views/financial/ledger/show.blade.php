@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('finance.ledger.index') }}" class="text-decoration-none small">&larr; {{ __('Back to Ledger') }}</a>
            <h1 class="h3 text-gray-800 mb-0 mt-1">
                {{ __('Ledger Entry') }} <span class="text-muted">{{ $entry->entry_no }}</span>
                @if($entry->type === 'income')
                    <span class="badge bg-success">{{ __('Income') }}</span>
                @else
                    <span class="badge bg-danger">{{ __('Expense') }}</span>
                @endif
            </h1>
        </div>
        <form action="{{ route('finance.ledger.destroy', $entry) }}" method="POST" onsubmit="return confirm('{{ __('Delete this entry? Balance will be restored.') }}')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> {{ __('Delete') }}</button>
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-md-8">
            <div class="card shadow mb-3">
                <div class="card-header"><strong>{{ __('Entry Details') }}</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">{{ __('Date') }}</dt>
                        <dd class="col-sm-8">{{ optional($entry->entry_date)->format('d/m/Y') }}</dd>

                        <dt class="col-sm-4">{{ __('Bank Account') }}</dt>
                        <dd class="col-sm-8">
                            {{ $entry->bankAccount?->bank_name }} — {{ $entry->bankAccount?->account_name }}
                            @if($entry->bankAccount?->account_type === 'personal')
                                <span class="badge bg-secondary ms-2">{{ __('Personal — off-book') }}</span>
                            @else
                                <span class="badge bg-primary ms-2">{{ __('Company') }}</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">{{ __('Counterparty') }}</dt>
                        <dd class="col-sm-8">
                            {{ $entry->counterparty_name ?: '—' }}
                            @if($entry->counterparty_tax_id)
                                <span class="text-muted small">({{ $entry->counterparty_tax_id }})</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">{{ __('Description') }}</dt>
                        <dd class="col-sm-8">{{ $entry->description ?: '—' }}</dd>

                        @if($entry->tax_invoice_no)
                            <dt class="col-sm-4">{{ __('Tax Invoice') }}</dt>
                            <dd class="col-sm-8">{{ $entry->tax_invoice_no }} ({{ optional($entry->tax_invoice_date)->format('d/m/Y') }})</dd>
                        @endif

                        @if($entry->notes)
                            <dt class="col-sm-4">{{ __('Notes') }}</dt>
                            <dd class="col-sm-8">{{ $entry->notes }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header"><strong>{{ __('Attachments') }}</strong></div>
                <div class="card-body">
                    @if($entry->receipt_path)
                        <div class="mb-2">
                            <a href="{{ asset('storage/' . $entry->receipt_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-receipt"></i> {{ __('Receipt / Slip') }}
                            </a>
                        </div>
                    @endif
                    @if(!empty($entry->attached_files))
                        @foreach($entry->attached_files as $file)
                            <div class="mb-1">
                                <a href="{{ asset('storage/' . ($file['path'] ?? '')) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-paperclip"></i> {{ $file['name'] ?? __('Attachment') }}
                                </a>
                            </div>
                        @endforeach
                    @endif
                    @if(!$entry->receipt_path && empty($entry->attached_files))
                        <p class="text-muted mb-0">{{ __('No attachments.') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header"><strong>{{ __('Amount Breakdown') }}</strong></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><td>{{ __('Gross') }}</td><td class="text-end">{{ number_format($entry->gross_amount, 2) }}</td></tr>
                        <tr><td>{{ __('Subtotal') }}</td><td class="text-end">{{ number_format($entry->subtotal, 2) }}</td></tr>
                        <tr>
                            <td>VAT ({{ rtrim(rtrim($entry->vat_rate, '0'), '.') }}%)
                                <span class="badge bg-light text-dark border">{{ $entry->vat_treatment }}</span>
                            </td>
                            <td class="text-end text-info">{{ number_format($entry->vat_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td>WHT ({{ rtrim(rtrim($entry->wht_rate, '0'), '.') }}%)
                                <span class="badge bg-light text-dark border">{{ strtoupper($entry->wht_type) }}</span>
                            </td>
                            <td class="text-end text-warning">{{ number_format($entry->wht_amount, 2) }}</td>
                        </tr>
                        <tr class="table-active">
                            <th>{{ __('Net (to/from bank)') }}</th>
                            <th class="text-end {{ $entry->type === 'income' ? 'text-success' : 'text-danger' }}">
                                {{ $entry->type === 'income' ? '+' : '-' }}{{ number_format($entry->net_amount, 2) }}
                            </th>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card shadow mt-3">
                <div class="card-header"><strong>{{ __('Audit') }}</strong></div>
                <div class="card-body small text-muted">
                    <div>{{ __('Created') }}: {{ $entry->created_at?->format('d/m/Y H:i') }}
                        @if($entry->creator) {{ __('by') }} {{ $entry->creator->name }} @endif
                    </div>
                    <div>{{ __('Updated') }}: {{ $entry->updated_at?->format('d/m/Y H:i') }}
                        @if($entry->updater) {{ __('by') }} {{ $entry->updater->name }} @endif
                    </div>
                    <div>{{ __('Source') }}: {{ $entry->ai_source }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

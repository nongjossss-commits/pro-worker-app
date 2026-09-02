@extends('labor.layout')

@section('title', $team->name . ' - Pro Walker Labour')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="{{ route('labor.dashboard') }}" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left me-1"></i>{{ __('Back to overview') }}
        </a>
        <h4 class="fw-bold mb-0">{{ $team->name }}</h4>
    </div>
    @can('manage-labor-ledger')
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEntryModal">
        <i class="bi bi-plus-lg me-1"></i>{{ __('Add Entry') }}
    </button>
    @endcan
</div>

<div class="card stat-card shadow-sm border-0 bg-dark text-white mb-4" style="max-width: 320px;">
    <div class="card-body">
        <div class="text-white-50 small text-uppercase fw-bold">{{ __('Total Owed') }}</div>
        <div class="fs-2 fw-bold">{{ number_format($totalOwed, 2) }}</div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold">{{ __('Bills') }}</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Bill No.') }}</th>
                    <th>{{ __('Period') }}</th>
                    <th class="text-end">{{ __('Total Due') }}</th>
                    <th class="text-center">{{ __('Status') }}</th>
                    <th class="text-end">{{ __('PDF') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bills as $bill)
                <tr>
                    <td>{{ $bill->bill_no }}</td>
                    <td class="text-nowrap">{{ $bill->period_start->format('d/m/Y') }} - {{ $bill->period_end->format('d/m/Y') }}</td>
                    <td class="text-end fw-bold">{{ number_format($bill->total_due, 2) }}</td>
                    <td class="text-center">
                        @if($bill->status === 'void')
                            <span class="badge bg-danger">{{ __('Void') }}</span>
                        @else
                            <span class="badge bg-success">{{ __('Issued') }}</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('labor.bills.download', $bill) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-download"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">{{ __('No bills issued yet.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @can('manage-labor-ledger')
    <div class="card-footer bg-white text-end">
        <a href="{{ route('labor.bills.index') }}" class="small">{{ __('Go to Bills') }} →</a>
    </div>
    @endcan
</div>

@can('manage-labor-ledger')
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold">{{ __('Auto-Billing Schedule') }}</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('labor.teams.billing-schedule.update', $team) }}" class="row g-2 align-items-end">
            @csrf
            @method('PUT')
            <div class="col-auto">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="auto_billing_enabled" value="1"
                           id="autoBillingEnabled" {{ $team->auto_billing_enabled ? 'checked' : '' }}>
                    <label class="form-check-label" for="autoBillingEnabled">{{ __('Enabled') }}</label>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('Cadence') }}</label>
                <select name="billing_cadence" class="form-select form-select-sm">
                    <option value="">-- {{ __('None') }} --</option>
                    <option value="daily" {{ $team->billing_cadence === 'daily' ? 'selected' : '' }}>{{ __('Daily') }}</option>
                    <option value="weekly" {{ $team->billing_cadence === 'weekly' ? 'selected' : '' }}>{{ __('Weekly') }}</option>
                    <option value="monthly" {{ $team->billing_cadence === 'monthly' ? 'selected' : '' }}>{{ __('Monthly') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('Day of Week') }}</label>
                <select name="billing_day_of_week" class="form-select form-select-sm">
                    <option value="">-</option>
                    @foreach(['0'=>__('Sun'),'1'=>__('Mon'),'2'=>__('Tue'),'3'=>__('Wed'),'4'=>__('Thu'),'5'=>__('Fri'),'6'=>__('Sat')] as $val => $label)
                        <option value="{{ $val }}" {{ (string) $team->billing_day_of_week === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="form-text">{{ __('For Weekly') }}</div>
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('Day of Month') }}</label>
                <input type="number" min="1" max="31" name="billing_day_of_month" class="form-control form-control-sm"
                       value="{{ $team->billing_day_of_month }}">
                <div class="form-text">{{ __('For Monthly') }}</div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Save') }}</button>
            </div>
        </form>
        @if($team->last_auto_billed_on)
        <p class="text-muted small mt-2 mb-0">{{ __('Last auto-generated') }}: {{ $team->last_auto_billed_on->format('d/m/Y') }}</p>
        @endif
    </div>
</div>
@endcan

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">{{ __('Team Members') }}</h6>
        @can('manage-labor-ledger')
        <a href="{{ route('labor.team-members.index') }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-person-gear me-1"></i>{{ __('Manage Members') }}
        </a>
        @endcan
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th class="text-center">{{ __('Status') }}</th>
                    <th class="text-end">{{ __('Jobs Filed') }}</th>
                    <th class="text-end">{{ __('Total Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                <tr>
                    <td>{{ $member->name }}</td>
                    <td class="text-center">
                        @if($member->is_active)
                            <span class="badge bg-success">{{ __('Active') }}</span>
                        @else
                            <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                        @endif
                    </td>
                    <td class="text-end">{{ $member->ledger_entries_count }}</td>
                    <td class="text-end fw-bold">{{ number_format($member->total_amount ?? 0, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-3">
                        {{ __('No members yet.') }}
                        @can('manage-labor-ledger')
                            <a href="{{ route('labor.team-members.index') }}">{{ __('Register one') }}</a>
                        @endcan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th>{{ __('Team Member') }}</th>
                    <th class="text-end">{{ __('Amount') }}</th>
                    <th>{{ __('Recorded By') }}</th>
                    @can('manage-labor-ledger')
                    <th class="text-end">{{ __('Actions') }}</th>
                    @endcan
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                <tr>
                    <td>{{ $entry->entry_date->format('d/m/Y') }}</td>
                    <td>{{ $entry->description }}</td>
                    <td>{{ $entry->member->name ?? '-' }}</td>
                    <td class="text-end fw-bold {{ $entry->amount > 0 ? 'text-danger' : 'text-success' }}">
                        {{ number_format($entry->amount, 2) }}
                    </td>
                    <td class="text-muted small">
                        {{ $entry->creator->name ?? '-' }}
                        @if($entry->updater)
                            <br><span class="fst-italic">{{ __('edited by') }} {{ $entry->updater->name }}</span>
                        @endif
                    </td>
                    @can('manage-labor-ledger')
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal" data-bs-target="#editEntryModal{{ $entry->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="{{ route('labor.ledger.destroy', [$team, $entry]) }}" class="d-inline"
                              onsubmit="return confirm('{{ __('Remove this entry?') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                    @endcan
                </tr>

                @can('manage-labor-ledger')
                <div class="modal fade" id="editEntryModal{{ $entry->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" action="{{ route('labor.ledger.update', [$team, $entry]) }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">{{ __('Edit Entry') }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Date') }}</label>
                                        <input type="date" name="entry_date" class="form-control"
                                               value="{{ $entry->entry_date->format('Y-m-d') }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Description') }}</label>
                                        <input type="text" name="description" class="form-control"
                                               value="{{ $entry->description }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Team Member (optional)') }}</label>
                                        <select name="labor_team_member_id" class="form-select">
                                            <option value="">-- {{ __('Team-wide (no specific member)') }} --</option>
                                            @foreach($members as $memberOption)
                                                <option value="{{ $memberOption->id }}" {{ $entry->labor_team_member_id == $memberOption->id ? 'selected' : '' }}>
                                                    {{ $memberOption->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Amount') }}</label>
                                        <input type="number" step="0.01" name="amount" class="form-control"
                                               value="{{ $entry->amount }}" required>
                                        <div class="form-text">{{ __('Positive = charge owed. Negative = payment/credit.') }}</div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endcan
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">{{ __('No entries yet.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($entries->hasPages())
    <div class="card-footer bg-white">
        {{ $entries->links() }}
    </div>
    @endif
</div>

@can('manage-labor-ledger')
<div class="modal fade" id="addEntryModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('labor.ledger.store', $team) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add Entry') }} — {{ $team->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Date') }}</label>
                        <input type="date" name="entry_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Description') }}</label>
                        <input type="text" name="description" class="form-control" placeholder="{{ __('e.g. Monthly service fee') }}" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Team Member (optional)') }}</label>
                        <select name="labor_team_member_id" class="form-select">
                            <option value="">-- {{ __('Team-wide (no specific member)') }} --</option>
                            @foreach($members as $memberOption)
                                <option value="{{ $memberOption->id }}">{{ $memberOption->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Amount') }}</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required>
                        <div class="form-text">{{ __('Positive = charge owed. Negative = payment/credit.') }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection

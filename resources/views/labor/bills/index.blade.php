@extends('labor.layout')

@section('title', 'Bills - Pro Walker Labour')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">{{ __('Bills') }}</h4>
    <div class="d-flex flex-wrap gap-2 justify-content-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateBillModal">
            <i class="bi bi-file-earmark-plus me-1"></i>{{ __('Generate Bill') }}
        </button>
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#itemizedBillModal">
            <i class="bi bi-ui-checks me-1"></i>{{ __('Bill Selected Items') }}
        </button>
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#monthlyBillModal">
            <i class="bi bi-calendar2-month me-1"></i>{{ __('Monthly Charge Bill') }}
        </button>
    </div>
</div>

@if($createdBills->isNotEmpty())
<div class="alert alert-info">
    <div class="fw-semibold mb-1"><i class="bi bi-file-earmark-pdf me-1"></i>{{ __('New bills — one PDF per team:') }}</div>
    <ul class="mb-0 ps-3">
        @foreach($createdBills as $created)
            <li>
                <a href="{{ route('labor.bills.download', $created) }}" target="_blank">{{ $created->bill_no }}</a>
                · {{ $created->team->name ?? '-' }} · {{ number_format($created->total_due, 2) }}
            </li>
        @endforeach
    </ul>
</div>
@endif

{{-- Filter bar: team + payment status (GET form) + period quick-links (preserve
     whichever team/payment_status are already selected via request()->except) --}}
<div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('labor.bills.index') }}" class="row g-2 align-items-end">
            <input type="hidden" name="period" value="{{ $period }}">
            <div class="col-md-4">
                <label class="form-label small">{{ __('Team') }}</label>
                <select name="team_id" class="form-select">
                    <option value="">-- {{ __('All Teams') }} --</option>
                    @foreach($teams as $team)
                        <option value="{{ $team->id }}" {{ (string) $teamId === (string) $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">{{ __('Payment Status') }}</label>
                <select name="payment_status" class="form-select">
                    <option value="" {{ !$paymentStatus ? 'selected' : '' }}>{{ __('All') }}</option>
                    <option value="outstanding" {{ $paymentStatus === 'outstanding' ? 'selected' : '' }}>{{ __('Outstanding') }}</option>
                    <option value="paid" {{ $paymentStatus === 'paid' ? 'selected' : '' }}>{{ __('Fully Paid') }}</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>{{ __('Filter') }}</button>
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label small">{{ __('Issued in month') }}</label>
                <input type="month" name="month" class="form-control" value="{{ $filterMonth }}">
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label small">{{ __('Issued on day') }}</label>
                <input type="date" name="date" class="form-control" value="{{ $filterDate }}">
            </div>
            @if($filterMonth || $filterDate)
            <div class="col-md-4">
                <a href="{{ route('labor.bills.index', request()->except(['page', 'month', 'date'])) }}" class="btn btn-outline-secondary w-100">{{ __('Clear month / day') }}</a>
            </div>
            @endif
        </form>
        <div class="mt-3">
            <label class="form-label small d-block mb-1">{{ __('Period') }}</label>
            <div class="btn-group flex-wrap" role="group">
                @foreach(['all' => __('All Time'), 'today' => __('Today'), 'month' => __('This Month'), 'quarter' => __('This Quarter'), 'year' => __('This Year')] as $val => $label)
                    <a href="{{ route('labor.bills.index', array_merge(request()->except(['page', 'period', 'month', 'date']), ['period' => $val])) }}"
                       class="btn btn-sm {{ $period === $val && !$filterMonth && !$filterDate ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Summary cards — reflect the current team + period scope --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card shadow-sm border-0 stat-card h-100">
            <div class="card-body">
                <div class="text-muted small">{{ __('Total Billed') }}</div>
                <div class="h4 fw-bold mb-0">{{ number_format($totalBilled, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm border-0 stat-card h-100">
            <div class="card-body">
                <div class="text-muted small">{{ __('Total Paid') }}</div>
                <div class="h4 fw-bold mb-0 text-success">{{ number_format($totalPaid, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm border-0 stat-card h-100">
            <div class="card-body">
                <div class="text-muted small">{{ __('Total Outstanding') }}</div>
                <div class="h4 fw-bold mb-0 {{ $totalOutstanding > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($totalOutstanding, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm border-0 stat-card h-100">
            <div class="card-body">
                <div class="text-muted small">{{ __('Bills') }}</div>
                <div class="h4 fw-bold mb-0">{{ number_format($billCount) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Per-team follow-up (all-time, as of now): billed / paid / remaining / not billed yet --}}
<div class="card shadow-sm border-0 mb-3">
    <div class="card-body pb-2">
        <h6 class="fw-bold mb-1">{{ __('Billing Status by Team') }}</h6>
        <p class="text-muted small mb-0">{{ __('All-time totals as of now. Paid includes payments and other credits. Charges dated inside an earlier statement bill count as billed.') }}</p>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Team') }}</th>
                    <th class="text-end">{{ __('Billed') }}</th>
                    <th class="text-end">{{ __('Paid') }}</th>
                    <th class="text-end">{{ __('Remaining (billed − paid)') }}</th>
                    <th class="text-end">{{ __('Not billed yet') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($tracking as $row)
                <tr>
                    <td>{{ $row->team->name }}</td>
                    <td class="text-end">{{ number_format($row->billed, 2) }}</td>
                    <td class="text-end text-success">{{ number_format($row->paid, 2) }}</td>
                    <td class="text-end fw-bold {{ $row->remaining > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($row->remaining, 2) }}</td>
                    <td class="text-end {{ $row->unbilled > 0 ? 'text-warning-emphasis fw-semibold' : 'text-muted' }}">
                        {{ number_format($row->unbilled, 2) }}
                        @if($row->unbilled_count)<div class="small text-muted fw-normal">{{ __(':count item(s)', ['count' => $row->unbilled_count]) }}</div>@endif
                    </td>
                    <td class="text-end">
                        @if($row->unbilled_count)
                        <button type="button" class="btn btn-sm btn-outline-primary text-nowrap"
                                onclick="window.dispatchEvent(new CustomEvent('open-itemized-bill', { detail: { teamId: {{ $row->team->id }} } }))">
                            <i class="bi bi-ui-checks me-1"></i>{{ __('Bill now') }}
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-3">{{ __('No charges recorded yet.') }}</td></tr>
                @endforelse
            </tbody>
            @if($tracking->count() > 1)
            <tfoot class="table-light fw-bold">
                <tr>
                    <td>{{ __('Total') }}</td>
                    <td class="text-end">{{ number_format($tracking->sum('billed'), 2) }}</td>
                    <td class="text-end text-success">{{ number_format($tracking->sum('paid'), 2) }}</td>
                    <td class="text-end">{{ number_format($tracking->sum('remaining'), 2) }}</td>
                    <td class="text-end">{{ number_format($tracking->sum('unbilled'), 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-2">{{ __('Billing Header') }}</h6>
        <p class="text-muted small mb-3">{{ __('Which company letterhead (from the main program\'s Financial Profiles) new bills are issued under. Past bills keep whichever profile they were issued with.') }}</p>
        <form method="POST" action="{{ route('labor.billing-settings.update') }}" class="row g-2 align-items-end">
            @csrf
            @method('PUT')
            <div class="col-md-6">
                <select name="financial_profile_id" class="form-select">
                    <option value="">-- {{ __('None selected') }} --</option>
                    @foreach($profiles as $profile)
                        <option value="{{ $profile->id }}" {{ (string) $currentProfileId === (string) $profile->id ? 'selected' : '' }}>
                            {{ $profile->name }} @if($profile->tax_id) ({{ $profile->tax_id }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary">{{ __('Save') }}</button>
            </div>
        </form>
        @if($profiles->isEmpty())
        <div class="alert alert-warning small mt-3 mb-0">
            {{ __('No biller-type Financial Profile exists yet in the main program. Create one there first (Finance → Profiles).') }}
        </div>
        @endif
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Bill No.') }}</th>
                    <th>{{ __('Team') }}</th>
                    <th>{{ __('Period') }}</th>
                    <th class="text-end">{{ __('Previous Balance') }}</th>
                    <th class="text-end">{{ __('Period Charges') }}</th>
                    <th class="text-end">{{ __('Total Due') }}</th>
                    <th class="text-end">{{ __('Paid') }}</th>
                    <th class="text-center">{{ __('Status') }}</th>
                    <th>{{ __('Issued') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bills as $bill)
                <tr>
                    <td>
                        <a href="{{ route('labor.bills.show', $bill) }}">{{ $bill->bill_no }}</a>
                        @if($bill->bill_type === 'monthly')
                            <br><span class="badge bg-info-subtle text-info-emphasis">{{ __('Monthly charges') }}</span>
                        @elseif($bill->bill_type === 'itemized')
                            <br><span class="badge bg-primary-subtle text-primary-emphasis">{{ __('Selected items') }}</span>
                        @endif
                    </td>
                    <td>{{ $bill->team->name ?? '-' }}</td>
                    <td class="text-nowrap">{{ $bill->period_start->format('d/m/Y') }} - {{ $bill->period_end->format('d/m/Y') }}</td>
                    <td class="text-end">{{ number_format($bill->previous_balance, 2) }}</td>
                    <td class="text-end">{{ number_format($bill->period_charges, 2) }}</td>
                    <td class="text-end fw-bold">{{ number_format($bill->total_due, 2) }}</td>
                    <td class="text-end text-success">{{ number_format($bill->total_paid, 2) }}</td>
                    <td class="text-center">
                        @if($bill->status === 'void')
                            <span class="badge bg-danger">{{ __('Void') }}</span>
                        @else
                            <span class="badge bg-success">{{ __('Issued') }}</span>
                            <br>
                            @switch($bill->payment_status)
                                @case('paid')
                                    <span class="badge bg-success mt-1"><i class="bi bi-check-circle me-1"></i>{{ __('Paid in Full') }}</span>
                                    @break
                                @case('partial')
                                    <span class="badge bg-warning text-dark mt-1">{{ __('Partial') }}</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary mt-1">{{ __('Unpaid') }}</span>
                            @endswitch
                        @endif
                        @if($bill->is_auto_generated)
                            <span class="badge bg-secondary" title="{{ __('Auto-generated') }}"><i class="bi bi-robot"></i></span>
                        @endif
                    </td>
                    <td class="text-muted small">
                        {{ optional($bill->issued_at)->format('d/m/Y H:i') }}
                        <br>{{ $bill->creator->name ?? __('System') }}
                    </td>
                    <td class="text-end">
                        <a href="{{ route('labor.bills.download', $bill) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="{{ __('Preview / Download') }}">
                            <i class="bi bi-file-pdf"></i>
                        </a>
                        @if($bill->status !== 'void' && $bill->balance_due > 0)
                        <a href="{{ route('labor.bills.show', $bill) }}?pay=1" class="btn btn-sm btn-success" title="{{ __('Record Payment') }}">
                            <i class="bi bi-cash-coin"></i>
                        </a>
                        @endif
                        @if($bill->status !== 'void')
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                data-bs-toggle="modal" data-bs-target="#voidBillModal{{ $bill->id }}">
                            <i class="bi bi-x-circle"></i>
                        </button>
                        @endif
                    </td>
                </tr>

                @if($bill->status !== 'void')
                <div class="modal fade" id="voidBillModal{{ $bill->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" action="{{ route('labor.bills.void', $bill) }}">
                            @csrf
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">{{ __('Void Bill') }} {{ $bill->bill_no }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <label class="form-label">{{ __('Reason') }}</label>
                                    <input type="text" name="void_reason" class="form-control" required autofocus>
                                    <div class="form-text">{{ __('The bill stays on record for audit — it just gets marked void.') }}</div>
                                    @if($bill->isItemized())
                                    <div class="form-text">{{ __('Its items go back to "not billed yet" and can be billed again.') }}</div>
                                    @endif
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                    <button type="submit" class="btn btn-danger">{{ __('Void') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endif
                @empty
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">{{ __('No bills generated yet.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($bills->hasPages())
    <div class="card-footer bg-white">
        {{ $bills->links() }}
    </div>
    @endif
</div>

<div class="modal fade" id="generateBillModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('labor.bills.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Generate Bill') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Team') }}</label>
                        <select name="labor_team_id" class="form-select" required>
                            <option value="">-- {{ __('Select') }} --</option>
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">{{ __('Period Start') }}</label>
                            <input type="date" name="period_start" class="form-control" value="{{ now()->startOfMonth()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">{{ __('Period End') }}</label>
                            <input type="date" name="period_end" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Billing Header (optional override)') }}</label>
                        <select name="financial_profile_id" class="form-select">
                            <option value="">-- {{ __('Use current default') }} --</option>
                            @foreach($profiles as $profile)
                                <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-light border small mb-0">
                        {{ __('Includes every ledger entry dated within the period, plus the balance carried forward from before it. Charges are not "closed" by billing — they stay outstanding until an actual payment is recorded.') }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Generate') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Bill selected items: filter not-yet-billed charges, tick the ones to bill (one bill per team) --}}
<div class="modal fade" id="itemizedBillModal" tabindex="-1" x-data="itemizedBill()" x-init="init()">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="POST" action="{{ route('labor.bills.itemized.store') }}" class="modal-content" @submit="submitting = true">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-ui-checks me-1"></i>{{ __('Bill Selected Items') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 align-items-end mb-2">
                    <div class="col-md-3">
                        <label class="form-label small">{{ __('Team') }}</label>
                        <select class="form-select" x-model="teamId" @change="load()">
                            <option value="">-- {{ __('All Teams') }} --</option>
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small d-block">{{ __('Billing status') }}</label>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn" :class="status === 'unbilled' ? 'btn-primary' : 'btn-outline-secondary'" @click="status = 'unbilled'; load()">{{ __('Not billed yet') }}</button>
                            <button type="button" class="btn" :class="status === 'billed' ? 'btn-primary' : 'btn-outline-secondary'" @click="status = 'billed'; load()">{{ __('Billed') }}</button>
                            <button type="button" class="btn" :class="status === 'all' ? 'btn-primary' : 'btn-outline-secondary'" @click="status = 'all'; load()">{{ __('All') }}</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small d-block">{{ __('Date recorded') }}</label>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn" :class="mode === 'all' ? 'btn-primary' : 'btn-outline-secondary'" @click="mode = 'all'; load()">{{ __('All') }}</button>
                                <button type="button" class="btn" :class="mode === 'month' ? 'btn-primary' : 'btn-outline-secondary'" @click="mode = 'month'; load()">{{ __('Monthly') }}</button>
                                <button type="button" class="btn" :class="mode === 'day' ? 'btn-primary' : 'btn-outline-secondary'" @click="mode = 'day'; load()">{{ __('Daily') }}</button>
                                <button type="button" class="btn" :class="mode === 'range' ? 'btn-primary' : 'btn-outline-secondary'" @click="mode = 'range'; load()">{{ __('Date range') }}</button>
                            </div>
                            <input type="month" class="form-control form-control-sm w-auto" x-show="mode === 'month'" x-model="month" @change="load()">
                            <input type="date" class="form-control form-control-sm w-auto" x-show="mode === 'day'" x-model="date" @change="load()">
                            <span class="d-inline-flex align-items-center gap-1" x-show="mode === 'range'">
                                <input type="date" class="form-control form-control-sm w-auto" x-model="from" @change="load()" aria-label="{{ __('From date') }}">
                                <span class="small">{{ __('to') }}</span>
                                <input type="date" class="form-control form-control-sm w-auto" x-model="to" @change="load()" aria-label="{{ __('To date') }}">
                            </span>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small d-block mb-1">{{ __('Charge types (none ticked = all types)') }}</label>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach($chargeTypes as $type)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="itemizedType{{ $type->id }}" value="{{ $type->id }}" x-model="typeIds" @change="load()">
                                <label class="form-check-label" for="itemizedType{{ $type->id }}">{{ $type->name }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-top pt-2 mb-2">
                    <div class="small">
                        <span x-show="loading" class="text-muted"><span class="spinner-border spinner-border-sm me-1"></span>{{ __('Loading...') }}</span>
                        <span x-show="!loading">
                            {{ __('Not billed yet') }}: <strong x-text="billable.length"></strong> {{ __('items') }}
                            = <strong x-text="money(sum(billable))"></strong>
                            <template x-if="status !== 'unbilled'">
                                <span>· {{ __('Billed') }}: <strong x-text="entries.length - billable.length"></strong> {{ __('items') }}
                                = <strong x-text="money(sum(entries) - sum(billable))"></strong></span>
                            </template>
                            <br>{{ __('Selected') }}: <strong x-text="selected.length"></strong>
                            = <strong x-text="money(selectedTotal)"></strong> {{ __('THB') }}
                            <span x-show="selectedTeams > 0" class="text-muted">(<span x-text="selectedTeams"></span> {{ __('bill(s), one per team') }})</span>
                        </span>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" @click="selected = billable.map(e => e.id)">{{ __('Select all') }}</button>
                        <button type="button" class="btn btn-outline-secondary" @click="selected = []">{{ __('Clear selection') }}</button>
                    </div>
                </div>
                <div class="alert alert-warning small py-2" x-show="truncated">{{ __('Showing the first 1,000 items — narrow the filter to see the rest.') }}</div>

                <div class="table-responsive border rounded">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:2rem"><input type="checkbox" class="form-check-input" :disabled="!billable.length" :checked="billable.length && selected.length === billable.length" @change="selected = $event.target.checked ? billable.map(e => e.id) : []"></th>
                                <th>{{ __('Team') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Charge Type') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="e in entries" :key="e.id">
                                <tr @click="if (!e.bill_no && $event.target.type !== 'checkbox' && $event.target.tagName !== 'A') toggle(e.id)" :style="e.bill_no ? '' : 'cursor:pointer'" :class="e.bill_no ? 'text-body-secondary' : ''">
                                    <td><input type="checkbox" class="form-check-input" :value="e.id" x-model.number="selected" :disabled="!!e.bill_no"></td>
                                    <td class="small" x-text="e.team"></td>
                                    <td class="small text-nowrap" x-text="e.date"></td>
                                    <td class="small">
                                        <span x-text="e.description"></span>
                                        <div class="text-muted" x-show="e.member" x-text="e.member"></div>
                                    </td>
                                    <td class="small" x-text="e.charge_type || '-'"></td>
                                    <td class="small text-nowrap">
                                        <template x-if="e.bill_no">
                                            <a :href="e.bill_url" target="_blank" class="badge bg-success-subtle text-success-emphasis text-decoration-none" x-text="'{{ __('Billed') }} ' + e.bill_no"></a>
                                        </template>
                                        <template x-if="!e.bill_no">
                                            <span class="badge bg-warning-subtle text-warning-emphasis">{{ __('Not billed yet') }}</span>
                                        </template>
                                    </td>
                                    <td class="text-end" x-text="money(e.amount)"></td>
                                </tr>
                            </template>
                            <tr x-show="!loading && entries.length === 0">
                                <td colspan="7" class="text-center text-muted py-4">{{ __('No charges match this filter.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="entry_ids[]" :value="id">
                </template>
                <template x-for="id in typeIds" :key="'t' + id">
                    <input type="hidden" name="charge_type_ids[]" :value="id">
                </template>

                <div class="row g-2 mt-2">
                    <div class="col-md-6">
                        <label class="form-label small">{{ __('Billing Header (optional override)') }}</label>
                        <select name="financial_profile_id" class="form-select form-select-sm">
                            <option value="">-- {{ __('Use current default') }} --</option>
                            @foreach($profiles as $profile)
                                <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 small text-muted d-flex align-items-end">
                        {{ __('Only the ticked items are billed, without a brought-forward balance. Billed items can be viewed but not ticked again unless their bill is voided.') }}
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary" :disabled="selected.length === 0 || submitting">
                    <i class="bi bi-file-earmark-plus me-1"></i>{{ __('Generate') }} (<span x-text="selectedTeams"></span>)
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Monthly charge bill: tick team members, enter amount + description; one bill / PDF per team --}}
<div class="modal fade" id="monthlyBillModal" tabindex="-1" x-data="monthlyBill()" x-init="init()">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="POST" action="{{ route('labor.bills.monthly.store') }}" class="modal-content" @submit="submitting = true">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-calendar2-month me-1"></i>{{ __('Monthly Charge Bill') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3">
                        <label class="form-label small">{{ __('Month') }}</label>
                        <input type="month" name="month" class="form-control" x-model="month" @change="loadDefaults()" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small">{{ __('Charge Type') }}</label>
                        {{-- Typed freely (not the configured charge types); recent headings offered as suggestions. --}}
                        <input type="text" name="title" class="form-control" x-model="title" list="monthlyBillTitles" maxlength="150"
                               placeholder="{{ __('e.g. Flight booking — October batch') }}" autocomplete="off" required>
                        <datalist id="monthlyBillTitles">
                            <template x-for="t in titles" :key="t"><option :value="t"></option></template>
                        </datalist>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small">{{ __('Entry Date') }}</label>
                        <input type="date" name="entry_date" class="form-control" x-model="entryDate" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small">{{ __('Billing Header (optional override)') }}</label>
                        <select name="financial_profile_id" class="form-select">
                            <option value="">-- {{ __('Use current default') }} --</option>
                            @foreach($profiles as $profile)
                                <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <input type="search" class="form-control form-control-sm" style="max-width:260px" placeholder="{{ __('Search name or team...') }}" x-model="search">
                    <div class="small">
                        <span x-show="loading" class="text-muted me-2"><span class="spinner-border spinner-border-sm me-1"></span>{{ __('Loading...') }}</span>
                        {{ __('Selected') }}: <strong x-text="selectedRows.length"></strong> {{ __('people') }}
                        <span x-show="editCount" class="text-warning-emphasis">(<span x-text="editCount"></span> {{ __('edits to issued bills') }})</span>
                        = <strong x-text="money(selectedTotal)"></strong> {{ __('THB') }}
                        · <strong x-text="selectedTeamCount"></strong> {{ __('bill(s), one per team') }}
                    </div>
                </div>
                <div class="alert alert-light border small py-2">{{ __('Type the expense heading yourself. Each person\'s amount and description from their latest monthly bill are filled in for you and stay until you change them — usually only the month and date need changing.') }}<br>{{ __('Already on this month\'s bill under the same heading? Change the amount / description (or add people not billed yet) and save — the same bill (same number) is updated, no second bill is made.') }}</div>

                @if($memberTeams->isEmpty())
                    <div class="text-center text-muted py-4">{{ __('No active team members yet — add them under Team Members first.') }}</div>
                @endif

                <template x-for="team in teams" :key="team.id">
                    <div class="border rounded mb-2" x-show="visibleMembers(team).length">
                        <div class="d-flex justify-content-between align-items-center px-2 py-1 bg-body-tertiary border-bottom">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" :id="'mTeam' + team.id" :checked="teamAllChecked(team)" @change="toggleTeam(team, $event.target.checked)">
                                <label class="form-check-label fw-semibold" :for="'mTeam' + team.id" x-text="team.name"></label>
                            </div>
                            <span class="small text-muted"><span x-text="teamCount(team)"></span> {{ __('people') }} · <span x-text="money(teamTotal(team))"></span></span>
                        </div>
                        <template x-for="m in visibleMembers(team)" :key="m.id">
                            <div class="row g-2 align-items-center px-2 py-1 border-bottom mx-0">
                                <div class="col-md-3">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" :id="'mMember' + m.id" x-model="rows[m.id].checked">
                                        <label class="form-check-label" :for="'mMember' + m.id" x-text="m.name"></label>
                                    </div>
                                    <span class="badge bg-warning-subtle text-warning-emphasis text-wrap text-start" style="line-height:1.35" x-show="billedNo(rows[m.id]) && !billedLocked(rows[m.id])"
                                          x-text="'{{ __('Already billed this month') }}: ' + billedNo(rows[m.id]) + ' · ' + '{{ __('edit and save to update this bill') }}'"></span>
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis text-wrap text-start" style="line-height:1.35" x-show="billedLocked(rows[m.id])"
                                          x-text="'{{ __('Already billed this month') }}: ' + billedNo(rows[m.id]) + ' · ' + '{{ __('paid / tax invoice issued — cannot be changed') }}'"></span>
                                </div>
                                <div class="col-4 col-md-2">
                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end" placeholder="{{ __('Amount') }}"
                                           x-model="rows[m.id].amount" :disabled="billedLocked(rows[m.id])" @input="if (rows[m.id].amount !== '') rows[m.id].checked = true">
                                </div>
                                <div class="col-8 col-md-7">
                                    <input type="text" maxlength="255" class="form-control form-control-sm" placeholder="{{ __('Description') }}"
                                           x-model="rows[m.id].description" :disabled="billedLocked(rows[m.id])" @input="if (rows[m.id].amount !== '') rows[m.id].checked = true">
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-for="(r, i) in selectedRows" :key="r.id">
                    <span>
                        <input type="hidden" :name="'rows[' + i + '][member_id]'" :value="r.id">
                        <input type="hidden" :name="'rows[' + i + '][amount]'" :value="r.amount">
                        <input type="hidden" :name="'rows[' + i + '][description]'" :value="r.description">
                    </span>
                </template>
                <div class="small text-muted mt-2">{{ __('Each ticked person becomes a charge under this heading on their team\'s balance, and each team gets its own bill and PDF. People without an amount are left out.') }}</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary" :disabled="!title.trim() || selectedRows.length === 0 || submitting">
                    <i class="bi bi-file-earmark-plus me-1"></i>{{ __('Generate') }} (<span x-text="selectedTeamCount"></span>)
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function billMoney(n) {
        return Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function itemizedBill() {
        return {
            teamId: '', typeIds: [], mode: 'month', status: 'unbilled',
            month: @json(now()->format('Y-m')), date: @json(now()->format('Y-m-d')),
            from: @json(now()->startOfMonth()->format('Y-m-d')), to: @json(now()->format('Y-m-d')),
            entries: [], selected: [], loading: false, truncated: false, submitting: false, loaded: false,
            init() {
                const modal = document.getElementById('itemizedBillModal');
                modal.addEventListener('show.bs.modal', () => { if (!this.loaded) this.load(); });
                window.addEventListener('open-itemized-bill', (ev) => {
                    this.teamId = String(ev.detail.teamId || '');
                    this.mode = 'all';
                    this.status = 'unbilled';
                    this.load();
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                });
            },
            money: billMoney,
            sum(list) { return list.reduce((s, e) => s + e.amount, 0); },
            get billable() { return this.entries.filter(e => !e.bill_no); },
            toggle(id) {
                const i = this.selected.indexOf(id);
                i === -1 ? this.selected.push(id) : this.selected.splice(i, 1);
            },
            get selectedTotal() {
                const ids = new Set(this.selected);
                return this.entries.filter(e => ids.has(e.id)).reduce((s, e) => s + e.amount, 0);
            },
            get selectedTeams() {
                const ids = new Set(this.selected);
                return new Set(this.entries.filter(e => ids.has(e.id)).map(e => e.team_id)).size;
            },
            load() {
                this.loading = true;
                this.loaded = true;
                const params = new URLSearchParams({ mode: this.mode, status: this.status });
                if (this.teamId) params.append('team_id', this.teamId);
                if (this.mode === 'month' && this.month) params.append('month', this.month);
                if (this.mode === 'day' && this.date) params.append('date', this.date);
                if (this.mode === 'range' && this.from) params.append('from', this.from);
                if (this.mode === 'range' && this.to) params.append('to', this.to);
                this.typeIds.forEach(id => params.append('charge_type_ids[]', id));
                fetch(@json(route('labor.bills.unbilled')) + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
                    .then(r => r.ok ? r.json() : Promise.reject(r))
                    .then(data => {
                        this.entries = data.entries;
                        this.truncated = data.truncated;
                        // Tick every billable row by default; untick what shouldn't go on this bill.
                        this.selected = data.entries.filter(e => !e.bill_no).map(e => e.id);
                    })
                    .catch(() => appToast(@json(__('Could not load the items. Please try again.')), 'error'))
                    .finally(() => { this.loading = false; });
            },
        };
    }

    function monthlyBill() {
        const teams = @json($memberTeams);
        const rows = {};
        teams.forEach(t => t.members.forEach(m => {
            rows[m.id] = { id: m.id, teamId: t.id, name: m.name, checked: false, amount: '', description: '', billed: [] };
        }));
        const descTemplate = @json(__(':type for :month'));
        const locale = @json(app()->getLocale());
        const norm = v => String(v || '').trim().replace(/\s+/g, ' ').toLowerCase();

        return {
            teams, rows, search: '', loading: false, submitting: false, loaded: false,
            month: @json($monthlyDefaultMonth), title: '', titles: [], entryDate: @json(now()->format('Y-m-d')),
            init() {
                // Fill in last time's figures as soon as the window opens (once —
                // reopening keeps what was typed in the meantime).
                this.$el.addEventListener('show.bs.modal', () => { if (!this.loaded) this.loadDefaults(); });
                this.$watch('month', () => this.loadDefaults());
                this.$watch('title', () => {
                    Object.values(this.rows).forEach(r => this.applyBilled(r));
                });
            },
            money: billMoney,
            monthLabelOf(ym) {
                const [y, m] = String(ym || '').split('-').map(Number);
                if (!y || !m) return ym || '';
                try {
                    return new Date(y, m - 1, 1).toLocaleDateString(locale === 'th' ? 'th-TH' : locale, { month: 'long', year: 'numeric' });
                } catch (e) { return ym; }
            },
            monthLabel() { return this.monthLabelOf(this.month); },
            defaultDescription() {
                return descTemplate.replace(':type', this.title.trim()).replace(':month', this.monthLabel());
            },
            // Already on a monthly bill of the chosen month with the heading being typed.
            billedEntry(r) { return (r.billed || []).find(b => norm(b.title) === norm(this.title)) || null; },
            billedNo(r) { const hit = this.billedEntry(r); return hit ? hit.bill_no : null; },
            billedLocked(r) { const hit = this.billedEntry(r); return !!(hit && hit.locked); },
            // Someone already on this month's bill: show the bill's current figures,
            // unticked — editing them ticks the row, and saving updates that same bill.
            applyBilled(r) {
                const hit = this.billedEntry(r);
                if (hit) {
                    r.amount = hit.amount;
                    r.description = hit.description || '';
                    r.checked = false;
                } else if (r.checked === false && r.amount !== '' && r._wasBilled) {
                    r.checked = true;
                }
                r._wasBilled = !!hit;
            },
            get editCount() { return this.selectedRows.filter(r => this.billedNo(r)).length; },
            visibleMembers(team) {
                const q = this.search.trim().toLowerCase();
                if (!q || team.name.toLowerCase().includes(q)) return team.members;
                return team.members.filter(m => m.name.toLowerCase().includes(q));
            },
            teamAllChecked(team) { return team.members.every(m => this.rows[m.id].checked); },
            toggleTeam(team, on) { this.visibleMembers(team).forEach(m => { this.rows[m.id].checked = on; }); },
            teamCount(team) { return team.members.filter(m => this.rows[m.id].checked).length; },
            teamTotal(team) { return team.members.filter(m => this.rows[m.id].checked).reduce((s, m) => s + Number(this.rows[m.id].amount || 0), 0); },
            get selectedRows() {
                return Object.values(this.rows).filter(r => r.checked && Number(r.amount) > 0 && !this.billedLocked(r))
                    .map(r => ({ ...r, description: (r.description || '').trim() || this.defaultDescription() }));
            },
            get selectedTotal() { return this.selectedRows.reduce((s, r) => s + Number(r.amount), 0); },
            get selectedTeamCount() { return new Set(this.selectedRows.map(r => r.teamId)).size; },
            loadDefaults() {
                if (!this.month) return;
                this.loading = true;
                const firstLoad = !this.loaded;
                const params = new URLSearchParams({ month: this.month });
                fetch(@json(route('labor.bills.monthly.defaults')) + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
                    .then(r => r.ok ? r.json() : Promise.reject(r))
                    .then(data => {
                        this.titles = data.titles || [];
                        if (!this.title.trim() && data.last_title) this.title = data.last_title;
                        const thisLabel = this.monthLabel();
                        Object.values(this.rows).forEach(r => {
                            const d = data.defaults[r.id] || null;
                            r.billed = d ? (d.billed || []) : [];
                            // Amounts / descriptions: filled from the latest bill on the first
                            // load; on a month change only the month name in the text moves.
                            if (firstLoad) {
                                r.amount = d && d.amount !== null ? d.amount : '';
                                r.description = d && d.description ? d.description.split(this.monthLabelOf(d.source_month)).join(thisLabel) : '';
                            } else if (r.description && r._descMonth) {
                                r.description = r.description.split(r._descMonth).join(thisLabel);
                            }
                            r._descMonth = thisLabel;
                            r.checked = r.amount !== '';
                            this.applyBilled(r);
                        });
                        this.loaded = true;
                    })
                    .catch(() => appToast(@json(__('Could not load the items. Please try again.')), 'error'))
                    .finally(() => { this.loading = false; });
            },
        };
    }
</script>
@endpush

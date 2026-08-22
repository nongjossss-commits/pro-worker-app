{{--
    Shared "รายงาน" content — period-based Account Balances + itemized
    Category Breakdown + PDF/Excel export. Used standalone by
    financial/books-reports/index.blade.php (kept for bookmarks) AND as the
    "รายงาน" tab in financial/books/index.blade.php.

    Expects: $accounts (FinanceBookReportService::accountBalancesForRange),
    $categoryTransactions (::categoryTransactions), $from, $to,
    $activePeriod, $activeDate.
--}}
<div class="d-flex justify-content-end gap-2 mb-3">
    <a href="{{ route('finance.books-reports.pdf', ['period' => $activePeriod, 'date' => $activeDate->format('Y-m-d')]) }}" target="_blank" class="btn btn-outline-danger">
        <i class="bi bi-file-earmark-pdf me-1"></i>{{ __('Export PDF') }}
    </a>
    <a href="{{ route('finance.books-reports.export', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" class="btn btn-outline-success">
        <i class="bi bi-file-earmark-excel me-1"></i>{{ __('Export Excel') }}
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ $reportFormAction }}" id="reportFilterForm" class="row g-2 align-items-end">
            <input type="hidden" name="period" id="report-period" value="{{ $activePeriod }}">
            @if(!empty($isEmbeddedTab))
                <input type="hidden" name="tab" value="report">
            @endif
            <div class="col-auto">
                <label class="form-label small">{{ __('Date') }}</label>
                <input type="text" name="date" id="report-date" class="form-control form-control-sm js-flatpickr" value="{{ $activeDate->format('Y-m-d') }}" autocomplete="off">
            </div>
            <div class="col-auto">
                <label class="form-label small d-block">&nbsp;</label>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-nav="prev" title="{{ __('Previous period') }}"><i class="bi bi-chevron-left"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-nav="next" title="{{ __('Next period') }}"><i class="bi bi-chevron-right"></i></button>
                </div>
            </div>
            <div class="col-auto ms-2 border-start ps-3">
                <label class="form-label small d-block">&nbsp;</label>
                @foreach(['day' => __('Day'), 'week' => __('Week'), 'month' => __('Month'), 'quarter' => __('Quarter'), 'year' => __('Year')] as $key => $label)
                    <button type="button" class="btn btn-sm {{ $activePeriod === $key ? 'btn-primary' : 'btn-outline-secondary' }}" data-period="{{ $key }}">{{ $label }}</button>
                @endforeach
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;background:rgba(var(--bs-success-rgb),.12);">
                    <i class="bi bi-wallet2 fs-5" style="color:var(--bs-success);"></i>
                </div>
                <div class="min-w-0">
                    <div class="text-muted small text-uppercase fw-bold">{{ __('Opening Balance') }}</div>
                    <div class="fs-5 fw-bold mb-0 text-truncate">{{ number_format($accounts->totals->opening_balance, 2) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;background:rgba(var(--bs-success-rgb),.12);">
                    <i class="bi bi-arrow-down-circle fs-5 text-success"></i>
                </div>
                <div class="min-w-0">
                    <div class="text-muted small text-uppercase fw-bold">{{ __('Income This Period') }}</div>
                    <div class="fs-5 fw-bold mb-0 text-success text-truncate">{{ number_format($accounts->totals->income, 2) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;background:rgba(220,53,69,.12);">
                    <i class="bi bi-arrow-up-circle fs-5 text-danger"></i>
                </div>
                <div class="min-w-0">
                    <div class="text-muted small text-uppercase fw-bold">{{ __('Expense This Period') }}</div>
                    <div class="fs-5 fw-bold mb-0 text-danger text-truncate">{{ number_format($accounts->totals->expense, 2) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm border-0 h-100" style="background-image: linear-gradient(to right, #22C55E, var(--bs-success));">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;background:rgba(255,255,255,.2);">
                    <i class="bi bi-piggy-bank fs-5 text-white"></i>
                </div>
                <div class="min-w-0">
                    <div class="text-white-50 small text-uppercase fw-bold">{{ __('Closing Balance') }}</div>
                    <div class="fs-5 fw-bold mb-0 text-white text-truncate">{{ number_format($accounts->totals->closing_balance, 2) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4 border-start border-4 border-success">
    <div class="card-header d-flex align-items-center gap-2" style="background-color: rgba(var(--bs-success-rgb), .06);">
        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:34px;height:34px;background:rgba(var(--bs-success-rgb),.15);">
            <i class="bi bi-bank" style="color:var(--bs-success);"></i>
        </div>
        <div class="min-w-0">
            <h6 class="mb-0 fw-bold">{{ __('Account Balances') }}</h6>
            <div class="text-muted small">{{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover table-striped align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Account') }}</th>
                    <th>{{ __('Bank') }}</th>
                    <th class="text-end">{{ __('Opening Balance') }}</th>
                    <th class="text-end">{{ __('Income') }}</th>
                    <th class="text-end">{{ __('Expense') }}</th>
                    <th class="text-end">{{ __('Closing Balance') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts->rows as $r)
                <tr>
                    <td class="fw-semibold">{{ $r->account->account_name ?: $r->account->bank_name }}</td>
                    <td class="text-muted small">{{ $r->account->bank_name ?: '-' }}</td>
                    <td class="text-end">{{ number_format($r->opening_balance, 2) }}</td>
                    <td class="text-end text-success">{{ number_format($r->income, 2) }}</td>
                    <td class="text-end text-danger">{{ number_format($r->expense, 2) }}</td>
                    <td class="text-end fw-bold {{ $r->closing_balance < 0 ? 'text-danger' : '' }}">{{ number_format($r->closing_balance, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">{{ __('No book accounts yet.') }}</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="table-light fw-bold" style="border-top: 2px solid var(--bs-border-color);">
                    <td colspan="2">{{ __('Total') }}</td>
                    <td class="text-end">{{ number_format($accounts->totals->opening_balance, 2) }}</td>
                    <td class="text-end">{{ number_format($accounts->totals->income, 2) }}</td>
                    <td class="text-end">{{ number_format($accounts->totals->expense, 2) }}</td>
                    <td class="text-end">{{ number_format($accounts->totals->closing_balance, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="card shadow-sm mb-4 border-start border-4 border-success">
    <div class="card-header d-flex align-items-center gap-2" style="background-color: rgba(var(--bs-success-rgb), .06);">
        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:34px;height:34px;background:rgba(var(--bs-success-rgb),.15);">
            <i class="bi bi-list-check" style="color:var(--bs-success);"></i>
        </div>
        <div class="min-w-0">
            <h6 class="mb-0 fw-bold">{{ __('Category Breakdown') }} <span class="fw-normal text-muted">({{ __('itemized') }})</span></h6>
            <div class="text-muted small">{{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}</div>
        </div>
    </div>
    @php
        // Per-account income/expense for the selected period — already
        // computed above for the Account Balances section, reused here
        // (no extra queries) so hovering an account name shows that
        // account's period totals instantly.
        $accountStatsById = collect($accounts->rows)->mapWithKeys(function ($r) {
            return [$r->account->id => [
                'name' => $r->account->account_name ?: $r->account->bank_name,
                'bank' => $r->account->bank_name,
                'income' => $r->income,
                'expense' => $r->expense,
            ]];
        });
    @endphp
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th>{{ __('Account') }}</th>
                    <th class="text-end">{{ __('Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categoryTransactions->groups as $group)
                <tr class="table-light" style="border-top: 2px solid var(--bs-border-color);">
                    <td colspan="3">
                        @if($group->type === 'income')
                            <span class="badge bg-success me-2">{{ __('Income') }}</span>
                        @else
                            <span class="badge bg-danger me-2">{{ __('Expense') }}</span>
                        @endif
                        <strong>{{ $group->label }}</strong>
                    </td>
                    <td class="text-end fw-bold">{{ number_format($group->subtotal, 2) }}</td>
                </tr>
                @foreach($group->items as $item)
                @php
                    $stats = $item->bankAccount ? ($accountStatsById[$item->bankAccount->id] ?? null) : null;
                @endphp
                <tr>
                    <td class="ps-4 text-muted small">{{ $item->entry_date->format('d/m/Y') }}</td>
                    <td class="text-muted small">{{ $item->description }}</td>
                    <td class="small">
                        @if($item->bankAccount)
                            <span class="account-hover-trigger text-decoration-underline-dotted" role="button" tabindex="0"
                                data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-html="true" data-bs-placement="top"
                                data-bs-custom-class="account-hover-popover"
                                data-bs-title="{{ $item->bankAccount->account_name ?: $item->bankAccount->bank_name }}{{ $item->bankAccount->bank_name ? ' ('.$item->bankAccount->bank_name.')' : '' }}"
                                data-bs-content="<div class='d-flex justify-content-between gap-3'><span class='text-success'>{{ __('Income') }}</span><span class='fw-bold text-success'>{{ number_format($stats['income'] ?? 0, 2) }}</span></div><div class='d-flex justify-content-between gap-3'><span class='text-danger'>{{ __('Expense') }}</span><span class='fw-bold text-danger'>{{ number_format($stats['expense'] ?? 0, 2) }}</span></div>">
                                <i class="bi bi-bank text-muted me-1"></i>{{ $item->bankAccount->account_name ?: $item->bankAccount->bank_name }}
                            </span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-end small">{{ number_format($item->net_amount, 2) }}</td>
                </tr>
                @endforeach
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">{{ __('No transactions in this period.') }}</td>
                </tr>
                @endforelse
            </tbody>
            @if($categoryTransactions->groups->isNotEmpty())
            <tfoot>
                <tr class="border-top">
                    <td colspan="3" class="text-muted">{{ __('Total Income') }}</td>
                    <td class="text-end text-success">{{ number_format($categoryTransactions->income_total, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="3" class="text-muted">{{ __('Total Expense') }}</td>
                    <td class="text-end text-danger">{{ number_format($categoryTransactions->expense_total, 2) }}</td>
                </tr>
                <tr class="fw-bold border-top border-2">
                    <td colspan="3">{{ __('Grand Total') }}</td>
                    <td class="text-end {{ $categoryTransactions->net >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($categoryTransactions->net, 2) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@push('styles')
<style>
    .account-hover-trigger { cursor: pointer; }
    .text-decoration-underline-dotted { text-decoration: underline dotted; text-underline-offset: 3px; }

    .popover.account-hover-popover {
        animation: accountPopoverPop .16s ease-out;
        --bs-popover-max-width: 220px;
    }
    @keyframes accountPopoverPop {
        from { transform: scale(.85); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('reportFilterForm');
    const periodInput = document.getElementById('report-period');
    const dateInput = document.getElementById('report-date');

    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
        new bootstrap.Popover(el, { delay: { show: 1500, hide: 100 } });
    });

    document.querySelectorAll('[data-period]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            periodInput.value = btn.dataset.period;
            form.submit();
        });
    });

    document.querySelectorAll('[data-nav]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const period = periodInput.value;
            const d = new Date(dateInput.value + 'T00:00:00');
            const dir = btn.dataset.nav === 'prev' ? -1 : 1;

            switch (period) {
                case 'day':
                    d.setDate(d.getDate() + dir);
                    break;
                case 'week':
                    d.setDate(d.getDate() + (dir * 7));
                    break;
                case 'month':
                    d.setMonth(d.getMonth() + dir);
                    break;
                case 'quarter':
                    d.setMonth(d.getMonth() + (dir * 3));
                    break;
                case 'year':
                    d.setFullYear(d.getFullYear() + dir);
                    break;
            }

            const fmt = (x) => x.toISOString().slice(0, 10);
            dateInput.value = fmt(d);
            if (dateInput._flatpickr) {
                dateInput._flatpickr.setDate(d, false);
            }
            form.submit();
        });
    });
});
</script>
@endpush

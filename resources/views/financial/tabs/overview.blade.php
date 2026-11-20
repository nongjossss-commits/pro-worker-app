{{-- Stats Cards --}}
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        {{-- Click = list the bills behind this number; click again = clear. --}}
        <a href="{{ request('card') === 'income_today' ? route('finance.index', ['tab' => 'overview']) : route('finance.index', ['tab' => 'overview', 'card' => 'income_today']) }}"
           class="text-decoration-none" title="{{ __('Click to list these bills') }}">
        <div class="card border-start border-primary border-4 shadow h-100 py-2 {{ request('card') === 'income_today' ? 'bg-primary-subtle' : '' }}">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="small fw-bold text-primary text-uppercase mb-1">
                            {{ __('Income Today') }}</div>
                        <div class="h5 mb-0 fw-bold text-dark">{{ number_format($stats['income_today'], 2) }}</div>
                        @if(request('card') === 'income_today')
                            <div class="small text-muted mt-1"><i class="bi bi-funnel-fill"></i> {{ __('Filtering — click again to clear') }}</div>
                        @endif
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-calendar-day fs-2 text-secondary"></i>
                    </div>
                </div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        {{-- Click = list the bills behind this number; click again = clear. --}}
        <a href="{{ request('card') === 'income_month' ? route('finance.index', ['tab' => 'overview']) : route('finance.index', ['tab' => 'overview', 'card' => 'income_month']) }}"
           class="text-decoration-none" title="{{ __('Click to list these bills') }}">
        <div class="card border-start border-success border-4 shadow h-100 py-2 {{ request('card') === 'income_month' ? 'bg-success-subtle' : '' }}">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="small fw-bold text-success text-uppercase mb-1">
                            {{ __('Income This Month') }}</div>
                        <div class="h5 mb-0 fw-bold text-dark">{{ number_format($stats['income_month'], 2) }}</div>
                        @if(request('card') === 'income_month')
                            <div class="small text-muted mt-1"><i class="bi bi-funnel-fill"></i> {{ __('Filtering — click again to clear') }}</div>
                        @endif
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-calendar-month fs-2 text-secondary"></i>
                    </div>
                </div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        {{-- Click = list the bills behind this number; click again = clear. --}}
        <a href="{{ request('card') === 'pending' ? route('finance.index', ['tab' => 'overview']) : route('finance.index', ['tab' => 'overview', 'card' => 'pending']) }}"
           class="text-decoration-none" title="{{ __('Click to list these bills') }}">
        <div class="card border-start border-warning border-4 shadow h-100 py-2 {{ request('card') === 'pending' ? 'bg-warning-subtle' : '' }}">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="small fw-bold text-warning text-uppercase mb-1">
                            {{ __('Pending Amount') }}</div>
                        <div class="h5 mb-0 fw-bold text-dark">{{ number_format($stats['pending_amount'], 2) }}</div>
                        @if(request('card') === 'pending')
                            <div class="small text-muted mt-1"><i class="bi bi-funnel-fill"></i> {{ __('Filtering — click again to clear') }}</div>
                        @endif
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-clock-history fs-2 text-secondary"></i>
                    </div>
                </div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        {{-- Click = list the bills behind this number; click again = clear. --}}
        <a href="{{ request('card') === 'overdue' ? route('finance.index', ['tab' => 'overview']) : route('finance.index', ['tab' => 'overview', 'card' => 'overdue']) }}"
           class="text-decoration-none" title="{{ __('Click to list these bills') }}">
        <div class="card border-start border-danger border-4 shadow h-100 py-2 {{ request('card') === 'overdue' ? 'bg-danger-subtle' : '' }}">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="small fw-bold text-danger text-uppercase mb-1">
                            {{ __('Overdue Amount') }}</div>
                        <div class="h5 mb-0 fw-bold text-dark">{{ number_format($stats['overdue_amount'], 2) }}</div>
                        @if(request('card') === 'overdue')
                            <div class="small text-muted mt-1"><i class="bi bi-funnel-fill"></i> {{ __('Filtering — click again to clear') }}</div>
                        @endif
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-exclamation-circle fs-2 text-secondary"></i>
                    </div>
                </div>
            </div>
        </div>
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('finance.index') }}" method="GET" class="row g-3">
            <input type="hidden" name="tab" value="overview">
            @if(request('card'))
                <input type="hidden" name="card" value="{{ request('card') }}">
            @endif
            <div class="col-md-3">
                <x-live-search-filter
                    endpoint="{{ route('finance.preview_search_combined') }}"
                    name="employer_filter_name"
                    id-target="employer_filter_id"
                    id-value="{{ request('employer_filter_id') }}"
                    placeholder="{{ __('Search employer or bill (name, ID, bill #...)') }}"
                    value="{{ request('employer_filter_name') }}"
                    class="form-control"
                />
            </div>
            <div class="col-md-3">
                <x-live-search-filter
                    endpoint="{{ route('finance.preview_search', ['tab' => 'overview']) }}"
                    name="search"
                    placeholder="{{ request('employer_filter_id') ? __('Search within this employer...') : __('Pick an employer above to narrow further') }}"
                    value="{{ request('search') }}"
                    class="form-control"
                    :disabled="!request('employer_filter_id')"
                />
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">{{ __('All Statuses') }}</option>
                    <option value="pending"     {{ request('status') == 'pending'     ? 'selected' : '' }}>{{ __('Pending') }}</option>
                    <option value="partial"     {{ request('status') == 'partial'     ? 'selected' : '' }}>{{ __('Partial') }}</option>
                    <option value="paid"        {{ request('status') == 'paid'        ? 'selected' : '' }}>{{ __('Paid') }}</option>
                    <option value="overdue"     {{ request('status') == 'overdue'     ? 'selected' : '' }}>{{ __('Overdue') }}</option>
                    <option value="outstanding" {{ request('status') == 'outstanding' ? 'selected' : '' }}>{{ __('Outstanding (All Unpaid)') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="source" class="form-select">
                    <option value="">{{ __('All Sources') }}</option>
                    <option value="active_tab"  {{ request('source') == 'active_tab'  ? 'selected' : '' }}>{{ __('From Active Tabs') }}</option>
                    <option value="deleted_tab" {{ request('source') == 'deleted_tab' ? 'selected' : '' }}>{{ __('From Deleted Tabs') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" placeholder="{{ __('From Date') }}" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" placeholder="{{ __('To Date') }}" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-1 d-grid">
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i> {{ __('Filter') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Bills from deleted work tabs — still real receivables, so they stay in
     every total above; this only flags them so Finance can decide per bill
     (keep collecting, or issue a credit note for the remaining balance). --}}
@if(($stats['deleted_tab_open_count'] ?? 0) > 0 && request('source') !== 'deleted_tab')
<div class="alert alert-danger d-flex justify-content-between align-items-center mb-4">
    <div>
        <i class="bi bi-exclamation-triangle-fill me-1"></i>
        {{ __('There are :count unpaid bills totalling :amount from work tabs that have been deleted.', ['count' => number_format($stats['deleted_tab_open_count']), 'amount' => number_format($stats['deleted_tab_outstanding'], 2)]) }}
    </div>
    <a href="{{ route('finance.index', ['tab' => 'overview', 'source' => 'deleted_tab']) }}" class="btn btn-sm btn-danger">
        {{ __('View these bills') }}
    </a>
</div>
@endif

{{-- Filtered Summary — แสดงเมื่อมี filter active --}}
@if(!empty($filteredStats))
<div class="card border-info shadow-sm mb-4">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="m-0 fw-bold text-info">
                <i class="bi bi-funnel-fill me-1"></i>
                {{ __('Filtered Summary') }}
                @if(request('search'))
                    <span class="badge bg-info-subtle text-info ms-2">{{ __('Search') }}: "{{ request('search') }}"</span>
                @endif
                @if(request('status'))
                    @php
                        // Map internal filter value → translated display label.
                        $statusLabels = [
                            'pending'     => __('Pending'),
                            'partial'     => __('Partial'),
                            'paid'        => __('Paid'),
                            'overdue'     => __('Overdue'),
                            'outstanding' => __('Outstanding (All Unpaid)'),
                        ];
                    @endphp
                    <span class="badge bg-info-subtle text-info ms-1">{{ __('Status') }}: {{ $statusLabels[request('status')] ?? ucfirst(request('status')) }}</span>
                @endif
                @if(request('card'))
                    @php
                        $cardLabels = [
                            'income_today' => __('Income Today'),
                            'income_month' => __('Income This Month'),
                            'pending'      => __('Pending Amount'),
                            'overdue'      => __('Overdue Amount'),
                        ];
                    @endphp
                    <span class="badge bg-info-subtle text-info ms-1">{{ $cardLabels[request('card')] ?? request('card') }}</span>
                @endif
                @if(request('source'))
                    <span class="badge bg-info-subtle text-info ms-1">{{ __('Source') }}: {{ request('source') === 'deleted_tab' ? __('From Deleted Tabs') : __('From Active Tabs') }}</span>
                @endif
                @if(request('date_from') || request('date_to'))
                    <span class="badge bg-info-subtle text-info ms-1">
                        {{ request('date_from', '…') }} → {{ request('date_to', '…') }}
                    </span>
                @endif
            </h6>
            <a href="{{ route('finance.index', ['tab' => 'overview']) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-x-circle"></i> {{ __('Clear Filter') }}
            </a>
        </div>
        <div class="row g-2 text-center">
            <div class="col-6 col-md-3">
                <div class="border rounded p-2 bg-light">
                    <div class="small text-muted">{{ __('Total Bills') }}</div>
                    <div class="h5 mb-0 fw-bold text-dark">{{ number_format($filteredStats['total_count']) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded p-2 bg-light">
                    <div class="small text-muted">{{ __('Paid in Full') }}</div>
                    <div class="h5 mb-0 fw-bold text-success">{{ number_format($filteredStats['paid_count']) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded p-2 bg-light">
                    <div class="small text-muted">{{ __('Unpaid / Partial') }}</div>
                    <div class="h5 mb-0 fw-bold text-warning">{{ number_format($filteredStats['unpaid_count']) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded p-2 bg-light">
                    <div class="small text-muted">{{ __('Overdue') }}</div>
                    <div class="h5 mb-0 fw-bold text-danger">{{ number_format($filteredStats['overdue_count']) }}</div>
                </div>
            </div>
        </div>
        <div class="row g-2 text-center mt-1">
            <div class="col-md-4">
                <div class="border rounded p-2">
                    <div class="small text-muted">{{ __('Total Billed') }}</div>
                    <div class="h5 mb-0 fw-bold text-dark">{{ number_format($filteredStats['total_amount'], 2) }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-2">
                    <div class="small text-muted">{{ !empty($filteredStats['period_from']) ? __('Received in this period') : __('Received') }}</div>
                    <div class="h5 mb-0 fw-bold text-success">{{ number_format($filteredStats['total_paid'], 2) }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-2 {{ $filteredStats['total_outstanding'] > 0 ? 'bg-danger-subtle' : '' }}">
                    <div class="small text-muted">{{ __('Outstanding') }}</div>
                    <div class="h5 mb-0 fw-bold {{ $filteredStats['total_outstanding'] > 0 ? 'text-danger' : 'text-muted' }}">
                        {{ number_format($filteredStats['total_outstanding'], 2) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Transactions Table --}}
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">{{ __('Recent Transactions') }}</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table data-mobile-table="scroll" class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Bill No.') }}</th>
                        <th>{{ __('Employer / Project') }}</th>
                        <th>{{ __('Job Owner') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th class="text-end">{{ __('Amount') }}</th>
                        <th class="text-end">{{ __('Paid') }}</th>
                        <th class="text-end">{{ __('Outstanding') }}</th>
                        <th class="text-center">{{ __('Status') }}</th>
                        <th class="text-center">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $txn)
                    @php
                        // Subtract issued credit notes too — same formula as the stats cards
                        // above, so a bill whose remainder was credit-noted shows 0 here.
                        $outstanding = (float) $txn->amount - (float) $txn->paid_amount - (float) $txn->credit_amount;
                        $jobOwnerName = optional(optional(optional($txn->productionOrder)->employer)->jobOwner)->name;
                    @endphp
                    <tr>
                        <td>{{ $txn->created_at->format('d/m/Y') }}</td>
                        <td>
                            <a href="{{ route('production.edit', ['production' => $txn->production_order_id, 'tab' => 'financial']) }}" class="text-decoration-none">
                                #{{ $txn->production_order_id }}-{{ $txn->id }}
                            </a>
                        </td>
                        <td>
                            @if($txn->productionOrder && $txn->productionOrder->employer)
                                <div class="fw-bold">{{ $txn->productionOrder->employer->employerNameTh }}</div>
                                <div class="small text-muted">{{ $txn->productionOrder->project_name }}</div>
                                @include('financial.partials._source_badge', ['order' => $txn->productionOrder])
                            @else
                                <span class="text-muted">{{ __('Unknown') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($jobOwnerName)
                                <span class="badge bg-secondary-subtle text-dark border">{{ $jobOwnerName }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $txn->type)) }}</span>
                        </td>
                        <td class="text-end">{{ number_format($txn->amount, 2) }}</td>
                        <td class="text-end {{ $txn->paid_amount >= $txn->amount ? 'text-success' : 'text-warning' }}">
                            {{ number_format($txn->paid_amount, 2) }}
                            @if(in_array(request('card'), ['income_today', 'income_month'], true) && $txn->payments->isNotEmpty())
                                {{-- Each payment on this bill, so it's clear whether today's
                                     money cleared the bill or only part of it. --}}
                                <div class="small text-muted text-nowrap mt-1">
                                    @foreach($txn->payments as $pay)
                                        @php
                                            // Bold = counted in this card's period; faded = received in another period.
                                            $payDate = \Carbon\Carbon::parse($pay->paid_at)->toDateString();
                                            $inPeriod = !empty($filteredStats['period_from'])
                                                && $payDate >= $filteredStats['period_from'] && $payDate <= $filteredStats['period_to'];
                                        @endphp
                                        <div class="{{ $inPeriod ? 'fw-bold' : 'opacity-50' }}" title="{{ $pay->bankAccount ? $pay->bankAccount->bank_name . ' ' . $pay->bankAccount->account_number : __('No bank account') }}">
                                            {{ \Carbon\Carbon::parse($pay->paid_at)->format('d/m/Y') }}:
                                            <span class="{{ $inPeriod ? 'text-success' : '' }}">+{{ number_format($pay->amount, 2) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="text-end {{ $outstanding > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                            {{ number_format($outstanding, 2) }}
                        </td>
                        <td class="text-center">
                            @php
                                $statusClass = match($txn->status) {
                                    'paid' => 'success',
                                    'pending' => 'warning',
                                    'overdue' => 'danger',
                                    'partial' => 'info',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge bg-{{ $statusClass }}">{{ ucfirst($txn->status) }}</span>
                        </td>
                        <td class="text-center">
                            @php
                                $hasBalance = $outstanding > 0.005;
                                $hasAnyPayment = (float) $txn->paid_amount > 0.005;
                                $groupParam = $txn->production_financial_group_id
                                    ? '?group_id=' . $txn->production_financial_group_id . '&transaction_ids=' . $txn->id
                                    : '?transaction_ids=' . $txn->id;
                                $invoiceUrl = route('production.documents.show', [
                                    'id' => $txn->production_order_id,
                                    'type' => 'invoice',
                                ]) . $groupParam;
                                $reminderUrl = route('production.documents.show', [
                                    'id' => $txn->production_order_id,
                                    'type' => 'reminder',
                                ]) . $groupParam;
                            @endphp
                            <div class="btn-group btn-group-sm" role="group">
                                {{-- View: existing behaviour — jumps to finance edit page --}}
                                <a href="{{ route('production.edit', ['production' => $txn->production_order_id, 'tab' => 'financial']) }}"
                                   class="btn btn-outline-primary"
                                   title="{{ __('View Details') }}">
                                    <i class="bi bi-eye"></i>
                                </a>

                                {{-- Issue-bill quick actions.
                                     Each button now opens a shared Bill Options
                                     modal so the operator picks Variant (document
                                     only / with list / list only) and Payment
                                     Methods (bank accounts) without having to
                                     jump into the Manage Finance page. --}}
                                @if($hasBalance)
                                    <button type="button"
                                            class="btn btn-outline-warning js-issue-bill-btn"
                                            data-doc-type="invoice"
                                            data-base-url="{{ $invoiceUrl }}"
                                            data-txn-label="#{{ $txn->production_order_id }}-{{ $txn->id }}"
                                            data-employer="{{ $txn->productionOrder?->employer?->employerNameTh ?? '' }}"
                                            title="{{ __('Reissue Bill (Quick)') }}">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </button>
                                    @if($hasAnyPayment)
                                        <button type="button"
                                                class="btn btn-outline-danger js-issue-bill-btn"
                                                data-doc-type="reminder"
                                                data-base-url="{{ $reminderUrl }}"
                                                data-txn-label="#{{ $txn->production_order_id }}-{{ $txn->id }}"
                                                data-employer="{{ $txn->productionOrder?->employer?->employerNameTh ?? '' }}"
                                                title="{{ __('Reminder Bill (Outstanding Only)') }}">
                                            <i class="bi bi-exclamation-square"></i>
                                        </button>
                                    @endif
                                @endif

                                {{-- Quick payment update — only when balance remains --}}
                                @if($hasBalance)
                                    <button type="button"
                                            class="btn btn-outline-success js-quick-pay-btn"
                                            data-txn-id="{{ $txn->id }}"
                                            data-txn-label="#{{ $txn->production_order_id }}-{{ $txn->id }}"
                                            data-outstanding="{{ number_format($outstanding, 2, '.', '') }}"
                                            data-amount="{{ number_format($txn->amount, 2, '.', '') }}"
                                            data-paid="{{ number_format($txn->paid_amount, 2, '.', '') }}"
                                            data-employer="{{ $txn->productionOrder?->employer?->employerNameTh ?? '' }}"
                                            title="{{ __('Record Payment (Quick)') }}">
                                        <i class="bi bi-cash-coin"></i>
                                    </button>
                                @endif

                                @if($txn->slip_path)
                                    <a href="#" onclick="event.preventDefault(); viewPDF('{{ asset('storage/' . $txn->slip_path) }}', '{{ __('View Slip') }}')"
                                       class="btn btn-outline-secondary"
                                       title="{{ __('View Slip') }}">
                                        <i class="bi bi-receipt"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">{{ __('No transactions found.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mt-3">
            {{ $transactions->links() }}
        </div>
    </div>
</div>

{{-- =============================================================
     Quick Payment — the bill's own "Payment History & Details"
     Every "cash-coin" button in the Recent Transactions table opens
     the SAME Payment History & Details screen as the bill's View
     page (production/{id}/edit?tab=financial): lump sum or
     individual collection, edit employees / prices, documents — so
     both places work exactly alike (2026-11-21). The panel is loaded
     on click (ProductionController::financePanel) into a hidden host;
     only its Payment History popup is shown. The page reloads when
     that popup closes so the row totals are fresh.
     ============================================================= --}}
@php
    // Company bank accounts for the Issue Bill Options modal below. Silently
    // skip if the table isn't there yet on a fresh install.
    try {
        $qpBanks = \App\Models\BankAccount::orderBy('bank_name')->get(['id','bank_name','bank_code','account_number','account_name','account_type']);
    } catch (\Throwable $e) {
        $qpBanks = collect();
    }
@endphp
<div id="qpPanelHost" style="position:absolute; width:0; height:0; overflow:visible;"></div>
<div class="modal fade" id="qpLoadingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4" id="qpLoadingBody">
                <div class="spinner-border text-success" role="status"></div>
                <div class="small text-muted mt-2">{{ __('Opening payment history...') }}</div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    let fmScript = null;
    function ensureFinancialManager() {
        if (!fmScript) {
            fmScript = new Promise((resolve, reject) => {
                if (typeof window.financialManager === 'function') { resolve(); return; }
                const s = document.createElement('script');
                s.src = '{{ asset("js/financial-manager.js") }}?v={{ @filemtime(public_path("js/financial-manager.js")) }}';
                s.onload = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
            });
        }
        return fmScript;
    }

    const sameId = (a, b) => String(a) === String(b);

    async function openQuickPay(txnId) {
        const host = document.getElementById('qpPanelHost');
        const loadingEl = document.getElementById('qpLoadingModal');
        const loading = bootstrap.Modal.getOrCreateInstance(loadingEl);
        document.getElementById('qpLoadingBody').innerHTML = '<div class="spinner-border text-success" role="status"></div><div class="small text-muted mt-2">{{ __('Opening payment history...') }}</div>';
        // Bootstrap ignores hide() while the show animation runs — wait for it.
        const shown = loadingEl.classList.contains('show') ? Promise.resolve()
            : new Promise(res => loadingEl.addEventListener('shown.bs.modal', res, { once: true }));
        loading.show();
        try {
            const [html] = await Promise.all([
                fetch('/production/transactions/' + encodeURIComponent(txnId) + '/panel', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                    credentials: 'same-origin',
                }).then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.text(); }),
                ensureFinancialManager(),
            ]);

            if (window.Alpine && host.firstElementChild) Alpine.destroyTree(host);
            const doc = new DOMParser().parseFromString(html, 'text/html');
            doc.querySelectorAll('script').forEach(s => s.remove());
            host.innerHTML = doc.body.innerHTML;
            if (window.Alpine) Alpine.initTree(host);

            const root = host.querySelector('[x-data]');
            const fm = root && window.Alpine ? Alpine.$data(root) : null;
            if (!fm) throw new Error('panel');
            const t = (fm.transactions || []).find(x => sameId(x.id, txnId));
            if (!t) throw new Error('bill');
            if (t.production_financial_group_id && !sameId(fm.activeGroupId, t.production_financial_group_id)) {
                const g = (fm.financialGroups || []).find(x => sameId(x.id, t.production_financial_group_id));
                if (g) fm.switchGroup(g.id);
            }

            await shown;
            loadingEl.addEventListener('hidden.bs.modal', function onHidden() {
                loadingEl.removeEventListener('hidden.bs.modal', onHidden);
                fm.openPayModal(t);
                const payEl = fm.$refs.payModal;
                payEl.addEventListener('hidden.bs.modal', function onPayHidden(e) {
                    if (e.target !== payEl) return; // inner popups bubble up too
                    payEl.removeEventListener('hidden.bs.modal', onPayHidden);
                    window.location.reload();
                });
            });
            loading.hide();
        } catch (err) {
            console.error('[quickPay] could not open the payment history:', err);
            document.getElementById('qpLoadingBody').innerHTML =
                '<i class="bi bi-exclamation-triangle-fill text-danger fs-3 d-block mb-2"></i>' +
                '<div class="small mb-3">{{ __('Could not open the payment history. Please try again, or open the bill with the View button.') }}</div>' +
                '<button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>';
        }
    }

    // Delegated, so rows rendered later (pagination / AJAX) work too.
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.js-quick-pay-btn');
        if (!btn) return;
        e.preventDefault();
        openQuickPay(btn.dataset.txnId);
    });
})();
</script>

{{-- =============================================================
     Issue Bill Options Modal
     Opens when any .js-issue-bill-btn in the Recent Transactions
     table is clicked. Same UX as the finance-tab createInvoiceModal
     but scoped to a single transaction row so the operator can
     "quick print" without navigating into the Manage Finance page.
     Adds ?include_employee_list / ?list_only / ?payment_methods
     to the base URL then opens the resulting PDF in a new tab.
     ============================================================= --}}
<div class="modal fade" id="issueBillOptionsModal" tabindex="-1" aria-labelledby="issueBillOptionsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning-subtle">
                <h5 class="modal-title" id="issueBillOptionsLabel">
                    <i class="bi bi-file-earmark-text me-1"></i>
                    <span id="ibDocTitle">{{ __('Reissue Bill (Quick)') }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                {{-- Context --}}
                <div class="card bg-light border-0 mb-3">
                    <div class="card-body py-2">
                        <div class="small">
                            <div class="text-muted">{{ __('Bill No.') }}</div>
                            <div class="fw-bold" id="ibTxnLabel">—</div>
                            <div class="text-muted mt-2">{{ __('Employer / Project') }}</div>
                            <div class="fw-bold" id="ibEmployer">—</div>
                        </div>
                    </div>
                </div>

                {{-- Variant --}}
                <div class="mb-3">
                    <label class="form-label small fw-bold mb-2">{{ __('Document Variant') }}</label>
                    <div class="form-check small">
                        <input class="form-check-input" type="radio" name="ibVariant" id="ibVarInvoice" value="invoice" checked>
                        <label class="form-check-label" for="ibVarInvoice">
                            {{ __('Document only (no list)') }}
                        </label>
                    </div>
                    <div class="form-check small">
                        <input class="form-check-input" type="radio" name="ibVariant" id="ibVarWithList" value="with_list">
                        <label class="form-check-label" for="ibVarWithList">
                            {{ __('Document + Employee List') }}
                        </label>
                    </div>
                    <div class="form-check small">
                        <input class="form-check-input" type="radio" name="ibVariant" id="ibVarListOnly" value="list_only">
                        <label class="form-check-label" for="ibVarListOnly">
                            {{ __('Employee List only (no document)') }}
                        </label>
                    </div>
                </div>

                {{-- Payment methods / bank accounts --}}
                <div class="border rounded p-2 mb-2 bg-light">
                    <div class="small fw-bold mb-2">
                        <i class="bi bi-credit-card-2-front me-1"></i> {{ __('Payment Methods') }}
                        <span class="text-muted fw-normal">— {{ __('Tick channels shown on the printed bill') }}</span>
                    </div>

                    <div class="form-check form-check-inline small mb-1">
                        <input type="checkbox" class="form-check-input" id="ibPmCash">
                        <label class="form-check-label" for="ibPmCash"><i class="bi bi-cash-stack me-1"></i>{{ __('Cash') }}</label>
                    </div>

                    <div class="form-check form-check-inline small mb-1">
                        <input type="checkbox" class="form-check-input" id="ibPmPP">
                        <label class="form-check-label" for="ibPmPP"><i class="bi bi-qr-code me-1"></i>{{ __('PromptPay') }}</label>
                    </div>
                    <div id="ibPPWrap" class="mb-2" style="display:none;">
                        <input type="text" id="ibPPId" class="form-control form-control-sm" placeholder="{{ __('Phone (10 digits) or Tax ID (13 digits)') }}">
                    </div>

                    <div class="mt-2">
                        <label class="form-label small fw-bold mb-1"><i class="bi bi-bank2 me-1"></i>{{ __('Bank Account') }}</label>
                        <div class="small text-muted mb-1">{{ __('Select one or more company bank accounts to display on the invoice.') }}</div>
                        <div id="ibBankList" style="max-height:180px; overflow-y:auto;">
                            @foreach($qpBanks as $bank)
                                <div class="form-check small">
                                    <input class="form-check-input js-ib-bank" type="checkbox"
                                           id="ibBank{{ $bank->id }}"
                                           data-bank-id="{{ $bank->id }}"
                                           data-bank-code="{{ $bank->bank_code ?? '' }}"
                                           data-bank-name="{{ $bank->bank_name }}"
                                           data-account-name="{{ $bank->account_name }}"
                                           data-account-number="{{ $bank->account_number }}">
                                    <label class="form-check-label" for="ibBank{{ $bank->id }}">
                                        {{ $bank->account_type === 'personal' ? '👤' : '🏢' }}
                                        <strong>{{ $bank->bank_name }}</strong>
                                        <span class="text-muted"> — {{ $bank->account_name ?? $bank->account_number }}</span>
                                        @if($bank->account_name && $bank->account_number)
                                            <br><span class="small text-muted ms-4">{{ $bank->account_number }}</span>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                            @if(!$qpBanks->count())
                                <div class="text-muted small">{{ __('No bank accounts configured. Add one in Finance › Bank Accounts.') }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-success" id="ibGenerateBtn">
                    <i class="bi bi-printer me-1"></i> {{ __('Generate & Open') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('issueBillOptionsModal');
    if (!modalEl) return;
    let modalInstance = null;
    function getModal() {
        if (!modalInstance) {
            if (typeof bootstrap === 'undefined' || !bootstrap.Modal) return null;
            modalInstance = new bootstrap.Modal(modalEl);
        }
        return modalInstance;
    }

    let currentBaseUrl = null;

    // Open the modal — populate context + title + reset options
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.js-issue-bill-btn');
        if (!btn) return;
        currentBaseUrl = btn.dataset.baseUrl || '';
        const docType = btn.dataset.docType || 'invoice';
        document.getElementById('ibDocTitle').textContent =
            docType === 'reminder' ? '{{ __("Reminder Bill (Outstanding Only)") }}' : '{{ __("Reissue Bill (Quick)") }}';
        document.getElementById('ibTxnLabel').textContent = btn.dataset.txnLabel || '—';
        document.getElementById('ibEmployer').textContent = btn.dataset.employer || '—';
        // Reset options
        document.getElementById('ibVarInvoice').checked = true;
        document.getElementById('ibPmCash').checked = false;
        document.getElementById('ibPmPP').checked = false;
        document.getElementById('ibPPId').value = '';
        document.getElementById('ibPPWrap').style.display = 'none';
        document.querySelectorAll('.js-ib-bank').forEach(cb => cb.checked = false);
        const modal = getModal();
        if (modal) modal.show();
    });

    // Toggle PromptPay input
    document.getElementById('ibPmPP').addEventListener('change', function () {
        document.getElementById('ibPPWrap').style.display = this.checked ? '' : 'none';
    });

    // Generate — build URL and open new tab
    document.getElementById('ibGenerateBtn').addEventListener('click', function () {
        if (!currentBaseUrl) return;
        const variant = document.querySelector('input[name="ibVariant"]:checked')?.value || 'invoice';
        // Base URL already has ?group_id=…&transaction_ids=… so append with '&'
        const sep = currentBaseUrl.includes('?') ? '&' : '?';
        const params = [];
        if (variant === 'with_list') params.push('include_employee_list=1');
        if (variant === 'list_only') params.push('list_only=1');

        // Payment methods payload — MUST match the shape that documents/layout.blade.php
        // (invoice) and documents/reminder.blade.php read: each entry keyed by `type`
        // with values 'cash' | 'promptpay' | 'transfer'. Sending 'method' or
        // 'bank_transfer' here silently drops the entry from the printed bill.
        const methods = [];
        if (document.getElementById('ibPmCash').checked) {
            methods.push({ type: 'cash' });
        }
        if (document.getElementById('ibPmPP').checked) {
            const ppid = document.getElementById('ibPPId').value.trim();
            methods.push({ type: 'promptpay', promptpay_id: ppid });
        }
        document.querySelectorAll('.js-ib-bank:checked').forEach(cb => {
            methods.push({
                type: 'transfer',
                bank_code: cb.dataset.bankCode || '',
                bank_name: cb.dataset.bankName || '',
                account_name: cb.dataset.accountName || '',
                account_number: cb.dataset.accountNumber || '',
            });
        });
        if (methods.length > 0) {
            const json = JSON.stringify(methods);
            const b64 = btoa(unescape(encodeURIComponent(json)));
            params.push('payment_methods=' + encodeURIComponent(b64));
        }

        const url = currentBaseUrl + (params.length ? sep + params.join('&') : '');
        window.open(url, '_blank');
        const modal = getModal();
        if (modal) modal.hide();
    });
});
</script>

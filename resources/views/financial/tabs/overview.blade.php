{{-- Stats Cards --}}
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start border-primary border-4 shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="small fw-bold text-primary text-uppercase mb-1">
                            {{ __('Income Today') }}</div>
                        <div class="h5 mb-0 fw-bold text-dark">{{ number_format($stats['income_today'], 2) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-calendar-day fs-2 text-secondary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start border-success border-4 shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="small fw-bold text-success text-uppercase mb-1">
                            {{ __('Income This Month') }}</div>
                        <div class="h5 mb-0 fw-bold text-dark">{{ number_format($stats['income_month'], 2) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-calendar-month fs-2 text-secondary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start border-warning border-4 shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="small fw-bold text-warning text-uppercase mb-1">
                            {{ __('Pending Amount') }}</div>
                        <div class="h5 mb-0 fw-bold text-dark">{{ number_format($stats['pending_amount'], 2) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-clock-history fs-2 text-secondary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start border-danger border-4 shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="small fw-bold text-danger text-uppercase mb-1">
                            {{ __('Overdue Amount') }}</div>
                        <div class="h5 mb-0 fw-bold text-dark">{{ number_format($stats['overdue_amount'], 2) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-exclamation-circle fs-2 text-secondary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('finance.index') }}" method="GET" class="row g-3">
            <input type="hidden" name="tab" value="overview">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="{{ __('Search Bill #, Employer, Project, Job Owner...') }}" value="{{ request('search') }}">
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
                <input type="date" name="date_from" class="form-control" placeholder="{{ __('From Date') }}" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" placeholder="{{ __('To Date') }}" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i> {{ __('Filter') }}</button>
            </div>
        </form>
    </div>
</div>

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
                    <div class="small text-muted">{{ __('Received') }}</div>
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
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
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
                        $outstanding = (float) $txn->amount - (float) $txn->paid_amount;
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
     Quick Payment Modal
     Shared modal used by every "cash-coin" button in the Recent
     Transactions table. Submits directly to the existing
     storePayment endpoint so we reuse the well-tested code path
     that recomputes paid_amount + status + bank balance.
     ============================================================= --}}
@php
    // Pull bank accounts for the payment method select. Silently skip if
    // the table isn't there yet on a fresh install.
    try {
        $qpBanks = \App\Models\BankAccount::orderBy('bank_name')->get(['id','bank_name','account_number','account_name','account_type']);
    } catch (\Throwable $e) {
        $qpBanks = collect();
    }
@endphp
<div class="modal fade" id="quickPaymentModal" tabindex="-1" aria-labelledby="quickPaymentLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="quickPaymentForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-success-subtle">
                    <h5 class="modal-title" id="quickPaymentLabel">
                        <i class="bi bi-cash-coin me-1"></i> {{ __('Record Payment (Quick)') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    {{-- Context card --}}
                    <div class="card bg-light border-0 mb-3">
                        <div class="card-body py-2">
                            <div class="row g-2 small">
                                <div class="col-6">
                                    <div class="text-muted">{{ __('Bill No.') }}</div>
                                    <div class="fw-bold" id="qpBillNo">—</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted">{{ __('Employer / Project') }}</div>
                                    <div class="fw-bold" id="qpEmployer">—</div>
                                </div>
                                <div class="col-4">
                                    <div class="text-muted">{{ __('Bill Amount') }}</div>
                                    <div id="qpBillAmount">—</div>
                                </div>
                                <div class="col-4">
                                    <div class="text-muted">{{ __('Already Paid') }}</div>
                                    <div id="qpPaidSoFar">—</div>
                                </div>
                                <div class="col-4">
                                    <div class="text-muted">{{ __('Outstanding') }}</div>
                                    <div class="text-danger fw-bold" id="qpOutstanding">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">{{ __('Amount Paid') }} <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="qpAmount" class="form-control" required>
                            <div class="form-text">
                                <a href="#" id="qpFillOutstandingBtn" class="text-decoration-none">
                                    <i class="bi bi-arrow-return-left"></i> {{ __('Fill outstanding balance') }}
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">{{ __('Paid On') }} <span class="text-danger">*</span></label>
                            <input type="date" name="paid_at" id="qpPaidAt" class="form-control" required value="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">{{ __('Bank Account') }}</label>
                            <select name="bank_account_id" class="form-select">
                                <option value="">— {{ __('None (skip bank balance update)') }} —</option>
                                @foreach($qpBanks as $bank)
                                    <option value="{{ $bank->id }}">
                                        {{ $bank->account_type === 'personal' ? '👤' : '🏢' }} {{ $bank->bank_name }} — {{ $bank->account_name ?? $bank->account_number }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">{{ __('Slip / Receipt Image') }}</label>
                            <input type="file" name="slip_file" class="form-control" accept="image/*,.pdf">
                            <div class="form-text small">{{ __('Optional — JPG / PNG / PDF, max 5MB') }}</div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">{{ __('Notes') }}</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('e.g., Transfer via mobile banking') }}"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-success" id="qpSubmitBtn">
                        <i class="bi bi-check-lg"></i> {{ __('Save Payment') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Wrap in DOMContentLoaded + lazy modal init so bootstrap (loaded via Vite
// later in the layout) is guaranteed to be available by the time a button
// is actually clicked. Event delegation also lets buttons rendered by
// pagination or AJAX refreshes keep working without re-binding.
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('quickPaymentModal');
    if (!modalEl) return;
    const form = document.getElementById('quickPaymentForm');
    if (!form) return;
    const fmt = (n) => Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    let currentTxnId = null;
    let currentOutstanding = 0;
    let modalInstance = null;

    function getModal() {
        // bootstrap may not exist at DOMContentLoaded (module scripts still
        // parsing) — poll on first click.
        if (!modalInstance) {
            if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
                alert('{{ __('Bootstrap not loaded yet — please try again in a moment.') }}');
                return null;
            }
            modalInstance = new bootstrap.Modal(modalEl);
        }
        return modalInstance;
    }

    // Delegate the click so newly rendered rows (pagination / AJAX) still work.
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.js-quick-pay-btn');
        if (!btn) return;
        currentTxnId = btn.dataset.txnId;
        currentOutstanding = parseFloat(btn.dataset.outstanding) || 0;
        const setText = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
        setText('qpBillNo', btn.dataset.txnLabel || '—');
        setText('qpEmployer', btn.dataset.employer || '—');
        setText('qpBillAmount', fmt(btn.dataset.amount));
        setText('qpPaidSoFar', fmt(btn.dataset.paid));
        setText('qpOutstanding', fmt(currentOutstanding));
        form.reset();
        const paidAtEl = document.getElementById('qpPaidAt');
        if (paidAtEl) paidAtEl.value = new Date().toISOString().slice(0, 10);
        const modal = getModal();
        if (modal) modal.show();
    });

    // "Fill outstanding" convenience button
    const fillBtn = document.getElementById('qpFillOutstandingBtn');
    if (fillBtn) {
        fillBtn.addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('qpAmount').value = currentOutstanding.toFixed(2);
        });
    }

    // Submit — hits the existing storePayment endpoint that recomputes
    // paid_amount, transitions status, and updates the bank balance
    // inside one DB transaction.
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!currentTxnId) return;

        // No bank account selected — that payment won't post into the
        // ledger at all, so confirm the user really means to record it
        // that way rather than silently accepting it (or, previously,
        // silently blocking it behind a required Notes field).
        const bankAccountSelect = form.querySelector('[name="bank_account_id"]');
        if (bankAccountSelect && !bankAccountSelect.value) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '{{ __('No bank account selected') }}',
                    text: '{{ __('You have not selected a bank account. This payment will not be posted to any bank balance. Save it anyway?') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '{{ __('Save anyway') }}',
                    cancelButtonText: '{{ __('Cancel') }}',
                }).then(function (result) {
                    if (result.isConfirmed) submitQuickPayment();
                });
            } else if (confirm('{{ __('You have not selected a bank account. This payment will not be posted to any bank balance. Save it anyway?') }}')) {
                submitQuickPayment();
            }
            return;
        }

        submitQuickPayment();
    });

    function submitQuickPayment() {
        const submitBtn = document.getElementById('qpSubmitBtn');
        submitBtn.disabled = true;
        const originalHtml = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> {{ __('Saving...') }}';

        const formData = new FormData(form);
        // The CSRF token is inside the form via @csrf; also send it as a
        // header for belt-and-braces since some middleware setups only
        // check the header.
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || formData.get('_token');

        fetch('/production/transactions/' + currentTxnId + '/payments', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken || '',
            },
            credentials: 'same-origin',
            body: formData
        })
        .then(async function (res) {
            let data = {};
            try { data = await res.json(); } catch (_) {}
            if (!res.ok) {
                const msg = data.message || data.error || ('HTTP ' + res.status + ' ' + res.statusText);
                throw new Error(msg);
            }
            return data;
        })
        .then(function (data) {
            if (data.success) {
                if (typeof showToast === 'function') {
                    showToast('{{ __("Payment recorded") }}', 'success');
                }
                const modal = getModal();
                if (modal) modal.hide();
                setTimeout(function () { window.location.reload(); }, 400);
            } else {
                throw new Error(data.message || 'Save failed');
            }
        })
        .catch(function (err) {
            alert('{{ __("Error saving payment:") }} ' + err.message);
            console.error('[quickPayment] save failed:', err);
        })
        .finally(function () {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalHtml;
        });
    }
});
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

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
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" placeholder="From Date" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" placeholder="To Date" value="{{ request('date_to') }}">
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
                    <span class="badge bg-info-subtle text-info ms-1">{{ __('Status') }}: {{ ucfirst(request('status')) }}</span>
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
                            <a href="{{ route('production.edit', ['production' => $txn->production_order_id, 'tab' => 'financial']) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if($txn->slip_path)
                                <a href="#" onclick="event.preventDefault(); viewPDF('{{ asset('storage/' . $txn->slip_path) }}', 'View Slip')" class="btn btn-sm btn-outline-secondary" title="View Slip">
                                    <i class="bi bi-receipt"></i>
                                </a>
                            @endif
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

{{-- Stats Cards --}}
<div class="row mb-4">
    <div class="col-xl-{{ isset($stats['total_unpriced']) ? '2' : '3' }} col-md-6 mb-4">
        <div class="card border-start border-primary border-4 shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="small fw-bold text-primary text-uppercase mb-1">
                            {{ __('Income Today') }}</div>
                        <div class="h5 mb-0 fw-bold text-dark">{{ number_format($stats['income_today'] ?? 0, 2) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-calendar-day fs-2 text-secondary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-{{ isset($stats['total_unpriced']) ? '2' : '3' }} col-md-6 mb-4">
        <div class="card border-start border-success border-4 shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="small fw-bold text-success text-uppercase mb-1">
                            {{ __('Income This Month') }}</div>
                        <div class="h5 mb-0 fw-bold text-dark">{{ number_format($stats['income_month'] ?? 0, 2) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-calendar-month fs-2 text-secondary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-{{ isset($stats['total_unpriced']) ? '2' : '3' }} col-md-6 mb-4">
        <div class="card border-start border-warning border-4 shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="small fw-bold text-warning text-uppercase mb-1">
                            {{ __('Pending Amount') }}</div>
                        <div class="h5 mb-0 fw-bold text-dark">{{ number_format($stats['pending_amount'] ?? 0, 2) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-clock-history fs-2 text-secondary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-{{ isset($stats['total_unpriced']) ? '2' : '3' }} col-md-6 mb-4">
        <div class="card border-start border-danger border-4 shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="small fw-bold text-danger text-uppercase mb-1">
                            {{ __('Overdue Amount') }}</div>
                        <div class="h5 mb-0 fw-bold text-dark">{{ number_format($stats['overdue_amount'] ?? 0, 2) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-exclamation-circle fs-2 text-secondary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(isset($stats['total_unpriced']))
    <div class="col-xl-4 col-md-12 mb-4">
        <a href="{{ route('finance.index', ['tab' => $tab, 'unpriced' => 1]) }}" class="text-decoration-none">
            <div class="card border-start border-info border-4 shadow h-100 py-2 {{ request('unpriced') == 1 ? 'bg-info text-white' : '' }}">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="small fw-bold {{ request('unpriced') == 1 ? 'text-white' : 'text-info' }} text-uppercase mb-1">
                                {{ __('Unpriced Employees') }}</div>
                            <div class="h5 mb-0 fw-bold {{ request('unpriced') == 1 ? 'text-white' : 'text-dark' }}">{{ number_format($stats['total_unpriced']) }}</div>
                            <div class="small mt-1 {{ request('unpriced') == 1 ? 'text-white' : 'text-muted' }}">
                                <i class="bi bi-filter"></i> {{ __('Click to filter missing prices') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-person-exclamation fs-2 {{ request('unpriced') == 1 ? 'text-white' : 'text-secondary' }}"></i>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    @endif
</div>

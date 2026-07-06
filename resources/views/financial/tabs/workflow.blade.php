@include('financial.partials._stats_cards')

<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('finance.index') }}" method="GET" class="row g-3">
            <input type="hidden" name="tab" value="workflow">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="{{ __('Search Employer, Project...') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-4">
                <select name="billing_status" class="form-select">
                    <option value="">{{ __('All Billing Statuses') }}</option>
                    <option value="not_billed" {{ request('billing_status') == 'not_billed' ? 'selected' : '' }}>{{ __('Not Billed Yet') }}</option>
                    <option value="billed"     {{ request('billing_status') == 'billed'     ? 'selected' : '' }}>{{ __('Billed') }}</option>
                </select>
            </div>
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i> {{ __('Filter') }}</button>
            </div>
        </form>
    </div>
</div>

@if(request('billing_status'))
    <div class="alert alert-info d-flex justify-content-between align-items-center mb-4">
        <span>
            <i class="bi bi-funnel-fill me-1"></i>
            {{ __('Filtering by billing status:') }}
            <strong>
                {{ request('billing_status') === 'not_billed' ? __('Not Billed Yet') : __('Billed') }}
            </strong>
        </span>
        <a href="{{ route('finance.index', array_merge(request()->except('billing_status'), ['tab' => 'workflow'])) }}" class="btn btn-sm btn-outline-info">{{ __('Clear Filter') }}</a>
    </div>
@endif

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">{{ __('Workflow & Pre Production Orders') }}</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('ID') }}</th>
                        <th>{{ __('Employer') }}</th>
                        <th>{{ __('Project Name') }}</th>
                        <th class="text-center">{{ __('Items') }}</th>
                        <th class="text-center">{{ __('Status') }}</th>
                        <th class="text-center">{{ __('Billing') }}</th>
                        <th class="text-center">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    @php
                        // Determine billing state from the eager-loaded relations
                        // (financialGroups.transactions is already loaded).
                        $hasBill = false;
                        if ($order->relationLoaded('financialGroups')) {
                            foreach ($order->financialGroups as $g) {
                                if ($g->relationLoaded('transactions') && $g->transactions->count() > 0) {
                                    $hasBill = true; break;
                                }
                            }
                        }
                    @endphp
                    <tr>
                        <td>#{{ $order->id }}</td>
                        <td>{{ $order->employer ? $order->employer->employerNameTh : __('Unknown') }}</td>
                        <td>{{ $order->project_name }}</td>
                        <td class="text-center">{{ $order->items_count }}</td>
                        <td class="text-center">
                            <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                        </td>
                        <td class="text-center">
                            @if($hasBill)
                                <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>{{ __('Billed') }}</span>
                            @else
                                <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle-fill me-1"></i>{{ __('Not Billed Yet') }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('production.edit', ['production' => $order->id, 'tab' => 'financial']) }}" class="btn btn-sm btn-outline-primary" title="Manage Finance">
                                <i class="bi bi-wallet2"></i> {{ __('Manage Finance') }}
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">{{ __('No orders found.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mt-3">
            {{ $orders->links() }}
        </div>
    </div>
</div>

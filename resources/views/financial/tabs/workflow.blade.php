@include('financial.partials._stats_cards')

<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('finance.index') }}" method="GET" class="row g-3">
            <input type="hidden" name="tab" value="workflow">
            <div class="col-md-8">
                <input type="text" name="search" class="form-control" placeholder="{{ __('Search Employer, Project...') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-4 d-grid">
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i> {{ __('Search') }}</button>
            </div>
        </form>
    </div>
</div>

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
                        <th class="text-center">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td>#{{ $order->id }}</td>
                        <td>{{ $order->employer ? $order->employer->employerNameTh : __('Unknown') }}</td>
                        <td>{{ $order->project_name }}</td>
                        <td class="text-center">{{ $order->items_count }}</td>
                        <td class="text-center">
                            <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('production.edit', ['production' => $order->id, 'tab' => 'financial']) }}" class="btn btn-sm btn-outline-primary" title="Manage Finance">
                                <i class="bi bi-wallet2"></i> {{ __('Manage Finance') }}
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">{{ __('No orders found.') }}</td>
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

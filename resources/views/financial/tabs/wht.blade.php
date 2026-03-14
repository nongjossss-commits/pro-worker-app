{{-- WHT Tracking Table --}}
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">{{ __('WHT Tracking (Missing 3% Docs)') }}</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Bill No.') }}</th>
                        <th>{{ __('Employer') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th class="text-end">{{ __('Amount') }}</th>
                        <th class="text-center">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $txn)
                    <tr>
                        <td>{{ $txn->created_at->format('d/m/Y') }}</td>
                        <td>
                            <a href="{{ route('production.edit', ['production' => $txn->production_order_id, 'tab' => 'financial']) }}" class="text-decoration-none">
                                #{{ $txn->production_order_id }}-{{ $txn->id }}
                            </a>
                        </td>
                        <td>
                            @if($txn->productionOrder && $txn->productionOrder->{{ __('employer)') }}<div class="fw-bold">{{ $txn->productionOrder->employer->employerNameTh }}</div>
                            @else
                                <span class="text-muted">{{ __('Unknown') }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-warning text-dark border">{{ __('Pending WHT Document') }}</span>
                        </td>
                        <td class="text-end">{{ number_format($txn->amount, 2) }}</td>
                        <td class="text-center">
                            <a href="{{ route('production.edit', ['production' => $txn->production_order_id, 'tab' => 'financial']) }}" class="btn btn-sm btn-outline-primary" title="{{ __('View Details') }}">
                                <i class="bi bi-eye"></i>{{ __('Upload WHT Doc') }}</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">{{ __('No pending WHT tracking found.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mt-3">
            @if(method_exists($transactions, 'links'))
                {{ $transactions->links() }}
            @endif
        </div>
    </div>
</div>

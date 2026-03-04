<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>{{ __('Employer') }}</th>
                <th>{{ __('Employee') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Deleted At') }}</th>
                <th class="text-end">{{ __('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td>
                        <div class="fw-bold">{{ $item->employer->employerNameTh ?? '-' }}</div>
                        <div class="small text-muted">{{ $item->employer->employerNameEn ?? '-' }}</div>
                    </td>
                    <td>
                        <div class="fw-bold">{{ $item->employeeNameTh ?? $item->employeeNameEn }}</div>
                        <div class="small text-muted">{{ $item->employeePassport ?? '-' }}</div>
                    </td>
                    <td>
                        @if($item->status == 'renewal_pending')
                            <span class="badge bg-warning text-dark">{{ __('Pending') }}</span>
                        @elseif($item->status == 'renewal_completed')
                            <span class="badge bg-success">{{ __('Completed') }}</span>
                        @elseif($item->status == 'renewal_cancelled')
                            <span class="badge bg-secondary">{{ __('Cancelled') }}</span>
                        @else
                            <span class="badge bg-light text-dark border">{{ $item->status }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="small">{{ $item->deleted_at->format('d/m/Y H:i') }}</div>
                        <div class="small text-muted">{{ $item->deleted_at->diffForHumans() }}</div>
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-success" onclick="restoreTrashItem({{ $item->id }})">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('Restore') }}
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                        <i class="bi bi-trash fs-4 d-block mb-2"></i>
                        {{ __('No deleted items found.') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="d-flex justify-content-center mt-3">
        {{ $items->links('pagination::bootstrap-5') }}
    </div>
</div>

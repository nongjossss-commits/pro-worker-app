<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>{{ __('Project / Employer') }}</th>
                <th>{{ __('Employee') }}</th>
                <th>{{ __('Deleted At') }}</th>
                <th class="text-end">{{ __('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td>
                        <div class="fw-bold">{{ $item->order->project_name ?? '-' }}</div>
                        <div class="small text-muted">{{ $item->order->employer->employerNameTh ?? '-' }}</div>
                    </td>
                    <td>
                        @if($item->employee)
                            <div>{{ $item->employee->employeeNameTh ?? $item->employee->employeeNameEn }}</div>
                            <div class="small text-muted">{{ $item->employee->employeePassport ?? '-' }}</div>
                        @else
                            <span class="text-muted">{{ __('No Employee') }}</span>
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
                    <td colspan="4" class="text-center py-4 text-muted">
                        <i class="bi bi-trash fs-4 d-block mb-2"></i>
                        {{ __('No deleted items found.') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="d-flex justify-content-center mt-3">
        {{ $items->links() }}
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 pagination-controls">
    <div class="d-flex align-items-center gap-2">
        <label for="perPage-{{ $employer->id }}" class="form-label mb-0 small text-muted">{{ __('Show') }}:</label>
        <select id="perPage-{{ $employer->id }}" class="form-select form-select-sm per-page-selector" style="width: 80px;" data-employer-id="{{ $employer->id }}">
            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
            <option value="100" {{ request('per_page') == 100 || !request('per_page') ? 'selected' : '' }}>100</option>
        </select>
    </div>
    @if($employees instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="employer-pagination">
            {{ $employees->links() }}
        </div>
    @endif
</div>

@foreach($employees as $employee)
    @include('production.registration._employee_card', [
        'employee' => $employee,
        'steps' => $steps,
        'loop' => $loop,
        'isHistory' => $isHistory ?? false
    ])
@endforeach

@if($employees instanceof \Illuminate\Pagination\LengthAwarePaginator && $employees->{{ __('hasPages())') }}<div class="d-flex justify-content-end align-items-center mt-3 employer-pagination">
        {{ $employees->links() }}
    </div>
@endif

{{-- resources/views/production/_employee_list_content.blade.php
     Pre-Production-only override, picked up automatically by
     ProductionController::fetchEmployees()'s view()->exists() check.
     Same structure as production/registration/_employee_list_content.blade.php
     (the shared file used by Registration Resolution — left untouched),
     with team/batch (ProductionItem.group_name) grouping added so the
     existing "Manage Team" modal JS (which scans for h6.fw-bold.text-dark.mb-0
     headers, see production/_index_scripts.blade.php::openManageTeamModal)
     has something to find. Header markup mirrors
     workflow/partials/order_items.blade.php:13-21 exactly. --}}
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

@php
    // Employees with no team (group_name empty/null) fall under the ''
    // key and render without a header — same convention as
    // workflow/partials/order_items.blade.php. sortKeys() puts '' first,
    // matching WorkflowController::fetchOrderItems()'s orderBy('group_name').
    $groupedEmployees = $employees->groupBy(function ($employee) {
        return isset($employee->production_item) ? ($employee->production_item->group_name ?: '') : '';
    })->sortKeys();
@endphp

@foreach($groupedEmployees as $groupName => $group)
    @if($groupName)
        <div class="d-flex align-items-center mb-2 mt-4 px-2">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px;">
                <i class="bi bi-collection-fill" style="font-size: 0.75rem;"></i>
            </div>
            <h6 class="fw-bold text-dark mb-0">{{ $groupName }}</h6>
            <span class="badge bg-secondary bg-opacity-10 text-secondary ms-2 rounded-pill">{{ $group->count() }}</span>
        </div>
    @endif

    @foreach($group as $employee)
        @include('production.registration._employee_card', [
            'employee' => $employee,
            'steps' => $steps,
            'loop' => $loop,
            'order' => $order ?? null,
            'isHistory' => $isHistory ?? false,
            'show_employer' => true,
            'currentTab' => $currentTab ?? null,
            'allTabs' => $allTabs ?? collect(),
            'renewalTargets' => $renewalTargets ?? null,
        ])
    @endforeach
@endforeach

@if($employees instanceof \Illuminate\Pagination\LengthAwarePaginator && $employees->hasPages())
    <div class="d-flex justify-content-end align-items-center mt-3 employer-pagination">
        {{ $employees->links() }}
    </div>
@endif

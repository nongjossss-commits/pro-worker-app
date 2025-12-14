@extends('layouts.app')

@section('title', 'Registration Resolution')

@section('content')
<style>
    .cursor-pointer { cursor: pointer; }
    .filter-active {
        transform: scale(1.15);
        border: 2px solid #3b82f6 !important; /* Blue-500 */
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        z-index: 10;
        transition: all 0.2s ease-in-out;
    }
    .filter-active .badge {
        box-shadow: 0 0 10px rgba(59, 130, 246, 0.5) !important;
    }
</style>

<div class="container-fluid">
    {{-- Top Stats --}}
    <div class="row row-cols-1 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
        {{-- Total Employees --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm" style="background-color: #FBBF24; border: none;"> {{-- Yellow-ish --}}
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0" id="global-total-count">{{ $totalEmployees }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Total Employees') }}</p>
                </div>
            </div>
        </div>

        {{-- Not Started --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm cursor-pointer filter-card"
                 id="filter-not-started"
                 onclick="toggleFilter('not_started')"
                 style="background-color: #EF4444; border: none; transition: transform 0.2s;"> {{-- Red --}}
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0" id="global-not-started-count">{{ $notStartedCount }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Not Started') }}</p>
                </div>
            </div>
        </div>

        {{-- Cancelled (New) --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm" style="background-color: #6B7280; border: none;"> {{-- Gray --}}
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0" id="global-cancelled-count">{{ $totalCancelled }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Total Cancelled') }}</p>
                </div>
            </div>
        </div>

        {{-- Saved (New) --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm" style="background-color: #10B981; border: none;"> {{-- Green --}}
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0" id="global-saved-count">{{ $totalSaved }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Saved to Database') }}</p>
                </div>
            </div>
        </div>

        {{-- Total Employers --}}
        <div class="col">
            <div class="card bg-dark text-white h-100 shadow-sm" style="border: none;">
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0" id="global-employers-count">{{ $totalEmployers }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Total Employers') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Global Workflow Progress --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="card-title fw-bold text-secondary mb-0"><i class="bi bi-bar-chart-fill me-2"></i>{{ __('Workflow Progress (Global)') }}</h5>
                @can('manage-own-workflow')
                <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#manageStepsModal">
                    <i class="bi bi-gear-fill me-1"></i> {{ __('Settings') }}
                </button>
                @endcan
            </div>

            <div class="d-flex gap-2 flex-wrap justify-content-start align-items-center" id="global-stats-container">
                @foreach($steps as $step)
                    @php
                        $count = $stepStats[$step->id] ?? 0;
                        $isZero = $count === 0;
                        $isLastStep = ($step->id === $lastStepId);

                        if ($isLastStep) {
                             // Last Step Special Styling
                             if ($isZero) {
                                 $bgClass = "bg-secondary bg-opacity-50 text-white";
                             } else {
                                 $bgClass = "bg-primary"; // Blue for last step
                             }
                             $sizeClass = "fs-2 p-3"; // Bigger font and padding
                             $dimensions = "width: 64px; height: 64px;"; // Bigger circle
                             $containerClass = "py-3 px-4"; // Bigger pill
                             $nameClass = "fs-4"; // Bigger name
                        } else {
                            // Normal Step
                            if ($isZero) {
                                $bgClass = "bg-secondary bg-opacity-50 text-white";
                            } else {
                                $bgClass = "bg-success";
                            }
                            $sizeClass = "fs-6 p-2";
                            $dimensions = "width: 32px; height: 32px;";
                            $containerClass = "py-2 px-3";
                            $nameClass = "fs-6";
                        }
                    @endphp
                    <div class="d-inline-flex align-items-center bg-white border rounded-pill {{ $containerClass }} shadow-sm gap-2 cursor-pointer filter-pill"
                         id="filter-step-{{ $step->id }}"
                         onclick="toggleFilter('{{ $step->id }}')">
                        <span class="badge rounded-circle {{ $sizeClass }} {{ $bgClass }} global-stat-badge shadow-sm d-flex align-items-center justify-content-center"
                              style="{{ $dimensions }}"
                              data-step-id="{{ $step->id }}">
                            {{ $count }}
                        </span>
                        <span class="fw-bold text-dark {{ $nameClass }} step-name-text" title="{{ $step->name }}">
                            {{ $step->name }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Actions Bar --}}
    <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-body p-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-3">
                <h4 class="mb-0 text-primary fw-bold text-nowrap"><i class="bi bi-people-fill me-2"></i>{{ __('Registration Resolution') }}</h4>

                <form action="{{ route('production.registration.index') }}" method="GET" class="d-flex flex-grow-1 w-100" style="max-width: 600px;">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="{{ __('Search employee or employer...') }}" value="{{ request('search') }}">
                        <button class="btn btn-primary" type="submit">{{ __('Search') }}</button>
                        @if(request('search'))
                            <a href="{{ route('production.registration.index') }}" class="btn btn-outline-secondary">{{ __('Clear') }}</a>
                        @endif
                    </div>
                </form>

                <div class="d-flex gap-2 flex-wrap justify-content-end">
                    @can('manage-own-workflow')
                    <a href="{{ route('production.registration.create') }}" class="btn btn-warning text-white fw-bold">
                        <i class="bi bi-plus-lg me-1"></i> {{ __('New Employee') }}
                    </a>
                    <a href="{{ route('production.registration.import') }}" class="btn btn-success fw-bold">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> {{ __('Import') }}
                    </a>
                    <a href="{{ route('admin.trash.index', ['tab' => 'employees']) }}" class="btn btn-secondary fw-bold">
                        <i class="bi bi-trash-fill me-1"></i> {{ __('Trash') }}
                    </a>
                    @endcan
                </div>
            </div>

            @can('manage-own-workflow')
            {{-- Bulk Action Bar --}}
            <div class="bulk-action-bar mt-3 align-items-center gap-2 p-2 bg-light border rounded"
                 style="display: none;"
                 id="bulkActionBar"
                 draggable="true"
                 ondragstart="window.startDragBulk(event)">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="select-all-checkbox">
                    <label class="form-check-label fw-bold" for="select-all-checkbox">
                        {{ __('Select All') }} (<span id="selected-count">0</span>)
                    </label>
                </div>

                <div class="vr mx-2"></div>

                <div class="dropdown">
                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="bulkActionDropdown" data-bs-toggle="dropdown" aria-expanded="false" disabled>
                        {{ __('Actions') }}
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="bulkActionDropdown">
                        <li><a class="dropdown-item" href="#" id="bulk-advanced-edit-btn"><i class="bi bi-pencil-square me-2"></i>{{ __('Advanced Edit') }}</a></li>
                        <li><a class="dropdown-item" href="#" id="bulk-advanced-export-btn"><i class="bi bi-file-earmark-spreadsheet me-2"></i>{{ __('Advanced Export') }}</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" id="bulk-download-btn"><i class="bi bi-download me-2"></i>{{ __('Download Files') }}</a></li>
                        <li><a class="dropdown-item" href="#" id="bulk-transfer-btn"><i class="bi bi-arrow-left-right me-2"></i>{{ __('Transfer') }}</a></li>
                        <li><a class="dropdown-item" href="#" id="bulk-send-data-btn"><i class="bi bi-send me-2"></i>{{ __('Send Data') }}</a></li>
                    </ul>
                </div>

                <button class="btn btn-sm btn-outline-danger ms-2" onclick="window.clearGlobalSelection();">{{ __('Clear Selection') }}</button>
                <button class="btn btn-sm btn-info text-white" id="btn-view-selected">
                    <i class="bi bi-eye me-1"></i> {{ __('View Selected') }}
                </button>
                <div class="ms-auto text-muted small d-none d-md-block">
                    <i class="bi bi-arrows-move me-1"></i> {{ __('Drag to Chat') }}
                </div>
            </div>
            @endcan
        </div>
    </div>

    {{-- Employers List --}}
    <div class="accordion" id="employersAccordion">
        @foreach($employers as $employer)
            <div class="card mb-4 border border-primary border-2 shadow-sm overflow-hidden" id="employer-card-{{ $employer->id }}">
                <div class="card-header bg-white py-3 px-4 border-bottom" id="heading{{ $employer->id }}">

                    {{-- Top Row: Identity + Stats + Actions --}}
                    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-3">

                        {{-- Left: Identity --}}
                        <div class="d-flex align-items-center flex-wrap gap-3">
                            @can('manage-own-workflow')
                            {{-- Select All for Employer --}}
                            <div class="form-check mb-0">
                                <input class="form-check-input employer-select-all" type="checkbox" data-employer-id="{{ $employer->id }}" title="Select All for this Employer">
                            </div>
                            @endcan

                            {{-- Name & Collapse Trigger --}}
                            <button class="btn btn-link text-decoration-none text-dark p-0 text-start d-flex align-items-center gap-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $employer->id }}">
                                <h4 class="fw-bold mb-0 text-primary">{{ $employer->employerNameTh }}</h4>
                            </button>

                            {{-- Preview --}}
                            <button class="btn btn-sm btn-outline-info btn-preview rounded-circle" data-model-type="employer" data-model-id="{{ $employer->id }}" title="Preview Employer Data">
                                <i class="bi bi-search"></i>
                            </button>

                            {{-- English Name --}}
                            <div class="text-muted small border-start ps-3 fw-bold">
                                {{ $employer->employerNameEn }}
                            </div>

                            {{-- Job Owner --}}
                            @if($employer->jobOwner)
                                <div class="text-muted small border-start ps-3">
                                    <i class="bi bi-person-badge me-1"></i>
                                    <a href="{{ route('production.registration.index', ['search' => $employer->jobOwner->name]) }}" class="text-decoration-none text-secondary">
                                        {{ $employer->jobOwner->name }}
                                    </a>
                                </div>
                            @endif
                        </div>

                        {{-- Right: Stats & Finance --}}
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                             {{-- Stats Badges --}}
                             <div class="d-flex align-items-center gap-2">
                                {{-- Total --}}
                                <span class="badge bg-light text-dark border d-flex align-items-center gap-2 px-2 py-1" title="Total Employees">
                                    <i class="bi bi-people-fill text-muted"></i>
                                    <span class="fw-bold" id="employer-total-{{ $employer->id }}">{{ $employer->activeEmployeesCount ?? 0 }}</span>
                                    <span class="text-muted small ms-1" style="font-size: 0.75rem;">TOTAL</span>
                                </span>
                                {{-- Not Started --}}
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger d-flex align-items-center gap-2 px-2 py-1" title="Pending">
                                     <span class="fw-bold" id="employer-not-started-{{ $employer->id }}">{{ $employer->notStartedCount ?? 0 }}</span>
                                     <span class="small ms-1 opacity-75" style="font-size: 0.75rem;">PENDING</span>
                                </span>
                                {{-- Saved --}}
                                <span class="badge bg-success bg-opacity-10 text-success border border-success d-flex align-items-center gap-2 px-2 py-1" title="Saved">
                                     <span class="fw-bold" id="employer-saved-{{ $employer->id }}">{{ $employer->savedCount ?? 0 }}</span>
                                     <span class="small ms-1 opacity-75" style="font-size: 0.75rem;">SAVED</span>
                                </span>
                                {{-- Cancelled --}}
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary d-flex align-items-center gap-2 px-2 py-1" title="Cancelled">
                                    <span class="fw-bold" id="employer-cancelled-{{ $employer->id }}">{{ $employer->cancelledCount ?? 0 }}</span>
                                    <span class="small ms-1 opacity-75" style="font-size: 0.75rem;">CANCEL</span>
                                </span>
                             </div>

                             <div class="vr d-none d-xl-block"></div>

                             @can('manage-own-workflow')
                             {{-- Add Employee Button --}}
                             <a href="{{ route('production.registration.create', ['employer_id' => $employer->id]) }}" class="btn btn-outline-warning btn-sm fw-bold">
                                <i class="bi bi-plus-lg"></i> {{ __('Add Employee') }}
                             </a>
                             @endcan

                             {{-- Finance Button --}}
                             @can('view-finance')
                             <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#financeModal-{{ $employer->id }}" onclick="event.stopPropagation()">
                                <i class="bi bi-currency-dollar"></i> {{ __('Finance') }}
                            </button>
                            @endcan

                            {{-- Collapse Chevron --}}
                            <button class="btn btn-light btn-sm rounded-circle" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $employer->id }}">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Bottom Row: Workflow Steps (Full Width) --}}
                    <div class="w-100 overflow-auto custom-scrollbar pb-1 employer-stats-container"
                         id="employer-stats-{{ $employer->id }}"
                         style="scrollbar-width: thin;">
                         <div class="d-flex flex-nowrap align-items-center gap-2">
                             @foreach($steps as $step)
                                @php
                                    $count = $employer->stepStats[$step->id] ?? 0;
                                    $isZero = $count === 0;
                                    $isLastStep = ($step->id === $lastStepId);

                                    // Refined Styling for Compact Single Row
                                    if ($isLastStep) {
                                        if ($isZero) {
                                            $bgClass = "bg-secondary bg-opacity-25 text-muted";
                                        } else {
                                            $bgClass = "bg-primary text-white";
                                        }
                                        $sizeClass = "";
                                        $dimensions = "width: 28px; height: 28px;";
                                        $containerClass = "px-3 py-1 border-primary";
                                    } else {
                                        if ($isZero) {
                                            $bgClass = "bg-secondary bg-opacity-25 text-muted";
                                        } else {
                                             $bgClass = "bg-success text-white";
                                        }
                                        $sizeClass = "";
                                        $dimensions = "width: 24px; height: 24px;";
                                        $containerClass = "px-3 py-1";
                                    }
                                @endphp
                                <div class="d-inline-flex align-items-center bg-light border rounded-pill {{ $containerClass }} gap-2 flex-shrink-0"
                                     style="min-width: max-content;">
                                    <span class="badge rounded-circle d-flex align-items-center justify-content-center {{ $sizeClass }} {{ $bgClass }} employer-stat-badge"
                                          style="{{ $dimensions }}"
                                          data-step-id="{{ $step->id }}">
                                        {{ $count }}
                                    </span>
                                    <span class="text-dark fw-bold" style="font-size: 0.85rem;">{{ $step->name }}</span>
                                </div>
                             @endforeach
                         </div>
                    </div>
                </div>

                <div id="collapse{{ $employer->id }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $employer->id }}">
                    <div class="card-body bg-light p-4">
                         <div class="employee-list">
                            @foreach($employer->employees as $employee)
                                {{-- Filter out cancelled if needed, or show them differently. The controller returns them. --}}
                                @include('production.registration._employee_card', ['employee' => $employee, 'steps' => $steps])
                            @endforeach
                         </div>
                    </div>
                </div>
            </div>

            {{-- Finance Modal for this Employer --}}
            <div class="modal fade" id="financeModal-{{ $employer->id }}" tabindex="-1" aria-hidden="true" onclick="event.stopPropagation()">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('Finance') }}: {{ $employer->employerNameTh }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body bg-light">
                            @include('production.partials.financial-tab', ['production' => $employer->financeOrder])
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- Include Drawer --}}
@include('production.registration.partials.offcanvas_drawer')

{{-- Include Add Custom Field Modal --}}
@include('production.registration.partials.modals.add_custom_field')

{{-- Include Advanced Export & Target Employer Modals (reused from Employees) --}}
@include('employees.modals.advanced_export')
@include('employees.modals.select_target_employer_modal')

{{-- View Selected Items Modal --}}
<div class="modal fade" id="viewSelectedModal" tabindex="-1" aria-labelledby="viewSelectedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewSelectedModalLabel">{{ __('Selected Employees') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="selected-list-container" class="list-group list-group-flush">
                    <!-- Items will be populated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Manage Steps Modal --}}
<div class="modal fade" id="manageStepsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-diagram-3-fill me-2"></i>Manage Workflow Steps</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                {{-- Add New Step --}}
                <form id="addStepForm" class="mb-4 p-3 bg-light rounded border">
                    <label class="form-label fw-bold">Add New Step</label>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="text" class="form-control" id="newStepName" placeholder="Step Name (e.g., Medical Checkup)" required>
                        <button class="btn btn-primary px-4" type="submit"><i class="bi bi-plus-lg"></i> Add</button>
                    </div>
                </form>

                <h6 class="fw-bold mb-3 text-secondary">Existing Steps</h6>
                <ul class="list-group list-group-flush" id="stepsList">
                    @foreach($steps as $step)
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3" id="step-item-{{ $step->id }}">
                            <div class="d-flex align-items-center gap-3 flex-grow-1">
                                <span class="badge bg-secondary rounded-pill">{{ $step->order }}</span>

                                {{-- Display Mode --}}
                                <div class="d-flex align-items-center gap-2 step-display">
                                    @php
                                        $bgClass = "bg-success";
                                        $bgStyle = "";
                                    @endphp
                                    <span class="badge rounded-pill {{ $bgClass }}" style="{{ $bgStyle }}">&nbsp;</span>
                                    <span class="fw-bold step-name-text">{{ $step->name }}</span>
                                </div>

                                {{-- Edit Mode --}}
                                <div class="step-edit d-none flex-grow-1 d-flex gap-2 align-items-center">
                                    <input type="text" class="form-control form-control-sm step-edit-input" value="{{ $step->name }}">
                                </div>
                            </div>

                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary btn-edit-step" onclick="toggleEditStep({{ $step->id }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-success d-none btn-save-step" onclick="saveStep({{ $step->id }})">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteStep({{ $step->id }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const lastStepId = {{ $lastStepId ?? 'null' }};

    // State for Global Client-Side Filter
    let currentStepFilter = null; // 'not_started', 'step_ID', or null

    // Toggle Filter Function
    function toggleFilter(filterKey) {
        // 1. Update State
        if (currentStepFilter === filterKey) {
            currentStepFilter = null; // Toggle OFF
        } else {
            currentStepFilter = filterKey; // Toggle ON
        }

        // 2. Update UI (Visual Active State)
        updateFilterUI();

        // 3. Apply Filter (Show/Hide Rows)
        applyFilters();

        // 4. Recalculate Stats (Dynamic Counters)
        recalculateVisibleStats();
    }

    function updateFilterUI() {
        // Reset all
        document.querySelectorAll('.filter-card, .filter-pill').forEach(el => el.classList.remove('filter-active'));

        if (!currentStepFilter) return;

        if (currentStepFilter === 'not_started') {
            document.getElementById('filter-not-started').classList.add('filter-active');
        } else {
            // It's a step ID
            const pill = document.getElementById(`filter-step-${currentStepFilter}`);
            if (pill) pill.classList.add('filter-active');
        }
    }

    function applyFilters() {
        const cards = document.querySelectorAll('.employee-card-wrapper');
        let visibleCount = 0;

        cards.forEach(card => {
            const highestStepId = card.dataset.highestStepId;
            const isNotStarted = card.dataset.isNotStarted === 'true';

            let show = true;

            if (currentStepFilter) {
                if (currentStepFilter === 'not_started') {
                    if (!isNotStarted) show = false;
                } else {
                    // Check if highest step matches filter
                    if (highestStepId != currentStepFilter) show = false;
                }
            }

            if (show) {
                card.classList.remove('d-none');
                visibleCount++;
            } else {
                card.classList.add('d-none');
            }
        });

        // Optional: Hide employers with 0 visible employees if desired?
        // For now, let's keep employer cards visible but maybe update their badges.
    }

    function recalculateVisibleStats() {
        // Reset Global Counters
        let globalTotal = 0;
        let globalNotStarted = 0;
        let globalCancelled = 0;
        let globalSaved = 0;

        // Track global step counts for the filter pills
        const globalStepCounts = {};

        // Determine all employers present
        const employersMap = {}; // empId -> { total, notStarted, cancelled, saved, stepCounts: {} }

        // Actually, we need to iterate through VISIBLE cards for the stats.
        const visibleCards = document.querySelectorAll('.employee-card-wrapper:not(.d-none)');

        visibleCards.forEach(card => {
            // Check Parent Visibility (for robustness against other filters/search)
            // if (card.offsetParent === null) return; // Removed to allow counting in collapsed accordions

            const status = card.dataset.status;
            const isNotStarted = card.dataset.isNotStarted === 'true';
            const highestStepId = card.dataset.highestStepId;
            const empId = card.dataset.employerId;

            // Init Employer Stats if new
            if (!employersMap[empId]) {
                employersMap[empId] = { total: 0, notStarted: 0, cancelled: 0, saved: 0, stepCounts: {} };
            }
            const empStats = employersMap[empId];

            // 1. Total (Active)
            if (status !== 'registration_cancelled') {
                globalTotal++;
                empStats.total++;
            }

            // 2. Not Started
            if (isNotStarted) {
                globalNotStarted++;
                empStats.notStarted++;
            }

            // 3. Cancelled
            if (status === 'registration_cancelled') {
                globalCancelled++;
                empStats.cancelled++;
            }

            // 4. Saved
            if (status === 'registration_completed') {
                globalSaved++;
                empStats.saved++;
            }

            // 5. Step Counts (for Badge Updates)
            if (highestStepId && highestStepId !== 'none' && highestStepId !== '') {
                 // Employer
                 if (!empStats.stepCounts[highestStepId]) empStats.stepCounts[highestStepId] = 0;
                 empStats.stepCounts[highestStepId]++;

                 // Global
                 if (!globalStepCounts[highestStepId]) globalStepCounts[highestStepId] = 0;
                 globalStepCounts[highestStepId]++;
            }
        });

        // Update Global UI
        updateText('global-total-count', globalTotal);
        updateText('global-not-started-count', globalNotStarted);
        updateText('global-cancelled-count', globalCancelled);
        updateText('global-saved-count', globalSaved);
        // Employers count (visible employers)
        updateText('global-employers-count', Object.keys(employersMap).length);

        // Update Global Workflow Badges
        document.querySelectorAll('.global-stat-badge').forEach(badge => {
            const stepId = badge.dataset.stepId;
            const count = globalStepCounts[stepId] || 0;
            badge.textContent = count;

            // Update Global Badge Style (similar to employer badges)
            if (count === 0) {
                 badge.classList.add('bg-secondary', 'bg-opacity-50', 'text-white');
                 badge.classList.remove('bg-primary', 'bg-success');
            } else {
                 badge.classList.remove('bg-secondary', 'bg-opacity-50');
                 if (badge.dataset.stepId == lastStepId) {
                     badge.classList.add('bg-primary'); // Blue for last
                 } else {
                     badge.classList.add('bg-success'); // Green for others
                 }
            }
        });

        // Update Per-Employer UI
        // We need to loop through ALL employers to potentially zero-out those with no visible employees
        document.querySelectorAll('[id^="employer-card-"]').forEach(empCard => {
            // Extract ID from e.g. employer-card-5
            const empId = empCard.id.replace('employer-card-', '');
            const stats = employersMap[empId] || { total: 0, notStarted: 0, cancelled: 0, saved: 0, stepCounts: {} };

            updateText(`employer-total-${empId}`, stats.total);
            updateText(`employer-not-started-${empId}`, stats.notStarted);
            updateText(`employer-cancelled-${empId}`, stats.cancelled);
            updateText(`employer-saved-${empId}`, stats.saved);

            // Update Employer Step Badges?
            // This is trickier as we need to reset all badges to 0 first or track them.
            // Let's iterate all badges in this employer card
            const badges = empCard.querySelectorAll('.employer-stat-badge');
            badges.forEach(badge => {
                const stepId = badge.dataset.stepId;
                const count = stats.stepCounts[stepId] || 0;
                badge.textContent = count;

                // Update Style (simple zero check)
                // We reuse the logic from toggleStep's response handler if possible, or simple check
                if (count === 0) {
                     badge.classList.add('bg-secondary', 'bg-opacity-25', 'text-muted');
                     badge.classList.remove('bg-primary', 'bg-success', 'text-white');
                } else {
                     badge.classList.remove('bg-secondary', 'bg-opacity-25', 'text-muted');
                     if (badge.dataset.stepId == lastStepId) {
                         badge.classList.add('bg-primary', 'text-white');
                     } else {
                         badge.classList.add('bg-success', 'text-white');
                     }
                }
            });
        });
    }

    // Employer-level Select All (Modified for Visible & Active only)
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('employer-select-all')) {
            const employerId = e.target.dataset.employerId;
            const isChecked = e.target.checked;

            // Find all checkboxes for this employer
            const checkboxes = document.querySelectorAll(`.employee-checkbox[data-employer-id="${employerId}"]`);

            checkboxes.forEach(cb => {
                // Check if the wrapper row is visible
                const cardWrapper = document.getElementById(`employee-card-${cb.value}`);
                const isVisible = cardWrapper && !cardWrapper.classList.contains('d-none');

                // Explicitly check status data attribute to prevent selecting non-pending (Saved/Cancelled)
                // even if they are somehow visible or accessible.
                const status = cardWrapper.dataset.status;
                const isPending = (status === 'registration_pending');

                if (isVisible && isPending) {
                    if(cb.checked !== isChecked) {
                        cb.checked = isChecked;
                        // Dispatch change event to trigger global listener
                        cb.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                } else {
                    // If hidden or not pending, do not select.
                    // If unchecking the master, uncheck these too to be safe/clean state.
                    if (!isChecked) {
                        cb.checked = false;
                        cb.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            });
        }
    });

    // Helper: Update UI Stats
    function updateStatsUI(stats) {
        if (!stats) return;

        // Global
        if (stats.global) {
            updateText('global-total-count', stats.global.total);
            updateText('global-not-started-count', stats.global.not_started);
            updateText('global-cancelled-count', stats.global.cancelled);
            updateText('global-saved-count', stats.global.saved);
            updateText('global-employers-count', stats.global.employers_count);
        }

        // Employer
        if (stats.employer) {
            updateText(`employer-total-${stats.employer.id}`, stats.employer.total);
            updateText(`employer-not-started-${stats.employer.id}`, stats.employer.not_started);
            updateText(`employer-cancelled-${stats.employer.id}`, stats.employer.cancelled);
            updateText(`employer-saved-${stats.employer.id}`, stats.employer.saved);
        }
    }

    function updateText(id, value) {
        const el = document.getElementById(id);
        if (el) el.innerText = value;
    }

    // --- Manage Steps ---
    document.getElementById('addStepForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const name = document.getElementById('newStepName').value;

        fetch('{{ route("production.registration.steps.store") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ name: name })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Step Created',
                    text: 'New workflow step added successfully!',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            }
        });
    });

    function deleteStep(id) {
        // Use SweetAlert if available, else standard confirm
        Swal.fire({
            title: 'Delete Step?',
            text: "This will remove this step from all employees.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/production/registration/steps/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire('Deleted!', 'Step has been deleted.', 'success')
                        .then(() => location.reload());
                    }
                });
            }
        });
    }

    function toggleEditStep(id) {
        const item = document.getElementById(`step-item-${id}`);
        item.querySelector('.step-display').classList.toggle('d-none');
        item.querySelector('.step-edit').classList.toggle('d-none');
        item.querySelector('.btn-edit-step').classList.toggle('d-none');
        item.querySelector('.btn-save-step').classList.toggle('d-none');
    }

    function saveStep(id) {
        const item = document.getElementById(`step-item-${id}`);
        const newName = item.querySelector('.step-edit-input').value;

        fetch(`/production/registration/steps/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ name: newName })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: 'Step settings updated successfully.',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            }
        });
    }

    // --- Employee Actions (Updated for Immediate DOM Feedback & Stats) ---
    function finalizeEmployee(id) {
        Swal.fire({
            title: 'Save to Database?',
            text: "The employee will be marked as completed.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Save'
        }).then((result) => {
             if (result.isConfirmed) {
                fetch(`/production/registration/${id}/finalize`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        // Update Stats (Server Stats might be outdated if we have client filter, better to recalc)
                        // If we use server stats, it resets to global.
                        // Let's accept server stats for the DB update, but then re-run our client recalc
                        // to keep counters correct with filters.

                        // DOM Update: Completed State
                        const card = document.getElementById(`employee-card-${id}`);
                        if(card) {
                            // Update Data Attribute for Client Filter
                            card.dataset.status = 'registration_completed';
                            card.dataset.isNotStarted = 'false';

                            // 1. Change Card Style
                            card.className = 'card bg-success bg-opacity-10 border-0 text-muted mb-3 employee-card-wrapper'; // Ensure wrapper class stays

                            // 2. Hide/Show Buttons
                            toggleElement(`btn-save-${id}`, false);
                            toggleElement(`btn-cancel-${id}`, false);
                            toggleElement(`btn-restore-${id}`, false);
                            toggleElement(`btn-undo-${id}`, true);

                            // 3. Hide Checkbox & Steps Overlay
                            toggleElement(`checkbox-container-${id}`, false);
                            const infoContainer = document.getElementById(`info-container-${id}`);
                            if(infoContainer) infoContainer.classList.add('opacity-75', 'pointer-events-none');

                            const stepsContainer = document.getElementById(`steps-container-${id}`);
                            if(stepsContainer) stepsContainer.classList.add('opacity-75', 'pointer-events-none');

                            // 4. Update Badges
                            toggleElement(`badge-completed-${id}`, true);
                            toggleElement(`badge-cancelled-${id}`, false);

                            Swal.fire('Saved!', 'Employee marked as completed.', 'success');

                            // Re-apply filters (hide if filtering by Pending/Not Started) and Recalc
                            applyFilters();
                            recalculateVisibleStats();
                        }
                    }
                });
             }
        });
    }

    function restoreEmployeeState(id) {
        Swal.fire({
            title: 'Restore to Pending?',
            text: "This will move the employee back to the active list.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Restore'
        }).then((result) => {
             if (result.isConfirmed) {
                fetch(`/production/registration/${id}/restore`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        // DOM Update: Active State
                        const card = document.getElementById(`employee-card-${id}`);
                        if(card) {
                            card.dataset.status = 'registration_pending';
                            // Re-evaluate 'Not Started'
                            // We need to check steps. If restored, steps are preserved.
                            // If has steps, isNotStarted = false.
                            // The easiest way is to trust the current UI state or server data.
                            // But for immediate update:
                            // If we restore, we assume previous step state is valid.
                            // The card.dataset.highestStepId should be valid still.
                            const hasSteps = card.dataset.highestStepId && card.dataset.highestStepId !== '';
                            card.dataset.isNotStarted = hasSteps ? 'false' : 'true';

                            // 1. Change Card Style (Reset)
                            card.className = 'card bg-white border shadow-sm mb-3 employee-card-wrapper';
                            card.style.filter = ''; // Remove grayscale

                            // 2. Hide/Show Buttons
                            toggleElement(`btn-save-${id}`, true);
                            toggleElement(`btn-cancel-${id}`, true);
                            toggleElement(`btn-restore-${id}`, false);
                            toggleElement(`btn-undo-${id}`, false);

                            // 3. Show Checkbox & Enable Steps
                            toggleElement(`checkbox-container-${id}`, true);
                            const infoContainer = document.getElementById(`info-container-${id}`);
                            if(infoContainer) infoContainer.classList.remove('opacity-75', 'pointer-events-none', 'opacity-50');

                            const stepsContainer = document.getElementById(`steps-container-${id}`);
                            if(stepsContainer) stepsContainer.classList.remove('opacity-75', 'pointer-events-none', 'opacity-50');

                            // 4. Update Badges
                            toggleElement(`badge-completed-${id}`, false);
                            toggleElement(`badge-cancelled-${id}`, false);

                            // Disable Steps Buttons? No, they should be enabled.
                            const stepsBtns = card.querySelectorAll('button[data-step-id]');
                            stepsBtns.forEach(btn => btn.disabled = false);

                            Swal.fire('Restored!', 'Employee is back to pending.', 'success');

                            applyFilters();
                            recalculateVisibleStats();
                        }
                    }
                });
             }
        });
    }

    function cancelEmployee(id) {
        Swal.fire({
            title: 'Cancel Registration?',
            text: "The employee card will be grayed out.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Cancel'
        }).then((result) => {
             if (result.isConfirmed) {
                fetch(`/production/registration/${id}/cancel`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        const card = document.getElementById(`employee-card-${id}`);
                        if(card) {
                            card.dataset.status = 'registration_cancelled';
                            card.dataset.isNotStarted = 'false'; // Cancelled is not "Not Started" in our logic usually

                            // 1. Change Card Style
                            card.className = 'card bg-light border-0 text-secondary grayscale-mode mb-3 employee-card-wrapper';
                            card.style.filter = 'grayscale(100%)';

                            // 2. Hide/Show Buttons
                            toggleElement(`btn-save-${id}`, false);
                            toggleElement(`btn-cancel-${id}`, false);
                            toggleElement(`btn-restore-${id}`, true);
                            toggleElement(`btn-undo-${id}`, false);

                            // 3. Hide Checkbox & Steps Overlay
                            toggleElement(`checkbox-container-${id}`, false);
                            const infoContainer = document.getElementById(`info-container-${id}`);
                            if(infoContainer) infoContainer.classList.add('opacity-50', 'pointer-events-none');

                            const stepsContainer = document.getElementById(`steps-container-${id}`);
                            if(stepsContainer) stepsContainer.classList.add('opacity-50', 'pointer-events-none');

                            // 4. Update Badges
                            toggleElement(`badge-completed-${id}`, false);
                            toggleElement(`badge-cancelled-${id}`, true);

                            Swal.fire('Cancelled', 'Registration cancelled.', 'success');

                            applyFilters();
                            recalculateVisibleStats();
                        }
                    }
                });
             }
        });
    }

    function deleteEmployee(id) {
        Swal.fire({
            title: 'Delete Employee?',
            text: "This will move the employee to the trash.",
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, Delete'
        }).then((result) => {
             if (result.isConfirmed) {
                fetch(`/production/registration/${id}/destroy`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => {
                     if(!res.ok) throw new Error(res.statusText);
                     return res.json();
                })
                .then(data => {
                    if(data.success) {
                        const card = document.getElementById(`employee-card-${id}`);
                        if(card) {
                            card.style.transition = 'all 0.5s ease';
                            card.style.opacity = '0';
                            card.style.transform = 'scale(0.9)';
                            setTimeout(() => {
                                card.remove();
                                recalculateVisibleStats(); // Update stats after removal
                            }, 500);
                        }
                        Swal.fire('Deleted!', 'Employee has been deleted.', 'success');
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'Could not delete employee. Check console.', 'error');
                });
             }
        });
    }

    // Helper to toggle visibility
    function toggleElement(id, show) {
        const el = document.getElementById(id);
        if(el) {
            if(show) el.classList.remove('d-none');
            else el.classList.add('d-none');
        }
    }

    // --- Fixed AJAX Toggle Step with Real-time Updates ---
    function toggleStep(employeeId, stepId, completed) {
        // Find the card and button
        const card = document.getElementById(`employee-card-${employeeId}`);
        if (!card) return;

        // Use more specific selector to find the button
        const btn = card.querySelector(`button[data-step-id="${stepId}"]`);

        // Optimistic UI Update (Instant Feedback)
        const originalClass = btn.className;
        const originalHtml = btn.innerHTML;
        const originalOnClick = btn.getAttribute('onclick');

        if (btn) {
            if (completed) {
                // Becoming Complete: Change to Color
                // Reset base classes first to avoid conflicts
                btn.className = 'btn btn-sm rounded-pill px-3 text-white border-0 btn-success';
                // Force check icon
                if(!btn.querySelector('i.bi-check')) {
                    btn.innerHTML = btn.innerText + ' <i class="bi bi-check-circle-fill ms-1"></i>';
                }
            } else {
                // Becoming Incomplete: Change to Solid Gray (Light)
                btn.style.backgroundColor = '';
                btn.style.borderColor = '';
                btn.style.color = '';
                btn.className = 'btn btn-sm btn-light border text-secondary rounded-pill px-3';

                // Restore original text (remove check icon)
                // Assuming original text is just the step name.
                // To be safe, we strip the icon.
                const icon = btn.querySelector('i.bi-check-circle-fill');
                if(icon) icon.remove();
                // Also check for simple bi-check
                const iconSimple = btn.querySelector('i.bi-check');
                if(iconSimple) iconSimple.remove();
            }
        }

        fetch(`/production/registration/progress/${employeeId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ step_id: stepId, completed: completed })
        })
        .then(res => {
            if (!res.ok) {
                 return res.json().then(data => { throw new Error(data.message || 'Server error'); });
            }
            return res.json();
        })
        .then(data => {
            if(data.success) {
                // 1. Update Button State (Confirm & Update OnClick)
                if (btn) {
                    if (completed) {
                         // Set next action to un-complete (false)
                        btn.setAttribute('onclick', `toggleStep(${employeeId}, ${stepId}, false)`);
                    } else {
                         // Set next action to complete (true)
                        btn.setAttribute('onclick', `toggleStep(${employeeId}, ${stepId}, true)`);
                    }
                }

                // Ensure card is available (re-select to be safe in this scope)
                const card = document.getElementById(`employee-card-${employeeId}`);

                // 2. Client Side State Update (Highest Step Logic)
                // We need to fetch the highest step ID from the active buttons in the DOM to avoid complex server roundtrips for filtering,
                // OR we trust the server logic.
                // The server returns globalStats/employerStats but NOT the specific employee's new highest step ID explicitly in a simple way to update the DOM attr.
                // However, we can deduce it from the DOM state.

                // Get all active step buttons for this employee
                const allButtons = card.querySelectorAll('button[data-step-id]');
                let highestId = '';
                // Since buttons are likely rendered in order, we can check the last active one?
                // Or better, check the dataset.
                // But the loop above renders them.

                // Let's iterate and find the one with class btn-success/btn-primary (completed).
                // Note: The click just happened, so DOM class is updated.
                let maxOrder = -1;
                allButtons.forEach((b, index) => {
                     // Check if completed (has specific classes)
                     if (b.classList.contains('text-white') && !b.classList.contains('bg-secondary')) {
                         // It is completed.
                         // We assume the steps are rendered in order.
                         // But to be safe, we might need the step ID.
                         // Let's just assume the last one found is the highest for now (if rendered in order).
                         highestId = b.dataset.stepId;
                     }
                });

                card.dataset.highestStepId = highestId;
                card.dataset.isNotStarted = (highestId === '') ? 'true' : 'false';

                // 3. Re-apply filters & stats
                applyFilters();
                recalculateVisibleStats();

            } else {
                throw new Error(data.message || 'Unknown error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if(typeof showToast === 'function') {
                showToast('Failed to update progress: ' + error.message, 'danger');
            } else {
                alert('Failed: ' + error.message);
            }

            // Revert on failure
            if (btn) {
                btn.className = originalClass;
                btn.innerHTML = originalHtml;
                btn.setAttribute('onclick', originalOnClick);
                btn.style = ''; // Clear inline styles
            }
        });
    }

    // --- Bulk Action Handlers (Advanced Features) ---
    document.addEventListener('DOMContentLoaded', function() {
        const viewSelectedBtn = document.getElementById('btn-view-selected');
        const container = document.getElementById('selected-list-container');
        const modalEl = document.getElementById('viewSelectedModal');
        const modal = new bootstrap.Modal(modalEl);

        if (viewSelectedBtn) {
            viewSelectedBtn.addEventListener('click', function() {
                const data = window.getGlobalSelectedData();
                if (data.length === 0) {
                    showToast('{{ __('No employees selected') }}', 'danger');
                    return;
                }

                container.innerHTML = '';
                data.forEach(item => {
                    // Populate modal list (Copied logic from employees.index)
                    const li = document.createElement('div');
                    li.className = 'list-group-item d-flex align-items-center justify-content-between';
                    li.id = `selected-item-${item.id}`;

                    li.innerHTML = `
                        <div class="d-flex align-items-center">
                            <img src="${item.photo}" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                            <div>
                                <div class="fw-bold">${item.name_en || 'N/A'}</div>
                                <div class="text-muted small">${item.name_th || 'N/A'}</div>
                                <div class="text-muted small"><i class="bi bi-building me-1"></i>${item.employer_name || 'N/A'}</div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-selected" data-id="${item.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    `;
                    container.appendChild(li);
                });

                // Re-attach remove listeners inside modal
                container.querySelectorAll('.btn-remove-selected').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.dataset.id;
                        window.removeItemsByIds ? window.removeItemsByIds([id]) : console.error('removeItemsByIds not found');
                        // Update UI inside modal manually or let global listener handle page,
                        // but modal needs manual removal from list
                         const itemEl = document.getElementById(`selected-item-${id}`);
                        if (itemEl) itemEl.remove();
                        if (container.children.length === 0) modal.hide();
                    });
                });

                modal.show();
            });
        }

        // Bulk Advanced Edit
        const bulkEditBtn = document.getElementById('bulk-advanced-edit-btn');
        if (bulkEditBtn) {
            bulkEditBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const selected = window.getGlobalSelectedIds();

                if (selected.length === 0) {
                    showToast('{{ __('Please select employees first.') }}', 'danger');
                    return;
                }

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('employees.bulk_edit.select_fields') }}';

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken;
                form.appendChild(csrfInput);

                const redirectInput = document.createElement('input');
                redirectInput.type = 'hidden';
                redirectInput.name = 'redirect_to';
                redirectInput.value = window.location.href;
                form.appendChild(redirectInput);

                selected.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'employee_ids[]';
                    input.value = id;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            });
        }

        // Bulk Advanced Export
        const bulkExportBtn = document.getElementById('bulk-advanced-export-btn');
        if (bulkExportBtn) {
            bulkExportBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const selected = window.getGlobalSelectedIds();

                if (selected.length === 0) {
                    showToast('{{ __('Please select employees first.') }}', 'danger');
                    return;
                }

                document.getElementById('export_employee_ids').value = JSON.stringify(selected);
                const modalEl = document.getElementById('advancedExportModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            });
        }

        // Bulk Download Center
        const bulkDownloadBtn = document.getElementById('bulk-download-btn');
        if (bulkDownloadBtn) {
            bulkDownloadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const selected = window.getGlobalSelectedIds();
                if (selected.length === 0) {
                    showToast('{{ __('Please select employees first.') }}', 'danger');
                    return;
                }

                if (window.openBulkDownloadModal) {
                    window.openBulkDownloadModal(selected);
                } else {
                    console.error('Download modal function not found.');
                }
            });
        }

        // Bulk Send Data (To Ticket)
        const bulkSendDataBtn = document.getElementById('bulk-send-data-btn');
        if (bulkSendDataBtn) {
            bulkSendDataBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const selectedData = window.getGlobalSelectedData();
                const selectedIds = selectedData.map(item => item.id);

                if (selectedIds.length === 0) {
                    showToast('{{ __('Please select employees first.') }}', 'danger');
                    return;
                }

                // Check employers
                let employerIds = new Set();
                selectedData.forEach(item => {
                    if (item.employer_id) employerIds.add(item.employer_id);
                });

                if (employerIds.size > 1) {
                     Swal.fire({
                        icon: 'warning',
                        title: '{{ __('Multiple Employers Selected') }}',
                        text: '{{ __('You selected employees from different employers. Please select employees from the same employer for one transaction.') }}'
                    });
                    return;
                }

                window.pendingTicketEmployeeIds = selectedIds;
                const modalEl = document.getElementById('selectTargetEmployerModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            });
        }

        // Special handler for bulk drag (Required for "Drag to Chat")
        window.startDragBulk = function(e) {
            const ids = window.getGlobalSelectedIds();
            const count = ids.length;

            if (count === 0) {
                e.preventDefault();
                return;
            }

            const payload = {
                type: 'employees_bulk',
                title: count + ' Employees',
                count: count,
                ids: ids,
                url: window.location.href
            };
            e.dataTransfer.effectAllowed = 'copy';
            e.dataTransfer.setData('application/json', JSON.stringify(payload));
        }
    });
</script>
@endpush

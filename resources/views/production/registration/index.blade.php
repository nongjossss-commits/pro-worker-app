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
    .grayscale-mode {
        filter: grayscale(100%);
        opacity: 0.8;
    }
    /* CSS Counters for persistent slot numbering */
    #employersAccordion {
        counter-reset: employer-counter;
    }
    .employer-card-container:not(.d-none) {
        counter-increment: employer-counter;
    }
    .employer-sequence-number::before {
        content: counter(employer-counter);
    }
    .employer-sequence-number {
        /* Ensure it doesn't shift when content changes */
        min-width: 50px;
        text-align: center;
        font-size: 2.5rem; /* display-5 size approx */
        font-weight: bold;
        color: #6c757d; /* text-muted */
        opacity: 0.5;
    }

    /* CSS Counters for Employees (Per Employer) */
    .employee-list {
        counter-reset: employee-counter;
    }
    .employee-card-wrapper:not(.d-none) {
        counter-increment: employee-counter;
    }
    .employee-sequence-number::before {
        content: counter(employee-counter);
    }
    .employee-sequence-number {
        /* Ensure it doesn't shift when content changes */
        min-width: 30px;
        text-align: right;
        font-weight: bold;
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

        {{-- Total Cancelled Employees --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm cursor-pointer filter-card"
                 id="filter-cancelled"
                 onclick="toggleFilter('cancelled')"
                 style="background-color: #6B7280; border: none;"> {{-- Gray --}}
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0" id="global-cancelled-count">{{ $totalCancelled }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Total Cancelled') }}</p>
                </div>
            </div>
        </div>

        {{-- Saved (New) --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm cursor-pointer filter-card"
                 id="filter-saved"
                 onclick="toggleFilter('saved')"
                 style="background-color: #10B981; border: none;"> {{-- Green --}}
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

        {{-- Cancelled Employers --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm cursor-pointer filter-card"
                 id="filter-cancelled-employer"
                 onclick="toggleFilter('cancelled_employer')"
                 style="background-color: #4B5563; border: none;"> {{-- Dark Gray --}}
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0" id="global-cancelled-employers-count">{{ $cancelledEmployersCount }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Cancelled Employers') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Global Workflow Progress --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="card-title fw-bold text-secondary mb-0"><i class="bi bi-bar-chart-fill me-2"></i>{{ __('Workflow Progress (Global)') }}</h5>
                @can('edit-employees')
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
                    @can('edit-employees')
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

            @can('edit-employees')
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
            @php
                $isEmployerCancelled = $employer->financeOrder && $employer->financeOrder->status === 'registration_resolution_cancelled';
                $employerCardClass = $isEmployerCancelled ? 'border-secondary grayscale-mode' : 'border-primary border-2';
                $employerHeaderClass = $isEmployerCancelled ? 'bg-light' : 'bg-white';
            @endphp

            <div class="d-flex align-items-start employer-card-container mb-4" id="employer-card-{{ $employer->id }}">
                {{-- Sequence Number (CSS Counter will handle number) --}}
                <div class="employer-sequence-number me-3 pt-2"></div>

                <div class="card flex-grow-1 shadow-sm overflow-visible {{ $employerCardClass }}" style="position: relative;">
                    {{-- Status/Note Tab/Drawer --}}
                    <div class="position-absolute d-flex align-items-center gap-1 shadow-sm border border-secondary border-bottom-0 rounded-top bg-white px-2 py-1"
                         style="top: -34px; right: 20px; z-index: 5; height: 34px;">
                        {{-- Status Dropdown --}}
                        @php
                            $status = $employer->registration_resolution_status ?? 'preparing';
                            $statusColor = match($status) {
                                'preparing' => 'secondary',
                                'waiting' => 'warning',
                                'ready' => 'success',
                                default => 'secondary'
                            };
                            $statusLabel = match($status) {
                                'preparing' => __('Preparing'),
                                'waiting' => __('Waiting'),
                                'ready' => __('Ready'),
                                default => __('Preparing')
                            };
                        @endphp
                        @can('edit-employees')
                        <div class="dropdown">
                            <button class="btn btn-sm btn-{{ $statusColor }} dropdown-toggle fw-bold py-0" type="button" id="statusDropdown{{ $employer->id }}" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.75rem;">
                                {{ $statusLabel }}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="statusDropdown{{ $employer->id }}">
                                <li><button class="dropdown-item" onclick="updateResolutionStatus({{ $employer->id }}, 'preparing')"><span class="badge bg-secondary me-2">{{ __('Preparing') }}</span>{{ __('Preparing') }}</button></li>
                                <li><button class="dropdown-item" onclick="updateResolutionStatus({{ $employer->id }}, 'waiting')"><span class="badge bg-warning me-2">{{ __('Waiting') }}</span>{{ __('Waiting for Order') }}</button></li>
                                <li><button class="dropdown-item" onclick="updateResolutionStatus({{ $employer->id }}, 'ready')"><span class="badge bg-success me-2">{{ __('Ready') }}</span>{{ __('Ready to Proceed') }}</button></li>
                            </ul>
                        </div>
                        @else
                            <span class="badge bg-{{ $statusColor }}">{{ $statusLabel }}</span>
                        @endcan

                        {{-- Job Order / Note Button --}}
                        <button class="btn btn-sm btn-outline-secondary border-0"
                                data-note="{{ $employer->registration_resolution_note ?? '' }}"
                                onclick="openResolutionNoteModal({{ $employer->id }}, this.getAttribute('data-note'))"
                                title="{{ __('Job Order / Notes') }}">
                            <i class="bi bi-file-text-fill"></i>
                        </button>
                    </div>

                    <div class="card-header py-3 px-4 border-bottom {{ $employerHeaderClass }}" id="heading{{ $employer->id }}">

                    {{-- Top Row: Identity + Stats + Actions (Using Grid for Alignment) --}}
                    <div class="row align-items-xl-center g-3 mb-3">

                        {{-- Left: Identity --}}
                        <div class="col-12 col-xl-auto d-flex align-items-center flex-wrap gap-3">
                            @can('edit-employees')
                            {{-- Select All for Employer --}}
                            <div class="form-check mb-0">
                                <input class="form-check-input employer-select-all" type="checkbox" data-employer-id="{{ $employer->id }}" title="{{ __('Select All for this Employer') }}">
                            </div>
                            @endcan

                            {{-- Name & Collapse Trigger --}}
                            <button class="btn btn-link text-decoration-none text-dark p-0 text-start d-flex align-items-center gap-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $employer->id }}">
                                <h4 class="fw-bold mb-0 text-primary text-truncate" style="max-width: 300px;">{{ $employer->employerNameTh }}</h4>
                            </button>

                            {{-- Preview --}}
                            <button class="btn btn-sm btn-outline-info btn-preview rounded-circle" data-model-type="employer" data-model-id="{{ $employer->id }}" title="{{ __('Preview Employer Data') }}">
                                <i class="bi bi-search"></i>
                            </button>

                            {{-- English Name --}}
                            <div class="text-muted small border-start ps-3 fw-bold text-truncate" style="max-width: 200px;">
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
                        <div class="col-12 col-xl text-xl-end">
                            <div class="d-flex align-items-center justify-content-xl-end gap-2 flex-wrap">
                                 {{-- Stats Badges (Fixed Widths for Alignment) --}}
                                 <div class="d-flex align-items-center gap-2 me-xl-3">
                                    {{-- Total --}}
                                    <span class="badge bg-light text-dark border d-flex align-items-center justify-content-center gap-2 px-2 py-1" style="min-width: 90px;" title="{{ __('Total Employees') }}">
                                        <i class="bi bi-people-fill text-muted"></i>
                                        <span class="fw-bold" id="employer-total-{{ $employer->id }}">{{ $employer->activeEmployeesCount ?? 0 }}</span>
                                        <span class="text-muted small ms-1" style="font-size: 0.70rem;">{{ __('TOTAL') }}</span>
                                    </span>
                                    {{-- Not Started --}}
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger d-flex align-items-center justify-content-center gap-2 px-2 py-1" style="min-width: 100px;" title="{{ __('Pending') }}">
                                         <span class="fw-bold" id="employer-not-started-{{ $employer->id }}">{{ $employer->notStartedCount ?? 0 }}</span>
                                         <span class="small ms-1 opacity-75" style="font-size: 0.70rem;">{{ __('PENDING') }}</span>
                                    </span>
                                    {{-- Saved --}}
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success d-flex align-items-center justify-content-center gap-2 px-2 py-1" style="min-width: 90px;" title="{{ __('Saved') }}">
                                         <span class="fw-bold" id="employer-saved-{{ $employer->id }}">{{ $employer->savedCount ?? 0 }}</span>
                                         <span class="small ms-1 opacity-75" style="font-size: 0.70rem;">{{ __('SAVED') }}</span>
                                    </span>
                                    {{-- Cancelled --}}
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary d-flex align-items-center justify-content-center gap-2 px-2 py-1" style="min-width: 95px;" title="{{ __('Cancelled') }}">
                                        <span class="fw-bold" id="employer-cancelled-{{ $employer->id }}">{{ $employer->cancelledCount ?? 0 }}</span>
                                        <span class="small ms-1 opacity-75" style="font-size: 0.70rem;">{{ __('CANCEL') }}</span>
                                    </span>
                                 </div>

                                 <div class="vr d-none d-xl-block me-2"></div>

                                 @can('edit-employees')
                                 {{-- Add Employee Button --}}
                                 <a href="{{ route('production.registration.create', ['employer_id' => $employer->id]) }}" class="btn btn-outline-warning btn-sm fw-bold {{ $isEmployerCancelled ? 'd-none' : '' }}">
                                    <i class="bi bi-plus-lg"></i> {{ __('Add') }}
                                 </a>
                                 @endcan

                                 {{-- Finance Button --}}
                                 @can('view-finance')
                                 <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#financeModal-{{ $employer->id }}" onclick="event.stopPropagation()">
                                    <i class="bi bi-currency-dollar"></i> {{ __('Finance') }}
                                </button>
                                @endcan

                                {{-- Custom Fields Button (Employer) --}}
                                <button class="btn btn-outline-secondary btn-sm" onclick="toggleEmployerInlineDrawer({{ $employer->id }}, {{ json_encode($employer->customFields) }}); event.stopPropagation();">
                                    <i class="bi bi-list-task"></i> {{ __('Fields') }}
                                </button>

                                {{-- Cancel/Restore Employer Actions --}}
                                @can('edit-employers')
                                    @if($isEmployerCancelled)
                                        <button class="btn btn-outline-warning btn-sm" onclick="restoreEmployer({{ $employer->id }})">
                                            <i class="bi bi-arrow-counterclockwise"></i> {{ __('Restore') }}
                                        </button>
                                    @else
                                        <button class="btn btn-outline-secondary btn-sm" onclick="cancelEmployer({{ $employer->id }})">
                                            <i class="bi bi-x-circle"></i> {{ __('Cancel') }}
                                        </button>
                                    @endif
                                @endcan

                                {{-- Collapse Chevron --}}
                                <button class="btn btn-light btn-sm rounded-circle ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $employer->id }}">
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Employer Custom Fields Drawer --}}
                    <div class="collapse mt-3" id="drawer-employer-{{ $employer->id }}">
                        <div class="card card-body bg-light border-0 rounded-3">
                            <div id="drawer-content-employer-{{ $employer->id }}" class="position-relative" style="min-height: 100px;">
                                <div class="d-flex justify-content-center align-items-center h-100 py-3">
                                     <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                     <span class="ms-2 small text-muted">{{ __('Loading fields...') }}</span>
                                </div>
                            </div>
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
                         <div class="employee-list" id="employee-list-{{ $employer->id }}">
                            @foreach($employer->employees as $employee)
                                {{-- Filter out cancelled if needed, or show them differently. The controller returns them. --}}
                                @include('production.registration._employee_card', ['employee' => $employee, 'steps' => $steps, 'loop' => $loop])
                            @endforeach
                         </div>
                    </div>
                </div>
            </div> {{-- End card --}}
            </div> {{-- End d-flex wrapper --}}

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

{{-- Edit Employee Modal --}}
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editEmployeeModalLabel"><i class="bi bi-pencil-square me-2"></i>{{ __('Edit Employee') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light" id="editEmployeeModalBody">
                <div class="d-flex justify-content-center align-items-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ __('Loading...') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Manage Steps Modal --}}
<div class="modal fade" id="manageStepsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-diagram-3-fill me-2"></i>{{ __('Manage Workflow Steps') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                {{-- Add New Step --}}
                <form id="addStepForm" class="mb-4 p-3 bg-light rounded border">
                    <label class="form-label fw-bold">{{ __('Add New Step') }}</label>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="text" class="form-control" id="newStepName" placeholder="{{ __('Step Name (e.g., Medical Checkup)') }}" required>
                        <button class="btn btn-primary px-4" type="submit"><i class="bi bi-plus-lg"></i> {{ __('Add') }}</button>
                    </div>
                </form>

                <h6 class="fw-bold mb-3 text-secondary">{{ __('Existing Steps') }}</h6>
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

{{-- Job Order / Note Modal --}}
<div class="modal fade" id="resolutionNoteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-text me-2"></i>{{ __('Job Order / Notes') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="resolutionNoteForm">
                    <input type="hidden" id="noteEmployerId">
                    <div class="mb-3">
                        <label for="resolutionNoteText" class="form-label">{{ __('Details / Instructions') }}</label>
                        <textarea class="form-control" id="resolutionNoteText" rows="6"
                            @cannot('edit-employees') readonly @endcannot
                        ></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                @can('edit-employees')
                <button type="button" class="btn btn-primary" onclick="saveResolutionNote()">{{ __('Save Note') }}</button>
                @endcan
            </div>
        </div>
    </div>
</div>

@endsection

@include('employees.partials._edit_scripts')
@include('production.registration.partials.edit_modal_script')

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const lastStepId = @json($lastStepId);

    // --- Resolution Status & Note Functions ---
    function updateResolutionStatus(employerId, status) {
        fetch(`/production/registration/employer/${employerId}/resolution-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ status: status })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                // Instead of full reload, we could update UI locally, but status change changes text & color.
                // For simplicity and to show spinner/success, reload is safest to ensure consistency.
                // Or update button classes.
                location.reload();
            } else {
                Swal.fire('{{ __('Error') }}', '{{ __('Failed to update status') }}', 'error');
            }
        })
        .catch(err => Swal.fire('{{ __('Error') }}', '{{ __('Network error') }}', 'error'));
    }

    const noteModal = new bootstrap.Modal(document.getElementById('resolutionNoteModal'));

    function openResolutionNoteModal(employerId, currentNote) {
        document.getElementById('noteEmployerId').value = employerId;
        document.getElementById('resolutionNoteText').value = currentNote;
        noteModal.show();
    }

    function saveResolutionNote() {
        const employerId = document.getElementById('noteEmployerId').value;
        const note = document.getElementById('resolutionNoteText').value;

        fetch(`/production/registration/employer/${employerId}/resolution-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ note: note })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '{{ __('Saved!') }}',
                    text: '{{ __('Note updated successfully') }}',
                    timer: 1000,
                    showConfirmButton: false
                });
                noteModal.hide();
                // Update the onclick attribute in DOM to reflect new note so it persists without reload
                // Find button
                // This part is tricky because we need to escape the string properly for the onclick attribute.
                location.reload();
            } else {
                Swal.fire('{{ __('Error') }}', '{{ __('Failed to save note') }}', 'error');
            }
        })
        .catch(err => Swal.fire('{{ __('Error') }}', '{{ __('Network error') }}', 'error'));
    }

    // State for Global Client-Side Filter
    let currentStepFilter = null; // 'not_started', 'saved', 'cancelled', 'cancelled_employer', 'step_ID', or null

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

        // CSS Counters handle sequence automatically!
    }

    function updateFilterUI() {
        // Reset all
        document.querySelectorAll('.filter-card, .filter-pill').forEach(el => el.classList.remove('filter-active'));

        if (!currentStepFilter) return;

        if (currentStepFilter === 'not_started') {
            document.getElementById('filter-not-started').classList.add('filter-active');
        } else if (currentStepFilter === 'saved') {
            document.getElementById('filter-saved').classList.add('filter-active');
        } else if (currentStepFilter === 'cancelled') {
            document.getElementById('filter-cancelled').classList.add('filter-active');
        } else if (currentStepFilter === 'cancelled_employer') {
            document.getElementById('filter-cancelled-employer').classList.add('filter-active');
        } else {
            // It's a step ID
            const pill = document.getElementById(`filter-step-${currentStepFilter}`);
            if (pill) pill.classList.add('filter-active');
        }
    }

    function applyFilters() {
        const cards = document.querySelectorAll('.employee-card-wrapper');
        let visibleCount = 0;

        // Reset all employers to visible first (logic below handles hiding them)
        document.querySelectorAll('[id^="employer-card-"]').forEach(empCard => empCard.classList.remove('d-none'));

        // Handle 'cancelled_employer' specifically first
        if (currentStepFilter === 'cancelled_employer') {
            document.querySelectorAll('[id^="employer-card-"]').forEach(empCard => {
                 // We need to know if employer is cancelled.
                 // We can check if it has the 'grayscale-mode' class or look for the 'Restoe Employer' button presence?
                 // Better: Add a data attribute to employer card.
                 // Since I cannot modify the PHP loop easily in this patch block without getting messy,
                 // I'll rely on the class 'grayscale-mode' which is added in the view for cancelled employers.
                 const isCancelled = empCard.querySelector('.card').classList.contains('grayscale-mode');

                 if (isCancelled) {
                     empCard.classList.remove('d-none');
                     // Show all its employees (even cancelled ones?)
                     // Usually yes, if viewing cancelled employer.
                     empCard.querySelectorAll('.employee-card-wrapper').forEach(c => c.classList.remove('d-none'));
                 } else {
                     empCard.classList.add('d-none');
                 }
            });
            return; // Exit early for this special filter
        }

        // Standard Employee Filters
        cards.forEach(card => {
            const highestStepId = card.dataset.highestStepId;
            const isNotStarted = card.dataset.isNotStarted === 'true';
            const status = card.dataset.status;

            let show = true;

            if (currentStepFilter) {
                if (currentStepFilter === 'not_started') {
                    if (!isNotStarted || status === 'registration_cancelled') show = false;
                } else if (currentStepFilter === 'saved') {
                    if (status !== 'registration_completed') show = false;
                } else if (currentStepFilter === 'cancelled') {
                    if (status !== 'registration_cancelled') show = false;
                } else {
                    // Step Filter
                    // Should exclude cancelled? usually yes.
                    if (status === 'registration_cancelled') show = false;
                    // Check if highest step matches filter
                    if (highestStepId != currentStepFilter) show = false;
                }
            } else {
                // Default View: Hide Cancelled employees usually?
                // The PHP controller returns them, but usually they clog the view.
                // The current view displays them.
                // If "Total" (No filter) is selected, we show everything active.
                // But wait, the previous logic didn't hide cancelled explicitly unless filtered.
            }

            if (show) {
                card.classList.remove('d-none');
                visibleCount++;
            } else {
                card.classList.add('d-none');
            }
        });

        // Hide employers with no visible employees
        document.querySelectorAll('[id^="employer-card-"]').forEach(empCard => {
            // If employer is cancelled, we generally hide it in default view?
            // The controller sorts them to bottom.
            // If we are filtering by 'cancelled' employees, we might show active employers who have cancelled staff.

            const visibleEmployees = empCard.querySelectorAll('.employee-card-wrapper:not(.d-none)');
            // Also check if the employer ITSELF is the target (for cancelled employer filter - handled above)

            if (currentStepFilter !== null) {
                // Filter is active: Hide if no matches
                if (visibleEmployees.length === 0) {
                    empCard.classList.add('d-none');
                } else {
                    empCard.classList.remove('d-none');
                }
            } else {
                // No filter active: Always show employer
                empCard.classList.remove('d-none');
            }
        });
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
                    title: '{{ __('Step Created') }}',
                    text: '{{ __('New workflow step added successfully!') }}',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            }
        });
    });

    function deleteStep(id) {
        // Use SweetAlert if available, else standard confirm
        Swal.fire({
            title: '{{ __('Delete Step?') }}',
            text: "{{ __('This will remove this step from all employees.') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: '{{ __('Yes, delete it!') }}'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/production/registration/steps/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire('{{ __('Deleted!') }}', '{{ __('Step has been deleted.') }}', 'success')
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
                    title: '{{ __('Saved!') }}',
                    text: '{{ __('Step settings updated successfully.') }}',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            }
        });
    }

    // --- Employee Actions (Updated for Immediate DOM Feedback & Stats) ---
    function finalizeEmployee(id) {
        Swal.fire({
            title: '{{ __('Save to Database?') }}',
            text: "{{ __('The employee will be marked as completed.') }}",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '{{ __('Yes, Save') }}'
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

                            Swal.fire('{{ __('Saved!') }}', '{{ __('Employee marked as completed.') }}', 'success');

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
            title: '{{ __('Restore to Pending?') }}',
            text: "{{ __('This will move the employee back to the active list.') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __('Yes, Restore') }}'
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

                            Swal.fire('{{ __('Restored!') }}', '{{ __('Employee is back to pending.') }}', 'success');

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
            title: '{{ __('Cancel Registration?') }}',
            text: "{{ __('The employee card will be grayed out.') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6c757d',
            confirmButtonText: '{{ __('Yes, Cancel') }}'
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

                            Swal.fire('{{ __('Cancelled') }}', '{{ __('Registration cancelled.') }}', 'success');

                            applyFilters();
                            recalculateVisibleStats();
                        }
                    }
                });
             }
        });
    }

    function cancelEmployer(id) {
        Swal.fire({
            title: '{{ __('Cancel Employer?') }}',
            text: "{{ __('This will seal the card and move it to the end. Active employees will also be cancelled.') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: '{{ __('Yes, Cancel Employer') }}'
        }).then((result) => {
             if (result.isConfirmed) {
                fetch(`/production/registration/employer/${id}/cancel`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire('{{ __('Cancelled') }}', '{{ __('Employer registration cancelled.') }}', 'success')
                        .then(() => location.reload()); // Reload to handle sorting and complex UI changes
                    }
                });
             }
        });
    }

    function restoreEmployer(id) {
        Swal.fire({
            title: '{{ __('Restore Employer?') }}',
            text: "{{ __('This will restore the employer card and active employees.') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __('Yes, Restore') }}'
        }).then((result) => {
             if (result.isConfirmed) {
                fetch(`/production/registration/employer/${id}/restore`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire('{{ __('Restored') }}', '{{ __('Employer restored.") }}', 'success')
                        .then(() => location.reload());
                    }
                });
             }
        });
    }

    function deleteEmployee(id) {
        Swal.fire({
            title: '{{ __('Delete Employee?') }}',
            text: "{{ __('This will move the employee to the trash.') }}",
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: '{{ __('Yes, Delete') }}'
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
                        Swal.fire('{{ __('Deleted!') }}', '{{ __('Employee has been deleted.') }}', 'success');
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('{{ __('Error') }}', '{{ __('Could not delete employee. Check console.') }}', 'error');
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
                showToast('{{ __('Failed to update progress: ') }}' + error.message, 'danger');
            } else {
                alert('{{ __('Failed: ') }}' + error.message);
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

        // --- Sequence Number Logic ---
        // DEPRECATED: CSS Counters are now used for robust numbering.
        // function updateSequenceNumbers() { ... }

        // --- Restore UI State on Load (After Reload) ---
        const restoreEmployerId = sessionStorage.getItem('registration_restore_employer_id');
        const restoreEmployeeId = sessionStorage.getItem('registration_restore_employee_id');

        if (restoreEmployerId) {
            sessionStorage.removeItem('registration_restore_employer_id'); // Clear immediately
            const collapseEl = document.getElementById(`collapse${restoreEmployerId}`);
            if (collapseEl) {
                const bsCollapse = new bootstrap.Collapse(collapseEl, {
                    toggle: false
                });
                bsCollapse.show();

                // If we also have an employee to scroll to
                if (restoreEmployeeId) {
                    sessionStorage.removeItem('registration_restore_employee_id');
                    // Wait for collapse animation (approx 350ms usually, but we can try slightly more)
                    // Or listen to event 'shown.bs.collapse'
                    collapseEl.addEventListener('shown.bs.collapse', function () {
                        const employeeCard = document.getElementById(`employee-card-${restoreEmployeeId}`);
                        if (employeeCard) {
                            employeeCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            // Optional: Highlight effect
                            employeeCard.classList.add('border-warning');
                            setTimeout(() => employeeCard.classList.remove('border-warning'), 2000);
                        }
                    }, { once: true });
                }
            }
        }
    });
</script>
@endpush

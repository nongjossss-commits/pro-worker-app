@extends('layouts.app')

@section('title', 'Renewal Resolution')

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
        position: absolute;
        top: 8px;
        right: 12px;
        font-size: 1.1rem;
        font-weight: bold;
        color: #adb5bd;
        z-index: 1;
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
        position: absolute;
        top: 4px;
        right: 8px;
        font-size: 0.8rem;
        font-weight: bold;
        color: #adb5bd;
        z-index: 1;
    }

    /* Mobile/Tablet optimizations for employee cards */
    @media (max-width: 1024px) {
        .employee-card-wrapper .card-body { padding: 0.5rem !important; padding-top: 0.75rem !important; }
        .employee-card-wrapper .card-body > .d-flex { flex-direction: column !important; }
        .employee-card-wrapper .emp-info-section,
        .employee-card-wrapper .emp-info-section > .d-flex,
        .employee-card-wrapper .emp-info-section .d-flex.align-items-center.gap-3,
        .employee-card-wrapper .emp-info-section [id^="info-container-"] {
            flex-direction: column !important; align-items: center !important; text-align: center; width: 100% !important;
        }
        .employee-card-wrapper .emp-info-section .form-check:has(.employee-checkbox) { position: absolute; top: 28px; left: 8px; z-index: 2; }
        .employee-card-wrapper .position-absolute.bottom-0.end-0 { position: absolute !important; bottom: 8px !important; right: 8px !important; top: auto !important; left: auto !important; margin: 0 !important; }
        .employee-card-wrapper [style*="min-width: 250px"] { min-width: unset !important; width: 100% !important; }
    }

    @media (max-width: 576px) {
        .employee-card-wrapper .card-body { padding: 0.4rem !important; padding-top: 0.6rem !important; }
        .employee-card-wrapper .fw-bold.text-dark { font-size: 0.85rem; }
        .employee-card-wrapper .small, .employee-card-wrapper .text-muted.small { font-size: 0.7rem !important; }
    }
</style>

<div class="container-fluid">
    <x-resolution-tab-bar :currentTab="$currentTab" :allTabs="$allTabs" type="renewal" routePrefix="production.renewal" />

    {{-- Top Stats --}}
    <div class="row row-cols-1 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
        {{-- Total Employees --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm cursor-pointer" onclick="window.location.href = window.location.pathname;" style="background-color: #FBBF24; border: none;"> {{-- Yellow-ish --}}
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

        {{-- Appointment: Not Scheduled --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm cursor-pointer filter-card"
                 id="filter-appointment_not_scheduled"
                 onclick="toggleFilter('appointment_not_scheduled')"
                 style="background-color: #F97316; border: none; transition: transform 0.2s;"> {{-- Orange --}}
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0" id="global-not-scheduled-count">{{ $totalNotScheduled ?? 0 }}</h1>
                    <p class="fs-6 fw-light mb-0">{{ __('Not Scheduled') }}</p>
                </div>
            </div>
        </div>

        {{-- Appointment: Pending --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm cursor-pointer filter-card"
                 id="filter-appointment_pending"
                 onclick="toggleFilter('appointment_pending')"
                 style="background-color: #8B5CF6; border: none; transition: transform 0.2s;"> {{-- Purple --}}
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0" id="global-appointments-pending-count">{{ $totalAppointmentsPending ?? 0 }}</h1>
                    <p class="fs-6 fw-light mb-0">{{ __('Appointment Pending') }}</p>
                </div>
            </div>
        </div>

        {{-- Appointment: Completed --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm cursor-pointer filter-card"
                 id="filter-appointment_completed"
                 onclick="toggleFilter('appointment_completed')"
                 style="background-color: #059669; border: none; transition: transform 0.2s;"> {{-- Emerald --}}
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0" id="global-appointments-completed-count">{{ $totalAppointmentsCompleted ?? 0 }}</h1>
                    <p class="fs-6 fw-light mb-0">{{ __('Appointment Completed') }}</p>
                </div>
            </div>
        </div>

        {{-- Daily Check Pending (NEW) --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm cursor-pointer filter-card"
                 id="filter-pending_daily_check"
                 onclick="toggleFilter('pending_daily_check')"
                 style="background-color: #6366f1; border: none; transition: transform 0.2s;"> {{-- Indigo-500 --}}
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0" id="global-daily-check-pending-count">{{ $totalDailyCheckPending ?? 0 }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Daily Check Pending') }}</p>
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
                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-sm px-3 fw-bold shadow-sm" onclick="openCalendarModal()">
                        <i class="bi bi-calendar-event me-1"></i> {{ __('Calendar') }}
                    </button>
                    <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#resolutionSettingsModal">
                        <i class="bi bi-robot me-1"></i> {{ __('Auto Settings') }}
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#manageStepsModal">
                        <i class="bi bi-gear-fill me-1"></i> {{ __('Steps') }}
                    </button>
                </div>
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
    <x-address-filter :provinces="$addressOptions['provinces']" :districts="$addressOptions['districts']" :subDistricts="$addressOptions['subDistricts']" />

    <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-body p-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('production.renewal.index', ['resolutionTab' => $currentTab->id]) }}" class="btn btn-outline-secondary btn-sm" title="{{ __('Back to Dashboard') }}">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <h4 class="mb-0 text-primary fw-bold text-nowrap"><i class="bi bi-arrow-repeat me-2"></i>{{ __('Renewal Resolution') }}</h4>
                </div>

                <form action="{{ route('production.renewal.operations', ['resolutionTab' => $currentTab->id]) }}" method="GET" class="d-flex flex-grow-1 w-100" style="max-width: 600px;">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="{{ __('Search employee or employer...') }}" value="{{ request('search') }}">
                        <button class="btn btn-primary" type="submit">{{ __('Search') }}</button>
                        @if(request('search'))
                            <a href="{{ route('production.renewal.operations', ['resolutionTab' => $currentTab->id]) }}" class="btn btn-outline-secondary">{{ __('Clear') }}</a>
                        @endif
                    </div>
                </form>

                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-fill me-1"></i>
                        @if(request('operator_filter') == 'external')
                            ผู้ยื่นภายนอก
                        @else
                            {{ request('operator_filter') ? ($activeOperators->firstWhere('id', request('operator_filter'))->name ?? __('Operator')) : __('Operator') }}
                        @endif
                    </button>
                    <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                        <li><a class="dropdown-item" href="{{ route('production.renewal.operations', array_merge(request()->query(), ['operator_filter' => null, 'resolutionTab' => $currentTab->id])) }}">{{ __('All Operators') }}</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item {{ request('operator_filter') == 'external' ? 'active' : '' }}" href="{{ route('production.renewal.operations', array_merge(request()->query(), ['operator_filter' => 'external', 'resolutionTab' => $currentTab->id])) }}">ผู้ยื่นภายนอก (พิมพ์ชื่อเอง)</a></li>
                        <li><hr class="dropdown-divider"></li>
                        @foreach($activeOperators as $user)
                            <li><a class="dropdown-item {{ request('operator_filter') == $user->id ? 'active' : '' }}" href="{{ route('production.renewal.operations', array_merge(request()->query(), ['operator_filter' => $user->id, 'resolutionTab' => $currentTab->id])) }}">{{ $user->name }}</a></li>
                        @endforeach
                    </ul>
                </div>

                {{-- Insurance Filter --}}
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-shield-check me-1"></i>
                        {{ request('insurance_filter') ? (request('insurance_filter') === 'none' ? __('No Insurance') : request('insurance_filter')) : __('Insurance') }}
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('production.renewal.operations', array_merge(request()->query(), ['insurance_filter' => null, 'resolutionTab' => $currentTab->id])) }}">{{ __('All Types') }}</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('production.renewal.operations', array_merge(request()->query(), ['insurance_filter' => 'ประกันสังคม', 'resolutionTab' => $currentTab->id])) }}">{{ __('ประกันสังคม') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('production.renewal.operations', array_merge(request()->query(), ['insurance_filter' => 'ประกันเอกชน', 'resolutionTab' => $currentTab->id])) }}">{{ __('ประกันเอกชน') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('production.renewal.operations', array_merge(request()->query(), ['insurance_filter' => 'ประกันโรงพยาบาล', 'resolutionTab' => $currentTab->id])) }}">{{ __('ประกันโรงพยาบาล') }}</a></li>
                        <li><a class="dropdown-item text-muted" href="{{ route('production.renewal.operations', array_merge(request()->query(), ['insurance_filter' => 'none', 'resolutionTab' => $currentTab->id])) }}">{{ __('No Insurance') }}</a></li>
                    </ul>
                </div>

                <div class="d-flex gap-2 flex-wrap justify-content-end">
                    @can('edit-employees')
                    <a href="{{ route('production.renewal.create', ['resolutionTab' => $currentTab->id]) }}" class="btn btn-warning text-white fw-bold">
                        <i class="bi bi-plus-lg me-1"></i> {{ __('New Employee') }}
                    </a>
                    <a href="{{ route('production.renewal.import', ['resolutionTab' => $currentTab->id]) }}" class="btn btn-success fw-bold">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> {{ __('Import Employees') }}
                    </a>
                    <button class="btn btn-dark fw-bold" data-bs-toggle="modal" data-bs-target="#expiryConfigModal">
                        <i class="bi bi-calendar-check me-1"></i> {{ __('Configuration / Import by Expiry') }}
                    </button>
                    <button class="btn btn-secondary fw-bold" onclick="openTrashModal()">
                        <i class="bi bi-trash-fill me-1"></i> {{ __('Trash') }}
                    </button>
                    <button class="btn btn-outline-secondary fw-bold ms-2" id="btn-global-toggle-cancelled" onclick="toggleGlobalCancelled()">
                        <i class="bi bi-eye-slash-fill me-1"></i> {{ __('Hide Cancelled') }}
                    </button>
                    @endcan
                </div>
            </div>

            @can('edit-employees')
            {{-- Bulk Action Bar --}}
            <div class="bulk-action-bar mt-3 align-items-center gap-2 p-2 bg-light border rounded shadow-lg"
                 style="display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 1060; width: auto; min-width: 400px;"
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
                        @can('manage-tickets')
                        <li><a class="dropdown-item" href="#" id="bulk-generate-pdf-btn"><i class="bi bi-file-earmark-pdf me-2"></i>{{ __('Automated PDF') }}</a></li>
                        @endcan
                    </ul>
                </div>

                <button class="btn btn-sm btn-outline-danger ms-2" onclick="window.clearGlobalSelection();">{{ __('Clear Selection') }}</button>
                <button class="btn btn-sm btn-info text-white" id="btn-view-selected" onclick="window.openViewSelectedModal()">
                    <i class="bi bi-eye me-1"></i> {{ __('View Selected') }}
                    <span class="badge bg-white text-info ms-1" id="view-selected-count">0</span>
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

            <div class="employer-card-container w-100 mb-4" id="employer-card-{{ $employer->id }}" data-is-cancelled="{{ $isEmployerCancelled ? 'true' : 'false' }}">
                <div class="card shadow-sm overflow-visible {{ $employerCardClass }}" style="position: relative;">
                <div class="employer-sequence-number"></div>
                    <x-last-edited-badge :model="$employer" />
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

                    </div>

                    <div class="card-header py-3 px-4 border-bottom {{ $employerHeaderClass }}" id="heading{{ $employer->id }}">

                    {{-- Inline Note Editor (Top Center) --}}
                    <div class="d-flex justify-content-center mb-2">
                        <div class="position-relative w-50" x-data="{
                            editing: false,
                            note: {{ json_encode($employer->renewal_resolution_note ?? '') }},
                            tempNote: '',
                            saving: false,
                            startEditing() {
                                this.tempNote = this.note;
                                this.editing = true;
                                this.$nextTick(() => { this.$refs.noteInput.focus(); });
                            },
                            saveNote() {
                                this.saving = true;
                                fetch(`{{ url('production/renewal/' . $currentTab->id . '/employer') }}/{{ $employer->id }}/resolution-note`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    body: JSON.stringify({ note: this.tempNote })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    this.saving = false;
                                    if(data.success) {
                                        this.note = data.note ?? this.tempNote;
                                        this.editing = false;
                                    } else {
                                        Swal.fire('Error', 'Failed to save note', 'error');
                                    }
                                })
                                .catch(err => {
                                    this.saving = false;
                                    Swal.fire('Error', 'Network error', 'error');
                                });
                            }
                        }">
                            <div x-cloak :style="{ display: !editing ? 'flex' : 'none' }" class="align-items-start gap-1 w-100">
                                <div class="text-dark small border rounded px-2 py-1 bg-light flex-grow-1 text-wrap overflow-hidden" style="min-height: 31px; word-break: break-word;">
                                    <span x-text="note || '-'"></span>
                                </div>
                                <button @click="startEditing()" class="btn btn-sm btn-outline-secondary rounded-circle flex-shrink-0" style="padding: 2px 6px;" title="{{ __('Edit Note') }}">
                                    <i class="bi bi-pencil-fill" style="font-size: 0.75rem;"></i>
                                </button>
                            </div>

                            <div x-cloak :style="{ display: editing ? 'block' : 'none' }" class="w-100">
                                <textarea x-ref="noteInput" x-model="tempNote" class="form-control form-control-sm mb-1" rows="3" placeholder="{{ __('Note...') }}"></textarea>
                                <div class="d-flex gap-1 justify-content-end">
                                    <button @click="saveNote()" :disabled="saving" class="btn btn-sm btn-success flex-grow-1" style="max-width: 80px;">
                                        <i class="bi bi-check-lg" x-show="!saving"></i>
                                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" x-show="saving" style="display: none;"></span>
                                    </button>
                                    <button @click="editing = false" :disabled="saving" class="btn btn-sm btn-outline-secondary flex-shrink-0">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                <h4 class="fw-bold mb-0 text-primary text-truncate" style="max-width: 300px;">
                                    {{ $employer->employerNameTh }}
                                    @if(request('addrProvince'))
                                        @foreach($employer->getMatchedAddressLabels(request('addrProvince'), request('addrDistrict'), request('addrSubDistrict')) as $label)
                                            <span class="badge bg-info text-white small ms-1" style="font-size: 0.7rem;">{{ $label }}</span>
                                        @endforeach
                                    @endif
                                </h4>
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
                                    <a href="{{ route('production.renewal.operations', ['search' => $employer->jobOwner->name, 'resolutionTab' => $currentTab->id]) }}" class="text-decoration-none text-secondary">
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
                                 <a href="{{ route('production.renewal.create', ['employer_id' => $employer->id, 'resolutionTab' => $currentTab->id]) }}" class="btn btn-outline-warning btn-sm fw-bold {{ $isEmployerCancelled ? 'd-none' : '' }}">
                                    <i class="bi bi-plus-lg"></i> {{ __('Add') }}
                                 </a>

                                 {{-- History Button --}}
                                 <button class="btn btn-outline-secondary btn-sm" onclick="openHistoryModal({{ $employer->id }})" title="{{ __('View History') }}">
                                     <i class="bi bi-clock-history"></i>
                                 </button>
                                 @endcan

                                 {{-- Finance Button --}}
                                 @can('view-finance')
                                 <button class="btn btn-outline-primary btn-sm" onclick="event.stopPropagation(); FinancialSecurity.checkAndRun(() => openFinanceModal({{ $employer->id }}))">
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

                                <button class="btn btn-outline-secondary btn-sm ms-1" id="btn-employer-toggle-cancelled-{{ $employer->id }}" onclick="toggleEmployerCancelled({{ $employer->id }}); event.stopPropagation();" title="{{ __('Hide Cancelled Items') }}">
                                    <i class="bi bi-eye-slash"></i>
                                </button>

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
                            <div class="d-flex justify-content-center align-items-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <span class="ms-2 small text-muted">Loading employees...</span>
                            </div>
                         </div>
                    </div>
                </div>
            </div> {{-- End card --}}

            {{-- Finance Modal for this Employer --}}
            <div class="modal fade" id="financeModal-{{ $employer->id }}" tabindex="-1" aria-hidden="true" onclick="event.stopPropagation()">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('Finance') }}: {{ $employer->employerNameTh }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body bg-light" id="finance-modal-body-{{ $employer->id }}">
                            <div class="d-flex justify-content-center align-items-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <span class="ms-2 small text-muted">{{ __('Loading financial data...') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div> {{-- End employer-card-container --}}
        @endforeach
    </div>

    <div class="mt-4 d-flex justify-content-between align-items-center mb-5">
        @include('partials.per_page_selector')
        <div>
            {{ $employers->links() }}
        </div>
    </div>
</div>

{{-- GPS Navigate: hidden input เก็บค่าให้ script ใน @push อ่าน --}}
@if(request('highlight_employer_id'))
<input type="hidden" id="gps-highlight-employer" value="{{ request('highlight_employer_id') }}">
<input type="hidden" id="gps-highlight-employee" value="{{ request('highlight_employee_id', '') }}">
@endif

{{-- Configuration Modal --}}
<div class="modal fade" id="expiryConfigModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-calendar-check me-2"></i>{{ __('Import Employees by Expiry') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('production.renewal.configure_expiry', ['resolutionTab' => $currentTab->id]) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <p class="text-muted mb-3">
                        {{ __('Select a target expiry date. The system will search for employees whose Work Permit OR Visa expires on this date (excluding MOU types) and add them to the Renewal list.') }}
                    </p>
                    <p class="small text-secondary mb-3">
                        <i class="bi bi-info-circle me-1"></i> {{ __('This setting is permanent until changed. Future employees matching this date will be automatically added.') }}
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('Target Expiry Date') }}</label>
                        <input type="date" name="target_expiry_date" class="form-control form-control-lg" required value="{{ $currentExpiryConfig ?? '' }}">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-success fw-bold">{{ __('Import Employees') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Include Drawer --}}
@include('production.registration.partials.offcanvas_drawer')

{{-- Include Add Custom Field Modal --}}
@include('production.registration.partials.modals.add_custom_field')

{{-- Include Advanced Export & Target Employer Modals (reused from Employees) --}}
@include('employees.modals.advanced_export')
@include('employees.modals.select_target_employer_modal')

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

{{-- Cropper Modal (Required for Employee Edit) --}}
<x-cropper-modal />

{{-- Resolution Auto-Settings Modal --}}
<div class="modal fade" id="resolutionSettingsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-dark">
                <h5 class="modal-title fw-bold"><i class="bi bi-robot me-2"></i>{{ __('Auto Settings (Resolution)') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">{{ __('These settings will automatically update employees 24 hours after they are marked as completed.') }}</p>
                <div class="mb-3">
                    <label class="form-label fw-bold">{{ __('Auto Work Permit Expiry Date') }}</label>
                    <input type="date" class="form-control" id="autoWorkPermitInput" value="{{ $resolutionSettings['renewal_auto_work_permit_expiry'] ?? '' }}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">{{ __('Auto Visa Expiry Date') }}</label>
                    <input type="date" class="form-control" id="autoVisaInput" value="{{ $resolutionSettings['renewal_auto_visa_expiry'] ?? '' }}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">{{ __('Auto MOU Group (Work Type)') }}</label>
                    <select class="form-select" id="autoMouInput">
                        <option value="">-- {{ __('No Auto Update') }} --</option>
                        <option value="MOU" {{ ($resolutionSettings['renewal_auto_mou_group'] ?? '') == 'MOU' ? 'selected' : '' }}>MOU</option>
                        <option value="MOU 2 ปีหลัง" {{ ($resolutionSettings['renewal_auto_mou_group'] ?? '') == 'MOU 2 ปีหลัง' ? 'selected' : '' }}>MOU 2 ปีหลัง</option>
                        <option value="มติต่ออายุในประเทศ" {{ ($resolutionSettings['renewal_auto_mou_group'] ?? '') == 'มติต่ออายุในประเทศ' ? 'selected' : '' }}>มติต่ออายุในประเทศ</option>
                        <option value="มติขึ้นทะเบียน" {{ ($resolutionSettings['renewal_auto_mou_group'] ?? '') == 'มติขึ้นทะเบียน' ? 'selected' : '' }}>มติขึ้นทะเบียน</option>
                        <option value="อื่นๆ" {{ ($resolutionSettings['renewal_auto_mou_group'] ?? '') == 'อื่นๆ' ? 'selected' : '' }}>อื่นๆ</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-link text-secondary text-decoration-none" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-info px-4" onclick="saveResolutionSettings()">
                    <i class="bi bi-save-fill me-1"></i> {{ __('Save Settings') }}
                </button>
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

                            <div class="d-flex align-items-center gap-2">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="moveStep({{ $step->id }}, 'up')" title="{{ __('Move Up') }}">
                                        <i class="bi bi-arrow-up"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="moveStep({{ $step->id }}, 'down')" title="{{ __('Move Down') }}">
                                        <i class="bi bi-arrow-down"></i>
                                    </button>
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

{{-- Large Calendar Modal (Workflow Style) --}}
<div class="modal fade" id="calendarModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <div class="modal-content bg-light">
            <div class="modal-header bg-primary text-white border-0 shadow-sm z-3">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-calendar-event me-2"></i>{{ __('Appointment Calendar') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" x-data="calendarApp()">
                <div class="row g-4 h-100">
                    {{-- Left Column: Monthly Calendar --}}
                    <div class="col-lg-5 col-xl-4 h-100 d-flex flex-column">
                        <div class="card border-0 shadow-sm flex-grow-1 d-flex flex-column">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom-0">
                                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-calendar-month me-2"></i>{{ __('Monthly Overview') }}</h5>
                                <div class="d-flex align-items-center gap-2">
                                    <button class="btn btn-sm btn-light border" @click="prevMonth()"><i class="bi bi-chevron-left"></i></button>
                                    <span class="fw-bold text-uppercase" style="min-width: 120px; text-align: center;" x-text="monthNames[month] + ' ' + year"></span>
                                    <button class="btn btn-sm btn-light border" @click="nextMonth()"><i class="bi bi-chevron-right"></i></button>
                                </div>
                            </div>
                            <div class="card-body p-3 flex-grow-1 d-flex flex-column">
                                {{-- Calendar Grid --}}
                                <div class="d-grid text-center mb-2" style="grid-template-columns: repeat(7, 1fr); font-size: 0.8rem; font-weight: bold; color: #6c757d;">
                                    <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                                </div>
                                <div class="d-grid flex-grow-1" style="grid-template-columns: repeat(7, 1fr); gap: 5px; min-height: 0;">
                                    <template x-for="day in days" :key="day.dateStr">
                                        <div
                                            class="border rounded p-2 d-flex flex-column align-items-center justify-content-between position-relative cursor-pointer transition-all h-100"
                                            :class="{
                                                'bg-light text-muted': !day.isCurrentMonth,
                                                'bg-white': day.isCurrentMonth,
                                                'border-primary bg-primary bg-opacity-10 shadow-sm': day.dateStr === selectedDate,
                                                'border-info bg-info bg-opacity-10': day.isToday && day.dateStr !== selectedDate
                                            }"
                                            @click="openDay(day.dateStr)"
                                        >
                                            <span class="fw-bold" style="font-size: 1.1rem;" x-text="day.dayNum"></span>

                                            <template x-if="counts[day.dateStr]">
                                                <span class="badge bg-danger rounded-pill mt-1" style="font-size: 0.75rem;" x-text="counts[day.dateStr]"></span>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Right Column: Appointments List --}}
                    <div class="col-lg-7 col-xl-8 h-100 d-flex flex-column">
                        <div class="card border-0 shadow-sm flex-grow-1 d-flex flex-column">
                            <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold text-primary mb-0">
                                    <i class="bi bi-list-check me-2"></i>{{ __('Appointments for') }}: <span class="text-dark" x-text="selectedDateFormatted"></span>
                                </h5>

                                <div class="input-group" style="max-width: 300px;">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0 bg-light" placeholder="{{ __('Search names, employer...') }}" x-model="searchQuery">
                                </div>
                            </div>
                            <div class="card-body p-0 overflow-auto bg-light position-relative custom-scrollbar" style="min-height: 300px;">

                                <div x-show="isLoading" class="position-absolute w-100 h-100 bg-white bg-opacity-75" style="z-index: 10;">
                                    <div class="w-100 h-100 d-flex justify-content-center align-items-center">
                                        <div class="spinner-border text-primary" role="status"></div>
                                    </div>
                                </div>

                                <div id="dayAppointmentsContent" class="p-3">
                                    {{-- Content loaded via AJAX will go here --}}
                                    <div x-show="!isLoading && (!appointmentsLoaded || Object.keys(counts).length === 0)" class="text-center py-5 text-muted">
                                        <i class="bi bi-calendar-x fs-1 opacity-25"></i>
                                        <p class="mt-2">{{ __('Select a date to view appointments.') }}</p>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- History Modal --}}
<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-clock-history me-2"></i>{{ __('Job History') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light" id="historyModalBody">
                 <div class="d-flex justify-content-center py-5">
                     <div class="spinner-border text-secondary" role="status"></div>
                 </div>
            </div>
        </div>
    </div>
</div>

{{-- Trash Modal --}}
<div class="modal fade" id="trashModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-trash-fill me-2"></i>{{ __('Trash Bin') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light" id="trashModalBody">
                 <div class="d-flex justify-content-center py-5">
                     <div class="spinner-border text-danger" role="status"></div>
                 </div>
            </div>
        </div>
    </div>
</div>

@endsection

@include('employees.partials._edit_scripts')
@include('production.registration.partials.edit_modal_script')

@push('scripts')
<script src="{{ asset('js/financial-manager.js') }}?v={{ @filemtime(public_path('js/financial-manager.js')) }}"></script>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('appointmentSearch', () => ({
            searchQuery: '',
            matchesSearch(el) {
                if (!this.searchQuery) return true;

                const query = this.searchQuery.toLowerCase();
                const nameTh = el.dataset.employeeNameTh || '';
                const nameEn = el.dataset.employeeNameEn || '';
                const employerName = el.dataset.employerName || '';
                const reference = el.dataset.reference || '';

                return nameTh.includes(query) ||
                       nameEn.includes(query) ||
                       employerName.includes(query) ||
                       reference.includes(query);
            }
        }));
    });

    // State for Global Server-Side Filter
    const currentStepFilter = @json(request('filter'));
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const lastStepId = @json($lastStepId);
    window.allUsers = @json($allUsers);

    // --- Calendar Logic ---
    window.openCalendarModal = function() {
        const modal = new bootstrap.Modal(document.getElementById('calendarModal'));
        modal.show();
    }

    function calendarApp() {
        return {
            month: new Date().getMonth(),
            year: new Date().getFullYear(),
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            days: [],
            counts: {},
            selectedDate: null,
            selectedDateFormatted: '',
            isLoading: false,
            appointmentsLoaded: false,
            searchQuery: '',

            init() {
                this.generateCalendar();
                this.fetchCounts();
                window.currentAppointmentContext = { module: 'production/renewal', tabId: {{ $currentTab->id }} };

                this.$watch('searchQuery', (value) => {
                    const cards = document.querySelectorAll('.appointment-card');
                    const query = value.toLowerCase();
                    cards.forEach(card => {
                        const nameTh = card.dataset.employeeNameTh || '';
                        const nameEn = card.dataset.employeeNameEn || '';
                        const employer = card.dataset.employerName || '';
                        const ref = card.dataset.reference || '';

                        if (nameTh.includes(query) || nameEn.includes(query) || employer.includes(query) || ref.includes(query)) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            },

            prevMonth() {
                if (this.month === 0) {
                    this.month = 11;
                    this.year--;
                } else {
                    this.month--;
                }
                this.generateCalendar();
                this.fetchCounts();
            },

            nextMonth() {
                if (this.month === 11) {
                    this.month = 0;
                    this.year++;
                } else {
                    this.month++;
                }
                this.generateCalendar();
                this.fetchCounts();
            },

            generateCalendar() {
                const firstDay = new Date(this.year, this.month, 1);
                const lastDay = new Date(this.year, this.month + 1, 0);
                const daysInMonth = lastDay.getDate();
                const startingDay = firstDay.getDay(); // 0 = Sunday

                let calendarDays = [];

                // Previous month padding
                const prevMonthLastDay = new Date(this.year, this.month, 0).getDate();
                for (let i = startingDay - 1; i >= 0; i--) {
                    let d = prevMonthLastDay - i;
                    let pm = this.month - 1;
                    let py = this.year;
                    if(pm < 0) { pm = 11; py--; }

                    calendarDays.push({
                        dayNum: d,
                        isCurrentMonth: false,
                        isToday: false,
                        dateStr: `${py}-${String(pm+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`
                    });
                }

                // Current Month
                const today = new Date();
                for (let i = 1; i <= daysInMonth; i++) {
                    const isToday = (i === today.getDate() && this.month === today.getMonth() && this.year === today.getFullYear());
                    calendarDays.push({
                        dayNum: i,
                        isCurrentMonth: true,
                        isToday: isToday,
                        dateStr: `${this.year}-${String(this.month+1).padStart(2,'0')}-${String(i).padStart(2,'0')}`
                    });
                }

                // Next Month padding
                const remaining = 42 - calendarDays.length;
                for (let i = 1; i <= remaining; i++) {
                    let nm = this.month + 1;
                    let ny = this.year;
                    if(nm > 11) { nm = 0; ny++; }

                    calendarDays.push({
                        dayNum: i,
                        isCurrentMonth: false,
                        isToday: false,
                        dateStr: `${ny}-${String(nm+1).padStart(2,'0')}-${String(i).padStart(2,'0')}`
                    });
                }

                this.days = calendarDays;
            },

            fetchCounts() {
                fetch(`{{ route('production.renewal.api.calendar', ['resolutionTab' => $currentTab->id]) }}?month=${this.month + 1}&year=${this.year}`)
                    .then(res => res.json())
                    .then(data => {
                        this.counts = data;
                    });
            },

            openDay(dateStr) {
                this.selectedDate = dateStr;
                const d = new Date(dateStr);
                this.selectedDateFormatted = d.toLocaleDateString('{{ app()->getLocale() }}', { day: 'numeric', month: 'short', year: 'numeric' });
                this.isLoading = true;
                this.appointmentsLoaded = true;

                fetch(`{{ route('production.renewal.api.appointments_by_date', ['resolutionTab' => $currentTab->id]) }}?date=${dateStr}`)
                    .then(res => res.json())
                    .then(data => {
                        document.getElementById('dayAppointmentsContent').innerHTML = data.html;
                        this.isLoading = false;
                        this.searchQuery = '';
                    })
                    .catch(() => {
                        this.isLoading = false;
                    });
            }
        }
    }

    // Needed for the appointment edit modal called from the day appointments list html
    window.editAppointment = function(employeeId, currentDate, currentLocation, isCompleted) {
        Swal.fire({
            title: '{{ __("Update Appointment") }}',
            html: `
                <div class="mb-3 text-start">
                    <label class="form-label fw-bold">{{ __("Appointment Date & Time") }}</label>
                    <input type="datetime-local" id="swal-appointment-date" class="form-control" value="${currentDate}">
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label fw-bold">{{ __("Location / Note") }}</label>
                    <input type="text" id="swal-appointment-location" class="form-control" value="${currentLocation}" placeholder="{{ __('e.g., Main Office') }}">
                </div>
                <div class="form-check form-switch text-start mt-4">
                    <input class="form-check-input" type="checkbox" id="swal-appointment-complete" ${isCompleted ? 'checked' : ''}>
                    <label class="form-check-label fw-bold" for="swal-appointment-complete">{{ __("Mark as Completed") }}</label>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '{{ __("Save Changes") }}',
            cancelButtonText: '{{ __("Cancel") }}',
            focusConfirm: false,
            preConfirm: () => {
                return {
                    date: document.getElementById('swal-appointment-date').value,
                    location: document.getElementById('swal-appointment-location').value,
                    isComplete: document.getElementById('swal-appointment-complete').checked
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const data = result.value;
                const moduleUrl = window.currentAppointmentContext ? window.currentAppointmentContext.module : 'production/renewal';
                const tabId = (window.currentAppointmentContext && window.currentAppointmentContext.tabId) ? window.currentAppointmentContext.tabId : {{ $currentTab->id }};

                // First update the details
                fetch(`/${moduleUrl}/${tabId}/${employeeId}/appointment`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        appointment_date: data.date,
                        appointment_location: data.location
                    })
                }).then(res => res.json()).then(response => {
                    // Then handle the complete toggle if it changed
                    if (data.isComplete !== isCompleted) {
                        return fetch(`/${moduleUrl}/${tabId}/${employeeId}/appointment-complete`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                            }
                        });
                    }
                    return Promise.resolve();
                }).then(() => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: '{{ __("Appointment Updated") }}',
                        showConfirmButton: false,
                        timer: 2000
                    });

                    // Trigger Alpine.js to refresh the selected day
                    const calendarScope = Alpine.$data(document.querySelector('[x-data="calendarApp()"]'));
                    if (calendarScope && calendarScope.selectedDate) {
                        calendarScope.fetchCounts();
                        calendarScope.openDay(calendarScope.selectedDate);
                    }

                    if (typeof window.fetchBatchStats === 'function') {
                        const visibleEmployers = Array.from(document.querySelectorAll('.employer-card-container')).map(el => el.id.replace('employer-card-', ''));
                        if (visibleEmployers.length > 0) {
                            fetchBatchStats(visibleEmployers);
                        }
                    }
                }).catch(err => {
                    Swal.fire('Error', 'Could not update appointment.', 'error');
                });
            }
        });
    }

    // Helper: Refresh Calendar Data manually
    window.refreshCalendarCounts = function() {
        const calendarScope = Alpine.$data(document.querySelector('[x-data="calendarApp()"]'));
        if (calendarScope) {
            calendarScope.fetchCounts();
        }

        // Trigger global batch stats update
        if (typeof fetchBatchStats === 'function') {
            const visibleEmployers = Array.from(document.querySelectorAll('.employer-card-container')).map(el => el.id.replace('employer-card-', ''));
            if (visibleEmployers.length > 0) {
                fetchBatchStats(visibleEmployers);
            }
        }
    }

    // --- Lazy Loading Logic ---
    window.loadedEmployers = {};
    window.employerCurrentPages = {};

    window.loadEmployees = function(employerId, pageUrl = null) {
        // Only skip if already loaded AND not a pagination request
        if (window.loadedEmployers[employerId] && !pageUrl) return;

        const container = document.getElementById(`employee-list-${employerId}`);
        if(pageUrl) {
            container.innerHTML = '<div class="d-flex justify-content-center align-items-center py-5"><div class="spinner-border text-primary" role="status"></div><span class="ms-2 small text-muted">Loading employees...</span></div>';
            // Save the pageUrl for this employer so it remembers its state
            window.employerCurrentPages[employerId] = pageUrl;
        }

        // Base URL for the new AJAX route
        // Use the saved page state if available, otherwise default to first page
        let baseUrl = pageUrl || window.employerCurrentPages[employerId] || `/production/renewal/{{ $currentTab->id }}/employer/${employerId}/employees`;

        // Append current search/filter params
        const url = new URL(baseUrl, window.location.origin);
        const currentParams = new URLSearchParams(window.location.search);

        // Strip out the main window 'page' param so it doesn't bleed into the AJAX URL
        // If the main window is on page 2, we do NOT want this employer's initial load to be on page 2
        // unless it was specifically saved via employerCurrentPages
        currentParams.forEach((value, key) => {
             if(key !== 'page') {
                 url.searchParams.append(key, value);
             }
        });

        // Ensure per_page is handled if selected
        const perPageSelect = document.getElementById(`perPage-${employerId}`);
        if(perPageSelect && !url.searchParams.has('per_page')) {
            url.searchParams.append('per_page', perPageSelect.value);
        }

        fetch(url)
        .then(res => res.text())
        .then(html => {
            container.innerHTML = html;
            window.loadedEmployers[employerId] = true;
            applyFilters(); // Re-apply client-side filters on newly loaded content
            if (window.refreshGlobalSelectionUI) {
                window.refreshGlobalSelectionUI();
            }
        })
        .catch(err => {
            container.innerHTML = `<div class="text-danger p-3">Failed to load employees. <button class="btn btn-sm btn-outline-primary" onclick="window.loadedEmployers[${employerId}]=false; loadEmployees(${employerId})">Retry</button></div>`;
            console.error(err);
        });
    }

    // Handle internal pagination clicks for employees
    document.addEventListener('click', function(e) {
        // Intercept pagination inside employee-list
        if(e.target.closest('.employee-list .pagination a')) {
            e.preventDefault();
            const url = e.target.closest('a').href;
            const employerId = e.target.closest('.employee-list').id.replace('employee-list-', '');
            window.loadEmployees(employerId, url);
        }
    });

    // Handle internal per page change
    document.addEventListener('change', function(e) {
        if(e.target.classList.contains('per-page-selector')) {
            const employerId = e.target.dataset.employerId;
            const perPage = e.target.value;
            const baseUrl = `/production/renewal/{{ $currentTab->id }}/employer/${employerId}/employees`;
            const url = new URL(baseUrl, window.location.origin);
            url.searchParams.append('per_page', perPage);
            url.searchParams.append('page', 1); // Reset to page 1 on resize
            window.loadEmployees(employerId, url.toString());
        }
    });

    // --- Stats & Finance Lazy Loading ---
    window.openFinanceModal = function(employerId) {
        const modalEl = document.getElementById(`financeModal-${employerId}`);
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        const body = document.getElementById(`finance-modal-body-${employerId}`);
        if (body.querySelector('[x-data]')) return;

        fetch(`/production/renewal/{{ $currentTab->id }}/employer/${employerId}/finance-tab`)
            .then(res => {
                if (!res.ok) throw new Error('Failed to load');
                return res.text();
            })
            .then(html => {
                body.innerHTML = html;
            })
            .catch(err => {
                body.innerHTML = '<div class="text-danger text-center p-4">Failed to load data.</div>';
                console.error(err);
            });
    }

    window.loadBatchStats = function() {
        const containers = document.querySelectorAll('.employer-card-container');
        const employerIds = Array.from(containers).map(el => el.id.replace('employer-card-', ''));

        if (employerIds.length === 0) return;

        const urlParams = new URLSearchParams(window.location.search);
        const search = urlParams.get('search');

        fetch('{{ route("production.renewal.stats.batch", ["resolutionTab" => $currentTab->id]) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                employer_ids: employerIds,
                search: search
            })
        })
        .then(res => res.json())
        .then(data => {
            for (const [empId, stats] of Object.entries(data)) {
                updateText(`employer-total-${empId}`, stats.activeEmployeesCount);
                updateText(`employer-not-started-${empId}`, stats.notStartedCount);
                updateText(`employer-cancelled-${empId}`, stats.cancelledCount);
                updateText(`employer-saved-${empId}`, stats.savedCount);

                const container = document.getElementById(`employer-stats-${empId}`);
                if (container && stats.stepStats) {
                    for (const [stepId, count] of Object.entries(stats.stepStats)) {
                        const badge = container.querySelector(`.employer-stat-badge[data-step-id="${stepId}"]`);
                        if (badge) badge.innerText = count;
                    }
                }
            }
        })
        .catch(err => console.error('Stats loading failed', err));
    }

    // Listen for Accordion Expand
    document.addEventListener('DOMContentLoaded', function() {
        // Trigger Stats Load
        loadBatchStats();

        const accordion = document.getElementById('employersAccordion');
        if (accordion) {
            accordion.addEventListener('show.bs.collapse', function (e) {
                if (e.target.classList.contains('accordion-collapse')) {
                    // Close other opened accordions (Single Employer Drawer behavior)
                    document.querySelectorAll('.accordion-collapse.show').forEach(openCollapse => {
                         if (openCollapse.id !== e.target.id) {
                             const bsCollapse = bootstrap.Collapse.getInstance(openCollapse);
                             if (bsCollapse) {
                                 bsCollapse.hide();
                             }
                         }
                    });

                    // ID is collapse{employerId}
                    const employerId = e.target.id.replace('collapse', '');
                    loadEmployees(employerId);
                }
            });

            // After expansion completes (and the previous card has fully collapsed,
            // shifting the layout), focus the heading of the just-opened card so
            // the user doesn't need to scroll up to find it.
            accordion.addEventListener('shown.bs.collapse', function (e) {
                if (!e.target.classList.contains('accordion-collapse')) return;
                const headingId = e.target.getAttribute('aria-labelledby');
                const heading = headingId ? document.getElementById(headingId) : null;
                if (heading) {
                    heading.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        }

        // Initial Filter UI State (Server-Side)
        if (currentStepFilter) {
             if (currentStepFilter === 'not_started') document.getElementById('filter-not-started')?.classList.add('filter-active');
             else if (currentStepFilter === 'saved') document.getElementById('filter-saved')?.classList.add('filter-active');
             else if (currentStepFilter === 'cancelled') document.getElementById('filter-cancelled')?.classList.add('filter-active');
             else if (currentStepFilter === 'cancelled_employer') document.getElementById('filter-cancelled-employer')?.classList.add('filter-active');
             else if (currentStepFilter === 'appointment_not_scheduled') document.getElementById('filter-appointment_not_scheduled')?.classList.add('filter-active');
             else if (currentStepFilter === 'appointment_pending') document.getElementById('filter-appointment_pending')?.classList.add('filter-active');
             else if (currentStepFilter === 'appointment_completed') document.getElementById('filter-appointment_completed')?.classList.add('filter-active');
             else if (currentStepFilter === 'pending_daily_check') document.getElementById('filter-pending_daily_check')?.classList.add('filter-active');
             else {
                 const pill = document.getElementById(`filter-step-${currentStepFilter}`);
                 if (pill) pill.classList.add('filter-active');
             }
        }

        // Ensure Bulk Action Bar is visible if items are selected
        const selectedData = window.getGlobalSelectedData();
        if (selectedData && selectedData.length > 0) {
            const bulkActionBar = document.getElementById('bulkActionBar');
            if (bulkActionBar) {
                bulkActionBar.style.display = 'flex';
                const countSpan = document.getElementById('selected-count');
                if (countSpan) countSpan.textContent = selectedData.length;
                const btn = document.getElementById('bulkActionDropdown');
                if (btn) btn.disabled = false;
            }
        }
    });

    // --- Operator Toggle ---
    window.toggleOperator = function(employeeId, btn, currentOperatorId, customOperatorName, url) {
        // Fetch operators first
        Swal.fire({
            title: '{{ __("Loading...") }}',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        fetch('{{ route("api-web.operators.list") }}')
            .then(res => res.json())
            .then(data => {
                if (!data.success) throw new Error('Failed to load operators');

                let operators = data.data;
                let optionsHtml = `<option value="">-- {{ __("None / Clear Operator") }} --</option>`;
                optionsHtml += `<option value="external" ${customOperatorName ? 'selected' : ''}>-- ผู้ยื่นภายนอก (พิมพ์ชื่อเอง) --</option>`;

                operators.forEach(op => {
                    let selected = (op.id == currentOperatorId && !customOperatorName) ? 'selected' : '';
                    optionsHtml += `<option value="${op.id}" ${selected}>${op.name}</option>`;
                });

                const htmlContent = `
                    <div class="form-group text-start">
                        <label class="form-label mb-2">{{ __("Select Operator") }}</label>
                        <select id="operator-select-${employeeId}" class="form-select form-select-lg mb-3">
                            ${optionsHtml}
                        </select>

                        <div id="custom-operator-container-${employeeId}" style="display: ${customOperatorName ? 'block' : 'none'};">
                            <label class="form-label mb-2">ระบุชื่อผู้ดำเนินการภายนอก</label>
                            <input type="text" id="custom-operator-input-${employeeId}" class="form-control form-control-lg" placeholder="พิมพ์ชื่อผู้ดำเนินการ...">
                        </div>
                    </div>
                `;

                Swal.fire({
                    title: '{{ __("Assign Operator") }}',
                    html: htmlContent,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '{{ __("Save") }}',
                    confirmButtonColor: '#0d6efd',
                    cancelButtonText: '{{ __("Cancel") }}',
                    showLoaderOnConfirm: true,
                    didOpen: () => {
                        const selectEl = document.getElementById(`operator-select-${employeeId}`);
                        const containerEl = document.getElementById(`custom-operator-container-${employeeId}`);
                        const input = document.getElementById(`custom-operator-input-${employeeId}`);

                        if (customOperatorName) {
                            input.value = customOperatorName;
                        }
                        selectEl.addEventListener('change', function() {
                            if (this.value === 'external') {
                                containerEl.style.display = 'block';
                                document.getElementById(`custom-operator-input-${employeeId}`).focus();
                            } else {
                                containerEl.style.display = 'none';
                            }
                        });
                    },
                    preConfirm: () => {
                        const selectedId = document.getElementById(`operator-select-${employeeId}`).value;
                        let customName = null;

                        if (selectedId === 'external') {
                            customName = document.getElementById(`custom-operator-input-${employeeId}`).value.trim();
                            if (!customName) {
                                Swal.showValidationMessage('กรุณาระบุชื่อผู้ดำเนินการภายนอก');
                                return false;
                            }
                        }

                        const fetchUrl = url || `/production/renewal/{{ $currentTab->id }}/${employeeId}/toggle-operator`;

                        return fetch(fetchUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                operator_id: selectedId === 'external' ? null : selectedId,
                                custom_operator_name: customName
                            })
                        })
                        .then(response => {
                            if (!response.ok) throw new Error(response.statusText);
                            return response.json();
                        })
                        .catch(error => {
                            Swal.showValidationMessage(`Request failed: ${error}`);
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        const resData = result.value;
                        if(resData && resData.success) {
                            if(resData.html) updateCardHTML(employeeId, resData.html);
                            if(typeof showToast === 'function') {
                                showToast(resData.message, 'success');
                            }
                        } else {
                            Swal.fire('{{ __("Error") }}', resData ? resData.message : 'Unknown error', 'error');
                        }
                    }
                });
            })
            .catch(err => {
                Swal.fire('Error', '{{ __("Could not load operators list.") }}', 'error');
                console.error(err);
            });
    }

    // --- Resolution Status & Note Functions ---
    // Make global for onclick
    window.updateResolutionStatus = function(employerId, status) {
        // Reuse registration route for employer status update as it's shared on Employer model
        fetch(`/production/registration/{{ \App\Models\ResolutionTab::where('type', 'registration')->where('is_default', true)->value('id') }}/employer/${employerId}/resolution-status`, {
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
                location.reload();
            } else {
                Swal.fire('{{ __('Error') }}', '{{ __('Failed to update status') }}', 'error');
            }
        })
        .catch(err => Swal.fire('{{ __('Error') }}', '{{ __('Network error') }}', 'error'));
    }

    // --- History Modal ---
    window.openHistoryModal = function(employerId) {
        const modal = new bootstrap.Modal(document.getElementById('historyModal'));
        modal.show();

        const body = document.getElementById('historyModalBody');
        body.innerHTML = '<div class="d-flex justify-content-center py-5"><div class="spinner-border text-secondary" role="status"></div></div>';

        fetch(`/production/renewal/{{ $currentTab->id }}/employer/${employerId}/history`)
            .then(res => res.text())
            .then(html => {
                body.innerHTML = html;
            })
            .catch(err => {
                body.innerHTML = '<div class="text-danger text-center p-4">Failed to load history.</div>';
            });
    }

    // --- Global & Employer Cancelled Toggle ---
    window.globalCancelledHidden = true; // Default: hide cancelled
    window.employerCancelledHidden = {}; // Per employer state

    // Initialize global button state on load
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('hide_cancelled')) {
            window.globalCancelledHidden = urlParams.get('hide_cancelled') === '1';
        }

        const btn = document.getElementById('btn-global-toggle-cancelled');
        if (btn) {
            if (window.globalCancelledHidden) {
                btn.innerHTML = '<i class="bi bi-eye-fill me-1"></i> {{ __('Show Cancelled') }}';
            } else {
                btn.innerHTML = '<i class="bi bi-eye-slash-fill me-1"></i> {{ __('Hide Cancelled') }}';
            }
        }
    });

    window.toggleGlobalCancelled = function() {
        const urlParams = new URLSearchParams(window.location.search);
        const currentHide = urlParams.has('hide_cancelled') ? urlParams.get('hide_cancelled') : '1';
        if (currentHide === '1') {
            urlParams.set('hide_cancelled', '0');
        } else {
            urlParams.set('hide_cancelled', '1');
        }
        window.location.search = urlParams.toString();
    }

    window.toggleEmployerCancelled = function(employerId) {
        if (typeof window.employerCancelledHidden[employerId] === 'undefined') {
            window.employerCancelledHidden[employerId] = window.globalCancelledHidden;
        }

        window.employerCancelledHidden[employerId] = !window.employerCancelledHidden[employerId];
        const btn = document.getElementById(`btn-employer-toggle-cancelled-${employerId}`);

        if (window.employerCancelledHidden[employerId]) {
            btn.innerHTML = '<i class="bi bi-eye"></i>';
            btn.title = '{{ __('Show Cancelled Items') }}';
            btn.classList.add('active', 'bg-secondary', 'text-white');
        } else {
            btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
            btn.title = '{{ __('Hide Cancelled Items') }}';
            btn.classList.remove('active', 'bg-secondary', 'text-white');
        }

        // Re-load the employee list for this employer with the specific hide_cancelled param
        const container = document.getElementById(`employee-list-${employerId}`);
        if(container) {
            container.innerHTML = '<div class="d-flex justify-content-center align-items-center py-5"><div class="spinner-border text-primary" role="status"></div><span class="ms-2 small text-muted">Loading employees...</span></div>';

            let baseUrl = `/production/renewal/{{ $currentTab->id }}/employer/${employerId}/employees`;
            const url = new URL(baseUrl, window.location.origin);
            const currentParams = new URLSearchParams(window.location.search);
            currentParams.forEach((value, key) => {
                 if(key !== 'page' || !url.searchParams.has('page')) {
                     url.searchParams.append(key, value);
                 }
            });
            // Override the global hide_cancelled for this specific employer
            url.searchParams.set('hide_cancelled', window.employerCancelledHidden[employerId] ? '1' : '0');

            const perPageSelect = document.getElementById(`perPage-${employerId}`);
            if(perPageSelect && !url.searchParams.has('per_page')) {
                url.searchParams.append('per_page', perPageSelect.value);
            }

            fetch(url)
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;
                window.loadedEmployers[employerId] = true;
                if (typeof applyFilters === 'function') applyFilters();
                if (window.refreshGlobalSelectionUI) {
                    window.refreshGlobalSelectionUI();
                }
            })
            .catch(err => {
                container.innerHTML = `<div class="text-danger p-3">Failed to load employees.</div>`;
                console.error(err);
            });
        }
    }

    // Modal variable init inside DOMContentLoaded to ensure Bootstrap is loaded
    document.addEventListener('DOMContentLoaded', function() {
        const noteModal = new bootstrap.Modal(document.getElementById('resolutionNoteModal'));

        // Expose open function globally
        window.openResolutionNoteModal = function(employerId, currentNote) {
            document.getElementById('noteEmployerId').value = employerId;
            document.getElementById('resolutionNoteText').value = currentNote;
            noteModal.show();
        }

        // Attach modal instance to window if needed elsewhere, but mostly used in open function above.
        window.noteModal = noteModal;
    });


    window.saveResolutionNote = function() {
        const employerId = document.getElementById('noteEmployerId').value;
        const note = document.getElementById('resolutionNoteText').value;

        // Reuse registration route for employer status update as it's shared on Employer model
        fetch(`/production/registration/{{ \App\Models\ResolutionTab::where('type', 'registration')->where('is_default', true)->value('id') }}/employer/${employerId}/resolution-status`, {
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
                window.noteModal.hide();
                location.reload();
            } else {
                Swal.fire('{{ __('Error') }}', '{{ __('Failed to save note') }}', 'error');
            }
        })
        .catch(err => Swal.fire('{{ __('Error') }}', '{{ __('Network error') }}', 'error'));
    }


    // Toggle Filter Function - Global (Server-Side)
    window.toggleFilter = function(filterKey) {
        const url = new URL(window.location.href);
        const currentFilter = url.searchParams.get('filter');

        if (currentFilter == filterKey) {
            url.searchParams.delete('filter'); // Toggle OFF
        } else {
            url.searchParams.set('filter', filterKey); // Toggle ON
        }

        window.location.href = url.toString();
    }

    // Client-side filter application (for when data changes without reload)
    function applyFilters() {
        // Since the main filter is server-side, we rely on reload.
        // However, if we implement client-side immediate update, we might hide/show cards.
        // But for now, stats update is enough.
        // If a card changes status (e.g. Completed), it should disappear if "Not Started" filter is on?
        // Yes.
        const filter = new URLSearchParams(window.location.search).get('filter');
        if (!filter) return;

        document.querySelectorAll('.employee-card-wrapper').forEach(card => {
            let visible = true;
            if (filter === 'not_started') {
                 visible = (card.dataset.isNotStarted === 'true' && card.dataset.status !== 'renewal_cancelled');
            } else if (filter === 'saved') {
                 visible = (card.dataset.status === 'renewal_completed');
            } else if (filter === 'cancelled') {
                 visible = (card.dataset.status === 'renewal_cancelled');
            } else if (filter === 'appointment_not_scheduled') {
                 visible = (card.dataset.hasAppointment === 'false' && card.dataset.status !== 'renewal_cancelled');
            } else if (filter === 'appointment_pending') {
                 visible = (card.dataset.hasAppointment === 'true' && card.dataset.appointmentCompleted === 'false');
            } else if (filter === 'appointment_completed') {
                 visible = (card.dataset.hasAppointment === 'true' && card.dataset.appointmentCompleted === 'true');
            } else if (!isNaN(filter)) {
                 // Step ID
                 visible = (card.dataset.highestStepId == filter && card.dataset.status !== 'renewal_cancelled');
            }

            if (visible) {
                card.classList.remove('d-none');
            } else {
                card.classList.add('d-none');
            }
        });
    }

    // Re-calculate stats based on DOM is difficult because of lazy loading.
    // We will rely on server response data for precise updates.
    window.recalculateVisibleStats = function() {
        // Placeholder if we move to client-side calc later.
    }

    // Employer-level Select All (Modified for Visible & Active only)
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('employer-select-all')) {
            const employerId = e.target.dataset.employerId;
            const isChecked = e.target.checked;

            // Find all checkboxes for this employer
            const checkboxes = document.querySelectorAll(`.employee-checkbox[data-employer-id="${employerId}"]`);

            checkboxes.forEach(cb => {
                // Determine if employee is eligible for selection
                const cardWrapper = document.getElementById(`employee-card-${cb.value}`);
                // Only filter by d-none if it was applied by the filter system (not just because accordion is closed)
                // The filter system applies d-none to non-matching items.
                // An accordion collapse applies display:none to the parent container, not the item itself usually,
                // but if the item itself has d-none, it's filtered out.
                const isFilteredOut = cardWrapper && cardWrapper.classList.contains('d-none');

                // Explicitly check status data attribute.
                // Renewal uses 'renewal_pending' status primarily.
                // Also allow 'cancelled' items if they are currently visible
                const status = cardWrapper ? cardWrapper.dataset.status : '';
                const isPending = (status === 'renewal_pending' || status === 'registration_pending' || status === 'renewal_cancelled' || status === 'registration_cancelled');

                if (!isFilteredOut && isPending) {
                    if(cb.checked !== isChecked) {
                        cb.checked = isChecked;
                        // Dispatch change event to trigger global listener
                        cb.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            });
        }
    });

    // Global Select All Listener (Custom for Renewal Page)
    document.addEventListener('change', function(e) {
        if (e.target.id === 'select-all-checkbox') {
            const isChecked = e.target.checked;

            // 1. Trigger all Employer Select All checkboxes
            const employerSelectAlls = document.querySelectorAll('.employer-select-all');
            employerSelectAlls.forEach(masterCb => {
                masterCb.checked = isChecked;
                masterCb.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }
    });

    // Sync Employer Select All state when individual employees change
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('employee-checkbox')) {
            const employerId = e.target.dataset.employerId;
            if (!employerId) return;

            const masterCb = document.querySelector(`.employer-select-all[data-employer-id="${employerId}"]`);
            if (masterCb) {
                const allCheckboxes = document.querySelectorAll(`.employee-checkbox[data-employer-id="${employerId}"]`);
                // Filter only relevant ones (pending & visible) to check against
                const relevantCheckboxes = Array.from(allCheckboxes).filter(cb => {
                    const cardWrapper = document.getElementById(`employee-card-${cb.value}`);
                    const isFilteredOut = cardWrapper && cardWrapper.classList.contains('d-none');
                    const status = cardWrapper ? cardWrapper.dataset.status : '';
                    const isPending = (status === 'renewal_pending' || status === 'registration_pending');
                    return !isFilteredOut && isPending;
                });

                if (relevantCheckboxes.length > 0) {
                    const allChecked = relevantCheckboxes.every(cb => cb.checked);
                    masterCb.checked = allChecked;
                    masterCb.indeterminate = !allChecked && relevantCheckboxes.some(cb => cb.checked);
                } else {
                    masterCb.checked = false;
                    masterCb.indeterminate = false;
                }
            }
        }
    });

        // Helper: Refresh Calendar Data manually
        window.refreshCalendarCounts = function() {
            if (typeof loadCalendar === 'function') {
                loadCalendar();
            }
            // Trigger global batch stats update
            if (typeof fetchBatchStats === 'function') {
                const visibleEmployers = Array.from(document.querySelectorAll('.employer-card-container')).map(el => el.id.replace('employer-card-', ''));
                if (visibleEmployers.length > 0) {
                    fetchBatchStats(visibleEmployers);
                }
            }
        }

    // Helper: Update UI Stats
    window.updateDailyCheckScoreboard = function(enabled, isPending) {
        const globalEl = document.getElementById('global-daily-check-pending-count');
        if (!globalEl) return;
        let current = parseInt(globalEl.innerText) || 0;

        // Optimistic update for "Check" action
        if (enabled === true && isPending === false) {
             // Checked -> Decrement
             globalEl.innerText = Math.max(0, current - 1);
        }
    }

    function updateStatsUI(stats) {
        if (!stats) return;

        // Note: The response from updateProgress has different structure than getStats
        // updateProgress returns: globalStats (array), globalNotStarted, employerStats (array), employerNotStarted
        // getStats returns: global: { total, not_started, cancelled, saved }, employer: { ... }

        // We handle the updateProgress format here:
        if (stats.globalStats) {
            // Update step badges
            // The globalStats is object/array: { stepId: count }
            for (const [stepId, count] of Object.entries(stats.globalStats)) {
                // Find global badge
                const badge = document.querySelector(`.global-stat-badge[data-step-id="${stepId}"]`);
                if(badge) badge.innerText = count;
            }
        }

        if (typeof stats.globalNotStarted !== 'undefined') {
            updateText('global-not-started-count', stats.globalNotStarted);
        }

        if (stats.employerStats && stats.employerId) {
            for (const [stepId, count] of Object.entries(stats.employerStats)) {
                 // Find employer badge inside the specific employer stats container
                 const container = document.getElementById(`employer-stats-${stats.employerId}`);
                 if (container) {
                     const badge = container.querySelector(`.employer-stat-badge[data-step-id="${stepId}"]`);
                     if(badge) badge.innerText = count;
                 }
            }
        }
        if (typeof stats.employerNotStarted !== 'undefined' && stats.employerId) {
             updateText(`employer-not-started-${stats.employerId}`, stats.employerNotStarted);
        }

        // Handle standard getStats format (from finalize/cancel)
        if (stats.global && typeof stats.global.total !== 'undefined') {
            updateText('global-total-count', stats.global.total);
            updateText('global-not-started-count', stats.global.not_started);
            updateText('global-cancelled-count', stats.global.cancelled);
            updateText('global-saved-count', stats.global.saved);
            updateText('global-employers-count', stats.global.employers_count);
            if(typeof stats.global.daily_check_pending !== 'undefined') {
                updateText('global-daily-check-pending-count', stats.global.daily_check_pending);
            }
            if(typeof stats.global.appointments_pending !== 'undefined') {
                updateText('global-appointments-pending-count', stats.global.appointments_pending);
            }
            if(typeof stats.global.appointments_completed !== 'undefined') {
                updateText('global-appointments-completed-count', stats.global.appointments_completed);
            }
        }
        if (stats.employer && typeof stats.employer.total !== 'undefined') {
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
    // (Renewal reuses Registration steps for now, or we can hide this if steps are different)
    // Assuming steps are shared via 'registration_steps' table as per controller logic.
    document.getElementById('addStepForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const name = document.getElementById('newStepName').value;

        fetch('{{ route("production.renewal.steps.store", ["resolutionTab" => $currentTab->id]) }}', {
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

    window.deleteStep = function(id) {
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
                fetch(`/production/renewal/{{ $currentTab->id }}/steps/${id}`, {
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

    window.toggleEditStep = function(id) {
        const item = document.getElementById(`step-item-${id}`);
        item.querySelector('.step-display').classList.toggle('d-none');
        item.querySelector('.step-edit').classList.toggle('d-none');
        item.querySelector('.btn-edit-step').classList.toggle('d-none');
        item.querySelector('.btn-save-step').classList.toggle('d-none');
    }

    window.saveStep = function(id) {
        const item = document.getElementById(`step-item-${id}`);
        const newName = item.querySelector('.step-edit-input').value;

        fetch(`/production/renewal/{{ $currentTab->id }}/steps/${id}`, {
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

    // --- Resolution Auto-Settings ---
    window.saveResolutionSettings = function() {
        const autoWorkPermit = document.getElementById('autoWorkPermitInput').value;
        const autoVisa = document.getElementById('autoVisaInput').value;
        const autoMou = document.getElementById('autoMouInput').value;

        fetch('{{ route("production.renewal.settings.resolution", ["resolutionTab" => $currentTab->id]) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({
                auto_work_permit_expiry: autoWorkPermit,
                auto_visa_expiry: autoVisa,
                auto_mou_group: autoMou
            })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                bootstrap.Modal.getInstance(document.getElementById('resolutionSettingsModal')).hide();
                Swal.fire('{{ __('Saved') }}', '{{ __('Auto Settings updated.') }}', 'success');
            }
        });
    }

    // Helper for submitting step reorder
    function submitReorder(order, behavior) {
        fetch('{{ route("production.renewal.steps.reorder", ["resolutionTab" => $currentTab->id]) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                order: order,
                handle_step_one_behavior: behavior
            })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
               if (behavior === 'auto_tick') {
                   Swal.fire({
                        icon: 'success',
                        title: '{{ __('Updated!') }}',
                        text: '{{ __('Order updated and employees processed.') }}',
                        timer: 1500,
                        showConfirmButton: false
                   }).then(() => location.reload());
               }
               // For 'none', silent success or toast
            }
        });
    }

    window.moveStep = function(id, direction) {
        const stepsList = document.getElementById('stepsList');
        const currentItem = document.getElementById(`step-item-${id}`);

        // Capture current first item ID before move
        const firstLi = stepsList.querySelector('li');
        const currentFirstId = firstLi ? firstLi.id.replace('step-item-', '') : null;

        if (direction === 'up') {
            const prevItem = currentItem.previousElementSibling;
            if (prevItem) {
                stepsList.insertBefore(currentItem, prevItem);
            }
        } else {
            const nextItem = currentItem.nextElementSibling;
            if (nextItem) {
                stepsList.insertBefore(nextItem, currentItem);
            }
        }

        // Collect new order
        const newOrder = [];
        stepsList.querySelectorAll('li').forEach(li => {
            newOrder.push(li.id.replace('step-item-', ''));
        });

        // Capture new first item ID
        const newFirstId = newOrder[0];

        // Check if Step 1 has changed
        if (currentFirstId && newFirstId && currentFirstId !== newFirstId) {
            Swal.fire({
                title: '{{ __('Change First Step?') }}',
                text: '{{ __('You are changing the first step. Select how to handle existing active employees:') }}',
                icon: 'warning',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: '{{ __('Auto-tick New Step 1') }}', // Choice 1
                denyButtonText: '{{ __('Just Move (No Data Change)') }}', // Choice 2
                cancelButtonText: '{{ __('Cancel') }}',
                confirmButtonColor: '#3085d6',
                denyButtonColor: '#6c757d',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Choice 1: Auto-tick
                    submitReorder(newOrder, 'auto_tick');
                } else if (result.isDenied) {
                    // Choice 2: Just Move
                    submitReorder(newOrder, 'none');
                } else {
                    // Cancel: Revert DOM change by reloading
                    location.reload();
                }
            });
        } else {
            // Standard move (Step 1 didn't change)
            submitReorder(newOrder, 'none');
        }
    }

    // --- Employee Actions (Updated for Immediate DOM Feedback & Stats) ---
    window.updateCardHTML = function(id, html) {
        const card = document.getElementById(`employee-card-${id}`);
        if(card && html) {
            card.outerHTML = html;
        }
    }

    window.finalizeEmployee = function(id) {
        Swal.fire({
            title: '{{ __('Save to Database?') }}',
            text: "{{ __('The employee will be marked as completed.') }}",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '{{ __('Yes, Save') }}'
        }).then((result) => {
             if (result.isConfirmed) {
                fetch(`/production/renewal/{{ $currentTab->id }}/${id}/finalize` + window.location.search, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        if(data.html) updateCardHTML(id, data.html);
                        Swal.fire('{{ __('Saved!') }}', '{{ __('Employee marked as completed.') }}', 'success');
                        // Renewal might return stats or not (Controller doesn't seem to calculate new stats yet on response?
                        // I removed stats from RenewalController getStats call in previous diff?
                        // No, I only changed html. Wait, RenewalController finalize returns success: true, html: ...
                        // It does NOT return stats in my change. I should check if stats were returned before.
                        // Original RenewalController@finalize: return response()->json(['success' => true]);
                        // It did NOT return stats. So updateStatsUI might fail or do nothing.
                        // I will add stats to RenewalController@finalize later if needed, but for now just HTML replacement fixes the visual glitch.
                        // Actually, index.blade.php calls updateStatsUI(data.stats). If data.stats is undefined, it does nothing.
                        // So it's fine.
                        if(data.stats) updateStatsUI(data.stats);
                        applyFilters();
                    }
                });
             }
        });
    }

    window.restoreEmployeeState = function(id) {
        Swal.fire({
            title: '{{ __('Restore to Pending?') }}',
            text: "{{ __('This will move the employee back to the active list.') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __('Yes, Restore') }}'
        }).then((result) => {
             if (result.isConfirmed) {
                fetch(`/production/renewal/{{ $currentTab->id }}/${id}/restore` + window.location.search, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        if(data.html) updateCardHTML(id, data.html);
                        Swal.fire('{{ __('Restored!') }}', '{{ __('Employee is back to pending.') }}', 'success');
                        if(data.stats) updateStatsUI(data.stats);
                        applyFilters();
                    }
                });
             }
        });
    }

    window.cancelEmployee = function(id) {
        Swal.fire({
            title: '{{ __('Cancel Renewal?') }}',
            text: "{{ __('The employee card will be grayed out.') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6c757d',
            confirmButtonText: '{{ __('Yes, Cancel') }}'
        }).then((result) => {
             if (result.isConfirmed) {
                fetch(`/production/renewal/{{ $currentTab->id }}/${id}/cancel` + window.location.search, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        if(data.html) updateCardHTML(id, data.html);
                        Swal.fire('{{ __('Cancelled') }}', '{{ __('Registration cancelled.') }}', 'success');
                        if(data.stats) updateStatsUI(data.stats);
                        applyFilters();
                    }
                });
             }
        });
    }

    window.cancelEmployer = function(id) {
        Swal.fire({
            title: '{{ __('Cancel Employer?') }}',
            text: "{{ __('This will seal the card and move it to the end. Active employees will also be cancelled.') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: '{{ __('Yes, Cancel Employer') }}'
        }).then((result) => {
             if (result.isConfirmed) {
                fetch(`/production/renewal/{{ $currentTab->id }}/employer/${id}/cancel` + window.location.search, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire('{{ __('Cancelled') }}', '{{ __('Employer renewal cancelled.') }}', 'success')
                        .then(() => location.reload());
                    }
                });
             }
        });
    }

    window.restoreEmployer = function(id) {
        Swal.fire({
            title: '{{ __('Restore Employer?') }}',
            text: "{{ __('This will restore the employer card and active employees.') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __('Yes, Restore') }}'
        }).then((result) => {
             if (result.isConfirmed) {
                fetch(`/production/renewal/{{ $currentTab->id }}/employer/${id}/restore` + window.location.search, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire('{{ __('Restored') }}', '{{ __('Employer restored.') }}', 'success')
                        .then(() => location.reload());
                    }
                });
             }
        });
    }

    window.deleteEmployee = function(id) {
        Swal.fire({
            title: '{{ __('Delete Employee?') }}',
            text: "{{ __('This will move the employee to the trash.') }}",
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: '{{ __('Yes, Delete') }}'
        }).then((result) => {
             if (result.isConfirmed) {
                fetch(`/production/renewal/{{ $currentTab->id }}/${id}/destroy` + window.location.search, {
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
                                updateStatsUI(data.stats); // Update stats after removal
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
    window.toggleStep = function(employeeId, stepId, completed) {
        // Optimistic UI could be kept for button only, but full replacement is safer for consistency.
        // We will optimistically update just the button style to give immediate feedback, then replace card.
        const card = document.getElementById(`employee-card-${employeeId}`);
        if (!card) return;
        const btn = card.querySelector(`button[data-step-id="${stepId}"]`);

        // Optimistic feedback (Visual only)
        if (btn) {
            btn.style.opacity = '0.5';
        }

        fetch(`/production/renewal/{{ $currentTab->id }}/progress/${employeeId}` + window.location.search, {
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
                if(data.html) updateCardHTML(employeeId, data.html);
                updateStatsUI(data);
                applyFilters();
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
            // Revert opacity
            if (btn) btn.style.opacity = '1';
        });
    }

    // --- Bulk Action Handlers (Advanced Features) ---
    document.addEventListener('DOMContentLoaded', function() {
        // Handle Bulk Generate PDF
        const bulkGeneratePdfBtn = document.getElementById('bulk-generate-pdf-btn');
        if (bulkGeneratePdfBtn) {
            bulkGeneratePdfBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const selected = window.getGlobalSelectedIds();

                if (selected.length === 0) {
                    showToast('{{ __('Please select employees first.') }}', 'danger');
                    return;
                }

                // Create form to post to generation modal setup
                const form = document.createElement('form');
                form.method = 'POST';
                // Use relative path to avoid protocol mismatch (http vs https) redirects which strip POST data
                form.action = '{{ route("admin.pdf-templates.generate.modal", [], false) }}';

                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = document.querySelector('meta[name="csrf-token"]').content;
                form.appendChild(csrf);

                const redirectInput = document.createElement('input');
                redirectInput.type = 'hidden';
                redirectInput.name = 'redirect_url';
                redirectInput.value = window.location.href;
                form.appendChild(redirectInput);

                selected.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'employees[]';
                    input.value = id;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
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
                if (document.getElementById('export_source_menu')) {
                    document.getElementById('export_source_menu').value = 'renewal';
                }
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

    // --- Trash Feature ---
    window.loadTrashContent = function(url) {
        const body = document.getElementById('trashModalBody');
        body.innerHTML = '<div class="d-flex justify-content-center py-5"><div class="spinner-border text-danger" role="status"></div></div>';

        fetch(url || '{{ route("production.renewal.trash", ["resolutionTab" => $currentTab->id]) }}')
            .then(res => res.text())
            .then(html => {
                body.innerHTML = html;
            })
            .catch(err => {
                body.innerHTML = '<div class="text-danger text-center p-4">Failed to load trash.</div>';
            });
    }

    window.openTrashModal = function() {
        const el = document.getElementById('trashModal');
        const modal = new bootstrap.Modal(el);
        modal.show();
        loadTrashContent();
    }

    window.restoreTrashItem = function(id) {
        Swal.fire({
            title: '{{ __("Restore Item?") }}',
            text: '{{ __("Restore this employee from trash?") }}',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '{{ __("Yes, Restore") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/production/renewal/{{ $currentTab->id }}/trash/${id}/restore`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        // Refresh modal content
                        loadTrashContent();

                        Swal.fire({
                            icon: 'success',
                            title: '{{ __("Restored") }}',
                            text: '{{ __("Employee restored successfully.") }}',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    }
                });
            }
        });
    }

    // Intercept Pagination Clicks inside Trash Modal
    const trashBody = document.getElementById('trashModalBody');
    if (trashBody) {
        trashBody.addEventListener('click', function(e) {
            // Check if clicked element is pagination link or inside one
            const link = e.target.closest('.pagination a, .page-link, a[href]');
            if (link && link.href) {
                e.preventDefault();
                loadTrashContent(link.href);
            }
        });
    }

        // GPS Navigate logic อยู่ด้านล่าง
    });

    // ─── GPS Navigate: Renewal Operations ───
    document.addEventListener('DOMContentLoaded', function() {
        var empInput = document.getElementById('gps-highlight-employer');
        if (!empInput) return;
        var empId = empInput.value;
        var employeeId = (document.getElementById('gps-highlight-employee') || {}).value || '';
        if (!empId) return;

        empInput.remove();

        var collapseEl = document.getElementById('collapse' + empId);
        var headingEl  = document.getElementById('heading' + empId);
        if (!collapseEl || !headingEl) return;

        setTimeout(function() {
            headingEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);

        if (!collapseEl.classList.contains('show')) {
            var btn = headingEl.querySelector('[data-bs-toggle="collapse"]');
            if (btn) btn.click();
        }

        setTimeout(function() {
            if (typeof window.loadEmployees === 'function') {
                window.loadEmployees(empId);
            }
        }, 200);

        if (!employeeId) return;

        function doHighlight(card) {
            setTimeout(function() {
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                card.classList.add('highlight-navigate');
                setTimeout(function() { card.classList.remove('highlight-navigate'); }, 5000);
            }, 500);
        }

        function tryFind() {
            var card = document.getElementById('employee-card-' + employeeId);
            if (card) { doHighlight(card); return true; }
            return false;
        }

        var listEl = document.getElementById('employee-list-' + empId);
        if (!listEl) return;

        var obs = new MutationObserver(function(_, o) {
            if (tryFind()) o.disconnect();
        });
        obs.observe(listEl, { childList: true, subtree: true });

        [800, 1500, 3000, 5000, 7000].forEach(function(ms) {
            setTimeout(function() { if (tryFind()) obs.disconnect(); }, ms);
        });
    });
</script>

@endpush

<script>
    window.markAppointmentCompleted = function(employeeId, module, btnElement) {
        Swal.fire({
            title: '{{ __("Complete Appointment?") }}',
            text: '{{ __("Are you sure you want to mark this appointment as completed?") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '{{ __("Yes, Complete it!") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                // Determine module path based on current view or explicit pass
                let modulePath = module || window.currentAppointmentContext?.module || 'production/renewal';
                let tabId = window.currentAppointmentContext?.tabId || {{ $currentTab->id }};

                // Show loading state on button
                const originalHtml = btnElement.innerHTML;
                btnElement.innerHTML = '<i class="spinner-border spinner-border-sm"></i>';
                btnElement.disabled = true;

                fetch(`/${modulePath}/${tabId}/${employeeId}/appointment-complete`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: '{{ __("Appointment Completed") }}',
                            showConfirmButton: false,
                            timer: 2000
                        });

                        // Hide the card instantly to give immediate feedback
                        const cardElement = btnElement.closest('.appt-card-item') || btnElement.closest('.col-12');
                        if (cardElement) {
                            cardElement.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                            cardElement.style.opacity = '0';
                            cardElement.style.transform = 'translateX(50px)';
                            setTimeout(() => {
                                cardElement.style.display = 'none';
                                const resultCount = document.getElementById('appt-result-count');
                                if (resultCount) {
                                    const visibleCards = document.querySelectorAll('.appt-card-item:not([style*="display: none"])');
                                    resultCount.textContent = visibleCards.length + ' {{ __("record(s)") }}';
                                }
                                if (window.refreshGlobalSelectionUI) window.refreshGlobalSelectionUI();
                            }, 300);
                        }

                        // Also find the main employee card and update it to reflect the completed state
                        const mainEmployeeCard = document.getElementById(`item-card-${employeeId}`);
                        if (mainEmployeeCard) {
                            // Find the Alpine component within the main card and trigger its toggle
                            // or manually update the UI elements if Alpine is hard to reach externally
                            const switchInput = mainEmployeeCard.querySelector('input[type="checkbox"][x-model="isAppCompleted"]');
                            if (switchInput && !switchInput.checked) {
                                switchInput.checked = true;
                                switchInput.dispatchEvent(new Event('change'));
                            }
                        }

                        // Also trigger global calendar refresh
                        if (typeof window.refreshCalendarCounts === 'function') {
                            window.refreshCalendarCounts();
                        }
                    } else {
                        btnElement.innerHTML = originalHtml;
                        btnElement.disabled = false;
                        Swal.fire('Error', 'Could not complete appointment.', 'error');
                    }
                })
                .catch(err => {
                    btnElement.innerHTML = originalHtml;
                    btnElement.disabled = false;
                    Swal.fire('Error', 'Network error occurred.', 'error');
                });
            }
        });
    }
</script>

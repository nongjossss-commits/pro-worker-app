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
        min-width: 40px; /* Increased to fit 3 digits */
        text-align: right;
        font-weight: bold;
        white-space: nowrap; /* Prevent wrapping for large numbers */
    }
</style>

<div class="container-fluid">
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

        {{-- Appointments (Pending / Completed) --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm cursor-pointer filter-card"
                 id="filter-total_appointments"
                 onclick="toggleFilter('total_appointments')"
                 style="background-color: #8B5CF6; border: none; transition: transform 0.2s;"> {{-- Purple --}}
                <div class="card-body text-center d-flex flex-column justify-content-center py-3">
                    <div class="d-flex justify-content-around w-100">
                        <div class="text-center">
                            <h2 class="display-5 fw-bold mb-0" id="global-appointments-pending-count">{{ $totalAppointmentsPending ?? 0 }}</h2>
                            <small class="fw-light">{{ __('Pending') }}</small>
                        </div>
                        <div class="border-end border-white opacity-50 mx-2"></div>
                        <div class="text-center">
                            <h2 class="display-5 fw-bold mb-0" id="global-appointments-completed-count">{{ $totalAppointmentsCompleted ?? 0 }}</h2>
                            <small class="fw-light">{{ __('Completed') }}</small>
                        </div>
                    </div>
                    <p class="fs-6 fw-bold mb-0 mt-2 text-uppercase" style="letter-spacing: 1px;">{{ __('Appointments') }}</p>
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

        {{-- Biometrics Collected --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm cursor-pointer filter-card"
                 id="filter-biometrics-collected"
                 onclick="toggleFilter('biometrics_collected')"
                 style="background-color: #06b6d4; border: none;"> {{-- Cyan-500 --}}
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0" id="global-biometrics-collected-count">{{ $totalBiometricsCollected ?? 0 }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Biometrics Collected') }}</p>
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
                    <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#notificationSettingsModal">
                        <i class="bi bi-bell-fill me-1"></i> {{ __('Notify Settings') }}
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

                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-fill me-1"></i>
                        {{ request('operator_filter') ? ($activeOperators->firstWhere('id', request('operator_filter'))->name ?? __('Operator')) : __('Operator') }}
                    </button>
                    <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                        <li><a class="dropdown-item" href="{{ route('production.registration.index', array_merge(request()->query(), ['operator_filter' => null])) }}">{{ __('All Operators') }}</a></li>
                        <li><hr class="dropdown-divider"></li>
                        @foreach($activeOperators as $user)
                            <li><a class="dropdown-item {{ request('operator_filter') == $user->id ? 'active' : '' }}" href="{{ route('production.registration.index', array_merge(request()->query(), ['operator_filter' => $user->id])) }}">{{ $user->name }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <div class="d-flex gap-2 flex-wrap justify-content-end">
                    <button class="btn btn-outline-info fw-bold"
                            id="btn-global-filter-biometrics"
                            onclick="toggleFilter('biometrics_collected')"
                            title="{{ __('Filter Biometrics Collected') }}">
                        <i class="bi bi-person-bounding-box me-1"></i>
                    </button>

                    <button class="btn btn-outline-warning fw-bold"
                            id="btn-global-filter-biometrics-not"
                            onclick="toggleFilter('biometrics_not_collected')"
                            title="{{ __('Filter Biometrics Not Collected') }}">
                        <i class="bi bi-person me-1"></i>
                    </button>

                    @can('edit-employees')
                    <a href="{{ route('production.registration.create') }}" class="btn btn-warning text-white fw-bold">
                        <i class="bi bi-plus-lg me-1"></i> {{ __('New Employee') }}
                    </a>
                    <a href="{{ route('production.registration.import') }}" class="btn btn-success fw-bold">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> {{ __('Import') }}
                    </a>
                    <button class="btn btn-secondary fw-bold" onclick="openTrashModal()">
                        <i class="bi bi-trash-fill me-1"></i> {{ __('Trash') }}
                    </button>
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

            <div class="d-flex align-items-start employer-card-container w-100 mb-4" id="employer-card-{{ $employer->id }}" data-is-cancelled="{{ $isEmployerCancelled ? 'true' : 'false' }}">
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

                    </div>

                    <div class="card-header py-3 px-4 border-bottom {{ $employerHeaderClass }}" id="heading{{ $employer->id }}">

                    {{-- Inline Note Editor (Top Center) --}}
                    <div class="d-flex justify-content-center mb-2">
                        <div class="position-relative w-50" x-data="{
                            editing: false,
                            note: {{ json_encode($employer->registration_resolution_note ?? '') }},
                            tempNote: '',
                            saving: false,
                            startEditing() {
                                this.tempNote = this.note;
                                this.editing = true;
                                this.$nextTick(() => { this.$refs.noteInput.focus(); });
                            },
                            saveNote() {
                                this.saving = true;
                                fetch(`{{ url('production/registration/employer') }}/{{ $employer->id }}/resolution-note`, {
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
                                    {{-- Biometrics --}}
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info d-flex align-items-center justify-content-center gap-2 px-2 py-1" style="min-width: 95px;" title="{{ __('Biometrics Collected') }}">
                                        <i class="bi bi-fingerprint"></i>
                                        <span class="fw-bold" id="employer-biometrics-collected-{{ $employer->id }}">{{ $employer->biometricsCollectedCount ?? 0 }}</span>
                                    </span>
                                 </div>

                                 <div class="vr d-none d-xl-block me-2"></div>

                                 @can('edit-employees')
                                 {{-- Add Employee Button --}}
                                 <a href="{{ route('production.registration.create', ['employer_id' => $employer->id]) }}" class="btn btn-outline-warning btn-sm fw-bold {{ $isEmployerCancelled ? 'd-none' : '' }}">
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

                                <button class="btn btn-outline-info btn-sm ms-1" id="btn-employer-toggle-biometrics-{{ $employer->id }}" onclick="toggleEmployerBiometrics({{ $employer->id }}); event.stopPropagation();" title="{{ __('Filter Biometrics Collected') }}">
                                    <i class="bi bi-fingerprint"></i>
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
                    <input type="date" class="form-control" id="autoWorkPermitInput" value="{{ $resolutionSettings['registration_auto_work_permit_expiry'] ?? '' }}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">{{ __('Auto Visa Expiry Date') }}</label>
                    <input type="date" class="form-control" id="autoVisaInput" value="{{ $resolutionSettings['registration_auto_visa_expiry'] ?? '' }}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">{{ __('Auto MOU Group (Work Type)') }}</label>
                    <select class="form-select" id="autoMouInput">
                        <option value="">-- {{ __('No Auto Update') }} --</option>
                        <option value="MOU" {{ ($resolutionSettings['registration_auto_mou_group'] ?? '') == 'MOU' ? 'selected' : '' }}>MOU</option>
                        <option value="มติต่ออายุในประเทศ" {{ ($resolutionSettings['registration_auto_mou_group'] ?? '') == 'มติต่ออายุในประเทศ' ? 'selected' : '' }}>มติต่ออายุในประเทศ</option>
                        <option value="มติขึ้นทะเบียน" {{ ($resolutionSettings['registration_auto_mou_group'] ?? '') == 'มติขึ้นทะเบียน' ? 'selected' : '' }}>มติขึ้นทะเบียน</option>
                        <option value="อื่นๆ" {{ ($resolutionSettings['registration_auto_mou_group'] ?? '') == 'อื่นๆ' ? 'selected' : '' }}>อื่นๆ</option>
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

{{-- Notification Settings Modal --}}
<div class="modal fade" id="notificationSettingsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="bi bi-bell-fill me-2"></i>{{ __('Notification Settings') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">{{ __('Set how many days in advance to show appointments on the Dashboard.') }}</p>
                <div class="mb-3">
                    <label class="form-label fw-bold">{{ __('Notify Days in Advance') }}</label>
                    <input type="number" class="form-control" id="notifyDaysInput" value="{{ $notificationSetting->days_before_expiry ?? 3 }}" min="0" max="365">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-link text-secondary text-decoration-none" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-warning px-4" onclick="saveNotificationSettings()">
                    <i class="bi bi-save-fill me-1"></i> {{ __('Save Settings') }}
                </button>
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

                                <div x-show="isLoading" class="position-absolute w-100 h-100 d-flex justify-content-center align-items-center bg-white bg-opacity-75" style="z-index: 10;">
                                    <div class="spinner-border text-primary" role="status"></div>
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
<script src="{{ asset('js/financial-manager.js') }}"></script>
<script>
    // State for Global Server-Side Filter
    const currentStepFilter = @json(request('filter'));
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const lastStepId = @json($lastStepId);
    window.allUsers = @json($allUsers);

    // --- Resolution Auto-Settings ---
    window.saveResolutionSettings = function() {
        const autoWorkPermit = document.getElementById('autoWorkPermitInput').value;
        const autoVisa = document.getElementById('autoVisaInput').value;
        const autoMou = document.getElementById('autoMouInput').value;

        fetch('{{ route("production.registration.settings.resolution") }}', {
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

    // --- Notification Settings ---
    window.saveNotificationSettings = function() {
        const days = document.getElementById('notifyDaysInput').value;

        fetch('{{ route("production.registration.settings.notification") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ notify_days_advance: days })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                bootstrap.Modal.getInstance(document.getElementById('notificationSettingsModal')).hide();
                Swal.fire('{{ __('Saved') }}', '{{ __('Settings updated.') }}', 'success');
            }
        });
    }

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
                window.currentAppointmentContext = { module: 'production/registration' };

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
                fetch(`{{ route('production.registration.api.calendar') }}?month=${this.month + 1}&year=${this.year}`)
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

                fetch(`{{ route('production.registration.api.appointments_by_date') }}?date=${dateStr}`)
                    .then(res => res.json())
                    .then(data => {
                        document.getElementById('dayAppointmentsContent').innerHTML = data.html;
                        this.isLoading = false;
                        this.searchQuery = ''; // Reset search query when changing dates
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
                const moduleUrl = window.currentAppointmentContext ? window.currentAppointmentContext.module : 'production/registration';

                // First update the details
                fetch(`/${moduleUrl}/${employeeId}/update-appointment`, {
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
                        return fetch(`/${moduleUrl}/${employeeId}/toggle-appointment-complete`, {
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

    window.loadEmployees = function(employerId, pageUrl = null) {
        // Only skip if already loaded AND not a pagination request
        if (window.loadedEmployers[employerId] && !pageUrl) return;

        const container = document.getElementById(`employee-list-${employerId}`);
        if(pageUrl) {
            container.innerHTML = '<div class="d-flex justify-content-center align-items-center py-5"><div class="spinner-border text-primary" role="status"></div><span class="ms-2 small text-muted">Loading employees...</span></div>';
        }

        // Base URL for the new AJAX route
        let baseUrl = pageUrl || `{{ route('production.registration.index') }}/employer/${employerId}/employees`;

        // Append current search/filter params
        const url = new URL(baseUrl, window.location.origin);
        const currentParams = new URLSearchParams(window.location.search);
        currentParams.forEach((value, key) => {
             // Don't overwrite 'page' if we have it in the pageUrl
             if(key !== 'page' || !url.searchParams.has('page')) {
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
            const baseUrl = `{{ route('production.registration.index') }}/employer/${employerId}/employees`;
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
        // Check if already loaded by checking for Alpine component root
        if (body.querySelector('[x-data]')) return;

        fetch(`{{ route('production.registration.index') }}/employer/${employerId}/finance-tab`)
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

        fetch('{{ route("production.registration.stats.batch") }}', {
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
                updateText(`employer-biometrics-collected-${empId}`, stats.biometricsCollectedCount);

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
        }

        // Initial Filter UI State (Server-Side)
        if (currentStepFilter) {
             if (currentStepFilter === 'not_started') document.getElementById('filter-not-started')?.classList.add('filter-active');
             else if (currentStepFilter === 'saved') document.getElementById('filter-saved')?.classList.add('filter-active');
             else if (currentStepFilter === 'cancelled') document.getElementById('filter-cancelled')?.classList.add('filter-active');
             else if (currentStepFilter === 'cancelled_employer') document.getElementById('filter-cancelled-employer')?.classList.add('filter-active');
             else if (currentStepFilter === 'biometrics_collected') {
                 document.getElementById('filter-biometrics-collected')?.classList.add('filter-active');
                 // Highlight global button too
                 const btn = document.getElementById('btn-global-filter-biometrics');
                 if(btn) btn.classList.add('active', 'bg-info', 'text-white');
             }
             else if (currentStepFilter === 'biometrics_not_collected') {
                 const btn = document.getElementById('btn-global-filter-biometrics-not');
                 if(btn) btn.classList.add('active', 'bg-warning', 'text-dark');
             }
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

    // --- Global & Employer Cancelled Toggle ---
    window.globalCancelledHidden = false; // Default: show cancelled
    window.employerCancelledHidden = {}; // Per employer state

    window.toggleGlobalCancelled = function() {
        window.globalCancelledHidden = !window.globalCancelledHidden;
        const btn = document.getElementById('btn-global-toggle-cancelled');

        if (window.globalCancelledHidden) {
            btn.innerHTML = '<i class="bi bi-eye-fill me-1"></i> {{ __('Show Cancelled') }}';
            // Hide all cancelled employers
            document.querySelectorAll('.employer-card-container[data-is-cancelled="true"]').forEach(el => el.classList.add('d-none'));
            // Hide all cancelled employees globally
            document.querySelectorAll('.employee-card-wrapper[data-status="registration_cancelled"]').forEach(el => el.classList.add('d-none'));
        } else {
            btn.innerHTML = '<i class="bi bi-eye-slash-fill me-1"></i> {{ __('Hide Cancelled') }}';
            // Show all cancelled employers
            document.querySelectorAll('.employer-card-container[data-is-cancelled="true"]').forEach(el => el.classList.remove('d-none'));
            // Show all cancelled employees globally
            document.querySelectorAll('.employee-card-wrapper[data-status="registration_cancelled"]').forEach(el => el.classList.remove('d-none'));
        }
    }

    window.toggleEmployerCancelled = function(employerId) {
        if (typeof window.employerCancelledHidden[employerId] === 'undefined') {
            window.employerCancelledHidden[employerId] = false;
        }

        window.employerCancelledHidden[employerId] = !window.employerCancelledHidden[employerId];
        const btn = document.getElementById(`btn-employer-toggle-cancelled-${employerId}`);
        const listContainer = document.getElementById(`employee-list-${employerId}`);

        if (window.employerCancelledHidden[employerId]) {
            btn.innerHTML = '<i class="bi bi-eye"></i>';
            btn.title = '{{ __('Show Cancelled Items') }}';
            btn.classList.add('active', 'bg-secondary', 'text-white');
            // Hide cancelled employees in this employer container
            if (listContainer) {
                listContainer.querySelectorAll('.employee-card-wrapper[data-status="registration_cancelled"]').forEach(el => el.classList.add('d-none'));
            }
        } else {
            btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
            btn.title = '{{ __('Hide Cancelled Items') }}';
            btn.classList.remove('active', 'bg-secondary', 'text-white');
            // Show cancelled employees in this employer container
            if (listContainer) {
                listContainer.querySelectorAll('.employee-card-wrapper[data-status="registration_cancelled"]').forEach(el => el.classList.remove('d-none'));
            }
        }
    }

    // Toggle Biometrics Filter (Employer Level)
    window.employerBiometricsFilter = {};

    window.toggleEmployerBiometrics = function(employerId) {
        if (typeof window.employerBiometricsFilter[employerId] === 'undefined') {
            window.employerBiometricsFilter[employerId] = false;
        }

        window.employerBiometricsFilter[employerId] = !window.employerBiometricsFilter[employerId];
        const btn = document.getElementById(`btn-employer-toggle-biometrics-${employerId}`);
        const listContainer = document.getElementById(`employee-list-${employerId}`);

        if (window.employerBiometricsFilter[employerId]) {
            btn.classList.add('active', 'bg-info', 'text-white');
            // Show ONLY biometrics collected
            if (listContainer) {
                listContainer.querySelectorAll('.employee-card-wrapper').forEach(el => {
                    if (el.dataset.biometricsCollected !== 'true') {
                        el.classList.add('d-none');
                    }
                });
            }
        } else {
            btn.classList.remove('active', 'bg-info', 'text-white');
            // Reset visibility (respecting cancelled filter if active)
            if (listContainer) {
                listContainer.querySelectorAll('.employee-card-wrapper').forEach(el => {
                    // Check if we should keep it hidden due to cancelled filter
                    const isCancelled = el.dataset.status === 'registration_cancelled';
                    const isCancelledHidden = window.employerCancelledHidden[employerId];

                    if (isCancelled && isCancelledHidden) {
                        // Keep hidden
                    } else {
                        el.classList.remove('d-none');
                    }
                });
            }
        }
    }

    // --- Operator Toggle ---
    window.toggleOperator = function(employeeId, btn, currentOperatorId, url) {
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

                operators.forEach(op => {
                    let selected = (op.id == currentOperatorId) ? 'selected' : '';
                    optionsHtml += `<option value="${op.id}" ${selected}>${op.name}</option>`;
                });

                const htmlContent = `
                    <div class="form-group text-start">
                        <label class="form-label mb-2">{{ __("Select Operator") }}</label>
                        <select id="operator-select-${employeeId}" class="form-select form-select-lg">
                            ${optionsHtml}
                        </select>
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
                        // Optional: Initialize Select2 or Choices.js here if needed
                    },
                    preConfirm: () => {
                        const selectedId = document.getElementById(`operator-select-${employeeId}`).value;
                        const fetchUrl = url || `/production/registration/${employeeId}/toggle-operator`;

                        return fetch(fetchUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ operator_id: selectedId })
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

        fetch(`/production/registration/employer/${employerId}/history`)
            .then(res => res.text())
            .then(html => {
                body.innerHTML = html;
            })
            .catch(err => {
                body.innerHTML = '<div class="text-danger text-center p-4">Failed to load history.</div>';
            });
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
                 visible = (card.dataset.isNotStarted === 'true' && card.dataset.status !== 'registration_cancelled');
            } else if (filter === 'saved') {
                 visible = (card.dataset.status === 'registration_completed');
            } else if (filter === 'cancelled') {
                 visible = (card.dataset.status === 'registration_cancelled');
            } else if (filter === 'biometrics_collected') {
                 visible = (card.dataset.biometricsCollected === 'true');
            } else if (filter === 'biometrics_not_collected') {
                 visible = (card.dataset.biometricsCollected === 'false');
            } else if (!isNaN(filter)) {
                 // Step ID
                 visible = (card.dataset.highestStepId == filter && card.dataset.status !== 'registration_cancelled');
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
        // For toggle ON/OFF, we rely on page refresh or subsequent loads for accuracy
        // to avoid desync without full server stats.
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
            if(typeof stats.global.biometrics_collected !== 'undefined') {
                updateText('global-biometrics-collected-count', stats.global.biometrics_collected);
            }
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
            if(typeof stats.employer.biometrics_collected !== 'undefined') {
                updateText(`employer-biometrics-collected-${stats.employer.id}`, stats.employer.biometrics_collected);
            }
        }
    }

    // --- Biometrics Upload Handler ---
    window.uploadBiometrics = function(employeeId) {
        const input = document.getElementById(`biometrics-input-${employeeId}`);
        if (!input || !input.files || input.files.length === 0) return;

        const file = input.files[0];
        const formData = new FormData();
        formData.append('biometrics_file', file);

        // Append current search/filter params to URL to ensure stats returned are consistent with view
        const currentQuery = window.location.search;

        // Show loading state
        const btn = document.getElementById(`btn-biometrics-${employeeId}`);
        if(btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        }

        fetch(`/production/registration/${employeeId}/biometrics` + currentQuery, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '{{ __('Success') }}',
                    text: '{{ __('Biometrics updated!') }}',
                    timer: 1500,
                    showConfirmButton: false
                });

                // Update Card HTML
                if(data.html) updateCardHTML(employeeId, data.html);

                Swal.fire({
                    icon: 'success',
                    title: '{{ __('Success') }}',
                    text: '{{ __('Biometrics updated!') }}',
                    timer: 1500,
                    showConfirmButton: false
                });

                // Update Stats
                updateStatsUI(data.stats);
                applyFilters();

            } else {
                Swal.fire('{{ __('Error') }}', data.message || 'Upload failed', 'error');
                if(btn) { // Reset button
                    btn.disabled = false;
                    const wasCollected = btn.dataset.collected === 'true';
                    if(!wasCollected) {
                         btn.innerHTML = '<i class="bi bi-fingerprint"></i> <span class="d-none d-lg-inline">{{ __('Biometrics') }}</span>';
                    }
                }
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('{{ __('Error') }}', '{{ __('Network error') }}', 'error');
            if(btn) btn.disabled = false; // Basic reset
        });
    }

    // --- Toggle Biometrics (Tick) Handler ---
    window.toggleBiometricsStatus = function(employeeId) {
        const btn = document.getElementById(`btn-biometrics-toggle-${employeeId}`);
        // Visual feedback
        if(btn) btn.disabled = true;

        fetch(`/production/registration/${employeeId}/biometrics-toggle` + window.location.search, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if(data.html) updateCardHTML(employeeId, data.html);
                updateStatsUI(data.stats);
                applyFilters();
            } else {
                Swal.fire('{{ __('Error') }}', data.message, 'error');
                if(btn) btn.disabled = false;
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('{{ __('Error') }}', '{{ __('Network error') }}', 'error');
            if(btn) btn.disabled = false;
        });
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

    // Helper for submitting step reorder
    function submitReorder(order, behavior) {
        fetch('{{ route("production.registration.steps.reorder") }}', {
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
                fetch(`/production/registration/${id}/finalize` + window.location.search, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        if(data.html) updateCardHTML(id, data.html);
                        Swal.fire('{{ __('Saved!') }}', '{{ __('Employee marked as completed.') }}', 'success');
                        updateStatsUI(data.stats);
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
                fetch(`/production/registration/${id}/restore` + window.location.search, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        if(data.html) updateCardHTML(id, data.html);
                        Swal.fire('{{ __('Restored!') }}', '{{ __('Employee is back to pending.') }}', 'success');
                        updateStatsUI(data.stats);
                        applyFilters();
                    }
                });
             }
        });
    }

    window.cancelEmployee = function(id) {
        Swal.fire({
            title: '{{ __('Cancel Registration?') }}',
            text: "{{ __('The employee card will be grayed out.') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6c757d',
            confirmButtonText: '{{ __('Yes, Cancel') }}'
        }).then((result) => {
             if (result.isConfirmed) {
                fetch(`/production/registration/${id}/cancel` + window.location.search, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        if(data.html) updateCardHTML(id, data.html);
                        Swal.fire('{{ __('Cancelled') }}', '{{ __('Registration cancelled.') }}', 'success');
                        updateStatsUI(data.stats);
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
                fetch(`/production/registration/employer/${id}/cancel` + window.location.search, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire('{{ __('Cancelled') }}', '{{ __('Employer registration cancelled.') }}', 'success')
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
                fetch(`/production/registration/employer/${id}/restore` + window.location.search, {
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
                fetch(`/production/registration/${id}/destroy` + window.location.search, {
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

        fetch(`/production/registration/progress/${employeeId}` + window.location.search, {
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

        fetch(url || '{{ route("production.registration.trash") }}')
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
                fetch(`/production/registration/trash/${id}/restore`, {
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

        // --- Restore UI State on Load (After Reload) ---
        const urlParams = new URLSearchParams(window.location.search);
        const restoreEmployerId = sessionStorage.getItem('registration_restore_employer_id') || urlParams.get('highlight_employer_id');
        const restoreEmployeeId = sessionStorage.getItem('registration_restore_employee_id') || urlParams.get('highlight_employee_id');

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
                        // Function to retry finding the element if lazy load takes time
                        const scrollToCard = () => {
                            const employeeCard = document.getElementById(`employee-card-${restoreEmployeeId}`);
                            if (employeeCard) {
                                employeeCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                // Optional: Highlight effect
                                employeeCard.classList.add('filter-active'); // Use filter-active for stronger highlight
                                setTimeout(() => employeeCard.classList.remove('filter-active'), 3000);
                            } else {
                                // Retry once after a short delay if loading
                                setTimeout(() => {
                                     const retryCard = document.getElementById(`employee-card-${restoreEmployeeId}`);
                                     if(retryCard) {
                                         retryCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                         retryCard.classList.add('filter-active');
                                         setTimeout(() => retryCard.classList.remove('filter-active'), 3000);
                                     }
                                }, 500);
                            }
                        };

                        // Small delay to ensure lazy load content injection (if instantaneous) or wait for mutation observer?
                        // loadEmployees is triggered by show.bs.collapse listener above in this file.
                        // That listener triggers fetch. fetch takes time.
                        // We need to wait for the fetch to complete.
                        // We can hook into window.loadEmployees promise if we refactored it, but it returns nothing.
                        // Workaround: Poll for element existence.
                        let attempts = 0;
                        const poller = setInterval(() => {
                            const card = document.getElementById(`employee-card-${restoreEmployeeId}`);
                            if (card) {
                                clearInterval(poller);
                                scrollToCard();
                            }
                            attempts++;
                            if (attempts > 20) clearInterval(poller); // 2 seconds timeout
                        }, 100);
                    }, { once: true });
                }
            }
        }
    });
</script>
@endpush

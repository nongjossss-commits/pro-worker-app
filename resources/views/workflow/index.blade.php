@extends('layouts.app')

@section('title', 'Workflow Dashboard')

@section('content')
@php
    $user = auth()->user();
    $isEmployer = $user->hasRole('employer');
    $canManage = $user->can('manage-own-workflow');
    // Read Only if Employer AND cannot manage
    $isReadOnly = $isEmployer && !$canManage;
@endphp
<style>
    .cursor-pointer { cursor: pointer; }
    .grayscale-mode { filter: grayscale(100%); opacity: 0.8; }
    /* Replicate Registration Stats Styling */
    .stat-badge { width: 24px; height: 24px; font-size: 0.75rem; }
    .custom-scrollbar::-webkit-scrollbar { height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 3px; }

    /* CSS Counters for persistent slot numbering */
    #workflowAccordion {
        counter-reset: employer-counter;
    }
    .production-order-card-container:not(.d-none) {
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
    .order-content-wrapper {
        counter-reset: employee-counter;
    }
    .item-card-wrapper:not(.d-none):not(.hide-cancelled) {
        counter-increment: employee-counter;
    }
    .employee-card-wrapper:not(.d-none):not(.hide-cancelled) {
        counter-increment: employee-counter;
    }
    .item-sequence-number::before,
    .employee-sequence-number::before {
        content: counter(employee-counter);
    }
    .item-sequence-number,
    .employee-sequence-number {
        position: absolute;
        top: 4px;
        right: 8px;
        font-size: 0.8rem;
        font-weight: bold;
        color: #adb5bd;
        z-index: 1;
    }
    /* Mobile/Tablet optimizations */
    @media (max-width: 1024px) {
        .item-card-wrapper .card-body { padding: 0.5rem !important; padding-top: 0.75rem !important; }
        .item-card-wrapper .item-info-section { flex-direction: column !important; align-items: center !important; text-align: center; width: 100% !important; }
        .item-card-wrapper .item-info-section > .d-flex.align-items-center.gap-3,
        .item-card-wrapper .item-info-section [id^="info-container-"] { flex-direction: column !important; align-items: center !important; text-align: center; width: 100% !important; }
        .item-card-wrapper .item-info-section > .form-check:has(.employee-checkbox) { position: absolute; top: 28px; left: 8px; z-index: 2; }
        .item-card-wrapper .position-absolute.bottom-0.end-0 { position: absolute !important; bottom: 8px !important; right: 8px !important; top: auto !important; left: auto !important; margin: 0 !important; }
        .item-card-wrapper [style*="min-width: 250px"],
        .item-card-wrapper [style*="min-width: 220px"] { min-width: unset !important; width: 100% !important; }
    }
    @media (max-width: 576px) {
        .item-card-wrapper .card-body { padding: 0.4rem !important; padding-top: 0.6rem !important; }
        .item-card-wrapper .fw-bold.text-dark { font-size: 0.85rem; }
        .item-card-wrapper .small { font-size: 0.7rem !important; }
    }

    .filter-active {
        transform: scale(1.05);
        border: 2px solid #3b82f6 !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        z-index: 10;
        transition: all 0.2s ease-in-out;
    }

    /* Visibility Toggle */
    .hide-cancelled .status-cancelled {
        display: none !important;
    }
</style>

<div class="container-fluid py-4">
    {{-- Scoreboard (Detailed Registration Style) --}}
    <div class="row row-cols-1 row-cols-md-3 row-cols-xl-6 g-3 mb-4">
        {{-- Total Employees --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm border-0 cursor-pointer" onclick="window.location.href = window.location.pathname + '?tab={{ $activeTab->slug ?? '' }}';" style="background-color: #FBBF24;">
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0" id="stats-total-employees">{{ $stats['total_employees'] ?? 0 }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Total Employees') }}</p>
                </div>
            </div>
        </div>

        {{-- Daily Check (Reset at Midnight) --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm border-0 cursor-pointer filter-card" id="filter-daily-check" onclick="toggleFilter('pending_daily_check')" style="background-color: #F97316;">
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0" id="stats-daily-check">{{ $stats['pending_daily_check'] ?? 0 }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Daily Check') }}</p>
                </div>
            </div>
        </div>

        {{-- Not Started --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm border-0 cursor-pointer filter-card" id="filter-not-started" onclick="toggleFilter('not_started')" style="background-color: #EF4444;">
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0">{{ $stats['not_started'] ?? 0 }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Not Started') }}</p>
                </div>
            </div>
        </div>

        {{-- Cancelled --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm border-0 cursor-pointer filter-card" id="filter-cancelled" onclick="toggleFilter('cancelled')" style="background-color: #6B7280;">
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0">{{ $stats['cancelled'] ?? 0 }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Cancelled') }}</p>
                </div>
            </div>
        </div>

        {{-- Completed --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm border-0 cursor-pointer filter-card" id="filter-completed" onclick="toggleFilter('completed')" style="background-color: #10B981;">
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0">{{ $stats['completed'] ?? 0 }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Completed') }}</p>
                </div>
            </div>
        </div>

        {{-- Total Projects --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm border-0 bg-primary bg-gradient">
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0" id="stats-total-projects">{{ $stats['total_projects'] ?? 0 }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Active Projects') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Global Workflow Progress --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="card-title fw-bold text-secondary mb-0">
                    <i class="bi bi-bar-chart-fill me-2"></i>{{ __('Workflow Progress (Global)') }} - {{ $activeTab->name ?? 'Overview' }}
                </h5>
                @if(isset($activeTab) && !$isReadOnly)
                    <div class="d-flex gap-2">
                        @if(request('hide_cancelled', '1') === '0')
                            <button class="btn btn-secondary btn-sm" id="btn-global-toggle-cancelled" onclick="toggleGlobalCancelled()">
                                <i class="bi bi-eye-fill me-1"></i> {{ __('Hide Cancelled') }}
                            </button>
                        @else
                            <button class="btn btn-outline-secondary btn-sm" id="btn-global-toggle-cancelled" onclick="toggleGlobalCancelled()">
                                <i class="bi bi-eye-slash-fill me-1"></i> {{ __('Hide Cancelled') }}
                            </button>
                        @endif
                        <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#notificationSettingsModal">
                            <i class="bi bi-bell-fill me-1"></i> {{ __('Notify Settings') }}
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#manageStepsModal">
                            <i class="bi bi-gear-fill me-1"></i> {{ __('Steps') }}
                        </button>
                    </div>
                @endif
            </div>

            <div class="d-flex gap-2 flex-wrap justify-content-start align-items-center">
                @foreach($steps as $step)
                    @php
                        $count = $stats['step_stats'][$step->id] ?? 0;
                        $bgClass = $count > 0 ? "bg-success" : "bg-secondary bg-opacity-50 text-white";
                    @endphp
                    <div class="d-inline-flex align-items-center bg-white border rounded-pill py-2 px-3 shadow-sm gap-2 cursor-pointer filter-pill"
                         id="filter-step-{{ $step->id }}"
                         onclick="toggleFilter('{{ $step->id }}')">
                        <span class="badge rounded-circle {{ $bgClass }} shadow-sm d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            {{ $count }}
                        </span>
                        <span class="fw-bold text-dark fs-6">{{ $step->name }}</span>
                    </div>
                @endforeach
                @if($steps->isEmpty())
                    <p class="text-muted small mb-0">{{ __('No steps configured.') }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Search & Actions (Moved Above Filter) --}}
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-3 mb-4">
        {{-- Search Bar --}}
        <form action="{{ route('workflow.index') }}" method="GET" class="d-flex flex-grow-1 justify-content-center w-100">
            @if(isset($activeTab))
                <input type="hidden" name="tab" value="{{ $activeTab->slug }}">
            @endif
            <div class="input-group input-group-lg" style="max-width: 500px;">
                <span class="input-group-text bg-white border-end-0 shadow-sm"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control bg-white border-start-0 shadow-sm" placeholder="{{ __('Search...') }}" value="{{ request('search') }}">
                <button class="btn btn-primary shadow-sm" type="submit">{{ __('Search') }}</button>
                @if(request('search'))
                    <a href="{{ route('workflow.index', ['tab' => $activeTab->slug ?? null]) }}" class="btn btn-outline-secondary shadow-sm bg-white">{{ __('Clear') }}</a>
                @endif
            </div>
        </form>

        {{-- Operator Filter --}}
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-fill me-1"></i>
                {{ request('operator_filter') ? ($users->firstWhere('id', request('operator_filter'))->name ?? __('Operator')) : __('Operator') }}
            </button>
            <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                <li><a class="dropdown-item" href="{{ route('workflow.index', array_merge(request()->query(), ['operator_filter' => null])) }}">{{ __('All Operators') }}</a></li>
                <li><hr class="dropdown-divider"></li>
                @foreach($users as $user)
                    <li><a class="dropdown-item {{ request('operator_filter') == $user->id ? 'active' : '' }}" href="{{ route('workflow.index', array_merge(request()->query(), ['operator_filter' => $user->id])) }}">{{ $user->name }}</a></li>
                @endforeach
            </ul>
        </div>

        {{-- Actions --}}
        @if(!$isReadOnly)
        <div class="d-flex gap-2">
            <button class="btn btn-secondary shadow-sm" onclick="openTrashModal()">
                <i class="bi bi-trash-fill me-1"></i> {{ __('Trash') }}
            </button>
            @if(isset($activeTab) && $activeTab->slug === 'mou')
                <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#createJobModal">
                    <i class="bi bi-plus-lg me-1"></i> {{ __('Create Job') }}
                </button>
            @else
                <button class="btn btn-primary fw-bold shadow-sm" onclick="openAddEmployeeModal(null, null, {{ $activeTab->id ?? 'null' }}, '{{ $activeTab->slug ?? '' }}', 'workflow')">
                    <i class="bi bi-plus-lg me-1"></i> {{ __('Add Employee') }}
                </button>
            @endif
        </div>
        @endif
    </div>

    <x-address-filter :provinces="$addressOptions['provinces']" :districts="$addressOptions['districts']" :subDistricts="$addressOptions['subDistricts']" />

    {{-- Tabs Navigation --}}
    <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-body p-3">
             <ul class="nav nav-pills gap-2 overflow-auto flex-nowrap pb-2 w-100" style="scrollbar-width: thin;">
                <li class="nav-item">
                    <a class="nav-link bg-white border text-secondary"
                       href="{{ route('workflow.index') }}"
                       style="white-space: nowrap;">
                        <i class="bi bi-speedometer2 me-1"></i> {{ __('Dashboard') }}
                    </a>
                </li>
                @foreach($tabs as $tab)
                    <li class="nav-item">
                        <a class="nav-link {{ isset($activeTab) && $activeTab->id === $tab->id ? 'active fw-bold shadow-sm' : 'bg-white border text-secondary' }}"
                           href="{{ route('workflow.index', ['tab' => $tab->slug]) }}"
                           style="white-space: nowrap;">
                            {{ $tab->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

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

    {{-- Accordion List --}}
    <div class="accordion" id="workflowAccordion">
        @forelse($orders as $order)
            @php
                 // Computed stats from controller
                 $computed = $order->computedStats ?? ['total'=>0, 'not_started'=>0, 'cancelled'=>0, 'completed'=>0, 'step_stats'=>[]];
                 $stepStats = $computed['step_stats'];
                 $isActive = ($computed['active_items_count'] ?? 0) > 0;
            @endphp
            <div class="production-order-card-container w-100 mb-4">
                <div class="card border-0 shadow-sm production-order-card position-relative {{ !$isActive ? 'grayscale-mode' : '' }}">
                <div class="employer-sequence-number"></div>
                    <div class="card-header bg-white border-bottom py-3 px-4" id="heading-{{ $order->id }}">

                    {{-- Inline Note Editor (Top Center) --}}
                    <div class="d-flex justify-content-center mb-2">
                        <div class="position-relative w-50" x-data="{
                            editing: false,
                            note: {{ json_encode($order->remarks ?? '') }},
                            tempNote: '',
                            saving: false,
                            startEditing() {
                                this.tempNote = this.note;
                                this.editing = true;
                                this.$nextTick(() => { this.$refs.noteInput.focus(); });
                            },
                            saveNote() {
                                this.saving = true;
                                fetch(`{{ url('workflow/order') }}/{{ $order->id }}/remarks`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    body: JSON.stringify({ remarks: this.tempNote })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    this.saving = false;
                                    if(data.success) {
                                        this.note = data.remarks ?? this.tempNote;
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

                        {{-- Top Row: Identity + Stats + Actions --}}
                    <div class="row align-items-xl-center g-3 mb-3">
                        {{-- Identity --}}
                        <div class="col-12 col-xl-auto d-flex align-items-center flex-wrap gap-3">
                            @if(!$isReadOnly)
                            <div class="form-check mb-0">
                                <input class="form-check-input employer-select-all" type="checkbox" data-employer-id="{{ $order->id }}" title="{{ __('Select All for this Employer/Project') }}">
                            </div>
                            @endif

                            <button class="btn btn-link text-decoration-none text-dark p-0 text-start d-flex align-items-center gap-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $order->id }}">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-primary" style="width: 40px; height: 40px;">
                                    @if($order->type === 'independent')
                                        <i class="bi bi-person-workspace fs-5"></i>
                                    @else
                                        <i class="bi bi-building fs-5"></i>
                                    @endif
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0 text-primary">
                                        {{ $order->employer->employerNameTh ?? $order->project_name }}
                                        @if(request('addrProvince') && $order->employer)
                                            @foreach($order->employer->getMatchedAddressLabels(request('addrProvince'), request('addrDistrict'), request('addrSubDistrict')) as $label)
                                                <span class="badge bg-info text-white small ms-1" style="font-size: 0.7rem;">{{ $label }}</span>
                                            @endforeach
                                        @endif
                                    </h5>
                                    @if($order->employer && $order->employer->employerNameEn)
                                        <div class="text-muted small fw-bold">{{ $order->employer->employerNameEn }}</div>
                                    @endif

                                    @if($order->employer && $order->employer->jobOwner)
                                        <div class="text-muted small border-start ps-2 mt-1 ms-1">
                                            <i class="bi bi-person-badge me-1"></i>
                                            <a href="{{ route('workflow.index', ['tab' => $activeTab->slug ?? null, 'search' => $order->employer->jobOwner->name]) }}" class="text-decoration-none text-secondary">
                                                {{ $order->employer->jobOwner->name }}
                                            </a>
                                        </div>
                                    @endif

                                    <div class="text-muted small mt-1">
                                        @if($order->updater)
                                            <i class="bi bi-clock-history me-1"></i>
                                            <a href="{{ route('workflow.index', ['tab' => $activeTab->slug ?? null, 'search' => $order->updater->name]) }}" class="text-decoration-none text-secondary" title="{{ __('Last Modified By') }}">
                                                {{ $order->updater->name }}
                                            </a>
                                        @elseif($order->creator)
                                            <i class="bi bi-person-circle me-1"></i>
                                            <a href="{{ route('workflow.index', ['tab' => $activeTab->slug ?? null, 'search' => $order->creator->name]) }}" class="text-decoration-none text-secondary" title="{{ __('Created By') }}">
                                                {{ $order->creator->name }}
                                            </a>
                                        @else
                                            <i class="bi bi-person-circle me-1"></i> System
                                        @endif
                                        &bull; {{ $order->updated_at->diffForHumans() }}
                                    </div>
                                </div>
                            </button>

                            @if($order->employer_id)
                                <button class="btn btn-sm btn-outline-info btn-preview rounded-circle"
                                    data-model-type="employer"
                                    data-model-id="{{ $order->employer_id }}"
                                    title="{{ __('Preview Employer Data') }}">
                                    <i class="bi bi-search"></i>
                                </button>
                            @endif
                        </div>

                        {{-- Stats & Actions --}}
                        <div class="col-12 col-xl text-xl-end">
                            <div class="d-flex align-items-center justify-content-xl-end gap-2 flex-wrap">
                                 {{-- Stats Badges --}}
                                 <div class="d-flex align-items-center gap-2 me-xl-3">
                                    {{-- Total --}}
                                    <span class="badge bg-light text-dark border d-flex align-items-center justify-content-center gap-2 px-2 py-1" style="min-width: 80px;">
                                        <span class="fw-bold" id="order-{{ $order->id }}-total">{{ $computed['total'] }}</span>
                                        <span class="text-muted small" style="font-size: 0.65rem;">TOTAL</span>
                                    </span>
                                    {{-- Not Started --}}
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger d-flex align-items-center justify-content-center gap-2 px-2 py-1" style="min-width: 90px;">
                                         <span class="fw-bold" id="order-{{ $order->id }}-pending">{{ $computed['not_started'] }}</span>
                                         <span class="small ms-1 opacity-75" style="font-size: 0.65rem;">PENDING</span>
                                    </span>
                                    {{-- Completed --}}
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success d-flex align-items-center justify-content-center gap-2 px-2 py-1" style="min-width: 80px;">
                                         <span class="fw-bold" id="order-{{ $order->id }}-completed">{{ $computed['completed'] }}</span>
                                         <span class="small ms-1 opacity-75" style="font-size: 0.65rem;">DONE</span>
                                    </span>
                                    {{-- Cancelled --}}
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary d-flex align-items-center justify-content-center gap-2 px-2 py-1" style="min-width: 80px;">
                                        <span class="fw-bold" id="order-{{ $order->id }}-cancelled">{{ $computed['cancelled'] }}</span>
                                        <span class="small ms-1 opacity-75" style="font-size: 0.65rem;">CANCEL</span>
                                    </span>
                                 </div>

                                 <div class="vr d-none d-xl-block me-2"></div>

                                 {{-- Actions --}}
                                 @if(!$isReadOnly)
                                 {{-- History Button --}}
                                 <button class="btn btn-outline-secondary btn-sm rounded-circle me-1 history-btn-{{ $order->id }}"
                                    onclick="openHistoryModal({{ $order->id }})"
                                    title="{{ __('View History') }}">
                                     <i class="bi bi-clock-history"></i>
                                 </button>

                                 {{-- Finance Button --}}
                                 @can('view-finance')
                                 <button class="btn btn-outline-primary btn-sm rounded-circle me-1"
                                    onclick="event.stopPropagation(); FinancialSecurity.checkAndRun(() => new bootstrap.Modal(document.getElementById('financeModal-{{ $order->id }}')).show())"
                                    title="{{ __('Finance') }}">
                                    <i class="bi bi-currency-dollar"></i>
                                </button>
                                @endcan

                                 {{-- Toggle Cancelled --}}
                                 <button class="btn btn-outline-secondary btn-sm rounded-circle me-1"
                                    onclick="toggleCancelled({{ $order->id }}, this)"
                                    title="{{ __('Toggle Cancelled Items') }}"
                                    data-hidden="true">
                                     <i class="bi bi-eye"></i>
                                 </button>

                                 @if(isset($activeTab) && $activeTab->slug !== 'mou')
                                     <button class="btn btn-outline-warning btn-sm fw-bold" onclick="openAddEmployeeModal({{ $order->id }}, {{ $order->employer_id }}, {{ $order->workType->id ?? 'null' }}, '{{ $order->workType->slug ?? '' }}', 'workflow')">
                                        <i class="bi bi-plus-lg"></i> {{ __('Add') }}
                                     </button>

                                     <a href="{{ route('employees.import_view', ['production_id' => $order->id, 'employer_id' => $order->employer_id, 'return_to' => 'workflow']) }}" class="btn btn-outline-success btn-sm fw-bold">
                                        <i class="bi bi-file-earmark-spreadsheet"></i> {{ __('Import') }}
                                     </a>
                                 @endif
                                 @endif

                                @if(isset($activeTab) && $activeTab->slug === 'mou')
                                {{-- Custom Fields Button (Order/Job) --}}
                                <button class="btn btn-outline-secondary btn-sm ms-2 fw-bold" onclick="toggleOrderInlineDrawer({{ $order->id }}, {{ json_encode($order->customFields ?? []) }}); event.stopPropagation();">
                                    <i class="bi bi-list-task"></i> {{ __('Fields') }}
                                </button>

                                {{-- SEND ENTIRE ORDER BACK TO PRE-PRODUCTION --}}
                                <button class="btn btn-info btn-sm ms-2 fw-bold px-3 text-white shadow-sm"
                                        onclick="sendOrderBackToPreProduction({{ $order->id }})"
                                        title="{{ __('Return Job to Pre-production') }}">
                                    <i class="bi bi-arrow-counterclockwise"></i> <span class="d-none d-lg-inline">{{ __('Back to Prep') }}</span>
                                </button>
                                @endif

                                <button class="btn btn-light btn-sm rounded-circle ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $order->id }}">
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Order Custom Fields Drawer --}}
                    @if(isset($activeTab) && $activeTab->slug === 'mou')
                    <div class="collapse mt-3 mx-4" id="drawer-order-{{ $order->id }}">
                        <div class="card card-body bg-light border-0 rounded-3 shadow-sm">
                            <div id="drawer-content-order-{{ $order->id }}" class="position-relative" style="min-height: 100px;">
                                <div class="d-flex justify-content-center align-items-center h-100 py-3">
                                     <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                     <span class="ms-2 small text-muted">{{ __('Loading fields...') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Bottom Row: Workflow Steps (Horizontal Scroll) --}}
                    <div class="w-100 overflow-auto custom-scrollbar pb-1 mt-2" style="scrollbar-width: thin;">
                         <div class="d-flex flex-nowrap align-items-center gap-2">
                             @foreach($steps as $step)
                                @php
                                    $count = $stepStats[$step->id] ?? 0;
                                    $bgClass = $count > 0 ? "bg-success text-white" : "bg-secondary bg-opacity-25 text-muted";
                                @endphp
                                <div class="d-inline-flex align-items-center bg-light border rounded-pill px-3 py-1 gap-2 flex-shrink-0">
                                    <span class="badge rounded-circle d-flex align-items-center justify-content-center stat-badge {{ $bgClass }}" id="order-{{ $order->id }}-step-{{ $step->id }}">
                                        {{ $count }}
                                    </span>
                                    <span class="text-dark fw-bold" style="font-size: 0.85rem;">{{ $step->name }}</span>
                                </div>
                             @endforeach
                         </div>
                    </div>

                </div>

                    <div id="collapse-{{ $order->id }}" class="accordion-collapse collapse" data-employer-id="{{ $order->employer_id }}" aria-labelledby="heading-{{ $order->id }}" data-bs-parent="#workflowAccordion">
                        <div class="card-body bg-light p-4">
                            {{-- Default: hide-cancelled class added --}}
                            <div id="order-content-{{ $order->id }}" class="order-content-wrapper hide-cancelled">
                                <div class="d-flex justify-content-center py-5">
                                    <div class="spinner-border text-primary" role="status"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            {{-- Finance Modal for this Order --}}
            <div class="modal fade" id="financeModal-{{ $order->id }}" tabindex="-1" aria-hidden="true" onclick="event.stopPropagation()">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('Finance') }}: {{ $order->project_name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body bg-light">
                            @include('production.partials.financial-tab', [
                                'production' => $order,
                                'employeeCount' => $order->items->count(),
                                'employees' => $order->items->pluck('employee')->filter()->values()
                            ])
                        </div>
                    </div>
                </div>
            </div>
            </div>
        @empty
            <div class="text-center py-5">
                <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="120" class="mb-3 opacity-50" alt="No Data">
                <h4 class="text-muted">{{ __('No jobs found in this tab.') }}</h4>
                @if(!$isReadOnly)
                <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#createJobModal">
                    {{ __('Create New Job') }}
                </button>
                @endif
            </div>
        @endforelse
    </div>

    <div class="mt-4 d-flex justify-content-between align-items-center">
        @include('partials.per_page_selector')
        <div>
            {{ $orders->links() }}
        </div>
    </div>
</div>

{{-- Create Job Modal --}}
@if(!$isReadOnly)
@include('workflow.partials.create_modal')
@endif

{{-- Add Employee Modal --}}
@if(!$isReadOnly)
@include('workflow.partials.add_employee_modal')
@endif

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
                    <input type="number" class="form-control" id="notifyDaysInput" value="{{ $activeTab->notify_days_advance ?? 3 }}" min="0" max="365">
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

{{-- Manage Steps Modal --}}
<div class="modal fade" id="manageStepsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-diagram-3-fill me-2"></i>{{ __('Manage Workflow Steps') }} - {{ $activeTab->name ?? '' }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                {{-- Add New Step --}}
                <form id="addStepForm" class="mb-4 p-3 bg-light rounded border">
                    <label class="form-label fw-bold">{{ __('Add New Step') }}</label>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="text" class="form-control" id="newStepName" placeholder="{{ __('Step Name') }}" required>
                        <button class="btn btn-primary px-4" type="submit"><i class="bi bi-plus-lg"></i> {{ __('Add') }}</button>
                    </div>
                </form>

                <h6 class="fw-bold mb-3 text-secondary">{{ __('Existing Steps') }}</h6>
                <ul class="list-group list-group-flush" id="stepsList">
                    @foreach($steps as $step)
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3" id="step-item-{{ $step->id }}">
                            <div class="d-flex align-items-center gap-3 flex-grow-1">
                                <span class="badge bg-secondary rounded-pill">{{ $step->order }}</span>
                                <div class="d-flex align-items-center gap-2 step-display">
                                    <span class="fw-bold step-name-text">{{ $step->name }}</span>
                                </div>
                                <div class="step-edit d-none flex-grow-1 d-flex gap-2 align-items-center">
                                    <input type="text" class="form-control form-control-sm step-edit-input" value="{{ $step->name }}">
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="moveStep({{ $step->id }}, 'up')"><i class="bi bi-arrow-up"></i></button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="moveStep({{ $step->id }}, 'down')"><i class="bi bi-arrow-down"></i></button>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary btn-edit-step" onclick="toggleEditStep({{ $step->id }})"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-success d-none btn-save-step" onclick="saveStep({{ $step->id }})"><i class="bi bi-check-lg"></i></button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteStep({{ $step->id }})"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Manage Team Modal --}}
<div class="modal fade" id="manageTeamModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-people-fill me-2"></i>{{ __('Manage Workflow Team') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="team_item_id">

                <div class="mb-4">
                    <label for="workflow_team_name" class="form-label fw-bold text-dark">{{ __('Team Name / Batch') }}</label>
                    <input type="text" class="form-control form-control-lg" id="workflow_team_name" placeholder="{{ __('e.g., Batch 1, Arrived 25/10') }}">
                    <div class="form-text text-muted">{{ __('Assign a group name to organize employees in this job.') }}</div>
                </div>

                <div id="existing-teams-wrapper" class="d-none">
                    <h6 class="fw-bold text-secondary mb-3 small text-uppercase">{{ __('Existing Teams in this Job') }}</h6>
                    <div class="d-flex flex-wrap gap-2" id="existing-teams-list">
                        <!-- Chips loaded via JS -->
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-link text-secondary text-decoration-none" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary px-4" onclick="saveItemTeam()">
                    <i class="bi bi-check-lg me-1"></i> {{ __('Save') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Edit Employee Modal (Full Form) --}}
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
@include('employees.modals.advanced_export')
@include('production.registration.partials.offcanvas_drawer')
@include('production.registration.partials.modals.add_custom_field')

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const activeTabId = @json($activeTab->id ?? null);

    // --- Global Toggle Cancelled ---
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

    // --- Step Management JS ---
    document.getElementById('addStepForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        if(!activeTabId) return;

        const name = document.getElementById('newStepName').value;
        fetch('{{ route("workflow.steps.store") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ work_type_id: activeTabId, name: name })
        }).then(res => res.json()).then(data => {
            if(data.success) location.reload();
        });
    });

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
        fetch(`/workflow/steps/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ name: newName })
        }).then(res => res.json()).then(data => {
            if(data.success) location.reload();
        });
    }

    window.deleteStep = function(id) {
        if(!confirm('Delete this step?')) return;
        fetch(`/workflow/steps/${id}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        }).then(res => res.json()).then(data => {
            if(data.success) location.reload();
        });
    }

    window.moveStep = function(id, direction) {
        const list = document.getElementById('stepsList');
        const item = document.getElementById(`step-item-${id}`);
        if(direction === 'up') {
            const prev = item.previousElementSibling;
            if(prev) list.insertBefore(item, prev);
        } else {
            const next = item.nextElementSibling;
            if(next) list.insertBefore(next, item);
        }

        const order = [];
        list.querySelectorAll('li').forEach(li => order.push(li.id.replace('step-item-', '')));

        fetch('{{ route("workflow.steps.reorder") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ order: order })
        });
        setTimeout(() => location.reload(), 500);
    }

    // --- Dynamic Numbering ---
    // DEPRECATED: CSS Counters are now used for robust numbering.
    // function updateSequenceNumbers() { ... }

    // --- Lazy Load Accordion ---
    const loadedOrders = {};

    document.getElementById('workflowAccordion').addEventListener('show.bs.collapse', function (e) {
        if (e.target.classList.contains('accordion-collapse')) {
            const orderId = e.target.id.replace('collapse-', '');

            if (!loadedOrders[orderId]) {
                const container = document.getElementById(`order-content-${orderId}`);

                // Construct URL with current params (filter, search)
                const baseUrl = `{{ route('workflow.index') }}/${orderId}/items`;
                const url = new URL(baseUrl, window.location.origin);
                const currentParams = new URLSearchParams(window.location.search);
                currentParams.forEach((value, key) => url.searchParams.append(key, value));

                fetch(url)
                .then(res => res.text())
                .then(html => {
                    container.innerHTML = html;
                    loadedOrders[orderId] = true;
                    if(window.refreshGlobalSelectionUI) window.refreshGlobalSelectionUI();
                })
                .catch(err => {
                    container.innerHTML = '<div class="text-danger text-center py-3">Failed to load items.</div>';
                });
            } else {
                 if(window.refreshGlobalSelectionUI) setTimeout(window.refreshGlobalSelectionUI, 100);
            }
        }
    });

    // --- Filter Logic ---
    const currentStepFilter = @json(request('filter'));

    document.addEventListener('DOMContentLoaded', function() {
        if (currentStepFilter) {
            if (currentStepFilter === 'not_started') document.getElementById('filter-not-started')?.classList.add('filter-active');
            else if (currentStepFilter === 'cancelled') document.getElementById('filter-cancelled')?.classList.add('filter-active');
            else if (currentStepFilter === 'completed') document.getElementById('filter-completed')?.classList.add('filter-active');
            else if (currentStepFilter === 'pending_daily_check') document.getElementById('filter-daily-check')?.classList.add('filter-active');
            else {
                const pill = document.getElementById(`filter-step-${currentStepFilter}`);
                if (pill) pill.classList.add('filter-active');
            }
        }

    // Initial Batch Stats Load
    setTimeout(loadBatchStats, 500);
});

window.loadBatchStats = function() {
    const orderIds = Array.from(document.querySelectorAll('.production-order-card-container'))
        .map(container => {
            const btn = container.querySelector('[data-bs-target^="#collapse-"]');
            if (btn) {
                const target = btn.getAttribute('data-bs-target');
                if (target) {
                    return target.replace('#collapse-', '');
                }
            }
            return null;
        })
        .filter(id => id !== null);

    if (orderIds.length === 0) return;

    fetch('{{ route("workflow.stats.batch") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            order_ids: orderIds,
            tab: '{{ $activeTab->slug ?? "" }}'
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.stats) {
            for (const [orderId, stats] of Object.entries(data.stats)) {
                updateOrderHeaderStats(orderId, stats);
                // Also update the active state (grayscale or not)
                const card = document.getElementById(`heading-${orderId}`).closest('.production-order-card');
                if (card) {
                    if (stats.active_items_count > 0) {
                        card.classList.remove('grayscale-mode');
                    } else {
                        card.classList.add('grayscale-mode');
                    }
                }
            }
        }
    })
    .catch(err => console.error('Failed to load batch stats:', err));
};

    window.toggleFilter = function(filterKey) {
        const url = new URL(window.location.href);
        const currentFilter = url.searchParams.get('filter');

        if (currentFilter == filterKey) {
            url.searchParams.delete('filter');
        } else {
            url.searchParams.set('filter', filterKey);
        }
        window.location.href = url.toString();
    }

    // --- Employer-level Select All ---
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('employer-select-all')) {
            const orderId = e.target.dataset.employerId;
            const isChecked = e.target.checked;

            // Find the container for this order to only select visible items within it
            const container = document.getElementById(`order-content-${orderId}`);
            if (!container) return;

            const checkboxes = container.querySelectorAll('.employee-checkbox');

            checkboxes.forEach(cb => {
                // Determine if employee is eligible for selection
                const cardWrapper = cb.closest('.item-card-wrapper') || cb.closest('.employee-card-wrapper');

                // Only select visible cards
                const isHidden = cardWrapper && (cardWrapper.classList.contains('d-none') || cardWrapper.classList.contains('hide-cancelled'));
                const status = cardWrapper ? cardWrapper.dataset.status : '';
                const isSelectable = true; // Let the backend filter handle 'cancelled' by default, or user toggles it visible

                // Workflow primarily uses status pending or complete. Select active items.
                if (!isHidden && isSelectable) {
                    if(cb.checked !== isChecked) {
                        cb.checked = isChecked;
                        cb.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            });
        }
    });

    // --- Sync Employer Select All state when individual employees change ---
    function updateEmployerCheckboxesState() {
        document.querySelectorAll('.employer-select-all').forEach(masterCb => {
            const orderId = masterCb.dataset.employerId;
            const container = document.getElementById(`order-content-${orderId}`);
            if (!container) return;

            const allCheckboxes = container.querySelectorAll('.employee-checkbox');
            // Filter only relevant ones (pending & visible) to check against
            const relevantCheckboxes = Array.from(allCheckboxes).filter(cb => {
                const cw = cb.closest('.item-card-wrapper') || cb.closest('.employee-card-wrapper');
                const isHidden = cw && (cw.classList.contains('d-none') || cw.classList.contains('hide-cancelled'));
                const status = cw ? cw.dataset.status : '';
                const isSelectable = true;
                return !isHidden && isSelectable;
            });

            if (relevantCheckboxes.length > 0) {
                const allChecked = relevantCheckboxes.every(cb => cb.checked);
                masterCb.checked = allChecked;
                masterCb.indeterminate = !allChecked && relevantCheckboxes.some(cb => cb.checked);
            } else {
                masterCb.checked = false;
                masterCb.indeterminate = false;
            }
        });
    }

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('employee-checkbox')) {
            updateEmployerCheckboxesState();
        }
    });

    document.addEventListener('global-selection-updated', function() {
        updateEmployerCheckboxesState();
    });

    // --- Bulk Actions ---
    document.getElementById('bulk-advanced-export-btn')?.addEventListener('click', function(e) {
        e.preventDefault();
        const selected = window.getGlobalSelectedIds();
        if (selected.length === 0) {
            showToast('{{ __('Please select employees first.') }}', 'danger');
            return;
        }
        document.getElementById('export_employee_ids').value = JSON.stringify(selected);
        if (document.getElementById('export_source_menu')) {
            document.getElementById('export_source_menu').value = 'workflow';
        }
        new bootstrap.Modal(document.getElementById('advancedExportModal')).show();
    });

    document.getElementById('bulk-download-btn')?.addEventListener('click', function(e) {
        e.preventDefault();
        const selected = window.getGlobalSelectedIds();
        if (selected.length === 0) {
            showToast('{{ __('Please select employees first.') }}', 'danger');
            return;
        }
        if (window.openBulkDownloadModal) window.openBulkDownloadModal(selected);
    });

    document.getElementById('bulk-generate-pdf-btn')?.addEventListener('click', function(e) {
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

    document.getElementById('bulk-advanced-edit-btn')?.addEventListener('click', function(e) {
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

    // --- Toggle Step API (Updated for Button) ---
    window.toggleWorkStep = function(itemId, stepId, completed) {
        // Toggle UI immediately (Optimistic)
        const btn = document.querySelector(`.step-btn-${itemId}-${stepId}`);
        if(btn) {
            // Simple toggle visual
            if(completed) {
                btn.classList.remove('btn-light', 'text-secondary', 'border');
                btn.classList.add('btn-success', 'text-white');
                 if(!btn.innerHTML.includes('bi-check')) btn.innerHTML += ' <i class="bi bi-check-circle-fill ms-1"></i>';
                btn.setAttribute('onclick', `toggleWorkStep(${itemId}, ${stepId}, false)`);
            } else {
                btn.classList.add('btn-light', 'text-secondary', 'border');
                btn.classList.remove('btn-success', 'text-white');
                 const icon = btn.querySelector('i');
                if(icon) icon.remove();
                btn.setAttribute('onclick', `toggleWorkStep(${itemId}, ${stepId}, true)`);
            }
        }

        fetch(`/workflow/item/${itemId}/step-toggle`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ step_id: stepId, completed: completed })
        })
        .then(res => res.json())
        .then(data => {
            if(!data.success) {
                // Revert
                location.reload();
            } else {
                if (data.order_stats) {
                    // Find order ID
                    const card = document.getElementById(`item-card-${itemId}`);
                    if (card) {
                        const wrapper = card.closest('.order-content-wrapper');
                        if (wrapper) {
                            const orderId = wrapper.id.replace('order-content-', '');
                            updateOrderHeaderStats(orderId, data.order_stats);
                        }
                    }
                }
                if (data.tab_stats) {
                    updateGlobalStats(data.tab_stats);
                }
            }
        })
        .catch(err => {
            console.error(err);
        });
    }

    window.updateGlobalStats = function(stats) {
        if (!stats) return;

        const setHtml = (id, html) => {
            const el = document.getElementById(id);
            if(el) el.innerHTML = html;
        };

        const setText = (selector, text) => {
            const el = document.querySelector(selector);
            if(el) el.innerText = text;
        };

        setHtml('stats-total-employees', stats.total_employees);
        setHtml('stats-total-projects', stats.total_projects);
        setHtml('stats-daily-check', stats.pending_daily_check);
        setText('#filter-not-started h1', stats.not_started);
        setText('#filter-cancelled h1', stats.cancelled);
        setText('#filter-completed h1', stats.completed);

        // Global Progress
        if (stats.step_stats) {
            for (const [stepId, count] of Object.entries(stats.step_stats)) {
                const badge = document.querySelector(`#filter-step-${stepId} .stat-badge`);
                if (badge) {
                    badge.innerText = count;
                    if (count > 0) {
                        badge.classList.remove('bg-secondary', 'bg-opacity-50', 'text-white');
                        badge.classList.add('bg-success');
                    } else {
                        badge.classList.add('bg-secondary', 'bg-opacity-50', 'text-white');
                        badge.classList.remove('bg-success');
                    }
                }
            }
        }
    }

    function updateOrderHeaderStats(orderId, stats) {
        if (!stats) return;

        const setText = (id, text) => {
            const el = document.getElementById(id);
            if(el) el.innerText = text;
        };

        setText(`order-${orderId}-total`, stats.total);
        setText(`order-${orderId}-pending`, stats.not_started);
        setText(`order-${orderId}-completed`, stats.completed);
        setText(`order-${orderId}-cancelled`, stats.cancelled);

        // Steps
        if (stats.step_stats) {
            for (const [stepId, count] of Object.entries(stats.step_stats)) {
                const badge = document.getElementById(`order-${orderId}-step-${stepId}`);
                if (badge) {
                    badge.innerText = count;
                    if (count > 0) {
                        badge.classList.remove('bg-secondary', 'bg-opacity-25', 'text-muted');
                        badge.classList.add('bg-success', 'text-white');
                    } else {
                        badge.classList.add('bg-secondary', 'bg-opacity-25', 'text-muted');
                        badge.classList.remove('bg-success', 'text-white');
                    }
                }
            }
        }
    }

    // --- Helper to Refresh Order Content (List) ---
    window.refreshOrderContent = function(orderId) {
        const container = document.getElementById(`order-content-${orderId}`);
        if (container) {
            // Keep min-height to prevent jitter
            container.style.minHeight = container.offsetHeight + 'px';
            container.style.opacity = '0.5';

            const baseUrl = `{{ route('workflow.index') }}/${orderId}/items`;
            const url = new URL(baseUrl, window.location.origin);
            const currentParams = new URLSearchParams(window.location.search);
            currentParams.forEach((value, key) => url.searchParams.append(key, value));

            fetch(url)
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;
                container.style.opacity = '1';
                container.style.minHeight = '';
                if(window.refreshGlobalSelectionUI) window.refreshGlobalSelectionUI();
            });
        }
    };

    // --- Helper to Refresh Card ---
    window.refreshItemCard = function(itemId) {
        fetch(`/workflow/item/${itemId}/card`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if(data.html) {
                const card = document.getElementById(`item-card-${itemId}`);
                if(card) {
                    card.outerHTML = data.html;
                }
            }
        });
    }

    // --- Helper to Remove Card ---
    window.removeItemCard = function(itemId) {
        const card = document.getElementById(`item-card-${itemId}`);
        if(card) {
            card.style.transition = 'all 0.3s ease';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.95)';
            setTimeout(() => {
                card.remove();
            }, 300);
        }
    }

    // --- New Features JS ---
    window.toggleCancelled = function(orderId, btn) {
        const container = document.getElementById(`order-content-${orderId}`);
        const icon = btn.querySelector('i');
        const isHidden = btn.dataset.hidden === "true";

        if (isHidden) {
            // Show them
            container.classList.remove('hide-cancelled');
            btn.dataset.hidden = "false";
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            // Hide them
            container.classList.add('hide-cancelled');
            btn.dataset.hidden = "true";
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }

    window.openHistoryModal = function(orderId) {
        const modal = new bootstrap.Modal(document.getElementById('historyModal'));
        modal.show();

        const body = document.getElementById('historyModalBody');
        body.innerHTML = '<div class="d-flex justify-content-center py-5"><div class="spinner-border text-secondary" role="status"></div></div>';

        fetch(`/workflow/${orderId}/history`)
            .then(res => res.text())
            .then(html => {
                body.innerHTML = html;
            })
            .catch(err => {
                body.innerHTML = '<div class="text-danger text-center p-4">Failed to load history.</div>';
            });
    }

    // --- Item Actions ---
    window.sendBackToPreProduction = function(itemId) {
        Swal.fire({
            title: '{{ __("Send Back to Preparation?") }}',
            text: '{{ __("Employee will be moved back to Pre-Production list.") }}',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '{{ __("Yes, Send Back") }}',
            confirmButtonColor: '#0dcaf0'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/workflow/item/${itemId}/send-back`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        // Remove card from UI
                        const card = document.getElementById(`item-card-${itemId}`);
                        const wrapper = card.closest('.order-content-wrapper');
                        card.remove();

                        // Check if wrapper is empty
                        if(wrapper && wrapper.querySelectorAll('.item-card-wrapper').length === 0) {
                            const orderCard = wrapper.closest('.production-order-card');
                            if(orderCard) {
                                orderCard.classList.add('grayscale-mode');
                            }
                        }

                        Swal.fire('{{ __("Sent Back!") }}', '{{ __("Employee moved to Pre-Production.") }}', 'success');

                        if(data.order_stats) {
                             if(wrapper) {
                                 const orderId = wrapper.id.replace('order-content-', '');
                                 updateOrderHeaderStats(orderId, data.order_stats);
                             }
                        }
                    } else {
                        Swal.fire('{{ __("Error") }}', data.message || '{{ __("Failed to send back.") }}', 'error');
                    }
                });
            }
        });
    }

    window.finalizeItem = function(itemId) {
        Swal.fire({
            title: '{{ __("Complete Item?") }}',
            text: '{{ __("Mark as completed?") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __("Yes") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/workflow/item/${itemId}/finalize`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                })
                .then(res => {
                    if (!res.ok) throw new Error(res.statusText);
                    return res.json();
                })
                .then(data => {
                    if(data.success) {
                        // Refresh card to show "Saved/Completed" state (Green/Flat)
                        refreshItemCard(itemId);

                        Swal.fire({
                            icon: 'success',
                            title: '{{ __("Finished") }}',
                            text: '{{ __("Item marked as completed.") }}',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        if(data.order_stats) {
                             const card = document.getElementById(`item-card-${itemId}`);
                             if(card) {
                                 const wrapper = card.closest('.order-content-wrapper');
                                 if(wrapper) {
                                     const orderId = wrapper.id.replace('order-content-', '');
                                     updateOrderHeaderStats(orderId, data.order_stats);
                                 }
                             }
                        }
                    } else {
                         Swal.fire('Error', data.message || '{{ __("Something went wrong") }}', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', '{{ __("Failed to save data.") }}', 'error');
                });
            }
        });
    }

    window.cancelItem = function(itemId) {
        Swal.fire({
            title: '{{ __("Cancel Item?") }}',
            text: '{{ __("Mark as cancelled?") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __("Yes") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/workflow/item/${itemId}/cancel`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        // Refresh card to show "Cancelled" state (Gray/Flat)
                        refreshItemCard(itemId);

                        Swal.fire({
                            icon: 'success',
                            title: '{{ __("Cancelled") }}',
                            text: '{{ __("Item cancelled.") }}',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        if(data.order_stats) {
                             const card = document.getElementById(`item-card-${itemId}`);
                             if(card) {
                                 const wrapper = card.closest('.order-content-wrapper');
                                 if(wrapper) {
                                     const orderId = wrapper.id.replace('order-content-', '');
                                     updateOrderHeaderStats(orderId, data.order_stats);
                                 }
                             }
                        }
                    }
                });
            }
        });
    }

    window.restoreItem = function(itemId) {
        Swal.fire({
            title: '{{ __("Restore Item?") }}',
            text: '{{ __("Restore to pending state?") }}',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '{{ __("Yes") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/workflow/item/${itemId}/restore`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                         refreshItemCard(itemId);
                         Swal.fire({
                            icon: 'success',
                            title: '{{ __("Restored") }}',
                            text: '{{ __("Item restored.") }}',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        if(data.order_stats) {
                             const card = document.getElementById(`item-card-${itemId}`);
                             if(card) {
                                 const wrapper = card.closest('.order-content-wrapper');
                                 if(wrapper) {
                                     const orderId = wrapper.id.replace('order-content-', '');
                                     updateOrderHeaderStats(orderId, data.order_stats);
                                 }
                             }
                        }
                    }
                });
            }
        });
    }

    window.deleteItem = function(itemId) {
        Swal.fire({
            title: '{{ __("Delete Item?") }}',
            text: '{{ __("This cannot be undone.") }}',
            icon: 'error',
            showCancelButton: true,
            confirmButtonText: '{{ __("Delete") }}',
            confirmButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/workflow/item/${itemId}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        removeItemCard(itemId);
                    }
                });
            }
        });
    }

    // --- Notification Settings JS ---
    window.saveNotificationSettings = function() {
        if(!activeTabId) return;
        const days = document.getElementById('notifyDaysInput').value;

        fetch(`/workflow/settings/${activeTabId}/notification`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
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

    // --- Manage Team JS (Workflow Batch) ---
    window.openManageTeamModal = function(itemId, btn) {
        const groupName = btn.dataset.groupName || '';
        const orderId = btn.dataset.orderId;

        document.getElementById('team_item_id').value = itemId;
        const nameInput = document.getElementById('workflow_team_name');
        nameInput.value = groupName;

        // Existing Teams (Scan DOM)
        const wrapper = document.getElementById('existing-teams-wrapper');
        const list = document.getElementById('existing-teams-list');
        list.innerHTML = '';
        wrapper.classList.add('d-none');

        if(orderId) {
            const container = document.getElementById(`order-content-${orderId}`);
            if(container) {
                // Find group headers (h6.fw-bold.text-dark.mb-0)
                const headers = container.querySelectorAll('h6.fw-bold.text-dark.mb-0');
                const uniqueGroups = new Set();
                headers.forEach(h => uniqueGroups.add(h.innerText.trim()));

                if(uniqueGroups.size > 0) {
                    wrapper.classList.remove('d-none');
                    uniqueGroups.forEach(name => {
                        const badge = document.createElement('button');
                        badge.className = 'btn btn-sm btn-outline-secondary rounded-pill px-3';
                        badge.type = 'button';
                        badge.innerText = name;
                        badge.onclick = () => { nameInput.value = name; };
                        list.appendChild(badge);
                    });
                }
            }
        }

        const modal = new bootstrap.Modal(document.getElementById('manageTeamModal'));
        modal.show();
    }

    window.saveItemTeam = function() {
        const itemId = document.getElementById('team_item_id').value;
        const groupName = document.getElementById('workflow_team_name').value;

        fetch(`/workflow/item/${itemId}/group`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ group_name: groupName })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                bootstrap.Modal.getInstance(document.getElementById('manageTeamModal')).hide();
                Swal.fire({
                    icon: 'success',
                    title: '{{ __('Saved') }}',
                    text: '{{ __('Team assigned successfully.') }}',
                    timer: 1500,
                    showConfirmButton: false
                });

                // Update UI: Reload the order items to reflect new grouping
                // Find Order ID
                const card = document.getElementById(`item-card-${itemId}`);
                if (card) {
                    const wrapper = card.closest('.order-content-wrapper');
                    if (wrapper) {
                        const orderId = wrapper.id.replace('order-content-', '');
                        refreshOrderContent(orderId);
                    }
                }
            } else {
                 Swal.fire('{{ __('Error') }}', data.message || '{{ __('Failed to assign team.') }}', 'error');
            }
        });
    }

    // --- Operator Toggle ---
    window.toggleOperator = function(itemId, btn, hasOperator) {
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
                    optionsHtml += `<option value="${op.id}">${op.name}</option>`;
                });

                const htmlContent = `
                    <div class="form-group text-start">
                        <label class="form-label mb-2">{{ __("Select Operator") }}</label>
                        <select id="operator-select-${itemId}" class="form-select form-select-lg">
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
                    didOpen: () => {},
                    preConfirm: () => {
                        const selectedId = document.getElementById(`operator-select-${itemId}`).value;

                        return fetch(`/workflow/item/${itemId}/toggle-operator`, {
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
                            refreshItemCard(itemId);
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

    // --- Daily Check ---
    window.checkDaily = function(itemId) {
        fetch(`/workflow/item/${itemId}/check-daily`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                const urlParams = new URLSearchParams(window.location.search);
                const currentFilter = urlParams.get('filter');

                // If filtering by "Daily Check", remove the card completely
                if (currentFilter === 'pending_daily_check') {
                    removeItemCard(itemId);
                    // Decrement scoreboard
                    const scoreboard = document.querySelector('#filter-daily-check h1');
                    if(scoreboard) {
                        let count = parseInt(scoreboard.innerText);
                        if(count > 0) scoreboard.innerText = count - 1;
                    }
                } else {
                    // UI update: Remove the button and the orange border
                    const card = document.getElementById(`item-card-${itemId}`);
                    if(card) {
                        const cardInner = card.querySelector('.card');
                        // Remove border warning classes
                        cardInner.classList.remove('border-warning', 'border-3', 'shadow');
                        cardInner.classList.add('shadow-sm'); // Reset to default shadow
                        // Find and remove the check button
                        const checkBtn = card.querySelector('button[title="Daily Check"]');
                        if(checkBtn) checkBtn.remove();
                    }
                }
            }
        });
    }

    // --- Trash Feature ---
    window.loadTrashContent = function(url) {
        const body = document.getElementById('trashModalBody');
        body.innerHTML = '<div class="d-flex justify-content-center py-5"><div class="spinner-border text-danger" role="status"></div></div>';

        fetch(url || '{{ route("workflow.trash") }}')
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
            text: '{{ __("Restore this item from trash?") }}',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '{{ __("Yes, Restore") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/workflow/trash/${id}/restore`, {
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
                            text: '{{ __("Item restored successfully.") }}',
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
            const link = e.target.closest('.pagination a, .page-link');
            if (link && link.href) {
                e.preventDefault();
                loadTrashContent(link.href);
            }
        });
    }

    // --- GPS / Deep Linking ---
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const targetOrderId = urlParams.get('order');
        const targetItemId = urlParams.get('item');
        const highlightEmployeeId = urlParams.get('highlight_employee_id');
        const highlightEmployerId = urlParams.get('highlight_employer_id');

        // Note: Workflow index uses order_id to expand accordions.
        // We will fallback to highlighting by employer id if passed and order is missing.
        let actualOrderId = targetOrderId;
        if (!actualOrderId && highlightEmployerId) {
             const collapseEls = document.querySelectorAll('.accordion-collapse');
             collapseEls.forEach(el => {
                  if (el.getAttribute('data-employer-id') === highlightEmployerId) {
                      actualOrderId = el.id.replace('collapse-', '');
                  }
             });
        }

        if (actualOrderId && (targetItemId || highlightEmployeeId)) {
            const orderHeading = document.getElementById('heading-' + actualOrderId);
            const collapseElement = document.getElementById('collapse-' + actualOrderId);

            if (orderHeading && collapseElement) {
                // Scroll to Order
                setTimeout(() => {
                    orderHeading.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 100);

                // Check if collapsed
                if (!collapseElement.classList.contains('show')) {
                    // Trigger click on the button
                    const btn = orderHeading.querySelector('button[data-bs-toggle="collapse"]');
                    if(btn) btn.click();
                }

                const highlightTarget = (card) => {
                    setTimeout(() => {
                         card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                         const innerCard = card.querySelector('.card') || card;
                         innerCard.classList.add('border-warning', 'border-3', 'shadow-lg');
                         // For Employee cards specifically
                         if (highlightEmployeeId) {
                             innerCard.classList.add('filter-active');
                         }
                         setTimeout(() => {
                             innerCard.classList.remove('border-warning', 'border-3', 'shadow-lg', 'filter-active');
                             innerCard.classList.add('shadow-sm');
                         }, 5000);
                    }, 500);
                };

                // Wait for Item to Load
                const observer = new MutationObserver(function(mutations, obs) {
                    let targetCard = document.getElementById('item-card-' + targetItemId);
                    // If highlighting an employee, look for the employee card inside the item card or on its own depending on the view
                    if (highlightEmployeeId) {
                        const empCard = document.getElementById('employee-card-' + highlightEmployeeId) ||
                                        document.querySelector(`[data-employee-id="${highlightEmployeeId}"]`)?.closest('.employee-card-wrapper') ||
                                        document.querySelector(`[data-employee-id="${highlightEmployeeId}"]`)?.closest('.list-group-item');
                        if (empCard) targetCard = empCard;
                    }

                    if (targetCard) {
                        highlightTarget(targetCard);
                        obs.disconnect();
                    }
                });

                // Start observing the content wrapper
                const contentWrapper = document.getElementById('order-content-' + actualOrderId);
                if (contentWrapper) {
                    observer.observe(contentWrapper, { childList: true, subtree: true });

                    // Fallback check
                    setTimeout(() => {
                         let targetCard = document.getElementById('item-card-' + targetItemId);
                         if (highlightEmployeeId) {
                            const empCard = document.getElementById('employee-card-' + highlightEmployeeId) ||
                                            document.querySelector(`[data-employee-id="${highlightEmployeeId}"]`)?.closest('.employee-card-wrapper') ||
                                            document.querySelector(`[data-employee-id="${highlightEmployeeId}"]`)?.closest('.list-group-item');
                            if (empCard) targetCard = empCard;
                         }

                         if(targetCard) {
                             highlightTarget(targetCard);
                             observer.disconnect();
                         }
                    }, 1500);
                }
            }
        }
    });
</script>
@endpush

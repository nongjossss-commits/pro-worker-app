@extends('layouts.app')

@section('title', 'Pre-Production Dashboard')

@section('content')
<x-help-button manual="production" title="{{ __('P Production') }}" />
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
    .stat-badge { width: 24px; height: 24px; font-size: 0.75rem; }
    .custom-scrollbar::-webkit-scrollbar { height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 3px; }

    /* CSS Counters for persistent slot numbering */
    #productionAccordion {
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
    /* Fallback if wrapper is employee-card-wrapper instead of item-card-wrapper */
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
        .item-card-wrapper .card-body > .d-flex { flex-direction: column !important; }
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
    .hide-cancelled .employee-card-wrapper[data-status="cancelled"],
    .hide-cancelled .employee-card-wrapper[data-status="registration_cancelled"],
    .hide-cancelled .employee-card-wrapper[data-status="renewal_cancelled"],
    .hide-cancelled .status-cancelled {
        display: none !important;
    }
</style>

<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-0"><i class="bi bi-boxes me-2"></i>{{ __('Pre-Production / Preparation') }}</h1>
            <p class="text-muted mb-0">{{ __('Prepare documents and confirm data before sending to Workflow.') }}</p>
        </div>
    </div>

    {{-- Search & Actions (Moved Above Filter) --}}
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-3 mb-4">
        {{-- Search Bar --}}
        <form action="{{ route('production.index') }}" method="GET" class="d-flex flex-grow-1 justify-content-center w-100">
            @if(isset($activeTab))
                <input type="hidden" name="tab" value="{{ $activeTab->slug }}">
            @endif
            <div class="input-group input-group-lg" style="max-width: 500px;">
                <span class="input-group-text bg-white border-end-0 shadow-sm"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control bg-white border-start-0 shadow-sm" placeholder="{{ __('Search...') }}" value="{{ request('search') }}">
                <button class="btn btn-primary shadow-sm" type="submit">{{ __('Search') }}</button>
                @if(request('search'))
                    <a href="{{ route('production.index', ['tab' => $activeTab->slug ?? null]) }}" class="btn btn-outline-secondary shadow-sm bg-white">{{ __('Clear') }}</a>
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
                <li><a class="dropdown-item" href="{{ route('production.index', array_merge(request()->query(), ['operator_filter' => null])) }}">{{ __('All Operators') }}</a></li>
                <li><hr class="dropdown-divider"></li>
                @foreach($users as $user)
                    <li><a class="dropdown-item {{ request('operator_filter') == $user->id ? 'active' : '' }}" href="{{ route('production.index', array_merge(request()->query(), ['operator_filter' => $user->id])) }}">{{ $user->name }}</a></li>
                @endforeach
            </ul>
        </div>

        {{-- Actions --}}
        @if(!$isReadOnly && isset($activeTab))
        <div class="d-flex gap-2">
            <button class="btn btn-secondary btn-sm shadow-sm" onclick="openTrashModal()">
                <i class="bi bi-trash-fill me-1"></i> {{ __('Trash') }}
            </button>
            <button class="btn btn-light btn-sm shadow-sm border" data-bs-toggle="modal" data-bs-target="#manageStepsModal">
                <i class="bi bi-gear-fill me-1"></i> {{ __('Steps') }}
            </button>
            @if(isset($activeTab) && in_array($activeTab->slug, ['mou', 'mou_import']))
                <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#createJobModal">
                    <i class="bi bi-plus-lg me-1"></i> {{ __('Create Job') }}
                </button>
            @else
                <button class="btn btn-primary fw-bold shadow-sm" onclick="openAddEmployeeModal(null, null, {{ $activeTab->id ?? 'null' }}, '{{ $activeTab->slug ?? '' }}', 'production')">
                    <i class="bi bi-plus-lg me-1"></i> {{ __('Add Employee') }}
                </button>
            @endif
        </div>
        @endif
    </div>

    {{-- Address Filter --}}
    <x-address-filter :provinces="$addressOptions['provinces']" :districts="$addressOptions['districts']" :subDistricts="$addressOptions['subDistricts']" />

    {{-- Tabs Navigation --}}
    <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-body p-3">
            <ul class="nav nav-pills gap-2 overflow-auto flex-nowrap w-100" style="scrollbar-width: thin;">
                @foreach($tabs as $tab)
                    <li class="nav-item">
                        <a class="nav-link {{ isset($activeTab) && $activeTab->id === $tab->id ? 'active fw-bold shadow-sm' : 'bg-white border text-secondary' }}"
                           href="{{ route('production.index', ['tab' => $tab->slug]) }}"
                           style="white-space: nowrap;">
                            {{ $tab->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Scoreboard --}}
    <div class="row row-cols-1 row-cols-md-3 row-cols-xl-6 g-3 mb-4">
        {{-- Total Employees --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm border-0 cursor-pointer" onclick="window.location.href = '{{ route('production.index', ['tab' => $activeTab->slug ?? null]) }}';" style="background-color: #FBBF24;">
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0" id="stats-total-employees">{{ $stats['total_employees'] ?? 0 }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Total Employees') }}</p>
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

        {{-- Daily Check (Reset at Midnight) --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm border-0 cursor-pointer filter-card" id="filter-daily-check" onclick="toggleFilter('pending_daily_check')" style="background-color: #F97316;">
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0" id="stats-daily-check">{{ $stats['pending_daily_check'] ?? 0 }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Daily Check') }}</p>
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

    {{-- Steps Progress Bar --}}
    @if(isset($activeTab))
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="card-title fw-bold text-secondary mb-0">
                    <i class="bi bi-bar-chart-fill me-2"></i>{{ __('Preparation Progress') }}
                </h5>
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
                    <span class="text-muted small">{{ __('No preparation steps configured.') }}</span>
                 @endif
            </div>
        </div>
    </div>
    @endif

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
                <li><a class="dropdown-item" href="#" id="bulk-send-to-workflow-btn"><i class="bi bi-send-check me-2"></i>{{ __('Send to Workflow') }}</a></li>
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
    <div class="accordion" id="productionAccordion">
        @forelse($orders as $order)
            @php
                 $computed = $order->computedStats ?? ['total'=>0, 'not_started'=>0, 'step_stats'=>[]];
                 $stepStats = $computed['step_stats'];
                 // Inactive logic: No Active Items (not cancelled/completed)
                 // This matches the Controller logic, but for UI styling
                 $isActive = $computed['active_items_count'] > 0;
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
                                fetch(`{{ url('production') }}/{{ $order->id }}/remarks`, {
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
                                <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center text-warning" style="width: 40px; height: 40px;">
                                    <i class="bi bi-hourglass-split fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark">
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

                                    {{-- MOU Import demand card info: สัญชาติ + เป้าหมายจำนวนคน --}}
                                    @if($order->mou_nationality)
                                        @php
                                            $natFlags = ['myanmar' => '🇲🇲', 'laos' => '🇱🇦', 'cambodia' => '🇰🇭', 'vietnam' => '🇻🇳'];
                                            $natLabels = ['myanmar' => __('Myanmar'), 'laos' => __('Laos'), 'cambodia' => __('Cambodia'), 'vietnam' => __('Vietnam')];
                                            $male = (int) ($order->mou_male_count ?? 0);
                                            $female = (int) ($order->mou_female_count ?? 0);
                                            $total = $male + $female;
                                        @endphp
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                                {{ $natFlags[$order->mou_nationality] ?? '' }} {{ $natLabels[$order->mou_nationality] ?? $order->mou_nationality }}
                                            </span>
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">
                                                <i class="bi bi-people-fill me-1"></i>{{ __('Total') }}: {{ $total }}
                                            </span>
                                            @if($male > 0)
                                                <span class="badge bg-light text-dark border">{{ __('Male') }}: {{ $male }}</span>
                                            @endif
                                            @if($female > 0)
                                                <span class="badge bg-light text-dark border">{{ __('Female') }}: {{ $female }}</span>
                                            @endif
                                        </div>
                                    @endif

                                    @if($order->employer && $order->employer->jobOwner)
                                        <div class="text-muted small border-start ps-2 mt-1 ms-1">
                                            <i class="bi bi-person-badge me-1"></i>
                                            <a href="{{ route('production.index', ['tab' => $activeTab->slug ?? null, 'search' => $order->employer->jobOwner->name]) }}" class="text-decoration-none text-secondary">
                                                {{ $order->employer->jobOwner->name }}
                                            </a>
                                        </div>
                                    @endif

                                    <div class="text-muted small mt-1">
                                        @if($order->updater)
                                            <i class="bi bi-clock-history me-1"></i>
                                            <a href="{{ route('production.index', ['tab' => $activeTab->slug ?? null, 'search' => $order->updater->name]) }}" class="text-decoration-none text-secondary" title="{{ __('Last Modified By') }}">
                                                {{ $order->updater->name }}
                                            </a>
                                        @elseif($order->creator)
                                            <i class="bi bi-person-circle me-1"></i>
                                            <a href="{{ route('production.index', ['tab' => $activeTab->slug ?? null, 'search' => $order->creator->name]) }}" class="text-decoration-none text-secondary" title="{{ __('Created By') }}">
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
                                 {{-- Stats --}}
                                 <div class="d-flex align-items-center gap-2 me-xl-3">
                                    <span class="badge bg-light text-dark border d-flex align-items-center justify-content-center gap-2 px-2 py-1" style="min-width: 80px;">
                                        <span class="fw-bold" id="order-{{ $order->id }}-total">{{ $computed['total'] }}</span>
                                        <span class="text-muted small" style="font-size: 0.65rem;">TOTAL</span>
                                    </span>
                                 </div>

                                 <div class="vr d-none d-xl-block me-2"></div>

                                 {{-- Actions --}}
                                 @if(!$isReadOnly)
                                 {{-- Finance Button --}}
                                 @can('view-finance')
                                 <button class="btn btn-outline-primary btn-sm rounded-circle me-1"
                                    onclick="event.stopPropagation(); FinancialSecurity.checkAndRun(() => openFinanceModal({{ $order->id }}))"
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

                                 {{-- Add Employee Button — แสดงทุก tab รวม MOU (ลูกจ้างใน demand card) --}}
                                 <button class="btn btn-outline-warning btn-sm fw-bold" onclick="openAddEmployeeModal({{ $order->id }}, {{ $order->employer_id }}, {{ $order->workType->id ?? 'null' }}, '{{ $order->workType->slug ?? '' }}', 'production')">
                                    <i class="bi bi-plus-lg"></i> {{ __('Add') }}
                                 </button>
                                 @endif

                                @if(isset($activeTab) && in_array($activeTab->slug, ['mou', 'mou_import']))
                                {{-- Custom Fields Button (Order/Job) --}}
                                <button class="btn btn-outline-secondary btn-sm ms-2 fw-bold" onclick="toggleOrderInlineDrawer({{ $order->id }}, {{ json_encode($order->customFields ?? []) }}); event.stopPropagation();">
                                    <i class="bi bi-list-task"></i> {{ __('Fields') }}
                                </button>

                                {{-- SEND ENTIRE ORDER TO WORKFLOW --}}
                                <button class="btn btn-primary btn-sm ms-2 fw-bold px-3 shadow-sm"
                                        onclick="sendOrderToWorkflow({{ $order->id }})"
                                        title="{{ __('Send Job to Workflow') }}">
                                    <i class="bi bi-box-arrow-right"></i> <span class="d-none d-lg-inline">{{ __('Send to Workflow') }}</span>
                                </button>
                                @endif

                                <button class="btn btn-light btn-sm rounded-circle ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $order->id }}">
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Order Custom Fields Drawer --}}
                    @if(isset($activeTab) && in_array($activeTab->slug, ['mou', 'mou_import']))
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

                    {{-- Bottom Row: Steps Progress --}}
                    <div class="w-100 overflow-auto custom-scrollbar pb-1 mt-2" style="scrollbar-width: thin;">
                         <div class="d-flex flex-nowrap align-items-center gap-2">
                             @foreach($steps as $step)
                                @php
                                    $count = $stepStats[$step->id] ?? 0;
                                    $bgClass = $count > 0 ? "bg-info text-dark" : "bg-secondary bg-opacity-10 text-muted";
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

                    <div id="collapse-{{ $order->id }}" class="accordion-collapse collapse" data-employer-id="{{ $order->employer_id }}" aria-labelledby="heading-{{ $order->id }}" data-bs-parent="#productionAccordion">
                        <div class="card-body bg-light p-4">
                             {{-- Lazy Load Content Container — inside a scroll
                                  frame so the order header stays visible while
                                  the employee/item list scrolls independently. --}}
                            <x-card-scroll-frame maxHeight="60vh" variant="inner">
                            <div id="order-content-{{ $order->id }}" class="order-content-wrapper hide-cancelled">
                                 <div class="d-flex justify-content-center py-5">
                                    <div class="spinner-border text-primary" role="status"></div>
                                </div>
                            </div>
                            </x-card-scroll-frame>
                        </div>
                    </div>
                </div>

                {{-- Finance Modal (lazy load — โหลดข้อมูลเมื่อเปิดครั้งแรก ไม่โหลดทุก order พร้อมกันตอนเปิดหน้า) --}}
                <div class="modal fade" id="financeModal-{{ $order->id }}" tabindex="-1" aria-hidden="true" onclick="event.stopPropagation()">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">{{ __('Finance') }}: {{ $order->employer->employerNameTh ?? $order->project_name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body bg-light p-0" id="finance-modal-body-{{ $order->id }}">
                                <div class="d-flex justify-content-center align-items-center py-5">
                                    <div class="spinner-border text-primary" role="status"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5">
                <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="120" class="mb-3 opacity-50" alt="No Data">
                <h4 class="text-muted">{{ __('No preparation jobs found.') }}</h4>
                <p class="text-muted">{{ __('Select a tab or create a new job to get started.') }}</p>
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

{{-- Create Job Modal (Reuse Workflow Partial) --}}
@if(!$isReadOnly)
@include('workflow.partials.create_modal')
@endif

{{-- Add Employee Modal (Reuse Workflow Partial) --}}
@if(!$isReadOnly)
@include('workflow.partials.add_employee_modal')
@endif

{{-- Manage Steps Modal (Preparation) --}}
<div class="modal fade" id="manageStepsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-dark">
                <h5 class="modal-title fw-bold"><i class="bi bi-diagram-3-fill me-2"></i>{{ __('Manage Preparation Steps') }} - {{ $activeTab->name ?? '' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
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
                             <span class="fw-bold">{{ $step->name }}</span>
                             <button class="btn btn-sm btn-outline-danger" onclick="deleteStep({{ $step->id }})"><i class="bi bi-trash"></i></button>
                        </li>
                    @endforeach
                </ul>
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

{{-- Manage Team Modal (Copied from Workflow for consistency) --}}
<div class="modal fade" id="manageTeamModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-people-fill me-2"></i>{{ __('Manage Team / Batch') }}</h5>
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

@include('employees.partials._edit_scripts')
@include('production.registration.partials.edit_modal_script')
@include('employees.modals.advanced_export')
@include('production.registration.partials.offcanvas_drawer')
@include('production.registration.partials.modals.add_custom_field')
@include('production.partials.order_fields_drawer')

{{-- Trash Modal --}}
<div class="modal fade" id="trashModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-trash-fill me-2"></i>{{ __('Trash Bin (Pre-Production)') }}</h5>
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

@include('production._index_scripts')

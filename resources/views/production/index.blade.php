@extends('layouts.app')

@section('title', 'Pre-Production Dashboard')

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
    .stat-badge { width: 24px; height: 24px; font-size: 0.75rem; }
    .custom-scrollbar::-webkit-scrollbar { height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 3px; }

    .employer-sequence-number {
        min-width: 50px;
        text-align: center;
        font-size: 2.5rem;
        font-weight: bold;
        color: #6c757d;
        opacity: 0.5;
    }

    .item-sequence-number {
        min-width: 40px;
        text-align: right;
        font-weight: bold;
        white-space: nowrap;
    }
    .filter-active {
        transform: scale(1.05);
        border: 2px solid #3b82f6 !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        z-index: 10;
        transition: all 0.2s ease-in-out;
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
            <button class="btn btn-primary fw-bold shadow-sm" onclick="openAddEmployeeModal(null, null, {{ $activeTab->id ?? 'null' }}, '{{ $activeTab->slug ?? '' }}', 'production')">
                <i class="bi bi-plus-lg me-1"></i> {{ __('Add Employee') }}
            </button>
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
    <div class="bulk-action-bar mb-3 align-items-center gap-2" style="display: none;" id="bulkActionBar">
        <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" id="select-all-checkbox">
            <label class="form-check-label" for="select-all-checkbox">
                {{ __('Select All') }} (<span id="selected-count">0</span>)
            </label>
        </div>

        <div class="dropdown">
            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="bulkActionDropdown" data-bs-toggle="dropdown" aria-expanded="false">
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
        <button class="btn btn-sm btn-outline-danger" onclick="window.clearGlobalSelection();">{{ __('Clear Selection') }}</button>
        <button class="btn btn-sm btn-info text-white ms-2" id="btn-view-selected" onclick="window.openViewSelectedModal()">
            <i class="bi bi-eye me-1"></i> {{ __('View Selected') }}
            <span class="badge bg-white text-info ms-1" id="view-selected-count">0</span>
        </button>
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
            <div class="d-flex align-items-start production-order-card-container w-100 mb-4">
                <div class="employer-sequence-number me-3 pt-2"></div>

                <div class="card border-0 shadow-sm flex-grow-1 production-order-card {{ !$isActive ? 'grayscale-mode' : '' }}">
                    <div class="card-header bg-white border-bottom py-3 px-4" id="heading-{{ $order->id }}">

                        {{-- Top Row: Identity + Stats + Actions --}}
                    <div class="row align-items-xl-center g-3 mb-3">
                        {{-- Identity --}}
                        <div class="col-12 col-xl-auto d-flex align-items-center flex-wrap gap-3">
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
                                    onclick="event.stopPropagation(); FinancialSecurity.checkAndRun(() => new bootstrap.Modal(document.getElementById('financeModal-{{ $order->id }}')).show())"
                                    title="{{ __('Finance') }}">
                                    <i class="bi bi-currency-dollar"></i>
                                </button>
                                @endcan

                                 {{-- Add Employee Button --}}
                                 <button class="btn btn-outline-warning btn-sm fw-bold" onclick="openAddEmployeeModal({{ $order->id }}, {{ $order->employer_id }}, {{ $order->workType->id ?? 'null' }}, '{{ $order->workType->slug ?? '' }}', 'production')">
                                    <i class="bi bi-plus-lg"></i> {{ __('Add') }}
                                 </button>
                                 @endif

                                <button class="btn btn-light btn-sm rounded-circle ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $order->id }}">
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Bottom Row: Steps Progress --}}
                    <div class="w-100 overflow-auto custom-scrollbar pb-1" style="scrollbar-width: thin;">
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

                    <div id="collapse-{{ $order->id }}" class="accordion-collapse collapse" aria-labelledby="heading-{{ $order->id }}" data-bs-parent="#productionAccordion">
                        <div class="card-body bg-light p-4">
                             {{-- Lazy Load Content Container --}}
                            <div id="order-content-{{ $order->id }}" class="order-content-wrapper">
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
                                <h5 class="modal-title">{{ __('Finance') }}: {{ $order->employer->employerNameTh ?? $order->project_name }}</h5>
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

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const activeTabId = @json($activeTab->id ?? null);

    // --- Pre-Production Flag Setting ---
    document.addEventListener('DOMContentLoaded', function() {
        // Set Create Job Modal Flag
        const createJobInput = document.getElementById('create_job_is_pre_production');
        if(createJobInput) createJobInput.value = '1';
    });

    // --- Dynamic Numbering ---
    window.recalculateSequenceNumbers = function() {
        // 1. Employer/Order Cards
        let orderCount = 1;
        document.querySelectorAll('.production-order-card-container').forEach(card => {
            if (card.offsetParent !== null) { // Visible check
                const numEl = card.querySelector('.employer-sequence-number');
                if(numEl) numEl.innerText = orderCount++;
            }
        });

        // 2. Employee/Item Cards (Per Order)
        document.querySelectorAll('.order-content-wrapper').forEach(wrapper => {
            let itemCount = 1;
            wrapper.querySelectorAll('.item-card-wrapper').forEach(card => {
                // Check visibility
                if (card.offsetParent !== null) {
                    const numEl = card.querySelector('.item-sequence-number');
                    if(numEl) numEl.innerText = itemCount++;
                }
            });
        });
    };

    // --- Lazy Load ---
    const loadedOrders = {};
    document.getElementById('productionAccordion').addEventListener('show.bs.collapse', function (e) {
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
                    recalculateSequenceNumbers();
                });
            } else {
                 if(window.refreshGlobalSelectionUI) setTimeout(window.refreshGlobalSelectionUI, 100);
                 setTimeout(recalculateSequenceNumbers, 50);
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
        setTimeout(recalculateSequenceNumbers, 100);
    });

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

    // --- Bulk Actions ---
    document.getElementById('bulk-advanced-export-btn')?.addEventListener('click', function(e) {
        e.preventDefault();
        const selected = window.getGlobalSelectedIds();
        if (selected.length === 0) {
            showToast('{{ __('Please select employees first.') }}', 'danger');
            return;
        }
        document.getElementById('export_employee_ids').value = JSON.stringify(selected);
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

    // --- Step Management ---
    document.getElementById('addStepForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        if(!activeTabId) return;
        const name = document.getElementById('newStepName').value;

        fetch('{{ route("production.steps.store") }}', { // New Route
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ work_type_id: activeTabId, name: name })
        }).then(res => res.json()).then(data => {
            if(data.success) location.reload();
        });
    });

    window.deleteStep = function(id) {
         if(!confirm('Delete this step?')) return;
        fetch(`/workflow/steps/${id}`, { // Can reuse existing delete endpoint as ID is unique
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        }).then(res => res.json()).then(data => {
            if(data.success) location.reload();
        });
    }

    // --- Send to Workflow ---
    window.sendToWorkflow = function(itemId) {
        Swal.fire({
            title: '{{ __("Send to Workflow?") }}',
            text: '{{ __("Employee will be moved to the Active Job list.") }}',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '{{ __("Yes, Send") }}',
            confirmButtonColor: '#0d6efd'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/production/${itemId}/send-to-workflow`, {
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
                        recalculateSequenceNumbers();

                        // Check if wrapper is empty
                        if(wrapper && wrapper.querySelectorAll('.item-card-wrapper').length === 0) {
                            const orderCard = wrapper.closest('.production-order-card');
                            if(orderCard) {
                                orderCard.classList.add('grayscale-mode');
                            }
                        }

                        Swal.fire('{{ __("Sent!") }}', '{{ __("Employee moved to Workflow.") }}', 'success');

                        if(data.order_stats) {
                             const card = document.getElementById(`item-card-${itemId}`);
                             // The card is removed above, but we can get orderId from wrapper if we saved reference,
                             // OR we assume we can find the order header by ID if we pass orderId.
                             // But wait, the card is removed!
                             // "const wrapper = card.closest('.order-content-wrapper');" was called BEFORE removal.
                             if(wrapper) {
                                 const orderId = wrapper.id.replace('order-content-', '');
                                 updateOrderHeaderStats(orderId, data.order_stats);
                             }
                        }
                    } else {
                        Swal.fire('{{ __("Error") }}', data.message, 'error');
                    }
                });
            }
        });
    }

    // --- Helper to Refresh Order Content (List) ---
    window.refreshOrderContent = function(orderId) {
        const container = document.getElementById(`order-content-${orderId}`);
        if (container) {
            container.style.minHeight = container.offsetHeight + 'px';
            container.style.opacity = '0.5';

            fetch(`{{ route('workflow.index') }}/${orderId}/items`)
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;
                container.style.opacity = '1';
                container.style.minHeight = '';
                recalculateSequenceNumbers();
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
                    setTimeout(recalculateSequenceNumbers, 50);
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
                recalculateSequenceNumbers();
            }, 300);
        }
    }

    window.updateOrderHeaderStats = function(orderId, stats) {
        if (!stats) return;
        // Basic implementation for Pre-Production if needed (badges are slightly different)
        // Usually Pre-Prod doesn't have detailed stats like Workflow, but if badges exist:

        const setText = (id, text) => {
            const el = document.getElementById(id);
            if(el) el.innerText = text;
        };

        // Steps
        if (stats.step_stats) {
            for (const [stepId, count] of Object.entries(stats.step_stats)) {
                const badge = document.getElementById(`order-${orderId}-step-${stepId}`);
                if (badge) {
                    badge.innerText = count;
                    if (count > 0) {
                        badge.classList.remove('bg-secondary', 'bg-opacity-10', 'text-muted');
                        badge.classList.add('bg-info', 'text-dark');
                    } else {
                        badge.classList.add('bg-secondary', 'bg-opacity-10', 'text-muted');
                        badge.classList.remove('bg-info', 'text-dark');
                    }
                }
            }
        }
    }

    // --- Actions for Pre-Production ---
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

    // "Save to Database" (Finish)
    window.finalizeItem = function(itemId) {
        Swal.fire({
            title: '{{ __("Save to Database?") }}',
            text: '{{ __("Mark this employee as saved/completed?") }}',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '{{ __("Yes, Save") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/workflow/item/${itemId}/finalize`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        refreshItemCard(itemId);
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __("Saved") }}',
                            text: '{{ __("Employee data saved successfully.") }}',
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

    // --- Manage Team JS (Copied from Workflow) ---
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
        const performToggle = () => {
            btn.disabled = true;
            fetch(`/workflow/item/${itemId}/toggle-operator`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    refreshItemCard(itemId);
                } else {
                    btn.disabled = false;
                }
            });
        };

        if (hasOperator) {
            Swal.fire({
                title: '{{ __("Change Operator?") }}',
                text: '{{ __("Are you sure you want to change or remove the operator?") }}',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '{{ __("Yes, Change") }}',
                confirmButtonColor: '#0d6efd',
                cancelButtonText: '{{ __("Cancel") }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    performToggle();
                }
            });
        } else {
            performToggle();
        }
    }

    // --- Reuse Toggle Step from Workflow (Global Function) ---
    // Make sure toggleWorkStep exists or include it here if not globally available.
    // It is defined in workflow/index.blade.php. We should copy it or extract to shared JS.
    // For now, I will include a minimal version here.

    window.toggleWorkStep = function(itemId, stepId, completed) {
        // Optimistic UI Update
        const btn = document.querySelector(`.step-btn-${itemId}-${stepId}`);
        if(btn) {
            if(completed) {
                btn.classList.remove('btn-light', 'text-secondary');
                btn.classList.add('btn-success', 'text-white');
                 if(!btn.innerHTML.includes('bi-check')) btn.innerHTML += ' <i class="bi bi-check-circle-fill ms-1"></i>';
                btn.setAttribute('onclick', `toggleWorkStep(${itemId}, ${stepId}, false)`);
            } else {
                btn.classList.add('btn-light', 'text-secondary');
                btn.classList.remove('btn-success', 'text-white');
                 const icon = btn.querySelector('i');
                if(icon) icon.remove();
                btn.setAttribute('onclick', `toggleWorkStep(${itemId}, ${stepId}, true)`);
            }
        }

        fetch(`/workflow/item/${itemId}/step-toggle`, { // Reuse Workflow endpoint
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ step_id: stepId, completed: completed })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                location.reload(); // Revert on failure
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
        .catch(err => console.error(err));
    }

    window.updateGlobalStats = function(stats) {
        if (!stats) return;

        // Scoreboard
        const setHtml = (id, html) => {
            const el = document.getElementById(id);
            if(el) el.innerHTML = html;
        };

        const setText = (selector, text) => {
            const el = document.querySelector(selector);
            if(el) el.innerText = text;
        };

        // Counters
        setHtml('stats-total-employees', stats.total_employees);
        setHtml('stats-total-projects', stats.total_projects);
        setHtml('stats-daily-check', stats.pending_daily_check);
        setText('#filter-not-started h1', stats.not_started);
        setText('#filter-cancelled h1', stats.cancelled);
        setText('#filter-completed h1', stats.completed);

        // Global Progress Bar Pills
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

    // --- Trash Feature ---
    window.loadTrashContent = function(url) {
        const body = document.getElementById('trashModalBody');
        body.innerHTML = '<div class="d-flex justify-content-center py-5"><div class="spinner-border text-danger" role="status"></div></div>';

        fetch(url || '{{ route("workflow.trash") }}?is_pre_production=1')
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
    document.addEventListener('DOMContentLoaded', function() {
        const trashBody = document.getElementById('trashModalBody');
        if (trashBody) {
            trashBody.addEventListener('click', function(e) {
                // Check if clicked element is pagination link or inside one
                const link = e.target.closest('.pagination a, .page-link');
                if (link && link.href) {
                    e.preventDefault();
                    // Append is_pre_production flag if not present in pagination link
                    let fetchUrl = link.href;
                    if (!fetchUrl.includes('is_pre_production')) {
                        const urlObj = new URL(fetchUrl);
                        urlObj.searchParams.set('is_pre_production', '1');
                        fetchUrl = urlObj.toString();
                    }
                    loadTrashContent(fetchUrl);
                }
            });
        }
    });

    // --- GPS / Deep Linking ---
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const targetOrderId = urlParams.get('order');
        const targetItemId = urlParams.get('item');
        const highlightEmployeeId = urlParams.get('highlight_employee_id');

        if (targetOrderId && targetItemId) {
            const orderHeading = document.getElementById('heading-' + targetOrderId);
            const collapseElement = document.getElementById('collapse-' + targetOrderId);

            if (orderHeading && collapseElement) {
                // Scroll to Order
                orderHeading.scrollIntoView({ behavior: 'smooth', block: 'center' });

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
                         innerCard.classList.add('border-info', 'border-3', 'shadow-lg');
                         // For Employee cards specifically
                         if (highlightEmployeeId) {
                             innerCard.classList.add('filter-active');
                         }
                         setTimeout(() => {
                             innerCard.classList.remove('border-info', 'border-3', 'shadow-lg', 'filter-active');
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
                const contentWrapper = document.getElementById('order-content-' + targetOrderId);
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

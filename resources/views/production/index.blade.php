@extends('layouts.app')

@section('title', 'Pre-Production Dashboard')

@section('content')
<style>
    .cursor-pointer { cursor: pointer; }
    .grayscale-mode { filter: grayscale(100%); opacity: 0.8; }
    .stat-badge { width: 24px; height: 24px; font-size: 0.75rem; }
    .custom-scrollbar::-webkit-scrollbar { height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 3px; }
</style>

<div class="container-fluid py-4">
    {{-- Scoreboard (Replicated from Workflow) --}}
    <div class="row row-cols-1 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
        {{-- Total Employees --}}
        <div class="col">
            <a href="{{ request()->fullUrlWithQuery(['filter_status' => null]) }}" class="text-decoration-none">
                <div class="card text-white h-100 shadow-sm border-0" style="background-color: #FBBF24;">
                    <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                        <h1 class="display-4 fw-bold mb-0">{{ $stats['total_employees'] ?? 0 }}</h1>
                        <p class="fs-5 fw-light mb-0">{{ __('Total Employees') }}</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- Not Started --}}
        <div class="col">
            <a href="{{ request()->fullUrlWithQuery(['filter_status' => 'not_started']) }}" class="text-decoration-none">
                <div class="card text-white h-100 shadow-sm border-0" style="background-color: #EF4444;">
                    <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                        <h1 class="display-4 fw-bold mb-0">{{ $stats['not_started'] ?? 0 }}</h1>
                        <p class="fs-5 fw-light mb-0">{{ __('Not Started') }}</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- Cancelled --}}
        <div class="col">
            <a href="{{ request()->fullUrlWithQuery(['filter_status' => 'cancelled']) }}" class="text-decoration-none">
                <div class="card text-white h-100 shadow-sm border-0" style="background-color: #6B7280;">
                    <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                        <h1 class="display-4 fw-bold mb-0">{{ $stats['cancelled'] ?? 0 }}</h1>
                        <p class="fs-5 fw-light mb-0">{{ __('Cancelled') }}</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- Completed --}}
        <div class="col">
            <a href="{{ request()->fullUrlWithQuery(['filter_status' => 'completed']) }}" class="text-decoration-none">
                <div class="card text-white h-100 shadow-sm border-0" style="background-color: #10B981;">
                    <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                        <h1 class="display-4 fw-bold mb-0">{{ $stats['completed'] ?? 0 }}</h1>
                        <p class="fs-5 fw-light mb-0">{{ __('Completed') }}</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- Total Projects --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm border-0 bg-primary bg-gradient">
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0">{{ $stats['total_projects'] ?? 0 }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Active Projects') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Global Progress --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="card-title fw-bold text-secondary mb-0">
                    <i class="bi bi-bar-chart-fill me-2"></i>{{ __('Preparation Progress') }}
                </h5>
                @if(isset($activeTab))
                    <div class="d-flex gap-2">
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
                    <a href="{{ request()->fullUrlWithQuery(['filter_step' => $step->id]) }}" class="text-decoration-none">
                        <div class="d-inline-flex align-items-center bg-white border rounded-pill py-2 px-3 shadow-sm gap-2 hover-shadow">
                            <span class="badge rounded-circle {{ $bgClass }} shadow-sm d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                {{ $count }}
                            </span>
                            <span class="fw-bold text-dark fs-6">{{ $step->name }}</span>
                        </div>
                    </a>
                @endforeach
                @if($steps->isEmpty())
                    <p class="text-muted small mb-0">{{ __('No steps configured.') }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Address Filter --}}
    <x-address-filter :provinces="$addressOptions['provinces']" :districts="$addressOptions['districts']" :subDistricts="$addressOptions['subDistricts']" />

    {{-- Tabs Navigation --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <ul class="nav nav-pills gap-2 overflow-auto flex-nowrap pb-2" style="scrollbar-width: thin;">
             {{-- No "Dashboard" overview tab for Pre-Production as per request (Mirror Workflow Tabs) --}}
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

        <div class="d-flex gap-2">
            @if(isset($activeTab))
                <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#manageStepsModal">
                    <i class="bi bi-gear-fill me-1"></i> {{ __('Preparation Steps') }}
                </button>
            @endif
            <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#createJobModal">
                <i class="bi bi-plus-lg me-1"></i> {{ __('Create Preparation Job') }}
            </button>
        </div>
    </div>

    {{-- Active Tab Steps (Global for Tab) --}}
    @if(isset($activeTab))
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-2">
                    <h6 class="fw-bold text-secondary mb-0 me-3">{{ __('Preparation Steps') }}</h6>
                    <span class="badge bg-info text-dark">{{ $activeTab->name }}</span>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                     @foreach($steps as $step)
                        <div class="badge bg-light text-dark border px-3 py-2">
                            {{ $step->name }}
                        </div>
                     @endforeach
                     @if($steps->isEmpty())
                        <span class="text-muted small">{{ __('No preparation steps configured. Click the gear icon to add steps.') }}</span>
                     @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Accordion List --}}
    <div class="accordion" id="productionAccordion">
        @forelse($orders as $order)
            @php
                 $computed = $order->computedStats ?? ['total'=>0, 'not_started'=>0, 'step_stats'=>[]];
                 $stepStats = $computed['step_stats'];
            @endphp
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom py-3 px-4" id="heading-{{ $order->id }}">

                    {{-- Top Row: Identity + Stats + Actions --}}
                    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-3">
                        {{-- Identity --}}
                        <div class="d-flex align-items-center gap-3 overflow-hidden" style="min-width: 0;">
                            <button class="btn btn-link text-decoration-none text-dark p-0 text-start d-flex align-items-center gap-3 border-0 bg-transparent" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $order->id }}">
                                <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center text-warning flex-shrink-0" style="width: 40px; height: 40px;">
                                    <i class="bi bi-hourglass-split fs-5"></i>
                                </div>
                                <div style="min-width: 0;">
                                    <h5 class="fw-bold mb-0 text-dark text-truncate">
                                        {{ $order->project_name }}
                                        @if(request('addrProvince') && $order->employer)
                                            @foreach($order->employer->getMatchedAddressLabels(request('addrProvince'), request('addrDistrict'), request('addrSubDistrict')) as $label)
                                                <span class="badge bg-info text-white small ms-1" style="font-size: 0.7rem;">{{ $label }}</span>
                                            @endforeach
                                        @endif
                                    </h5>
                                    <div class="text-muted small d-flex align-items-center gap-2 mt-1">
                                        <span class="text-truncate">{{ $order->employer->employerNameTh ?? 'Unknown Employer' }}</span>
                                        <button class="btn btn-sm btn-link p-0 text-primary btn-preview flex-shrink-0"
                                            data-model-type="employer"
                                            data-model-id="{{ $order->employer_id }}"
                                            title="Preview Employer">
                                            <i class="bi bi-search"></i>
                                        </button>
                                        <span class="text-muted opacity-50">&bull;</span>
                                        <span class="flex-shrink-0">{{ $order->updated_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </button>
                        </div>

                        {{-- Stats & Actions --}}
                        <div class="d-flex align-items-center justify-content-end gap-2 flex-wrap flex-grow-1">
                             {{-- Stats --}}
                             <div class="d-flex align-items-center gap-2 me-xl-3">
                                <span class="badge bg-light text-dark border d-flex align-items-center justify-content-center gap-2 px-2 py-1" style="min-width: 80px;">
                                    <span class="fw-bold">{{ $computed['total'] }}</span>
                                    <span class="text-muted small" style="font-size: 0.65rem;">TOTAL</span>
                                </span>
                             </div>

                             <div class="vr d-none d-xl-block me-2"></div>

                             {{-- Actions --}}
                             <div class="d-flex align-items-center gap-2">
                                 {{-- Add Employee Button --}}
                                 <button class="btn btn-outline-warning btn-sm fw-bold text-nowrap" onclick="openAddEmployeeModal({{ $order->id }}, {{ $order->employer_id }}, {{ $order->workType->id ?? 'null' }}, '{{ $order->workType->slug ?? '' }}')">
                                    <i class="bi bi-plus-lg"></i> {{ __('Add') }}
                                 </button>

                                 {{-- Import Button (Added to match user expectation) --}}
                                 <a href="{{ route('employees.import_view', ['production_id' => $order->id, 'employer_id' => $order->employer_id, 'return_to' => 'production']) }}" class="btn btn-outline-success btn-sm fw-bold text-nowrap">
                                    <i class="bi bi-file-earmark-spreadsheet"></i> {{ __('Import') }}
                                 </a>

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
        @empty
            <div class="text-center py-5">
                <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="120" class="mb-3 opacity-50" alt="No Data">
                <h4 class="text-muted">{{ __('No preparation jobs found.') }}</h4>
                <p class="text-muted">{{ __('Select a tab or create a new job to get started.') }}</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $orders->links() }}
    </div>
</div>

{{-- Create Job Modal (Reuse Workflow Partial) --}}
@include('workflow.partials.create_modal')

{{-- Add Employee Modal (Reuse Workflow Partial) --}}
@include('workflow.partials.add_employee_modal')

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
<div class="modal fade" id="cropperModal" tabindex="-1" aria-labelledby="cropperModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cropperModalLabel">ครอบตัดรูปภาพ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <style>
                    .img-container {
                        max-height: 500px;
                        display: block;
                    }
                    .img-container img {
                        max-width: 100%;
                        display: block;
                    }
                </style>
                <div class="img-container">
                    <img id="imageToCrop" src="" alt="Picture" style="display: block; max-width: 100%;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" id="cropImageBtn">ครอบตัดและบันทึก</button>
            </div>
        </div>
    </div>
</div>

@include('employees.partials._edit_scripts')
@include('production.registration.partials.edit_modal_script')

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

        // Override OpenAddEmployeeModal to set flag after opening (as form might reset)
        if(typeof window.openAddEmployeeModal === 'function') {
            const originalOpenFn = window.openAddEmployeeModal;
            window.openAddEmployeeModal = function(orderId, employerId, workTypeId, workTypeSlug) {
                originalOpenFn(orderId, employerId, workTypeId, workTypeSlug);
                // Force set the flag again
                const addEmpInput = document.getElementById('add_employee_is_pre_production');
                if(addEmpInput) addEmpInput.value = '1';
            }
        }
    });

    // --- Lazy Load ---
    const loadedOrders = {};
    document.getElementById('productionAccordion').addEventListener('show.bs.collapse', function (e) {
        if (e.target.classList.contains('accordion-collapse')) {
            const orderId = e.target.id.replace('collapse-', '');
            if (!loadedOrders[orderId]) {
                const container = document.getElementById(`order-content-${orderId}`);
                // Reusing Workflow item fetcher, passing order ID.
                // Since structure is identical, this returns _item_card.blade.php loops.
                fetch(`{{ route('workflow.index') }}/${orderId}/items`)
                .then(res => res.text())
                .then(html => {
                    container.innerHTML = html;
                    loadedOrders[orderId] = true;
                });
            }
        }
    });

    // --- Step Management ---
    document.getElementById('addStepForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        if(!activeTabId) return;
        const name = document.getElementById('newStepName').value;

        fetch('{{ route("production.steps.store") }}', { // New Route
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ work_type_id: activeTabId, name: name })
        }).then(res => res.json()).then(data => {
            if(data.success) location.reload();
        });
    });

    window.deleteStep = function(id) {
         if(!confirm('Delete this step?')) return;
        fetch(`/workflow/steps/${id}`, { // Can reuse existing delete endpoint as ID is unique
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken }
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
                fetch(`/production/item/${itemId}/send-to-workflow`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        // Remove card from UI
                        document.getElementById(`item-card-${itemId}`).remove();
                        Swal.fire('{{ __("Sent!") }}', '{{ __("Employee moved to Workflow.") }}', 'success');
                    } else {
                        Swal.fire('{{ __("Error") }}', data.message, 'error');
                    }
                });
            }
        });
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
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ step_id: stepId, completed: completed })
        });
    }

</script>
@endpush

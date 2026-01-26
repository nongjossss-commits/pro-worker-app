@extends('layouts.app')

@section('title', 'Workflow Dashboard')

@section('content')
<div class="container-fluid py-4">
    {{-- Scoreboard (Dynamic based on Stats) --}}
    <div class="row row-cols-1 row-cols-md-3 g-3 mb-4">
        <div class="col">
            <div class="card text-white h-100 shadow-sm border-0 bg-primary bg-gradient">
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0">{{ $stats['total_projects'] ?? 0 }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Active Projects') }}</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-white h-100 shadow-sm border-0 bg-success bg-gradient">
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0">{{ $stats['total_employees'] ?? 0 }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Total Employees') }}</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 shadow-sm border-0 bg-white">
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h5 class="text-muted fw-bold mb-2">{{ $activeTab->name ?? 'Overview' }}</h5>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        @foreach($steps as $step)
                            <span class="badge bg-light text-dark border">{{ $step->name }}</span>
                        @endforeach
                    </div>
                    @if($steps->isEmpty())
                        <p class="text-muted small mb-0">{{ __('No steps configured') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs Navigation --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <ul class="nav nav-pills gap-2 overflow-auto flex-nowrap pb-2" style="scrollbar-width: thin;">
            @foreach($tabs as $tab)
                <li class="nav-item">
                    <a class="nav-link {{ isset($activeTab) && $activeTab->id === $tab->id ? 'active fw-bold shadow-sm' : 'bg-white border text-secondary' }}"
                       href="{{ route('workflow.index', ['tab' => $tab->slug]) }}"
                       style="white-space: nowrap;">
                        {{ $tab->name }}
                    </a>
                </li>
            @endforeach
            {{-- Add Tab Button (Admin) --}}
            {{-- Permission check later --}}
            <li class="nav-item">
                <button class="btn btn-outline-secondary border-dashed" title="Add Work Type" onclick="alert('Feature coming soon: Add Custom Tab')">
                    <i class="bi bi-plus-lg"></i>
                </button>
            </li>
        </ul>

        <div class="d-flex gap-2">
            <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#createJobModal">
                <i class="bi bi-plus-lg me-1"></i> {{ __('Add Job / Employee') }}
            </button>
        </div>
    </div>

    {{-- Accordion List --}}
    <div class="accordion" id="workflowAccordion">
        @forelse($orders as $order)
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom-0 py-3" id="heading-{{ $order->id }}">
                    <div class="d-flex align-items-center justify-content-between w-100">
                        <div class="d-flex align-items-center gap-3 cursor-pointer flex-grow-1"
                             data-bs-toggle="collapse"
                             data-bs-target="#collapse-{{ $order->id }}"
                             aria-expanded="false"
                             aria-controls="collapse-{{ $order->id }}">

                            {{-- Icon based on Type --}}
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-primary" style="width: 48px; height: 48px;">
                                @if($order->type === 'independent')
                                    <i class="bi bi-person-workspace fs-4"></i>
                                @else
                                    <i class="bi bi-building fs-4"></i>
                                @endif
                            </div>

                            <div>
                                <h5 class="fw-bold mb-0 text-dark">{{ $order->project_name }}</h5>
                                <div class="text-muted small">
                                    @if($order->employer)
                                        {{ $order->employer->employerNameTh }}
                                        @if($order->employer->employerNameEn)
                                            / {{ $order->employer->employerNameEn }}
                                        @endif
                                    @else
                                        <span class="fst-italic">{{ __('Independent / Mixed') }}</span>
                                    @endif
                                    <span class="mx-2">•</span>
                                    {{ $order->updated_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3">
                            {{-- Item Count --}}
                            <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 py-2">
                                <i class="bi bi-people-fill me-1"></i> {{ $order->items_count }}
                            </span>

                            <div class="dropdown">
                                <button class="btn btn-light btn-sm rounded-circle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" onclick="openAddEmployeeModal({{ $order->id }}, {{ $order->employer_id }}, {{ $order->workType->id ?? 'null' }}, '{{ $order->workType->slug ?? '' }}')"><i class="bi bi-person-plus me-2"></i>{{ __('Add Employee') }}</a></li>
                                    {{-- <li><a class="dropdown-item" href="#"><i class="bi bi-pencil me-2"></i>{{ __('Edit Details') }}</a></li> --}}
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-trash me-2"></i>{{ __('Delete') }}</a></li>
                                </ul>
                            </div>

                            <button class="btn btn-light btn-sm rounded-circle" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $order->id }}">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="collapse-{{ $order->id }}" class="accordion-collapse collapse" aria-labelledby="heading-{{ $order->id }}" data-bs-parent="#workflowAccordion">
                    <div class="card-body bg-light border-top">
                        <div id="order-content-{{ $order->id }}" class="order-content-wrapper">
                            {{-- AJAX Content --}}
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
                <h4 class="text-muted">{{ __('No jobs found in this tab.') }}</h4>
                <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#createJobModal">
                    {{ __('Create New Job') }}
                </button>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $orders->links() }}
    </div>
</div>

{{-- Create Job Modal --}}
@include('workflow.partials.create_modal')

{{-- Add Employee Modal (Reuse or simplified) --}}
@include('workflow.partials.add_employee_modal')

@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // --- Lazy Load Accordion ---
    const loadedOrders = {};

    document.getElementById('workflowAccordion').addEventListener('show.bs.collapse', function (e) {
        if (e.target.classList.contains('accordion-collapse')) {
            const orderId = e.target.id.replace('collapse-', '');

            if (!loadedOrders[orderId]) {
                const container = document.getElementById(`order-content-${orderId}`);

                fetch(`{{ route('workflow.index') }}/${orderId}/items`)
                .then(res => res.text())
                .then(html => {
                    container.innerHTML = html;
                    loadedOrders[orderId] = true;
                })
                .catch(err => {
                    container.innerHTML = '<div class="text-danger text-center py-3">Failed to load items.</div>';
                });
            }
        }
    });

    // --- Toggle Step API ---
    window.toggleWorkStep = function(checkbox, itemId, stepId) {
        const completed = checkbox.checked;

        fetch(`/workflow/item/${itemId}/step-toggle`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ step_id: stepId, completed: completed })
        })
        .then(res => res.json())
        .then(data => {
            if(!data.success) {
                checkbox.checked = !completed; // Revert
                Swal.fire('Error', 'Failed to update step.', 'error');
            } else {
                // Optional: Toast
            }
        })
        .catch(err => {
            checkbox.checked = !completed;
            Swal.fire('Error', 'Network error.', 'error');
        });
    }

    // --- Edit Group Name ---
    window.editItemGroup = function(itemId, currentVal) {
        Swal.fire({
            title: '{{ __("Edit Group / Team") }}',
            input: 'text',
            inputValue: currentVal || '',
            showCancelButton: true,
            confirmButtonText: '{{ __("Save") }}',
            inputPlaceholder: 'e.g. Batch 1'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/workflow/item/${itemId}/group`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ group_name: result.value })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire('Saved!', '', 'success').then(() => {
                            // Reload content to reflect grouping change
                            // Find the collapse parent ID
                            // Actually, simpler to just reload page or find order ID context
                            // Let's reload for simplicity for now as grouping changes DOM structure significantly
                            location.reload();
                        });
                    }
                });
            }
        });
    }

    // --- Open Add Employee Modal ---
    window.openAddEmployeeModal = function(orderId, employerId) {
        // Populate hidden fields in the modal
        // Note: The modal partial needs to be implemented.
        // For now, simpler to just redirect to create page with params?
        // Or reuse the createJobModal but pre-fill data.

        // Let's trigger the createJobModal and set hidden inputs
        // This requires the modal to support "Adding to existing"

        // Simpler approach: Set the employer_id in the create modal and select the correct tab?
        // But we need to target a SPECIFIC Order ID.
        // The Create Job logic currently "Finds or Creates".
        // If we select the same employer and Work Type, it will find this order.

        // So we just need to know the Work Type of this order.
        // I'll leave this for the modal implementation step.
    }

    // --- Finalize Item ---
    window.finalizeItem = function(itemId) {
        Swal.fire({
            title: '{{ __("Complete Process?") }}',
            text: '{{ __("This will finalize the employee (Transfer or Terminate depending on type).") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __("Yes, Complete") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/workflow/item/${itemId}/finalize`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire('Completed!', '', 'success').then(() => {
                            location.reload();
                        });
                    }
                });
            }
        });
    }
</script>
@endpush

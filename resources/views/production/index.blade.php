@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-gray-800 fw-bold">{{ __('P Production (Preparation)') }}</h1>
            <p class="text-muted">{{ __('Prepare projects before sending to Workflow') }}</p>
        </div>
        <a href="{{ route('production.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>{{ __('New Project') }}
        </a>
    </div>

    <x-address-filter :provinces="$addressOptions['provinces']" :districts="$addressOptions['districts']" :subDistricts="$addressOptions['subDistricts']" />

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4" style="width: 25%;">{{ __('Project Name') }}</th>
                            <th style="width: 10%;">{{ __('Type') }}</th>
                            <th style="width: 20%;">{{ __('Employer') }}</th>
                            <th class="text-center" style="width: 10%;">{{ __('Employees') }}</th>
                            <th style="width: 20%;">{{ __('Readiness Status') }}</th>
                            <th class="text-end pe-4" style="width: 15%;">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold">{{ $order->project_name ?? 'Untitled Project' }}</div>
                                    <div class="small text-muted">{{ Str::limit($order->description, 50) }}</div>
                                    <div class="small text-muted">{{ __('Created') }} {{ $order->created_at->format('d/m/Y') }}</div>

                                    {{-- Waiting for Documents Warning --}}
                                    <div class="mt-1" id="missing-docs-display-{{ $order->id }}" style="{{ $order->waiting_for_documents ? '' : 'display:none;' }}">
                                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>{{ __('Waiting for Docs') }}</span>
                                        <div class="small text-danger fst-italic mt-1 missing-docs-text">{{ $order->missing_documents }}</div>
                                    </div>
                                </td>
                                <td>
                                    @if($order->type === 'independent')
                                        <span class="badge bg-purple text-white" style="background-color: #6f42c1;">{{ __('Independent') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('Standard') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($order->type === 'employer' && $order->employer)
                                        <div class="fw-bold">
                                            {{ $order->employer->employerNameEn ?? $order->employer->employerNameTh }}
                                            @if(request('addrProvince'))
                                                @foreach($order->employer->getMatchedAddressLabels(request('addrProvince'), request('addrDistrict'), request('addrSubDistrict')) as $label)
                                                    <div class="text-primary small fw-bold">{{ $label }}</div>
                                                @endforeach
                                            @endif
                                        </div>
                                    @elseif($order->type === 'independent')
                                        @php
                                            $employers = $order->items->map(function($item) {
                                                return $item->employee && $item->employee->employer ? ($item->employee->employer->name_th ?? $item->employee->employer->name_en) : null;
                                            })->filter()->unique()->values();
                                            $displayEmployers = $employers->take(2);
                                            $remaining = $employers->count() - 2;
                                        @endphp
                                        @foreach($displayEmployers as $empName)
                                            <div class="small fw-bold">{{ $empName }}</div>
                                        @endforeach
                                        @if($remaining > 0)
                                            <div class="small text-muted fst-italic">+ {{ $remaining }} other(s)</div>
                                        @endif
                                        @if($employers->isEmpty())
                                            <div class="text-muted fst-italic">Mixed / Independent</div>
                                        @endif
                                    @else
                                        <span class="text-danger">{{ __('Unknown') }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark rounded-pill">{{ $order->items_count }}</span>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-2">
                                        {{-- Documents Ready Toggle --}}
                                        <div class="form-check form-switch">
                                            <input class="form-check-input status-toggle" type="checkbox" id="docReady{{ $order->id }}"
                                                data-id="{{ $order->id }}" data-type="document_ready"
                                                {{ $order->document_ready_at ? 'checked' : '' }} disabled>
                                            <label class="form-check-label small" for="docReady{{ $order->id }}">{{ __('Documents Ready') }}</label>
                                        </div>

                                        {{-- Ready to Process (Financial) Toggle --}}
                                        @can('view-finance')
                                        <div class="form-check form-switch">
                                            <input class="form-check-input status-toggle" type="checkbox" id="finReady{{ $order->id }}"
                                                data-id="{{ $order->id }}" data-type="financial_approved"
                                                {{ $order->financial_approved_at ? 'checked' : '' }} disabled>
                                            <label class="form-check-label small" for="finReady{{ $order->id }}">{{ __('Ready to Process') }}</label>
                                        </div>
                                        @endcan

                                        {{-- Waiting for Docs Button/Toggle --}}
                                        <button class="btn btn-sm btn-link text-decoration-none p-0 text-start text-danger"
                                            onclick="openMissingDocsModal({{ $order->id }}, '{{ addslashes($order->missing_documents) }}')">
                                            <i class="bi bi-pencil-square"></i> <span class="small">{{ __('Missing Documents...') }}</span>
                                        </button>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex flex-column align-items-end gap-2">
                                        <a href="{{ route('production.edit', $order->id) }}" class="btn btn-sm btn-outline-warning w-100">
                                            <i class="bi bi-gear-fill me-1"></i>{{ __('Prepare') }}
                                        </a>

                                        {{-- Send to Workflow Button (Conditional) --}}
                                        <form action="{{ route('production.update', $order->id) }}" method="POST"
                                              id="workflow-form-{{ $order->id }}"
                                              class="w-100 workflow-btn-container"
                                              style="{{ ($order->document_ready_at && $order->financial_approved_at) ? '' : 'display:none;' }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="start_workflow" value="1">
                                            <button type="submit" class="btn btn-sm btn-success w-100">
                                                <i class="bi bi-send-fill me-1"></i>{{ __('Send to Workflow') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-clipboard-x fs-1 d-block mb-2"></i>
                                    {{ __('No projects in preparation.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-3">
        {{ $orders->links() }}
    </div>
</div>

{{-- Missing Documents Modal --}}
<div class="modal fade" id="missingDocsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="missingDocsForm" method="POST" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Missing Documents') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="waitingForDocsToggle" name="waiting_for_documents" value="1">
                    <label class="form-check-label" for="waitingForDocsToggle">{{ __('Status') }}: {{ __('Waiting for Docs') }}</label>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('List Missing Documents') }}</label>
                    <textarea class="form-control" name="missing_documents" id="missingDocsText" rows="4" placeholder="e.g. Copy of Passport, Photo..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Toggle Status Handler
        document.querySelectorAll('.status-toggle').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const id = this.dataset.id;
                const type = this.dataset.type;
                const status = this.checked ? 1 : 0;

                // Disable while processing
                this.disabled = true;

                fetch(`/production/${id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ type, status })
                })
                .then(res => res.json())
                .then(data => {
                    this.disabled = false;
                    if (data.success) {
                        checkWorkflowReadiness(id);
                        showToast('Status updated', 'success');
                    } else {
                        this.checked = !this.checked; // Revert
                        showToast(data.message || 'Error updating status', 'danger');
                    }
                })
                .catch(err => {
                    this.disabled = false;
                    this.checked = !this.checked;
                    showToast('Network error', 'danger');
                });
            });
        });
    });

    function checkWorkflowReadiness(id) {
        const docReady = document.getElementById(`docReady${id}`).checked;
        const finReady = document.getElementById(`finReady${id}`).checked;
        const btnContainer = document.getElementById(`workflow-form-${id}`);

        if (docReady && finReady) {
            btnContainer.style.display = 'block';
        } else {
            btnContainer.style.display = 'none';
        }
    }

    function openMissingDocsModal(id, currentText) {
        const form = document.getElementById('missingDocsForm');
        form.action = `/production/${id}`; // POST to update method

        document.getElementById('missingDocsText').value = currentText;

        // Determine toggle state based on display existence (or passed param if we had it)
        // Since we didn't pass "waiting" bool, we can infer or fetch.
        // For simplicity, if text exists, assume waiting. Or check the badge visibility in DOM?
        const displayEl = document.getElementById(`missing-docs-display-${id}`);
        const isWaiting = displayEl.style.display !== 'none';

        document.getElementById('waitingForDocsToggle').checked = isWaiting;

        new bootstrap.Modal(document.getElementById('missingDocsModal')).show();
    }
</script>
@endpush
@endsection

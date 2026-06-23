@extends('layouts.app')

@section('content')
<x-help-button manual="sales" title="{{ __('Read and Sale') }}" />
<style>
    /* CSS Grid: บังคับ 3 คอลัมน์เท่ากันเสมอ ไม่มีทางตกลงด้านล่าง */
    .kanban-board {
        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        gap: 1rem !important;
        align-items: start !important;
        width: 100% !important;
    }
    .kanban-board > .kanban-col {
        min-width: 0 !important;
        min-height: 0 !important;
    }
    .kanban-column { min-height: 250px; }
    .lead-card { cursor: grab; transition: transform 0.15s, box-shadow 0.15s; }
    .lead-card:active { cursor: grabbing; transform: scale(1.02); box-shadow: 0 4px 12px rgba(0,0,0,0.12) !important; }
    .gu-mirror { position: fixed !important; margin: 0 !important; z-index: 9999 !important; opacity: 0.8; }
    .gu-hide { display: none !important; }
    .gu-unselectable { user-select: none !important; }
    .gu-transit { opacity: 0.2; }
</style>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-megaphone-fill me-2 text-primary"></i>{{ __('Read and Sale') }}</h2>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSalesLeadModal">
                <i class="bi bi-plus-circle me-1"></i> เพิ่มรายการเสนอราคาใหม่
            </button>
            <button class="btn btn-secondary ms-2" data-bs-toggle="modal" data-bs-target="#historyModal">
                <i class="bi bi-clock-history me-1"></i> ประวัติรายการ (Snapshots)
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Kanban Board: ใช้ class kanban-board เพื่อบังคับ 3 คอลัมน์ --}}
    <div class="kanban-board">

        {{-- Column 1: เสนอราคา --}}
        <div class="kanban-col">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary bg-opacity-10 border-0 py-3">
                    <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-file-earmark-text me-2"></i>1. เสนอราคา <span class="badge bg-primary rounded-pill ms-2">{{ $quoted->count() }}</span></h6>
                </div>
                <div class="card-body kanban-column p-2" id="col-quoted" data-status="quoted" style="overflow-y: auto; max-height: 72vh; background: #f8f9fa;">
                    @foreach($quoted as $lead)
                        @include('sales.partials._lead_card', ['lead' => $lead, 'nextStatus' => 'deciding'])
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Column 2: รอลูกค้าคอนเฟิร์ม --}}
        <div class="kanban-col">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-warning bg-opacity-10 border-0 py-3">
                    <h6 class="mb-0 fw-bold text-warning"><i class="bi bi-hourglass-split me-2"></i>2. รอลูกค้าคอนเฟิร์ม <span class="badge bg-warning text-dark rounded-pill ms-2">{{ $deciding->count() }}</span></h6>
                </div>
                <div class="card-body kanban-column p-2" id="col-deciding" data-status="deciding" style="overflow-y: auto; max-height: 72vh; background: #f8f9fa;">
                    @foreach($deciding as $lead)
                        @include('sales.partials._lead_card', ['lead' => $lead, 'nextStatus' => 'confirmed'])
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Column 3: คอนเฟิร์มแล้ว รอส่งดำเนินงาน --}}
        <div class="kanban-col">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success bg-opacity-10 border-0 py-3">
                    <h6 class="mb-0 fw-bold text-success"><i class="bi bi-check-circle me-2"></i>3. คอนเฟิร์มแล้ว <small class="fw-normal">(รอส่งดำเนินงาน)</small> <span class="badge bg-success rounded-pill ms-2">{{ $confirmed->count() }}</span></h6>
                </div>
                <div class="card-body kanban-column p-2" id="col-confirmed" data-status="confirmed" style="overflow-y: auto; max-height: 72vh; background: #f8f9fa;">
                    @foreach($confirmed as $lead)
                        @include('sales.partials._lead_card', ['lead' => $lead, 'nextStatus' => 'transition'])
                    @endforeach
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Modals container: ใช้ div แยกเพื่อ teleport ออกไปนอก main ตอน DOM ready --}}
<div id="sales-modals-container">
    @include('sales.partials._create_modal')
    @include('sales.partials._history_modal')

    @foreach($quoted->merge($deciding)->merge($confirmed) as $lead)
        @if(!$lead->employer_id)
            @include('sales.partials._edit_employer_modal', ['lead' => $lead])
        @endif
        @include('sales.partials._manage_employees_modal', ['lead' => $lead])
        @include('sales.partials._quotation_modal', ['lead' => $lead])
        @if($lead->status === 'confirmed')
            @include('sales.partials._transition_modal', ['lead' => $lead])
        @endif
    @endforeach
</div>

{{-- Forms and scripts for Drag and Drop / Status Updates --}}
<form id="updateStatusForm" method="POST" style="display: none;">
    @csrf
    @method('PUT')
    <input type="hidden" name="status" id="updateStatusInput">
</form>

@endsection

{{-- styles ถูกย้ายไป inline <style> ภายใน @section('content') เพราะ layout ไม่มี @stack('styles') --}}

@push('scripts')
{{-- Include Dragula for drag and drop --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dragula/3.7.3/dragula.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/dragula/3.7.3/dragula.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Fix: Teleport all sales modals to body so they escape #main-content's overflow/z-index
        var modalsContainer = document.getElementById('sales-modals-container');
        if (modalsContainer) {
            var modals = modalsContainer.querySelectorAll('.modal');
            modals.forEach(function(modal) {
                document.body.appendChild(modal);
            });
        }

        // Re-open modal after page reload (for continuous employee adding)
        var reopenId = sessionStorage.getItem('reopenModal');
        if (reopenId) {
            sessionStorage.removeItem('reopenModal');
            setTimeout(function() {
                var modalEl = document.getElementById(reopenId);
                if (modalEl && typeof bootstrap !== 'undefined') {
                    var modal = new bootstrap.Modal(modalEl);
                    modal.show();
                }
            }, 300);
        }

        // Init Dragula
        const drake = dragula([
            document.getElementById('col-quoted'),
            document.getElementById('col-deciding'),
            document.getElementById('col-confirmed')
        ]);

        drake.on('drop', function (el, target, source, sibling) {
            if (target === source) return; // Didn't move between columns

            const leadId = el.getAttribute('data-id');
            const newStatus = target.getAttribute('data-status');

            // Update via AJAX
            updateLeadStatus(leadId, newStatus, el);
        });

        function updateLeadStatus(id, status, el) {
            const form = document.getElementById('updateStatusForm');
            const url = `{{ url('sales') }}/${id}/status`;

            fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: status })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Update badges manually or reload. We'll just let it be, or you can update DOM.
                    // A quick reload makes sure all action buttons inside the card refresh properly.
                    window.location.reload();
                } else {
                    alert('Error updating status');
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                window.location.reload();
            });
        }
    });

    // Helper for explicit status change buttons
    function changeStatusExplicit(id, status) {
        const form = document.getElementById('updateStatusForm');
        form.action = `{{ url('sales') }}/${id}/status`;
        document.getElementById('updateStatusInput').value = status;
        form.submit();
    }
</script>
@endpush

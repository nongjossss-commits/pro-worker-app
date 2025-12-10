@extends('layouts.app')

@section('title', 'Registration Resolution')

@section('content')
<div class="container-fluid" x-data="registrationHandler()">
    {{-- Header & Stats --}}
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>{{ __('Registration Resolution') }}</h2>
            <div>
                <button class="btn btn-outline-secondary me-2" @click="openSettingsModal()">
                    <i class="bi bi-gear-fill me-1"></i> {{ __('Settings') }}
                </button>
                <a href="{{ route('production.registration.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> {{ __('Add New Employee') }}
                </a>
                <a href="{{ route('employees.import_view') }}?target_status=registration_pending" class="btn btn-success ms-2">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> {{ __('Import from Excel') }}
                </a>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white h-100 shadow-sm">
                <div class="card-body text-center">
                    <h1 class="display-4 fw-bold">{{ $totalEmployees }}</h1>
                    <p class="card-text fs-5">{{ __('Total Employees') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-dark text-white h-100 shadow-sm">
                <div class="card-body text-center">
                    <h1 class="display-4 fw-bold">{{ $totalEmployers }}</h1>
                    <p class="card-text fs-5">{{ __('Total Employers') }}</p>
                </div>
            </div>
        </div>

        {{-- Workflow Steps Summary --}}
        <div class="col-md-6 mb-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white fw-bold">{{ __('Workflow Progress') }}</div>
                <div class="card-body overflow-auto">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($steps as $step)
                            <div class="badge bg-light text-dark border p-2 d-flex align-items-center gap-2">
                                <span class="fw-bold">{{ $step->name }}</span>
                                <span class="badge bg-success rounded-pill">{{ $stepStats[$step->id] ?? 0 }}</span>
                            </div>
                        @endforeach
                        @if($steps->isEmpty())
                            <span class="text-muted small">No steps defined. Click Settings to add.</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content: Employer List --}}
    <div class="accordion" id="employerAccordion">
        @forelse($employers as $employer)
            <div class="accordion-item mb-3 border rounded shadow-sm overflow-hidden">
                <h2 class="accordion-header" id="heading{{ $employer->id }}">
                    <button class="accordion-button collapsed bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $employer->id }}" aria-expanded="false" aria-controls="collapse{{ $employer->id }}">
                        <div class="d-flex w-100 justify-content-between align-items-center me-3">
                            <span class="fw-bold fs-5 text-primary">{{ $employer->employerNameTh }} ({{ $employer->employerNameEn }})</span>
                            <span class="badge bg-secondary rounded-pill">{{ $employer->employees->count() }} Employees</span>
                        </div>
                    </button>
                </h2>
                <div id="collapse{{ $employer->id }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $employer->id }}" data-bs-parent="#employerAccordion">
                    <div class="accordion-body bg-light p-3">
                        <div class="row g-3">
                            @foreach($employer->employees as $employee)
                                <div class="col-12">
                                    @include('production.registration._employee_card', ['employee' => $employee, 'steps' => $steps])
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                <p>No registration data found.</p>
            </div>
        @endforelse
    </div>

    {{-- Settings Modal --}}
    <div class="modal fade" id="settingsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Manage Workflow Steps') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" x-model="newStepName" placeholder="Enter step name (e.g., Medical Checkup)">
                        <button class="btn btn-primary" @click="addStep()">{{ __('Add Step') }}</button>
                    </div>

                    <ul class="list-group">
                        <template x-for="step in steps" :key="step.id">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2 w-100">
                                    <span class="badge bg-secondary" x-text="step.order"></span>
                                    <input type="text" class="form-control form-control-sm border-0" x-model="step.name" @change="updateStep(step)">
                                </div>
                                <button class="btn btn-sm btn-outline-danger ms-2" @click="deleteStep(step.id)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Alpine.js Logic --}}
<script>
    function registrationHandler() {
        return {
            steps: @json($steps),
            newStepName: '',

            init() {
                // Initial setup if needed
            },

            openSettingsModal() {
                new bootstrap.Modal(document.getElementById('settingsModal')).show();
            },

            addStep() {
                if (!this.newStepName) return;

                fetch('{{ route('production.registration.steps.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ name: this.newStepName })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
            },

            updateStep(step) {
                fetch(`/production/registration/steps/${step.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ name: step.name })
                });
            },

            deleteStep(id) {
                if (!confirm('Delete this step?')) return;
                fetch(`/production/registration/steps/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.steps = this.steps.filter(s => s.id !== id);
                        location.reload();
                    }
                });
            }
        }
    }
</script>
@endsection

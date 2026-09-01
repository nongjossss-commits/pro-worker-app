@props([
    'steps',
    'title',
    'storeUrl',
    'updateUrlBase',
    'destroyUrlBase',
    'reorderUrl',
    'supportsStepOnePrompt' => false,
    'headerClass' => 'bg-primary text-white',
    'closeButtonWhite' => true,
    'namePlaceholder' => null,
    'extraPayload' => [], // e.g. ['work_type_id' => $activeTab->id] — merged into the "Add" request body for menus (Pre-Production/Workflow) whose store endpoint needs more than just `name`.
])

{{-- Manage Steps Modal — shared by Pre-Production, Workflow, Registration
     Resolution and Renewal Resolution so all four work exactly the same way
     (add/edit/reorder/delete, no page reload). The URLs are per-menu (each
     points at that menu's own store/update/destroy/reorder endpoints) but
     the markup, ids and JS (see work-type-tab-scripts.blade.php-style
     manage-steps-scripts.blade.php) are identical everywhere. --}}
<div class="modal fade" id="manageStepsModal" tabindex="-1"
     data-store-url="{{ $storeUrl }}"
     data-update-url-base="{{ $updateUrlBase }}"
     data-destroy-url-base="{{ $destroyUrlBase }}"
     data-reorder-url="{{ $reorderUrl }}"
     data-supports-step-one-prompt="{{ $supportsStepOnePrompt ? '1' : '0' }}"
     data-extra-payload="{{ json_encode((object) $extraPayload) }}">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header {{ $headerClass }}">
                <h5 class="modal-title fw-bold"><i class="bi bi-diagram-3-fill me-2"></i>{{ $title }}</h5>
                <button type="button" class="btn-close {{ $closeButtonWhite ? 'btn-close-white' : '' }}" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                {{-- Add New Step --}}
                <form id="addStepForm" class="mb-4 p-3 bg-light rounded border">
                    <label class="form-label fw-bold">{{ __('Add New Step') }}</label>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="text" class="form-control" id="newStepName" placeholder="{{ $namePlaceholder ?? __('Step Name') }}" required>
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
                                    <button class="btn btn-sm btn-outline-secondary" onclick="moveStep({{ $step->id }}, 'up')" title="{{ __('Move Up') }}"><i class="bi bi-arrow-up"></i></button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="moveStep({{ $step->id }}, 'down')" title="{{ __('Move Down') }}"><i class="bi bi-arrow-down"></i></button>
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

@include('components.manage-steps-scripts')

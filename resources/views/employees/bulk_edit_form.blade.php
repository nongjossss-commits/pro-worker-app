@extends('layouts.app')

@section('title', 'Advanced Bulk Edit')

@section('content')

<div class="container-fluid p-4" id="bulk-edit-wrapper">
    {{-- CSS Fix for Tailwind + Bootstrap Collapse Conflict --}}
    <style>
        /* Fix for Tailwind's .collapse { visibility: collapse } conflicting with Bootstrap */
        .accordion-collapse.collapse {
            visibility: visible !important;
        }
        .accordion-collapse.collapsing {
            visibility: visible !important;
        }

        /* Enhanced UI Styles */
        .accordion-button:not(.collapsed) {
            background-color: var(--bs-primary-light);
            color: white;
            box-shadow: inset 0 -1px 0 rgba(0,0,0,.125);
        }
        .accordion-button:not(.collapsed)::after {
            filter: brightness(0) invert(1);
        }
        .master-control-card {
            border: 2px solid var(--bs-primary);
            box-shadow: 0 4px 6px -1px rgba(249, 115, 22, 0.2);
        }
        .employee-checkbox {
            width: 1.2em;
            height: 1.2em;
            cursor: pointer;
        }
    </style>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-ui-checks-grid me-2 text-primary"></i>{{ __('Advanced Bulk Edit') }}
            <span class="badge bg-secondary fs-6 ms-2">{{ count($employees) }} {{ __('Employees') }}</span>
        </h2>
        <div>
            <button type="button" class="btn btn-outline-secondary me-2" id="btn-expand-all">
                <i class="bi bi-arrows-expand"></i> {{ __('Expand All') }}
            </button>
            <button type="button" class="btn btn-outline-secondary" id="btn-collapse-all">
                <i class="bi bi-arrows-collapse"></i> {{ __('Collapse All') }}
            </button>
        </div>
    </div>

    <form id="bulkEditForm" action="{{ route('employees.bulk_update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Hidden inputs for Employee IDs --}}
        @foreach($employees as $employee)
            <input type="hidden" name="employee_ids[]" value="{{ $employee->id }}">
        @endforeach

        {{-- Hidden inputs for Selected Fields --}}
        @foreach($selectedFields as $field)
            <input type="hidden" name="selected_fields[]" value="{{ $field }}">
        @endforeach

        @if(isset($redirectTo))
            <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
        @endif

        {{-- Master Control Section --}}
        <div class="card mb-4 master-control-card">
            <div class="card-header bg-primary text-white fw-bold d-flex align-items-center">
                <i class="bi bi-sliders me-2 fs-5"></i>
                <span>{{ __('Master Controls (Apply to All)') }}</span>
            </div>
            <div class="card-body bg-light">
                <div class="alert alert-info border-0 shadow-sm mb-3 d-flex align-items-center">
                    <i class="bi bi-info-circle-fill me-2 fs-4"></i>
                    <span>{{ __('Use the inputs below to set a value for ALL employees at once. Click "Apply to All" to fill the individual forms below.') }}</span>
                </div>
                <div class="row g-3">
                    @foreach($selectedFields as $field)
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-white shadow-sm h-100 d-flex flex-column">
                                <label class="form-label fw-bold text-primary mb-2">{{ __($fieldLabels[$field] ?? $field) }}</label>

                                <div class="mb-2 flex-grow-1">
                                    @if(in_array($field, $fileFields))
                                        {{-- File Upload for Master --}}
                                        <div class="input-group">
                                            <input type="file" class="form-control master-input" data-field="{{ $field }}" id="master_input_{{ $field }}">
                                            <button type="button" class="btn btn-outline-secondary" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: 'master_input_{{ $field }}' } }))">
                                                <i class="bi bi-camera"></i>
                                            </button>
                                        </div>

                                        @php
                                            // Check if this is an "Other Document" (doc 9-18) and needs a description field
                                            $isOtherDoc = preg_match('/^employee_doc_([9]|1[0-8])$/', $field, $matches);
                                            $descField = null;
                                            if ($isOtherDoc) {
                                                $docIndex = (int)$matches[1];
                                                $descIndex = $docIndex - 8;
                                                $descField = "other_doc_{$descIndex}_desc";
                                            }
                                        @endphp

                                        @if($descField)
                                            <div class="mt-2">
                                                <input type="text"
                                                       class="form-control master-desc-input"
                                                       data-parent-field="{{ $field }}"
                                                       placeholder="{{ __('Document Description') }}">
                                            </div>
                                        @endif

                                        <div class="form-text text-muted small mt-1">
                                            <i class="bi bi-exclamation-circle"></i> {{ __('Uploads cannot be auto-applied due to browser security. Please upload individually.') }}
                                        </div>
                                    @elseif(in_array($field, $dateFields))
                                        {{-- Date Input --}}
                                        <input type="date" class="form-control master-input" data-field="{{ $field }}">
                                    @elseif(isset($options[$field]))
                                        {{-- Select Dropdown --}}
                                        <select class="form-select master-input" data-field="{{ $field }}">
                                            <option value="">-- {{ __('Select to Apply All') }} --</option>
                                            @foreach($options[$field] as $val => $label)
                                                <option value="{{ $val }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        {{-- Text Input --}}
                                        <input type="text" class="form-control master-input" data-field="{{ $field }}" placeholder="{{ __('Type to update all') }}">
                                    @endif
                                </div>

                                <button type="button" class="btn btn-sm btn-outline-primary w-100 apply-master-btn mt-auto" data-field="{{ $field }}" {{ (in_array($field, $fileFields) && !$isOtherDoc) ? 'disabled' : '' }}>
                                    <i class="bi bi-arrow-down-circle me-1"></i> {{ __('Apply to All') }}
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Individual Employee List --}}
        <div class="accordion shadow-sm" id="employeeAccordion">
            @foreach($employees as $index => $employee)
                <div class="accordion-item" id="employee-row-{{ $employee->id }}" data-employee-id="{{ $employee->id }}">
                    <h2 class="accordion-header" id="heading{{ $employee->id }}">
                        <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $employee->id }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="collapse{{ $employee->id }}">
                            <div class="d-flex align-items-center w-100">
                                <span class="badge bg-secondary me-3 rounded-pill">{{ $index + 1 }}</span>

                                {{-- Name Thai --}}
                                <div class="me-3">
                                    <span class="fw-bold d-block">{{ $employee->employeeTitleTh ?? '' }} {{ $employee->employeeNameTh ?? 'N/A' }}</span>
                                </div>

                                {{-- Name English --}}
                                <div class="me-3 d-none d-md-block">
                                    <span class="text-muted small d-block">{{ $employee->employeeTitleEn ?? '' }} {{ $employee->employeeNameEn ?? 'N/A' }}</span>
                                </div>

                                {{-- Nationality & Flag --}}
                                <div class="me-auto d-flex align-items-center">
                                    @php
                                        $countryCode = \App\Helpers\CountryHelper::getCountryCode($employee->employeeNationality);
                                    @endphp
                                    @if($countryCode)
                                        <img src="{{ asset('images/flags/' . strtolower($countryCode) . '.png') }}" alt="{{ $countryCode }}" class="me-2" style="width: 24px; height: 16px;">
                                    @endif
                                    <span class="badge bg-info text-dark">{{ $employee->employeeNationality ?? 'N/A' }}</span>
                                </div>

                                @if($employee->employer)
                                    <span class="badge bg-light text-dark border me-3 d-none d-md-inline-block">
                                        <i class="bi bi-building"></i> {{ $employee->employer->employerNameTh }}
                                    </span>
                                @endif
                            </div>
                        </button>
                    </h2>
                    <div id="collapse{{ $employee->id }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" aria-labelledby="heading{{ $employee->id }}">
                        <div class="accordion-body bg-white">
                            <div class="row g-3">
                                @foreach($selectedFields as $field)
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small text-uppercase fw-bold">{{ __($fieldLabels[$field] ?? $field) }}</label>

                                        @if(in_array($field, $fileFields))
                                            @php
                                                // Define mapping for legacy file fields to actual database columns
                                                $fieldMapping = [
                                                    'employeePhoto'      => 'employeePhoto',
                                                    'passport_file'      => 'employee_doc_1',
                                                    'visa_file'          => 'employee_doc_2',
                                                    'work_permit_file'   => 'employee_doc_3',
                                                    'pink_card_file'     => 'employee_doc_4',
                                                    'insurance_attachment' => 'insurance_document_path_private',
                                                ];
                                                $dbColumn = $fieldMapping[$field] ?? $field;
                                                $filePath = $employee->$dbColumn;
                                            @endphp

                                            @if($field === 'employeePhoto')
                                                {{-- Special Cropper UI for Employee Photo --}}
                                                <div class="d-flex flex-column align-items-center border rounded p-2">
                                                    <img id="preview-img-{{ $employee->id }}" src="{{ $filePath ? asset('storage/' . $filePath) : 'https://placehold.co/100x120/f8fafc/6c757d?text=Photo' }}" class="img-thumbnail mb-2" style="width: 100px; height: 120px; object-fit: cover;">
                                                    <div class="d-grid gap-2 w-100">
                                                        <button type="button" class="btn btn-sm btn-outline-primary btn-crop-trigger" data-employee-id="{{ $employee->id }}" data-action="file">
                                                            <i class="bi bi-file-earmark-image me-1"></i> {{ __('Select File') }}
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-crop-trigger" data-employee-id="{{ $employee->id }}" data-action="camera">
                                                            <i class="bi bi-camera-fill me-1"></i> {{ __('Camera') }}
                                                        </button>
                                                    </div>
                                                    {{-- The actual input that will be submitted --}}
                                                    <input type="file" class="d-none individual-input" name="data[{{ $employee->id }}][{{ $field }}]" id="photo-input-{{ $employee->id }}">
                                                </div>
                                            @else
                                                {{-- Standard File Upload --}}
                                                <div class="input-group">
                                                    <input type="file" class="form-control individual-input" name="data[{{ $employee->id }}][{{ $field }}]" data-field="{{ $field }}" id="individual_input_{{ $employee->id }}_{{ $field }}">
                                                    <button type="button" class="btn btn-outline-secondary" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: 'individual_input_{{ $employee->id }}_{{ $field }}' } }))">
                                                        <i class="bi bi-camera"></i>
                                                    </button>
                                                </div>
                                            @endif

                                            @php
                                                // Check if this is an "Other Document" (doc 9-18) and needs a description field
                                                $isOtherDoc = preg_match('/^employee_doc_([9]|1[0-8])$/', $field, $matches);
                                                $descField = null;
                                                if ($isOtherDoc) {
                                                    $docIndex = (int)$matches[1];
                                                    $descIndex = $docIndex - 8;
                                                    $descField = "other_doc_{$descIndex}_desc";
                                                }
                                            @endphp

                                            @if($descField)
                                                <div class="mt-2">
                                                    <input type="text"
                                                           class="form-control individual-desc-input"
                                                           name="data[{{ $employee->id }}][{{ $descField }}]"
                                                           value="{{ $employee->$descField }}"
                                                           data-parent-field="{{ $field }}"
                                                           placeholder="{{ __('Document Description') }}">
                                                </div>
                                            @endif

                                            @if($filePath)
                                                <div class="mt-1 text-success small">
                                                    <i class="bi bi-check-circle-fill"></i> {{ __('File exists') }}
                                                    @php
                                                        $isSensitive = in_array($field, ['passport_file', 'visa_file', 'work_permit_file', 'pink_card_file', 'insurance_attachment']);
                                                    @endphp

                                                    @if($isSensitive)
                                                         <a href="{{ route('employees.documents.serve', ['employee' => $employee->id, 'field' => $dbColumn]) }}" target="_blank" class="text-decoration-none ms-1">({{ __('View') }})</a>
                                                    @elseif($field === 'employeePhoto')
                                                        <a href="{{ Storage::disk('public')->url($filePath) }}" target="_blank" class="text-decoration-none ms-1">({{ __('View') }})</a>
                                                    @else
                                                        <a href="{{ Storage::disk('public')->url($filePath) }}" target="_blank" class="text-decoration-none ms-1">({{ __('View') }})</a>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="mt-1 text-muted small">
                                                    <i class="bi bi-dash-circle"></i> {{ __('No file') }}
                                                </div>
                                            @endif
                                        @elseif(in_array($field, $dateFields))
                                            {{-- Date Input --}}
                                            <input type="date" class="form-control individual-input" name="data[{{ $employee->id }}][{{ $field }}]" value="{{ $employee->$field ? \Carbon\Carbon::parse($employee->$field)->format('Y-m-d') : '' }}" data-field="{{ $field }}">
                                        @elseif(isset($options[$field]))
                                            {{-- Select Dropdown --}}
                                            <select class="form-select individual-input" name="data[{{ $employee->id }}][{{ $field }}]" data-field="{{ $field }}">
                                                <option value="">-- {{ __('Select') }} --</option>
                                                @foreach($options[$field] as $val => $label)
                                                    <option value="{{ $val }}" {{ $employee->$field == $val ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            {{-- Text Input --}}
                                            <input type="text" class="form-control individual-input" name="data[{{ $employee->id }}][{{ $field }}]" value="{{ $employee->$field }}" data-field="{{ $field }}">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="fixed-bottom bg-white border-top p-3 shadow-lg" style="z-index: 1030;">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    <i class="bi bi-info-circle"></i> {{ __('Changes are not saved until you click "Save All Changes".') }}
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ $redirectTo ?? route('employees.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-success btn-lg shadow-sm" id="btn-save-all">
                        <i class="bi bi-save-fill me-2"></i> {{ __('Save All Changes') }}
                    </button>
                </div>
            </div>
        </div>
        {{-- Spacer for fixed bottom bar --}}
        <div style="height: 100px;"></div>
    </form>

    <script>
        // Define initialization function globally so it can be called after AJAX injection
        window.initBulkEditForm = function() {
            const container = document.getElementById('bulk-edit-wrapper');
            if (!container) return;

            // Prevent double initialization if listeners are already attached
            if (container.dataset.initialized === 'true') return;
            container.dataset.initialized = 'true';

            console.log('Bulk Edit Script Initialized');

            // --- 1. Master Field Sync (Event Delegation on Container) ---
            container.addEventListener('click', function(e) {
                if (e.target.matches('.apply-master-btn') || e.target.closest('.apply-master-btn')) {
                    const btn = e.target.matches('.apply-master-btn') ? e.target : e.target.closest('.apply-master-btn');
                    const field = btn.dataset.field;
                    // Ensure we search within THIS container
                    const formContainer = container.querySelector('#bulkEditForm') || container.querySelector('form');
                    if (!formContainer) {
                        console.error('Bulk Edit Form not found');
                        return;
                    }

                    const masterInput = formContainer.querySelector(`.master-input[data-field="${field}"]`);
                    const individualInputs = formContainer.querySelectorAll(`.individual-input[data-field="${field}"]`);

                    if (!masterInput) return;

                    // Sync main input value (Skip for file inputs)
                    if (masterInput.type !== 'file') {
                        const value = masterInput.value;
                        individualInputs.forEach(input => {
                            input.value = value;
                            input.dispatchEvent(new Event('change'));
                            // Visual highlight
                            input.classList.add('bg-success-subtle');
                            setTimeout(() => input.classList.remove('bg-success-subtle'), 1000);
                        });
                    }

                    // Handle description field sync
                    const masterDescInput = formContainer.querySelector(`.master-desc-input[data-parent-field="${field}"]`);
                    const individualDescInputs = formContainer.querySelectorAll(`.individual-desc-input[data-parent-field="${field}"]`);

                    if (masterDescInput && individualDescInputs.length > 0) {
                        const descValue = masterDescInput.value;
                        individualDescInputs.forEach(input => {
                            input.value = descValue;
                            input.dispatchEvent(new Event('change'));
                            input.classList.add('bg-success-subtle');
                            setTimeout(() => input.classList.remove('bg-success-subtle'), 1000);
                        });
                    }

                    // Button Visual feedback
                    const originalHtml = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check-lg"></i> {{ __("Applied!") }}';
                    btn.classList.replace('btn-outline-primary', 'btn-success');

                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                        btn.classList.replace('btn-success', 'btn-outline-primary');
                    }, 1500);
                }
            });

            // --- 2. Expand/Collapse All (Event Delegation on Container) ---
            container.addEventListener('click', function(e) {
                if (e.target.matches('#btn-expand-all') || e.target.closest('#btn-expand-all')) {
                    container.querySelectorAll('.accordion-collapse').forEach(el => {
                        const bsCollapse = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
                        bsCollapse.show();
                    });
                }
                if (e.target.matches('#btn-collapse-all') || e.target.closest('#btn-collapse-all')) {
                    container.querySelectorAll('.accordion-collapse').forEach(el => {
                        const bsCollapse = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
                        bsCollapse.hide();
                    });
                }
            });

            // --- 3. Cropper Logic (Event Delegation) ---
            let currentEmployeeId = null;
            let currentOriginalFile = null;
            let cropper = null;

            const cropperModalEl = container.querySelector('#cropperModal'); // Scope to container
            let cropperModal = bootstrap.Modal.getInstance(cropperModalEl);
            if (!cropperModal) {
                cropperModal = new bootstrap.Modal(cropperModalEl);
            }
            const imageToCrop = container.querySelector('#imageToCrop');
            const cropImageBtn = container.querySelector('#cropImageBtn');
            const globalTriggerFile = container.querySelector('#globalTriggerFile');
            const globalTriggerCamera = container.querySelector('#globalTriggerCamera');

            // Trigger click listener (DELEGATED)
            container.addEventListener('click', function(e) {
                if (e.target.matches('.btn-crop-trigger') || e.target.closest('.btn-crop-trigger')) {
                    const btn = e.target.matches('.btn-crop-trigger') ? e.target : e.target.closest('.btn-crop-trigger');
                    currentEmployeeId = btn.dataset.employeeId;
                    const action = btn.dataset.action;

                    // Reset global inputs
                    globalTriggerFile.value = '';
                    globalTriggerCamera.value = '';

                    if (action === 'file') {
                        globalTriggerFile.click();
                    } else {
                        globalTriggerCamera.click();
                    }
                }
            });

            function handleFileSelect(event) {
                if (event.target.files && event.target.files.length > 0) {
                    currentOriginalFile = event.target.files[0];
                } else {
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    imageToCrop.src = e.target.result;
                    cropperModal.show();
                };
                reader.readAsDataURL(currentOriginalFile);
            }

            if (globalTriggerFile) globalTriggerFile.addEventListener('change', handleFileSelect);
            if (globalTriggerCamera) globalTriggerCamera.addEventListener('change', handleFileSelect);

            // --- Background Removal Logic ---
            const bgToolbar = container.querySelector('#bgToolbar');
            const loadingOverlay = container.querySelector('#cropperLoadingOverlay');
            const loadingText = container.querySelector('#cropperLoadingText');

            if (bgToolbar) {
                bgToolbar.addEventListener('click', async function(e) {
                    const btn = e.target.closest('button[data-bg-action]');
                    if (!btn) return;

                    const action = btn.dataset.bgAction;
                    // Use closure variable `currentOriginalFile`
                    if (!currentOriginalFile) {
                        alert('No image selected');
                        return;
                    }

                    try {
                        if (loadingOverlay) loadingOverlay.classList.remove('d-none');
                        if (loadingText) loadingText.textContent = 'Processing...';

                        const processedBlob = await window.backgroundRemoval.process(currentOriginalFile, action, (active, text) => {
                            if (loadingText && text) loadingText.textContent = text;
                        });

                        const newUrl = URL.createObjectURL(processedBlob);
                        imageToCrop.src = newUrl;

                        if (cropper) cropper.destroy();
                        setTimeout(initCropper, 100);

                    } catch (err) {
                        console.error(err);
                        alert('Failed to process image: ' + err.message);
                    } finally {
                        if (loadingOverlay) loadingOverlay.classList.add('d-none');
                    }
                });
            }

            cropperModalEl.addEventListener('shown.bs.modal', function () {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                if (imageToCrop.complete) {
                     setTimeout(initCropper, 200);
                } else {
                    imageToCrop.onload = function() {
                        setTimeout(initCropper, 200);
                    };
                }
            });

            function initCropper() {
                if (typeof Cropper === 'undefined') {
                    alert('Cropper.js not loaded.');
                    return;
                }
                cropper = new Cropper(imageToCrop, {
                    aspectRatio: 150 / 180,
                    viewMode: 1,
                    dragMode: 'move',
                    background: false,
                    autoCropArea: 0.8,
                    movable: true,
                    zoomable: true,
                    rotatable: true,
                    scalable: true,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                });
            }

            cropperModalEl.addEventListener('hidden.bs.modal', function () {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                imageToCrop.src = '';
                currentEmployeeId = null;
                currentOriginalFile = null;
            });

            if(cropImageBtn) {
                cropImageBtn.addEventListener('click', function () {
                    // Added check for currentOriginalFile to prevent errors if file reference is lost
                    if (!cropper || !currentEmployeeId || !currentOriginalFile) {
                        console.error('Missing crop context (cropper, employeeId, or file).');
                        return;
                    }

                    const canvas = cropper.getCroppedCanvas({
                        width: 300,
                        height: 360,
                        imageSmoothingQuality: 'high',
                    });

                    canvas.toBlob(function (blob) {
                        if (!blob) return;

                        const croppedImageUrl = URL.createObjectURL(blob);
                        const previewImg = container.querySelector(`#preview-img-${currentEmployeeId}`);
                        if (previewImg) {
                            previewImg.src = croppedImageUrl;
                        }

                        // Use a fallback name if currentOriginalFile.name is missing (safety)
                        const fileName = currentOriginalFile.name || 'cropped-image.jpg';
                        const fileType = currentOriginalFile.type || 'image/jpeg';

                        const croppedFile = new File([blob], fileName, {
                            type: fileType,
                            lastModified: Date.now()
                        });

                        const targetInput = container.querySelector(`#photo-input-${currentEmployeeId}`);
                        if (targetInput) {
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(croppedFile);
                            targetInput.files = dataTransfer.files;

                             previewImg.classList.add('border-success', 'border-2');
                        }

                        cropperModal.hide();

                    }, currentOriginalFile.type || 'image/jpeg');
                });
            }


            // --- 4. BATCH SAVE LOGIC (Fixing the 20/100 Limit) ---
            const form = container.querySelector('#bulkEditForm');
            if (form) {
                // Remove existing listener to prevent duplicates if re-initialized
                const newForm = form.cloneNode(true);
                form.parentNode.replaceChild(newForm, form);

                newForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    startBatchUpload(this);
                });
            }

            async function startBatchUpload(formElement) {
                const saveBtn = container.querySelector('#btn-save-all');
                if(saveBtn) saveBtn.disabled = true;

                // 1. Gather all Employee IDs involved
                const employeeIdInputs = formElement.querySelectorAll('input[name="employee_ids[]"]');
                const allEmployeeIds = Array.from(employeeIdInputs).map(input => input.value);
                const selectedFieldInputs = formElement.querySelectorAll('input[name="selected_fields[]"]');
                const selectedFields = Array.from(selectedFieldInputs).map(input => input.value);
                const redirectTo = formElement.querySelector('input[name="redirect_to"]')?.value;

                if (allEmployeeIds.length === 0) {
                    alert('No employees selected.');
                    return;
                }

                // 2. Show Progress Modal
                const progressModalEl = container.querySelector('#progressModal');
                // Check if modal instance already exists
                let progressModal = bootstrap.Modal.getInstance(progressModalEl);
                if (!progressModal) {
                    progressModal = new bootstrap.Modal(progressModalEl);
                }

                const progressBar = container.querySelector('#saveProgressBar');
                const progressText = container.querySelector('#saveProgressText');
                const errorText = container.querySelector('#saveErrorText');

                progressModal.show();
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
                errorText.classList.add('d-none');
                errorText.textContent = '';

                // 3. Batching Configuration
                const BATCH_SIZE = 5; // Safe limit to prevent max_file_uploads error
                const total = allEmployeeIds.length;
                let processed = 0;
                let errors = [];

                // 4. Iterate and Chunk
                for (let i = 0; i < total; i += BATCH_SIZE) {
                    const chunkIds = allEmployeeIds.slice(i, i + BATCH_SIZE);
                    const chunkFormData = new FormData();

                    // Add Metadata
                    chunkFormData.append('_method', 'PUT');
                    chunkFormData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                    selectedFields.forEach(f => chunkFormData.append('selected_fields[]', f));

                    // Add Employee IDs for this chunk
                    chunkIds.forEach(id => {
                        chunkFormData.append('employee_ids[]', id);

                        // extract inputs specific to this employee ID from the main form
                        // Text/Date inputs
                        const inputs = formElement.querySelectorAll(`[name^="data[${id}]"]`);
                        inputs.forEach(input => {
                            if (input.type === 'file') {
                                if (input.files.length > 0) {
                                    chunkFormData.append(input.name, input.files[0]);
                                }
                            } else {
                                chunkFormData.append(input.name, input.value);
                            }
                        });
                    });

                    // Update UI
                    progressText.textContent = `Saving batch ${Math.ceil((i+1)/BATCH_SIZE)} of ${Math.ceil(total/BATCH_SIZE)}... (${processed}/${total} employees)`;

                    try {
                        const response = await fetch(formElement.action, {
                            method: 'POST', // POST with _method=PUT
                            body: chunkFormData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) {
                            throw new Error(`Batch failed with status: ${response.status}`);
                        }
                    } catch (err) {
                        console.error(err);
                        errors.push(`Batch ${Math.ceil((i+1)/BATCH_SIZE)} failed.`);
                    }

                    processed += chunkIds.length;
                    const percent = Math.min(100, Math.round((processed / total) * 100));
                    progressBar.style.width = `${percent}%`;
                    progressBar.textContent = `${percent}%`;
                }

                // 5. Completion
                if (errors.length > 0) {
                    progressText.textContent = 'Completed with errors.';
                    progressBar.classList.add('bg-danger');
                    errorText.classList.remove('d-none');
                    errorText.textContent = 'Some batches failed to save. Please check your connection and try again.';
                    setTimeout(() => {
                         if(saveBtn) saveBtn.disabled = false;
                    }, 2000);
                } else {
                    progressText.textContent = 'All changes saved successfully!';
                    progressBar.classList.add('bg-success');
                    setTimeout(() => {
                         // Check for global callback (used in Import Modal)
                        if (typeof window.onBulkEditSuccess === 'function') {
                            const progressModalEl = container.querySelector('#progressModal');
                            const progressModal = bootstrap.Modal.getInstance(progressModalEl);
                            progressModal.hide();

                            window.onBulkEditSuccess();
                        } else {
                            window.location.href = redirectTo || '{{ route("employees.index") }}';
                        }
                    }, 1000);
                }
            }
        };
    </script>

    {{-- Cropper Modal (Moved to end for Z-Index safety) --}}
    <x-cropper-modal />

    {{-- Progress Modal --}}
    <div class="modal fade" id="progressModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" style="z-index: 1070;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Saving Changes') }}</h5>
                </div>
                <div class="modal-body text-center">
                    <div class="progress mb-3" style="height: 25px;">
                        <div id="saveProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
                    </div>
                    <p class="text-muted" id="saveProgressText">{{ __('Preparing to save...') }}</p>
                    <div class="text-danger small mt-2 d-none" id="saveErrorText"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Hidden inputs for triggering file/camera selection --}}
    <input type="file" style="display:none;" id="globalTriggerFile" accept="image/*">
    <input type="file" style="display:none;" id="globalTriggerCamera" accept="image/*" capture="environment">

</div>
<script>
    // Auto-init if not loaded via AJAX (fallback)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.initBulkEditForm);
    } else {
        window.initBulkEditForm();
    }
</script>
@endsection

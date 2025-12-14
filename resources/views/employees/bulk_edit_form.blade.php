@extends('layouts.app')

@section('title', 'Advanced Bulk Edit')

@section('content')

<div class="container-fluid p-4">
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

    <form action="{{ route('employees.bulk_update') }}" method="POST" enctype="multipart/form-data">
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
                                            <input type="file" class="form-control master-input" data-field="{{ $field }}">
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
                <div class="accordion-item">
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
                                                    <input type="file" class="form-control individual-input" name="data[{{ $employee->id }}][{{ $field }}]" data-field="{{ $field }}">
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
                    <button type="submit" class="btn btn-success btn-lg shadow-sm">
                        <i class="bi bi-save-fill me-2"></i> {{ __('Save All Changes') }}
                    </button>
                </div>
            </div>
        </div>
        {{-- Spacer for fixed bottom bar --}}
        <div style="height: 100px;"></div>
    </form>

    {{-- Cropper Modal --}}
    <div class="modal fade" id="cropperModal" tabindex="-1" aria-labelledby="cropperModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cropperModalLabel">{{ __('Crop Image') }}</h5>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="cropImageBtn">{{ __('Crop & Save') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Hidden inputs for triggering file/camera selection --}}
    <input type="file" class="d-none" id="globalTriggerFile" accept="image/*">
    <input type="file" class="d-none" id="globalTriggerCamera" accept="image/*" capture="environment">

    <script>
        (function() {
            // Handle Master Field Sync
            const applyButtons = document.querySelectorAll('.apply-master-btn');

            applyButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const field = this.dataset.field;
                    const masterInput = document.querySelector(`.master-input[data-field="${field}"]`);
                    const individualInputs = document.querySelectorAll(`.individual-input[data-field="${field}"]`);

                    if (!masterInput) return;

                    // Sync main input value (Skip for file inputs)
                    if (masterInput.type !== 'file') {
                        const value = masterInput.value;
                        individualInputs.forEach(input => {
                            input.value = value;
                            // Trigger change event so any other listeners know it updated
                            input.dispatchEvent(new Event('change'));

                            // Visual highlight effect
                            input.classList.add('bg-success-subtle');
                            setTimeout(() => input.classList.remove('bg-success-subtle'), 1000);
                        });
                    }

                    // Handle description field sync (if exists)
                    const masterDescInput = document.querySelector(`.master-desc-input[data-parent-field="${field}"]`);
                    const individualDescInputs = document.querySelectorAll(`.individual-desc-input[data-parent-field="${field}"]`);

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
                });
            });

            // Expand/Collapse All
            const expandAllBtn = document.getElementById('btn-expand-all');
            const collapseAllBtn = document.getElementById('btn-collapse-all');
            const accordionCollapses = document.querySelectorAll('.accordion-collapse');

            if(expandAllBtn && collapseAllBtn) {
                expandAllBtn.addEventListener('click', () => {
                    accordionCollapses.forEach(el => {
                        // Use Bootstrap 5 API if available, or fallback to class manipulation
                        const bsCollapse = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
                        bsCollapse.show();
                    });
                });

                collapseAllBtn.addEventListener('click', () => {
                    accordionCollapses.forEach(el => {
                        const bsCollapse = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
                        bsCollapse.hide();
                    });
                });
            }

            // --- Cropper Logic ---
            let currentEmployeeId = null;
            let currentOriginalFile = null;
            let cropper = null;

            const cropperModalEl = document.getElementById('cropperModal');
            const cropperModal = new bootstrap.Modal(cropperModalEl);
            const imageToCrop = document.getElementById('imageToCrop');
            const cropImageBtn = document.getElementById('cropImageBtn');
            const globalTriggerFile = document.getElementById('globalTriggerFile');
            const globalTriggerCamera = document.getElementById('globalTriggerCamera');

            // 1. Listen for trigger clicks
            document.querySelectorAll('.btn-crop-trigger').forEach(btn => {
                btn.addEventListener('click', function() {
                    currentEmployeeId = this.dataset.employeeId;
                    const action = this.dataset.action;

                    // Reset global inputs
                    globalTriggerFile.value = '';
                    globalTriggerCamera.value = '';

                    if (action === 'file') {
                        globalTriggerFile.click();
                    } else {
                        globalTriggerCamera.click();
                    }
                });
            });

            // 2. Handle File Selection
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

            // 3. Init Cropper on Modal Show
            cropperModalEl.addEventListener('shown.bs.modal', function () {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                // Ensure image is loaded
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

            // 4. Destroy Cropper on Modal Hide
            cropperModalEl.addEventListener('hidden.bs.modal', function () {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                imageToCrop.src = '';
                currentEmployeeId = null;
                currentOriginalFile = null;
            });

            // 5. Handle Crop & Save
            if(cropImageBtn) {
                cropImageBtn.addEventListener('click', function () {
                    if (!cropper || !currentEmployeeId) {
                        return;
                    }

                    const canvas = cropper.getCroppedCanvas({
                        width: 300,
                        height: 360,
                        imageSmoothingQuality: 'high',
                    });

                    canvas.toBlob(function (blob) {
                        if (!blob) return;

                        // Update Preview Image
                        const croppedImageUrl = URL.createObjectURL(blob);
                        const previewImg = document.getElementById(`preview-img-${currentEmployeeId}`);
                        if (previewImg) {
                            previewImg.src = croppedImageUrl;
                        }

                        // Create a new File object
                        const croppedFile = new File([blob], currentOriginalFile.name, {
                            type: currentOriginalFile.type || 'image/jpeg',
                            lastModified: Date.now()
                        });

                        // Update Hidden Input
                        const targetInput = document.getElementById(`photo-input-${currentEmployeeId}`);
                        if (targetInput) {
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(croppedFile);
                            targetInput.files = dataTransfer.files;

                             // Visual feedback
                             previewImg.classList.add('border-success', 'border-2');
                        }

                        cropperModal.hide();

                    }, currentOriginalFile.type || 'image/jpeg');
                });
            }

        })();
    </script>
</div>
@endsection
